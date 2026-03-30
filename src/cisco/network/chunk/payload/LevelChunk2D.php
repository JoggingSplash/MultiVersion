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
use cisco\network\utils\ReflectionUtils;
use GlobalLogger;
use pocketmine\data\bedrock\BiomeIds;
use pocketmine\data\bedrock\block\BlockStateDeserializeException;
use pocketmine\data\bedrock\block\convert\UnsupportedBlockStateException;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\NBT;
use pocketmine\nbt\NbtDataException;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\BinaryDataException;
use pocketmine\utils\BinaryStream;
use pocketmine\utils\Utils;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\exception\CorruptedChunkException;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\format\io\leveldb\ChunkDataKey;
use pocketmine\world\format\io\leveldb\ChunkVersion;
use pocketmine\world\format\io\leveldb\LevelDB as LevelDBI;
use pocketmine\world\format\io\leveldb\SubChunkVersion;
use pocketmine\world\format\PalettedBlockArray;
use pocketmine\world\World;
use function chr;
use function count;
use function implode;
use function ord;
use function str_repeat;
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

		$this->chunks[$hash] = $this->prepareChunk($chunk); // $chunk->isPopulated() ? $this->prepareChunk($chunk) : $this->prepareChunkFromDb($chunkX, $chunkZ);
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

	private function readVersion(int $chunkX, int $chunkZ) : ?int{
		$index = LevelDBI::chunkIndex($chunkX, $chunkZ);
		$chunkVersionRaw = $this->db->get($index . ChunkDataKey::NEW_VERSION);
		if($chunkVersionRaw === false){
			$chunkVersionRaw = $this->db->get($index . ChunkDataKey::OLD_VERSION);
			if($chunkVersionRaw === false){
				return null;
			}
		}

		return ord($chunkVersionRaw);
	}

	private function prepareChunkFromDb(int $chunkX, int $chunkZ) : ChunkDatum {
		$version = $this->readVersion($chunkX, $chunkZ);

		if($version === null){
			//bogus chunk?
			return ChunkDatum::empty();
		}

		$index = LevelDBI::chunkIndex($chunkX, $chunkZ);

		switch($version){
			case ChunkVersion::v1_21_120:
			case ChunkVersion::v1_21_40:
			case ChunkVersion::v1_18_30:
			case ChunkVersion::v1_18_0_25_beta:
			case ChunkVersion::v1_18_0_24_unused:
			case ChunkVersion::v1_18_0_24_beta:
			case ChunkVersion::v1_18_0_22_unused:
			case ChunkVersion::v1_18_0_22_beta:
			case ChunkVersion::v1_18_0_20_unused:
			case ChunkVersion::v1_18_0_20_beta:
			case ChunkVersion::v1_17_40_unused:
			case ChunkVersion::v1_17_40_20_beta_experimental_caves_cliffs:
			case ChunkVersion::v1_17_30_25_unused:
			case ChunkVersion::v1_17_30_25_beta_experimental_caves_cliffs:
			case ChunkVersion::v1_17_30_23_unused:
			case ChunkVersion::v1_17_30_23_beta_experimental_caves_cliffs:
			case ChunkVersion::v1_16_230_50_unused:
			case ChunkVersion::v1_16_230_50_beta_experimental_caves_cliffs:
			case ChunkVersion::v1_16_220_50_unused:
			case ChunkVersion::v1_16_220_50_beta_experimental_caves_cliffs:
			case ChunkVersion::v1_16_210:
			case ChunkVersion::v1_16_100_57_beta:
			case ChunkVersion::v1_16_100_52_beta:
			case ChunkVersion::v1_16_0:
			case ChunkVersion::v1_16_0_51_beta:
			case ChunkVersion::v1_12_0_unused2:
			case ChunkVersion::v1_12_0_unused1:
			case ChunkVersion::v1_12_0_4_beta:
			case ChunkVersion::v1_11_1:
			case ChunkVersion::v1_11_0_4_beta:
			case ChunkVersion::v1_11_0_3_beta:
			case ChunkVersion::v1_11_0_1_beta:
			case ChunkVersion::v1_9_0:
			case ChunkVersion::v1_8_0:
			case ChunkVersion::v1_2_13:
			case ChunkVersion::v1_2_0:
			case ChunkVersion::v1_2_0_2_beta:
			case ChunkVersion::v1_1_0_converted_from_console:
			case ChunkVersion::v1_1_0:
			case ChunkVersion::v1_0_0:
				$biomes = $this->readBiomes2d($index, $version);
				$subChunks = $this->deserializeSubChunks($index, $version);
				break;
			case ChunkVersion::v0_9_5:
			case ChunkVersion::v0_9_2:
			case ChunkVersion::v0_9_0:
				//TODO: legacy deserialize then break
			default:
				throw new CorruptedChunkException("don't know how to decode chunk format version $version");
		}

		return new ChunkDatum(
			$biomes,
			$subChunks
		);
	}

	private function deserializeSubChunks(string $index, int $version) : array {
		$subChunks = [];
		for($i = 0; $i < 16; $i++){
			$data = $this->db->get($index . ChunkDataKey::SUBCHUNK . chr($i));
			if($data === false){
				$subChunks[$i] = SubChunkDatum::empty();
				continue;
			}
			$binaryStream = new BinaryStream($data);
			if($binaryStream->feof()){
				throw new CorruptedChunkException("Unexpected empty data for subchunk $i");
			}
			$subChunkVersion = $binaryStream->getByte();
			$subChunks[$i] = $this->deserializeSubChunkDatum(
				$binaryStream,
				$version,
				$subChunkVersion,
			);
		}

		return $subChunks;
	}

	/**
	 * Deserializes subchunk data stored under a subchunk LevelDB key.
	 *
	 * @see ChunkDataKey::SUBCHUNK
	 * @throws CorruptedChunkException
	 */
	private function deserializeSubChunkDatum(BinaryStream $binaryStream, int $chunkVersion, int $subChunkVersion) : SubChunkDatum {
		switch($subChunkVersion){
			case SubChunkVersion::CLASSIC:
			case SubChunkVersion::CLASSIC_BUG_2:
			case SubChunkVersion::CLASSIC_BUG_3:
			case SubChunkVersion::CLASSIC_BUG_4:
			case SubChunkVersion::CLASSIC_BUG_5:
			case SubChunkVersion::CLASSIC_BUG_6:
			case SubChunkVersion::CLASSIC_BUG_7:
				$blocks = $binaryStream->get(4096);
				$data = $binaryStream->get(2048);
				return new SubChunkDatum(
					$blocks,
					$data
				);
			case SubChunkVersion::PALETTED_SINGLE:
				[$blocks, $data] = $this->palettedToClassic(
					$this->deserializeBlockPalette($binaryStream)
				);
				return new SubChunkDatum(
					$blocks,
					$data
				);
			case SubChunkVersion::PALETTED_MULTI:
			case SubChunkVersion::PALETTED_MULTI_WITH_OFFSET:

				$binaryStream->getByte(); //storage
				if($subChunkVersion >= SubChunkVersion::PALETTED_MULTI_WITH_OFFSET){
					//height ignored
					$binaryStream->getByte();
				}

			[$blocks, $data] = $this->palettedToClassic(
				$this->deserializeBlockPalette($binaryStream)
			);
			return new SubChunkDatum(
				$blocks,
				$data
			);
			default:
				//this should never happen - an unsupported chunk appearing in a supported world is a sign of corruption
				throw new CorruptedChunkException("don't know how to decode LevelDB subchunk format version $subChunkVersion");
		}
	}

	/**
	 * @throws \ReflectionException
	 */
	private function readBiomes2d(string $index, int $chunkVersion) : string {
		$logger = GlobalLogger::get();
		if(($maps2d = $this->db->get($index . ChunkDataKey::HEIGHTMAP_AND_2D_BIOMES)) !== false){
			$binaryStream = new BinaryStream($maps2d);

			try{
				$binaryStream->get(512); //heightmap, discard it
				$biomes = $binaryStream->get(256);
				if(!$binaryStream->feof()){
					$logger->error("Unexpected trailing data after 2D biome data");
				}
			}catch(BinaryDataException $e){
				throw new CorruptedChunkException($e->getMessage(), 0, $e);
			}
		}elseif(($maps3d = $this->db->get($index . ChunkDataKey::HEIGHTMAP_AND_3D_BIOMES)) !== false){
			$binaryStream = new BinaryStream($maps3d);

			try{
				$binaryStream->get(512);
				$biomes3d = ReflectionUtils::invokeStatic(LevelDBI::class, "deserialize3dBiomes", $binaryStream, $chunkVersion, $logger);
				$biomes = self::reduce3DBiomes(clone $biomes3d[Chunk::MIN_SUBCHUNK_INDEX]);
			}catch(BinaryDataException $e){
				throw new CorruptedChunkException($e->getMessage(), 0, $e);
			}
		}else{
			$logger->error("Missing biome data, using default ocean biome");
			$biomes = str_repeat(chr(BiomeIds::OCEAN), 256);
		}

		return $biomes;
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
	 * @throws CorruptedChunkException
	 */
	protected function deserializeBlockPalette(BinaryStream $stream) : PalettedBlockArray{
		$bitsPerBlock = $stream->getByte() >> 1;
		$logger = GlobalLogger::get();

		try{
			$words = $stream->get(PalettedBlockArray::getExpectedWordArraySize($bitsPerBlock));
		}catch(\InvalidArgumentException $e){
			throw new CorruptedChunkException("Failed to deserialize paletted storage: " . $e->getMessage(), 0, $e);
		}
		$nbt = new LittleEndianNbtSerializer();
		$palette = [];

		if($bitsPerBlock === 0){
			$paletteSize = 1;
			/*
			 * Due to code copy-paste in a public plugin, some PM4 worlds have 0 bpb palettes with a length prefix.
			 * This is invalid and does not happen in vanilla.
			 * These palettes were accepted by PM4 despite being invalid, but PM5 considered them corrupt, causing loss
			 * of data. Since many users were affected by this, a workaround is therefore necessary to allow PM5 to read
			 * these worlds without data loss.
			 *
			 * References:
			 * - https://github.com/Refaltor77/CustomItemAPI/issues/68
			 * - https://github.com/pmmp/PocketMine-MP/issues/5911
			 */
			$offset = $stream->getOffset();
			$byte1 = $stream->getByte();
			$stream->setOffset($offset); //reset offset

			if($byte1 !== NBT::TAG_Compound){ //normally the first byte would be the NBT of the blockstate
				$susLength = $stream->getLInt();
				if($susLength !== 1){ //make sure the data isn't complete garbage
					throw new CorruptedChunkException("CustomItemAPI borked 0 bpb palette should always have a length of 1");
				}
				$logger->error("Unexpected palette size for 0 bpb palette");
			}
		}else{
			$paletteSize = $stream->getLInt();
		}

		$blockDecodeErrors = [];

		$blockStateDeserializer = GlobalBlockStateHandlers::getDeserializer();
		$blockDataUpgrader = GlobalBlockStateHandlers::getUpgrader();

		for($i = 0; $i < $paletteSize; ++$i){
			try{
				$offset = $stream->getOffset();
				$blockStateNbt = $nbt->read($stream->getBuffer(), $offset)->mustGetCompoundTag();
				$stream->setOffset($offset);
			}catch(NbtDataException $e){
				//NBT borked, unrecoverable
				throw new CorruptedChunkException("Invalid blockstate NBT at offset $i in paletted storage: " . $e->getMessage(), 0, $e);
			}

			//TODO: remember data for unknown states so we can implement them later
			try{
				$blockStateData = $blockDataUpgrader->upgradeBlockStateNbt($blockStateNbt);
			}catch(BlockStateDeserializeException $e){
				//while not ideal, this is not a fatal error
				$errorMessage = "Upgrade error: " . $e->getMessage() . ", NBT: " . $blockStateNbt->toString();
				$blockDecodeErrors[$errorMessage][] = $i;
				$palette[] = $blockStateDeserializer->deserialize(GlobalBlockStateHandlers::getUnknownBlockStateData());
				continue;
			}
			try{
				$palette[] = $blockStateDeserializer->deserialize($blockStateData);
			}catch(UnsupportedBlockStateException $e){
				$blockDecodeErrors[$e->getMessage()][] = $i;
				$palette[] = $blockStateDeserializer->deserialize(GlobalBlockStateHandlers::getUnknownBlockStateData());
			}catch(BlockStateDeserializeException $e){
				$errorMessage = "Deserialize error: " . $e->getMessage() . ", NBT: " . $blockStateNbt->toString();
				$blockDecodeErrors[$errorMessage][] = $i;
				$palette[] = $blockStateDeserializer->deserialize(GlobalBlockStateHandlers::getUnknownBlockStateData());
			}
		}

		if(count($blockDecodeErrors) > 0){
			$finalErrors = [];
			foreach(Utils::promoteKeys($blockDecodeErrors) as $errorMessage => $paletteOffsets){
				$finalErrors[] = "$errorMessage (palette offsets: " . implode(", ", $paletteOffsets) . ")";
			}
			$logger->error("Errors decoding blocks:\n - " . implode("\n - ", $finalErrors));
		}

		//TODO: exceptions
		return PalettedBlockArray::fromData($bitsPerBlock, $words, $palette);
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
