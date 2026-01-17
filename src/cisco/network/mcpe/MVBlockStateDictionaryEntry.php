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

namespace cisco\network\mcpe;

use pocketmine\data\bedrock\block\BlockStateData;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\Tag;
use pocketmine\nbt\TreeRoot;
use function count;
use function ksort;
use const SORT_STRING;

class MVBlockStateDictionaryEntry
{
	/**
	 * @var string[]
	 * @phpstan-var array<string, string>
	 */
	private static array $uniqueRawStates = [];

	private string $rawStateProperties;

	/**
	 * @param Tag[] $stateProperties
	 */
	public function __construct(
		private string          $stateName,
		array                   $stateProperties,
		private int             $meta,
		private ?BlockStateData $oldBlockStateData
	)
	{
		$rawStateProperties = self::encodeStateProperties($stateProperties);
		$this->rawStateProperties = self::$uniqueRawStates[$rawStateProperties] ??= $rawStateProperties;
	}

	/**
	 * @param Tag[] $properties
	 */
	public static function encodeStateProperties(array $properties) : string
	{
		if (count($properties) === 0) {
			return "";
		}
		//TODO: make a more efficient encoding - NBT will do for now, but it's not very compact
		ksort($properties, SORT_STRING);
		$tag = new CompoundTag();
		foreach ($properties as $k => $v) {
			$tag->setTag($k, $v);
		}
		return (new LittleEndianNbtSerializer())->write(new TreeRoot($tag));
	}

	public function getStateName() : string
	{
		return $this->stateName;
	}

	public function getRawStateProperties() : string
	{
		return $this->rawStateProperties;
	}

	public function generateStateData() : BlockStateData
	{
		return $this->oldBlockStateData ?? $this->generateCurrentStateData();
	}

	public function generateCurrentStateData() : BlockStateData
	{
		return new BlockStateData(
			$this->stateName,
			self::decodeStateProperties($this->rawStateProperties),
			BlockStateData::CURRENT_VERSION
		);
	}

	/**
	 * @return Tag[]
	 */
	public static function decodeStateProperties(string $rawProperties) : array
	{
		if ($rawProperties === "") {
			return [];
		}
		return (new LittleEndianNbtSerializer())->read($rawProperties)->mustGetCompoundTag()->getValue();
	}

	public function getMeta() : int
	{
		return $this->meta;
	}
}
