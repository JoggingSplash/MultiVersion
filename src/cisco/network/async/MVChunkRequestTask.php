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

namespace cisco\network\async;

use cisco\MCProtocols;
use cisco\network\proto\TProtocol;
use Closure;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketBatch;
use pocketmine\network\mcpe\protocol\types\ChunkPosition;
use pocketmine\network\mcpe\serializer\ChunkSerializer;
use pocketmine\scheduler\AsyncTask;
use pocketmine\thread\NonThreadSafeValue;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use function chr;

class MVChunkRequestTask extends AsyncTask {

	private const TLS_KEY_PROMISE = "promise";
	private const TLS_KEY_ERROR_HOOK = "errorHook";

	protected string $chunk;
	protected int $chunkX;
	protected int $chunkZ;
	/** @phpstan-var NonThreadSafeValue<Compressor> */
	protected NonThreadSafeValue $compressor;
	private int $dimensionId;
	private string $tiles;
	private int $protocol;

	public function __construct(int $chunkX, int $chunkZ, int $dimensionId, Chunk $chunk, CompressBatchPromise $promise, Compressor $compressor, TProtocol $protocol, ?Closure $onError = null) {
		$this->compressor = new NonThreadSafeValue($compressor);
		$this->chunk = FastChunkSerializer::serializeTerrain($chunk);
		$this->chunkX = $chunkX;
		$this->chunkZ = $chunkZ;
		$this->dimensionId = $dimensionId;
		$this->tiles = ChunkSerializer::serializeTiles($chunk);
		$this->protocol = $protocol->getProtocolId();

		$this->storeLocal(self::TLS_KEY_PROMISE, $promise);
		$this->storeLocal(self::TLS_KEY_ERROR_HOOK, $onError);
	}

	public function onRun() : void {
		// As we are in a worker, we are creating new instances of protocols that should not load more thing than this.
		// TODO: new protocol system
		$protocol = MCProtocols::getProtocolInstance($this->protocol);
		$chunkSerializer = $protocol->getChunkSerializer();
		$chunk = FastChunkSerializer::deserializeTerrain($this->chunk);
		$subCount = $chunkSerializer->getSubChunkCount($chunk, $this->dimensionId);
		$payload = $chunkSerializer->serializeFullChunk($chunk, $this->dimensionId, $protocol->getTypeConverter()->getMVBlockTranslator(), $this->tiles);

		$packet = $protocol->outcoming(LevelChunkPacket::create(
			new ChunkPosition($this->chunkX, $this->chunkZ),
			$this->dimensionId,
			$subCount,
			false,
			null,
			$payload
		));

		if($packet === null) throw new \LogicException("LevelChunkPacket cannot be skipped.");

		$stream = new ByteBufferWriter();
		PacketBatch::encodePackets($stream, [$packet]);
		$compressor = $this->compressor->deserialize();
		$this->setResult((!$protocol->hasOldCompressionMethod() ? chr($compressor->getNetworkId()) : '') . $compressor->compress($stream->getData()));
	}

	public function onError() : void {
		/**
		 * @var ?Closure $hook
		 */
		$hook = $this->fetchLocal(self::TLS_KEY_ERROR_HOOK);
		if($hook !== null) {
			$hook();
		}
	}

	public function onCompletion() : void {
		/** @var CompressBatchPromise $promise */
		$promise = $this->fetchLocal(self::TLS_KEY_PROMISE);
		$promise->resolve($this->getResult());
	}

	protected function reallyDestruct() : void {
		unset($this->chunk, $this->chunkX, $this->chunkZ, $this->dimensionId, $this->tiles, $this->protocol, $this->compressor);
	}
}
