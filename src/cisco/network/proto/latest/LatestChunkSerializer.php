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

namespace cisco\network\proto\latest;

use cisco\network\global\MVChunkSerializer;
use cisco\network\mcpe\MVBlockTranslator;
use cisco\network\utils\ReflectionUtils;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\data\bedrock\LegacyBiomeIdToStringIdMap;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\serializer\ChunkSerializer;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\SubChunk;
use function count;

class LatestChunkSerializer implements MVChunkSerializer
{

	public function serializeFullChunk(Chunk $chunk, int $dimensionId, MVBlockTranslator $blockTranslator, ?string $tiles = null) : string
	{
		$writer = new ByteBufferWriter();
		$subChunkCount = self::getSubChunkCount($chunk, $dimensionId);
		$writtenCount = 0;
		for ($y = Chunk::MIN_SUBCHUNK_INDEX; $writtenCount < $subChunkCount; ++$y, ++$writtenCount) {
			self::serializeSubChunk($chunk->getSubChunk($y), $blockTranslator, $writer, false);
		}

		$biomeIdMap = LegacyBiomeIdToStringIdMap::getInstance();
		//all biomes must always be written :(
		for ($y = Chunk::MIN_SUBCHUNK_INDEX; $y <= Chunk::MAX_SUBCHUNK_INDEX; ++$y) {
			ReflectionUtils::invokeStatic(ChunkSerializer::class, "serializeBiomePalette", $chunk->getSubChunk($y)->getBiomeArray(), $biomeIdMap, $writer);
		}

		Byte::writeUnsigned($writer, 0);//border block array count
		//Border block entry format: 1 byte (4 bits X, 4 bits Z). These are however useless since they crash the regular client.

		if ($tiles !== null) {
			$writer->writeByteArray($tiles);
		} else {
			$writer->writeByteArray(self::serializeTiles($chunk));
		}

		return $writer->getData();
	}

	public function getSubChunkCount(Chunk $chunk, int $dimensionId) : int
	{
		return ChunkSerializer::getSubChunkCount($chunk, $dimensionId);
	}

	public function serializeSubChunk(SubChunk $subChunk, MVBlockTranslator $blockTranslator, ByteBufferWriter $writer, bool $persistentBlockStates) : void
	{
		$layers = $subChunk->getBlockLayers();
		Byte::writeUnsigned($writer, 8);
		Byte::writeUnsigned($writer, count($layers));

		$blockStateDictionary = $blockTranslator->getBlockStateDictionary();

		foreach ($layers as $blocks) {
			$bitsPerBlock = $blocks->getBitsPerBlock();
			$words = $blocks->getWordArray();
			Byte::writeUnsigned($writer, ($bitsPerBlock << 1) | ($persistentBlockStates ? 0 : 1));
			$writer->writeByteArray($words);
			$palette = $blocks->getPalette();

			if ($bitsPerBlock !== 0) {
				//these LSHIFT by 1 uvarints are optimizations: the client expects zigzag varints here
				//but since we know they are always unsigned, we can avoid the extra fcall overhead of
				//zigzag and just shift directly.
				VarInt::writeUnsignedInt($writer, count($palette) << 1); //yes, this is intentionally zigzag
			}

			if ($persistentBlockStates) {
				$nbtSerializer = new NetworkNbtSerializer();
				foreach ($palette as $p) {
					//TODO: introduce a binary cache for this
					$state = $blockStateDictionary->generateDataFromStateId($blockTranslator->internalIdToNetworkId($p));
					if ($state === null) {
						$state = $blockTranslator->getFallbackStateData();
					}

					$writer->writeByteArray($nbtSerializer->write(new TreeRoot($state->toNbt())));
				}
			} else {
				foreach ($palette as $p) {
					VarInt::writeUnsignedInt($writer, $blockTranslator->internalIdToNetworkId($p) << 1);
				}
			}

		}
	}

	public function serializeTiles(Chunk $chunk) : string
	{
		return ChunkSerializer::serializeTiles($chunk);
	}
}
