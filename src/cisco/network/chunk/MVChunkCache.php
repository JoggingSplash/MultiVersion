<?php

/*
 *     __  ___      ____  _ _    __               _
 *    /  |/  /_  __/ / /_(_) |  / /__  __________(_)___  ____
 *   / /|_/ / / / / / __/ /| | / / _ \/ ___/ ___/ / __ \/ __ \
 *  / /  / / /_/ / / /_/ / | |/ /  __/ /  (__  ) / /_/ / / / /
 * /_/  /_/\__,_/_/\__/_/  |___/\___/_/  /____/_/\____/_/ /_/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author JoggingSplash23
 * @link https://www.github.com/JoggingSplash
 *
 *
 */

declare(strict_types=1);

namespace cisco\network\chunk;

use cisco\network\async\MVChunkRequestTask;
use cisco\network\chunk\payload\LevelChunk2D;
use cisco\network\proto\TProtocol;
use GlobalLogger;
use InvalidArgumentException;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\world\ChunkListener;
use pocketmine\world\ChunkListenerNoOpTrait;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\leveldb\LevelDB;
use pocketmine\world\World;
use function get_class;
use function is_string;
use function spl_object_id;

class MVChunkCache implements ChunkListener
{

	/** @var MVChunkCache[][][] */
	private static array $instances = [];
	/** @var CompressBatchPromise|string[] */
	private array $caches = [];

	private ?MVChunkPayload $payload = null;
	private int $hits = 0;
	private int $misses = 0;

	private function __construct(
		private World      $world,
		private Compressor $compressor,
		private TProtocol  $protocol,
	) {
		$provider = $this->world->getProvider();
		$protocol = $this->protocol;
		if($this->protocol->hasOldCompressionMethod() && $provider instanceof LevelDB){
			$this->payload = new LevelChunk2D($provider->getDatabase(), $protocol);
		}else{
			$this->protocol->getLogger()->debug("Cannot create chunk payload for " . get_class($provider));
		}
	}

	/**
	 * Fetches the ChunkCache instance for the given world. This lazily creates parts systems as needed.
	 */
	public static function getInstance(World $world, Compressor $compressor, TProtocol $protocol) : self {
		$worldId = spl_object_id($world);
		$compressorId = spl_object_id($compressor);
		$protocolId = spl_object_id($protocol);
		if (!isset(self::$instances[$protocolId])) {
			GlobalLogger::get()->debug("Created new chunk packet parts (world#$worldId, compressor#$compressorId, protocol#$protocolId)");
			self::$instances[$protocolId] = [];
		}
		if (!isset(self::$instances[$protocolId][$worldId])) {
			self::$instances[$protocolId][$worldId] = [];
			$world->addOnUnloadCallback(static function () use ($worldId) : void {
				foreach (self::$instances as $translatorId => $worldCaches) {
					foreach ($worldCaches[$worldId] ?? [] as $compressorCache) {
						unset($compressorCache->caches);
						$compressorCache->caches = [];
					}
					unset(self::$instances[$translatorId][$worldId]);
					GlobalLogger::get()->debug("Destroyed chunk packet caches for world#$worldId");
				}
			});

		}

		return self::$instances[$protocolId][$worldId][$compressorId] ??= new self($world, $compressor, $protocol);
	}

	public function request(int $chunkX, int $chunkZ) : CompressBatchPromise|string {
		$chunkHash = World::chunkHash($chunkX, $chunkZ);

		if (isset($this->caches[$chunkHash])) {
			++$this->hits;
			return $this->caches[$chunkHash];
		}

		return $this->prepareChunkAsync($chunkX, $chunkZ, $chunkHash);
	}

	private function prepareChunkAsync(int $chunkX, int $chunkZ, int $chunkHash) : CompressBatchPromise{
		$this->world->registerChunkListener($this, $chunkX, $chunkZ);
		$chunk = $this->world->getChunk($chunkX, $chunkZ);

		if ($chunk === null) {
			throw new InvalidArgumentException("Cannot request an unloaded chunk");
		}


		++$this->misses;

		$this->world->timings->syncChunkSendPrepare->startTiming();
		try {
            $data = null;
            if($this->payload !== null){
                $this->payload->readChunk($chunkX, $chunkZ, clone $chunk);
                $data = $this->payload->requestChunk($chunkX, $chunkZ);
            }



            $promise = new CompressBatchPromise();
			$this->world->getServer()->getAsyncPool()->submitTask(new MVChunkRequestTask(
				$chunkX,
				$chunkZ,
				DimensionIds::OVERWORLD,
				$chunk,
				$promise,
				$this->compressor,
				$this->protocol,
				$data
			));
			$this->caches[$chunkHash] = $promise;
			$promise->onResolve(function (CompressBatchPromise $promise) use($chunkHash) : void {
				if(($this->caches[$chunkHash] ?? null) === $promise) {
					$this->caches[$chunkHash] = $promise->getResult();
				}
			});
			return $promise;
		} finally {
			$this->world->timings->syncChunkSendPrepare->stopTiming();
		}
	}

	/**
	 * @see ChunkListener::onChunkChanged()
	 */
	public function onChunkChanged(int $chunkX, int $chunkZ, Chunk $chunk) : void {
		$this->destroyOrRestart($chunkX, $chunkZ);
	}

	private function destroyOrRestart(int $chunkX, int $chunkZ) : void
	{
		$cache = $this->caches[$chunkHash = World::chunkHash($chunkX, $chunkZ)] ?? null;
		if ($cache !== null) {
			if (!is_string($cache)) {
				$cache->cancel();
				unset($this->caches[$chunkHash]);
                $this->payload?->destroyChunk($chunkX, $chunkZ);
				//some requesters are waiting for this chunk, so their request needs to be fulfilled
				$this->prepareChunkAsync($chunkX, $chunkZ, $chunkHash)
					->onResolve(...$cache->getResolveCallbacks());
			} else {
				//dump the parts, it'll be regenerated the next time it's requested
				$this->destroy($chunkX, $chunkZ);
			}
		}
	}

	use ChunkListenerNoOpTrait {
		//force overriding of these
		onChunkChanged as private;
		onBlockChanged as private;
		onChunkUnloaded as private;
	}

	private function destroy(int $chunkX, int $chunkZ) : bool {
		$chunkHash = World::chunkHash($chunkX, $chunkZ);
		$existing = $this->caches[$chunkHash] ?? null;
		$this->payload?->destroyChunk($chunkX, $chunkZ);
		unset($this->caches[$chunkHash]);

		return $existing !== null;
	}

	/**
	 * @see ChunkListener::onBlockChanged()
	 */
	public function onBlockChanged(Vector3 $block) : void {
		//FIXME: requesters will still receive this chunk after it's been dropped, but we can't mark this for a simple
		//sync here because it can spam the worker pool
		$this->destroy($block->getFloorX() >> Chunk::COORD_BIT_SIZE, $block->getFloorZ() >> Chunk::COORD_BIT_SIZE);
	}

	/**
	 * @see ChunkListener::onChunkUnloaded()
	 */
	public function onChunkUnloaded(int $chunkX, int $chunkZ, Chunk $chunk) : void
	{
		$this->destroy($chunkX, $chunkZ);
		$this->world->unregisterChunkListener($this, $chunkX, $chunkZ);
	}

}
