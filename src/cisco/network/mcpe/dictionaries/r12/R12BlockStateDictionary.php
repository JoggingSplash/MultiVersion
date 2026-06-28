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

namespace cisco\network\mcpe\dictionaries\r12;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\LE;
use pocketmine\block\BlockTypeIds;
use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\utils\AssumptionFailedError;

final class R12BlockStateDictionary {

	/**
	 * @param BlockStateData[] $blockStates
	 */
	static public function loadFromString(string $r12contents, array $blockIdMapContest, array $blockStates) : self {
		$nbtStream = new NetworkNbtSerializer();
		$result = new self();
		$stream = new ByteBufferReader($r12contents);
		/**
		 * @var R12BlockStateDictionaryEntry[] $maps
		 */
		$maps = [];

		while ($stream->getUnreadLength() > 0){
			$id = CommonTypes::getString($stream); // get(uvarint)
			$meta = LE::readUnsignedShort($stream);
			$offset = $stream->getOffset();
			$state = $nbtStream->read($stream->getData(), $offset)->mustGetCompoundTag();
			$stream->setOffset($offset);
			$maps[] = new R12BlockStateDictionaryEntry($id, $meta, $state);
		}

		$idToStatesMap = [];

		foreach ($blockStates as $k => $blockState){
			if(!isset($idToStatesMap[$f = $blockState->getName()])) {
				$idToStatesMap[$f] = [];
			}
			$idToStatesMap[$f][] = $k;
		}

		foreach ($maps as $map){
			$id = $blockIdMapContest[$map->getId()] ?? null;

			if($id === null) throw new AssumptionFailedError("No legacy matches " . $map->getId());
			$meta = $map->getMeta();

			if($meta > 15) continue;

			$state = $map->getBlockState();
			$name = $state->getString("name"); //TODO: no hardcode

			if(!isset($idToStatesMap[$name])) throw new AssumptionFailedError("No legacy matches " . $name);

			foreach ($idToStatesMap[$name] as $k){
				$networkState = $blockStates[$k] ?? null;
				if($networkState === null) throw new AssumptionFailedError("No legacy matches " . $k);
				if($state->equals($networkState->toNbt())){
					$result->register($k, $id, $meta);
					continue 2;
				}
			}

			throw new AssumptionFailedError("Mapped new state does not appears in the table");
		}

		return $result;
	}

	private array $legacyToRuntimeMap = [];
	private array $runtimeToLegacyMap = [];

	public function __construct(){

	}

	public function register(int $staticId, int $legacyId, int $legacyMeta) : void {
		$this->legacyToRuntimeMap[($legacyId << 4) | $legacyMeta] = $staticId;
		$this->runtimeToLegacyMap[$staticId] = ($legacyId << 4) | $legacyMeta;
	}

	public function toRuntimeId(int $internalStateId) : int{
		return $this->legacyToRuntimeMap[$internalStateId] ?? $this->legacyToRuntimeMap[BlockTypeIds::INFO_UPDATE << 4] ?? 0;
	}

	public function fromRuntimeId(int $runtimeId) : int{
		return $this->runtimeToLegacyMap[$runtimeId];
	}
}
