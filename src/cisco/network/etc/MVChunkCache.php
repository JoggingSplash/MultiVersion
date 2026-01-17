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

namespace cisco\network\etc;

use cisco\network\async\MVChunkRequestTask;
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
use pocketmine\world\World;
use function is_string;
use function spl_object_id;

class MVChunkCache implements ChunkListener
{

	/** @var MVChunkCache[][][] */
	private static array $instances = [];
	/** @var CompressBatchPromise|string[] */
	private array $caches = [];
	private int $hits = 0;
	private int $misses = 0;

	private function __construct(
		private World      $world,
		private Compressor $compressor,
		private TProtocol  $protocol,
	) {

	}

	/**
	 * Fetches the ChunkCache instance for the given world. This lazily creates cache systems as needed.
	 */
	public static function getInstance(World $world, Compressor $compressor, TProtocol $protocol) : self
	{
		$worldId = spl_object_id($world);
		$compressorId = spl_object_id($compressor);
		$protocolId = spl_object_id($protocol);
		if (!isset(self::$instances[$protocolId])) {
			GlobalLogger::get()->debug("Created new chunk packet cache (world#$worldId, compressor#$compressorId, protocol#$protocolId)");
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
		$this->world->registerChunkListener($this, $chunkX, $chunkZ);
		$chunk = $this->world->getChunk($chunkX, $chunkZ);
		if ($chunk === null) {
			throw new InvalidArgumentException("Cannot request an unloaded chunk");
		}
		$chunkHash = World::chunkHash($chunkX, $chunkZ);

		if (isset($this->caches[$chunkHash])) {
			++$this->hits;
			return $this->caches[$chunkHash];
		}

		++$this->misses;

		$this->world->timings->syncChunkSendPrepare->startTiming();
		try {
			$promise = new CompressBatchPromise();
			$this->world->getServer()->getAsyncPool()->submitTask(new MVChunkRequestTask(
				$chunkX,
				$chunkZ,
				DimensionIds::OVERWORLD,
				$chunk,
				$promise,
				$this->compressor,
				$this->protocol,
				function () use ($chunkX, $chunkZ) : void {
					$this->world->getLogger()->error("Failed preparing chunk $chunkX $chunkZ, retrying");
					$this->restartPendingRequest($chunkX, $chunkZ);
				}
			));
			$this->caches[$chunkHash] = $promise;
			$promise->onResolve(function (CompressBatchPromise $promise) use ($chunkHash) : void {
				//the promise may have been discarded or replaced if the chunk was unloaded or modified in the meantime
				if (($this->caches[$chunkHash] ?? null) === $promise) {
					$this->caches[$chunkHash] = $promise->getResult();
				}
			});
			return $promise;
		} finally {
			$this->world->timings->syncChunkSendPrepare->stopTiming();
		}
	}

	/**
	 * Restarts an async request for an unresolved chunk.
	 */
	private function restartPendingRequest(int $chunkX, int $chunkZ) : void {
		$chunkHash = World::chunkHash($chunkX, $chunkZ);
		$existing = $this->caches[$chunkHash] ?? null;
		if ($existing === null || (!is_string($existing) && $existing->hasResult())) {
			throw new InvalidArgumentException("Restart can only be applied to unresolved promises");
		}

		unset($this->caches[$chunkHash]);
		if ($existing instanceof CompressBatchPromise) {
			$existing->cancel();
			$this->request($chunkX, $chunkZ)->onResolve(...$existing->getResolveCallbacks());
		}

	}

	/**
	 * @see ChunkListener::onChunkChanged()
	 */
	public function onChunkChanged(int $chunkX, int $chunkZ, Chunk $chunk) : void
	{
		$this->destroyOrRestart($chunkX, $chunkZ);
	}

	private function destroyOrRestart(int $chunkX, int $chunkZ) : void
	{
		$cache = $this->caches[World::chunkHash($chunkX, $chunkZ)] ?? null;
		if ($cache !== null) {
			if (!is_string($cache) && !$cache->hasResult()) {
				//some requesters are waiting for this chunk, so their request needs to be fulfilled
				$this->restartPendingRequest($chunkX, $chunkZ);
			} else {
				//dump the cache, it'll be regenerated the next time it's requested
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
		unset($this->caches[$chunkHash]);

		return $existing !== null;
	}

	/**
	 * @see ChunkListener::onBlockChanged()
	 */
	public function onBlockChanged(Vector3 $block) : void
	{
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
