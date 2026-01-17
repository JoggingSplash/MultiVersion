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

namespace cisco\network\proto\v844;

use cisco\Loader;
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
use Symfony\Component\Filesystem\Path;

final class v844TypeConverter
{
	use SingletonTrait;

	private MVTypeConverter $converter;

	public function __construct()
	{
		$this->converter = new MVTypeConverter(
			$blockItemIdMap = BlockItemIdMap::getInstance(),
			$blockTranslator = new MVBlockTranslator(
				MVBlockStateDictionary::loadFromString(Filesystem::fileGetContents(BedrockDataFiles::CANONICAL_BLOCK_STATES_NBT), Filesystem::fileGetContents(
					Path::join(Loader::getPluginResourcePath(), "v844", "block_state_meta_map.json")
				)),
				GlobalBlockStateHandlers::getSerializer(),
			),
			$itemTypeDictionary = ItemTypeDictionaryFromDataHelper::loadFromString(Filesystem::fileGetContents(
				Path::join(Loader::getPluginResourcePath(), "v844", "required_item_list.json")
			)),
			new MVItemTranslator(
				$itemTypeDictionary,
				$blockTranslator->getBlockStateDictionary(),
				GlobalItemDataHandlers::getSerializer(),
				GlobalItemDataHandlers::getDeserializer(),
				$blockItemIdMap,
				new MVItemIdMetaDowngrader($itemTypeDictionary, 181)
			),
			new LegacySkinAdapter(),
		);
	}

	public function getConverter() : MVTypeConverter
	{
		return $this->converter;
	}
}
