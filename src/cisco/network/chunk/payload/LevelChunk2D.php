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

namespace cisco\network\chunk\payload;

use cisco\network\chunk\io\ChunkDatum;
use cisco\network\chunk\io\SubChunkDatum;
use cisco\network\chunk\MVChunkPayload;
use cisco\network\proto\TProtocol;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\World;
use function chr;
use function strlen;

final class LevelChunk2D implements MVChunkPayload {

	/** @var ChunkDatum[] $chunks */
	protected array $chunks = [];

	public function __construct(
		protected \LevelDB $db,
		protected TProtocol $protocol
	){

	}

	public function readChunk(int $chunkX, int $chunkZ, Chunk $chunk) : void {
		$hash = World::chunkHash($chunkX, $chunkZ);
		if(isset($this->chunks[$hash])) {
			//should not re-read
			return;
		}

		$this->chunks[$hash] = $this->prepareChunk($chunk);
	}

	public function destroyChunk(int $chunkX, int $chunkZ) : void    {
		unset($this->chunks[World::chunkHash($chunkX, $chunkZ)]);
	}

	private function prepareChunk(Chunk $chunk) : ChunkDatum {
		$biomes = self::reduce3DBiomes($chunk->getSubChunk(Chunk::MIN_SUBCHUNK_INDEX)->getBiomeArray());
		$subChunks = [];
		for($y = 0; $y < 16; $y++) {
			$subChunk = $chunk->getSubChunk($y);
			$layers = $subChunk->getBlockLayers();
			if(empty($layers)) {
				$subChunks[$y] = SubChunkDatum::empty();
				continue;
			}
			[$blocks, $data] = $this->palettedToClassic($layers[0]);
			$subChunks[$y] = new SubChunkDatum($blocks, $data);
		}

		return new ChunkDatum($biomes, $subChunks);
	}

	/**
	 * Converts a 3D biome palette into a 2D biome array (256 bytes).
	 * Assumes that all Y layers contain the same biome for each X/Z.
	 */
	private static function reduce3DBiomes(PalettedBlockArray $biomes3d) : string{
		$biomes2d = "";

		for($z = 0; $z < 16; ++$z){
			for($x = 0; $x < 16; ++$x){
				$biomeId = $biomes3d->get($x, 0, $z);

				// Ensure all Y values match
				for($y = 1; $y < 16; ++$y){
					if($biomes3d->get($x, $y, $z) !== $biomeId){
						throw new AssumptionFailedError("3D biome palette is not uniform across Y at X=$x Z=$z");
					}
				}

				$biomes2d .= chr($biomeId);
			}
		}

		if(strlen($biomes2d) !== 256){
			throw new \LogicException("Generated biome array is not exactly 256 bytes");
		}

		return $biomes2d;
	}

	/**
	 * Converts a PalettedBlockArray into classic IDs (4096 bytes) + DATA (2048 bytes nibbles).
	 *
	 * @return array{$blocks, $data}
	 */
	private function palettedToClassic(PalettedBlockArray $paletted) : array {
		$blocks = "";
		$data = "";
		$nibbleBuffer = 0;
		$i = 0;

		$converter = $this->protocol->getTypeConverter();
		$blockTranslator = $converter->getMVBlockTranslator();
		$dictionary = $blockTranslator->getBlockStateDictionary();
		for ($x = 0; $x < 16; $x++) {
			for ($z = 0; $z < 16; $z++) {
				for ($y = 0; $y < 16; $y++) {
					$stateId = $paletted->get($x, $y, $z);
					$networkStateId = $blockTranslator->internalIdToNetworkId($stateId);
					$legacyBlockId = $dictionary->getLegacyBlockIdFromStateId($networkStateId);
					$meta = $dictionary->getMetaFromStateId($stateId);

					$blocks .= chr($legacyBlockId & 0xFF);

					if (($i & 1) === 0) {
						$nibbleBuffer = $meta;
					} else {
						$data .= chr($nibbleBuffer | ($meta << 4));
					}
					$i++;
				}
			}
		}

		// Flush nibble-end
		if (($i & 1) !== 0) {
			$data .= chr($nibbleBuffer);
		}

		return [$blocks, $data];
	}

	public function requestChunk(int $chunkX, int $chunkZ) : ChunkDatum {
		return $this->chunks[World::chunkHash($chunkX, $chunkZ)] ?? throw new AssumptionFailedError();
	}
}
