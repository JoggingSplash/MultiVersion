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

use cisco\network\mcpe\MVBlockTranslator;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\block\tile\Spawnable;
use pocketmine\network\mcpe\serializer\ChunkSerializer;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\SubChunk;
use function count;
use function get_class;
use function min;

class PreFormattedChunkSerializer implements MVChunkSerializer {

	public function serializeFullChunk(Chunk $chunk, int $dimensionId, MVBlockTranslator $blockTranslator, ?string $tiles = null) : string
	{
		$stream = new ByteBufferWriter();
		$subChunkCount = self::getSubChunkCount($chunk, $dimensionId);

		for($i = 0; $i < $subChunkCount; $i++){
			self::serializeSubChunk($chunk->getSubChunk($i), $blockTranslator, $stream, false);
		}

		$stream->writeByteArray(self::reduce3DBiomes($chunk->getSubChunk(Chunk::MIN_SUBCHUNK_INDEX)->getBiomeArray()));
		Byte::writeUnsigned($stream, 0); //border block array count
		//Border block entry format: 1 byte (4 bits X, 4 bits Z). These are however useless since they crash the regular client.

		if ($tiles !== null) {
			$stream->writeByteArray($tiles);
		} else {
			$stream->writeByteArray($this->serializeTiles($chunk));
		}
		return $stream->getData();
	}

	public function getSubChunkCount(Chunk $chunk, int $dimensionId) : int {
		return min(ChunkSerializer::getSubChunkCount($chunk, $dimensionId), 16);
	}

	public function serializeSubChunk(SubChunk $subChunk, MVBlockTranslator $blockTranslator, ByteBufferWriter $writer, bool $persistentBlockStates) : void{
		$layers = $subChunk->getBlockLayers();
		$sDictionary = $blockTranslator->getBlockStateDictionary();
		$rDictionary = $sDictionary->getRDictionary() ?? throw new AssumptionFailedError("Given MVBlockTranslator (" . get_class($blockTranslator) . ") has no R12Dictionary");
		Byte::writeUnsigned($writer, 8);
		Byte::writeUnsigned($writer, count($layers));

		foreach($layers as $layer){
			$bPs = $layer->getBitsPerBlock();
			$words = $layer->getWordArray();
			Byte::writeUnsigned($writer, ($bPs << 1) | ($persistentBlockStates ? 0 : 1));
			$writer->writeByteArray($words);

			$pallete = $layer->getPalette();

			if($bPs !== 0){
				VarInt::writeSignedInt($writer, count($pallete));
			}

			foreach($pallete as $p){
				VarInt::writeSignedInt($writer, $rDictionary->toRuntimeId($p));
			}

		}
	}

	/**
	 * Converts a 3D biome palette into a 2D biome array (256 bytes).
	 * Assumes that all Y layers contain the same biome for each X/Z.
	 */
	protected static function reduce3DBiomes(PalettedBlockArray $biomes3d) : string{
		$biomes2d = new ByteBufferWriter();

		for($z = 0; $z < 16; ++$z){
			for($x = 0; $x < 16; ++$x){
				$biomeId = $biomes3d->get($x, 0, $z);

				// Ensure all Y values match
				for($y = 1; $y < 16; ++$y){
					if($biomes3d->get($x, $y, $z) !== $biomeId){
						throw new AssumptionFailedError("3D biome palette is not uniform across Y at X=$x Z=$z");
					}
				}

				Byte::writeUnsigned($biomes2d, $biomeId);
			}
		}

		return $biomes2d->getData();
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

}
