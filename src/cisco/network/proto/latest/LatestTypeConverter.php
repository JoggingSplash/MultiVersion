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

use cisco\network\global\MVTypeConverter;
use cisco\network\mcpe\MVBlockStateDictionary;
use cisco\network\mcpe\MVBlockTranslator;
use cisco\network\mcpe\MVItemIdMetaDowngrader;
use cisco\network\mcpe\MVItemTranslator;
use pocketmine\data\bedrock\BedrockDataFiles;
use pocketmine\data\bedrock\item\BlockItemIdMap;
use pocketmine\network\mcpe\convert\ItemTypeDictionaryFromDataHelper;
use pocketmine\network\mcpe\convert\LegacySkinAdapter;
use pocketmine\utils\Filesystem;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\format\io\GlobalBlockStateHandlers;
use pocketmine\world\format\io\GlobalItemDataHandlers;

class LatestTypeConverter
{
	use SingletonTrait;

	private MVTypeConverter $converter;

	public function __construct() {
		$this->converter = new MVTypeConverter(
			$blockItemIdMap = BlockItemIdMap::getInstance(),
			$blockTranslator = new MVBlockTranslator(
				MVBlockStateDictionary::loadFromString(Filesystem::fileGetContents(BedrockDataFiles::CANONICAL_BLOCK_STATES_NBT), Filesystem::fileGetContents(BedrockDataFiles::BLOCK_STATE_META_MAP_JSON)),
				GlobalBlockStateHandlers::getSerializer(),
			),
			$itemTypeDictionary = ItemTypeDictionaryFromDataHelper::loadFromString(Filesystem::fileGetContents(BedrockDataFiles::REQUIRED_ITEM_LIST_JSON)),
			new MVItemTranslator(
				$itemTypeDictionary,
				$blockTranslator->getBlockStateDictionary(),
				GlobalItemDataHandlers::getSerializer(),
				GlobalItemDataHandlers::getDeserializer(),
				$blockItemIdMap,
				new MVItemIdMetaDowngrader($itemTypeDictionary, 181) // This schema id is not necessary
			),
			new LegacySkinAdapter(),
		);
	}

	public function getConverter() : MVTypeConverter
	{
		return $this->converter;
	}

}
