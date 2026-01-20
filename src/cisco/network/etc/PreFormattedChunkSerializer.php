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

use cisco\network\global\MVChunkSerializer;
use cisco\network\mcpe\MVBlockTranslator;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\block\tile\Spawnable;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\serializer\ChunkSerializer;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\SubChunk;
use Throwable;
use function chr;
use function count;
use function min;
use function str_repeat;

final class PreFormattedChunkSerializer implements MVChunkSerializer {

	public function serializeFullChunk(Chunk $chunk, int $dimensionId, MVBlockTranslator $blockTranslator, ?string $tiles = null) : string
	{
		$stream = new ByteBufferWriter();
		$subChunkCount = self::getSubChunkCount($chunk, $dimensionId);
		for ($y = 0; $y < $subChunkCount; ++$y) {
			self::serializeSubChunk($chunk->getSubChunk($y), $blockTranslator, $stream, false);
		}

		$biome = str_repeat(chr(BiomeIds::OCEAN), 256); //2d biome array
		for ($x = 0; $x < 16; ++$x) {
			for ($z = 0; $z < 16; ++$z) {
				try {
					$biomeId = $chunk->getBiomeId($x, $chunk->getHighestBlockAt($x, $z), $z) ?? BiomeIds::OCEAN;
				} catch (Throwable $e) {
					$biomeId = BiomeIds::OCEAN;
				}

				$biome[($z << 4) | $x] = chr($biomeId);
			}
		}
		$stream->writeByteArray($biome);
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
}
