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

namespace cisco\network\chunk\serializers;

use cisco\network\chunk\io\ChunkDatum;
use cisco\network\chunk\io\SubChunkDatum;
use cisco\network\mcpe\MVBlockTranslator;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\block\tile\Spawnable;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\data\bedrock\LegacyBiomeIdToStringIdMap;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\serializer\ChunkSerializer;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\format\SubChunk;
use function count;
use function min;
use function ord;
use function str_repeat;

final class v486ChunkSerializer implements MVChunkSerializer {

	private static SubChunkDatum $emptyDatum;
	public const LOWER_PADDING_SIZE = 4;

	public function serializeFullChunk(Chunk $chunk, int $dimensionId, MVBlockTranslator $blockTranslator, ?ChunkDatum $datum, ?string $tiles = null) : string
	{
		$datum !== null || throw new AssumptionFailedError();
		$stream = new ByteBufferWriter();

		$emptyDatum = self::$emptyDatum ??= SubChunkDatum::empty();

		// HACK: fill in fake subchunks to make up for the new negative space client-side (-64 height)
		for($y = 0; $y < self::LOWER_PADDING_SIZE; $y++){
			Byte::writeUnsigned($stream, 0); //subchunk version
			$stream->writeByteArray($emptyDatum->blocksId);
			$stream->writeByteArray($emptyDatum->blocksData);
		}

		$subChunkCount = self::getSubChunkCount($chunk, $dimensionId);
		$subChunks = $datum->subChunkDatum;

		for ($y = 0; $y < $subChunkCount - 4; ++$y) {
			$subChunk = $subChunks[$y];
			Byte::writeUnsigned($stream, 0); // sub chunk version 0 (Classic)
			$stream->writeByteArray($subChunk->blocksId);
			$stream->writeByteArray($subChunk->blocksData);
		}

		// v486 (Bedrock 1.18.10) requires 3D biomes array copied 25 times
		$encodedBiomePalette = self::serializeBiomesAsPalette($datum->biomes);
		$stream->writeByteArray(str_repeat($encodedBiomePalette, 25));

		Byte::writeUnsigned($stream, 0); // border block array count

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
					$state = $blockStateDictionary->generateDataFromStateId($blockTranslator->internalIdToNetworkId($p));
					if ($state === null) {
						$state = $blockTranslator->getFallbackStateData();
					}

					$writer->writeByteArray($nbtSerializer->write(new TreeRoot($state->toNbt())));
				}
				continue;
			}

			foreach ($palette as $p) {
				VarInt::writeUnsignedInt($writer, $blockTranslator->internalIdToNetworkId($p) << 1);
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

	private static function serializeBiomesAsPalette(string $biomes2D) : string
	{
		$biomeIdMap = LegacyBiomeIdToStringIdMap::getInstance();
		$firstBiomeId = ord($biomes2D[0]);
		if($biomeIdMap->legacyToString($firstBiomeId) === null) {
			$firstBiomeId = BiomeIds::OCEAN;
		}

		$biomePalette = new PalettedBlockArray($firstBiomeId);
		for($x = 0; $x < 16; ++$x){
			for($z = 0; $z < 16; ++$z){
				$biomeId = ord($biomes2D[($z << 4) | $x]);
				if($biomeIdMap->legacyToString($biomeId) === null){
					$biomeId = BiomeIds::OCEAN;
				}
				for($y = 0; $y < 16; ++$y){
					$biomePalette->set($x, $y, $z, $biomeId);
				}
			}
		}

		$biomePaletteBitsPerBlock = $biomePalette->getBitsPerBlock();
		$tempStream = new ByteBufferWriter();

		Byte::writeUnsigned($tempStream, ($biomePaletteBitsPerBlock << 1) | 1); // the last bit is non-persistence
		$tempStream->writeByteArray($biomePalette->getWordArray());

		$biomePaletteArray = $biomePalette->getPalette();
		if($biomePaletteBitsPerBlock !== 0){
			VarInt::writeUnsignedInt($tempStream, count($biomePaletteArray) << 1);
		}
		foreach($biomePaletteArray as $p){
			VarInt::writeUnsignedInt($tempStream, $p << 1);
		}

		return $tempStream->getData();
	}
}
