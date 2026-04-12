<?php

/*
 *
 *
 *      __  ___      ____  _ _    __               _
 *     /  |/  /_  __/ / /_(_) |  / /__  __________(_)___  ____
 *    / /|_/ / / / / / __/ /| | / / _ \/ ___/ ___/ / __ \/ __ \
 *   / /  / / /_/ / / /_/ / | |/ /  __/ /  (__  ) / /_/ / / / /
 *  /_/  /_/\__,_/_/\__/_/  |___/\___/_/  /____/_/\____/_/ /_/
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  @author JoggingSplash23
 *  @link https://www.github.com/JoggingSplash
 *
 *
 */

declare(strict_types=1);

namespace cisco\network\chunk\serializers;

use cisco\network\chunk\io\ChunkDatum;
use cisco\network\chunk\io\SubChunkDatum;
use cisco\network\mcpe\MVBlockTranslator;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\block\tile\Spawnable;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\serializer\ChunkSerializer;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\SubChunk;
use function chr;
use function count;
use function min;
use function strlen;

class PreFormattedChunkSerializer implements MVChunkSerializer {

	public function serializeFullChunk(Chunk $chunk, int $dimensionId, MVBlockTranslator $blockTranslator, ?string $tiles = null) : string
	{
		$stream = new ByteBufferWriter();
		$subChunkCount = self::getSubChunkCount($chunk, $dimensionId);
		$datum = self::prepareChunk($blockTranslator, $chunk);
		$subChunks = $datum->subChunkDatum;

		for ($y = 0; $y < $subChunkCount; ++$y) {
			$subChunk = $subChunks[$y];
			Byte::writeUnsigned($stream, 0); // sub chunk version
			$stream->writeByteArray($subChunk->blocksId);
			$stream->writeByteArray($subChunk->blocksData);
		}

		$stream->writeByteArray($datum->biomes);
		Byte::writeUnsigned($stream, 0); //border block array count
		//Border block entry format: 1 byte (4 bits X, 4 bits Z). These are however useless since they crash the regular client.

		if ($tiles !== null) {
			$stream->writeByteArray($tiles);
		} else {
			$stream->writeByteArray($this->serializeTiles($chunk));
		}
		return $stream->getData();
	}

	public function getSubChunkCount(Chunk $chunk, int $dimensionId) : int
	{
		return min(ChunkSerializer::getSubChunkCount($chunk, $dimensionId), 16);
	}

	public function serializeSubChunk(SubChunk $subChunk, MVBlockTranslator $blockTranslator, ByteBufferWriter $writer, bool $persistentBlockStates) : void
	{
		$layers = $subChunk->getBlockLayers();
		Byte::writeUnsigned($writer, 8); //version

		Byte::writeUnsigned($writer, count($layers));

		$blockStateDictionary = $blockTranslator->getBlockStateDictionary();

		foreach ($layers as $blocks) {
			$bitsPerBlock = $blocks->getBitsPerBlock();
			$words = $blocks->getWordArray();
			Byte::writeUnsigned($writer, ($bitsPerBlock << 1) | ($persistentBlockStates ? 0 : 1));
			$writer->writeByteArray($words);
			$palette = $blocks->getPalette();

			if ($bitsPerBlock !== 0) {
				VarInt::writeUnsignedInt($writer, count($palette) << 1);
			}

			if ($persistentBlockStates) {
				$nbtSerializer = new NetworkNbtSerializer();
				foreach ($palette as $p) {
					//TODO: introduce a binary parts for this
					$state = $blockStateDictionary->generateDataFromStateId($blockTranslator->internalIdToNetworkId($p));
					if ($state === null) {
						$state = $blockTranslator->getFallbackStateData();
					}

					$writer->writeByteArray($nbtSerializer->write(new TreeRoot($state->toNbt())));
				}
				continue;
			}

			foreach ($palette as $p) {
				VarInt::writeUnsignedInt(
					$writer, $blockTranslator->internalIdToNetworkId($p) << 1
				);
			}
		}
	}

	public function serializeTiles(Chunk $chunk) : string
	{
		$stream = new ByteBufferWriter();
		foreach ($chunk->getTiles() as $tile) {
			if ($tile instanceof Spawnable) {
				$stream->writeByteArray(
					$tile->getSerializedSpawnCompound()->getEncodedNbt()
				);
			}
		}

		return $stream->getData();
	}

	static protected function prepareChunk(MVBlockTranslator $blockTranslator, Chunk $chunk) : ChunkDatum {
		$biomes = self::reduce3DBiomes($chunk->getSubChunk(Chunk::MIN_SUBCHUNK_INDEX)->getBiomeArray());
		$subChunks = [];
		for($y = 0; $y < 16; $y++) {
			$subChunk = $chunk->getSubChunk($y);
			$layers = $subChunk->getBlockLayers();
			if(empty($layers)) {
				$subChunks[$y] = SubChunkDatum::empty();
				continue;
			}
			[$blocks, $data] = self::palettedToClassic($layers[0], $blockTranslator);
			$subChunks[$y] = new SubChunkDatum($blocks, $data);
		}

		return new ChunkDatum($biomes, $subChunks);
	}

	/**
	 * Converts a PalettedBlockArray into classic IDs (4096 bytes) + DATA (2048 bytes nibbles).
	 *
	 * @return array{$blocks, $data}
	 */
	static private function palettedToClassic(PalettedBlockArray $paletted, MVBlockTranslator $blockTranslator) : array {
		$blocks = "";
		$data = "";
		$nibbleBuffer = 0;
		$i = 0;

		$dictionary = $blockTranslator->getBlockStateDictionary();
        for ($y = 0; $y < 16; $y++) {
			for ($z = 0; $z < 16; $z++) {
                for ($x = 0; $x < 16; $x++) {
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
}
