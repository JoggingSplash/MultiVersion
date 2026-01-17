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

namespace cisco\network\proto\v844\structure;

use cisco\Loader;
use cisco\network\proto\v844\mapping\v844BiomeDefinitionEntry;
use cisco\network\proto\v844\packets\v844BiomeDefinitionListPacket;
use cisco\network\utils\PacketCachedTrait;
use JsonMapper;
use JsonMapper_Exception;
use pocketmine\color\Color;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\network\mcpe\cache\CraftingDataCache;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use pocketmine\world\biome\model\BiomeDefinitionEntryData;
use RuntimeException;
use Symfony\Component\Filesystem\Path;
use function count;
use function get_debug_type;
use function is_array;
use function json_decode;

class v844StaticPacketCache
{
	use PacketCachedTrait;

	private v844BiomeDefinitionListPacket $biomeDefinitionListPacket;
	private CraftingDataPacket $craftingDataPacket;

	public function getBiomeDefinitionListPacket() : v844BiomeDefinitionListPacket
	{
		return $this->biomeDefinitionListPacket;
	}

	public function getCraftingDataPacket() : CraftingDataPacket
	{
		return $this->craftingDataPacket;
	}

	protected function load() : void
	{
		$this->biomeDefinitionListPacket = v844BiomeDefinitionListPacket::fromDefinitions(self::loadBiomeDefinitionModel(Path::join(Loader::getPluginResourcePath(), "v844", "biome_definitions.json")));
		$this->craftingDataPacket = CraftingDataCache::getInstance()->getCache($this->protocol->getCraftingManager());
	}

	private static function loadBiomeDefinitionModel(string $filePath) : array
	{
		$biomeEntries = json_decode(Filesystem::fileGetContents($filePath), associative: true);
		if (!is_array($biomeEntries)) {
			throw new SavedDataLoadingException("$filePath root should be an array, got " . get_debug_type($biomeEntries));
		}

		$jsonMapper = new JsonMapper();
		$jsonMapper->bExceptionOnMissingData = true;
		$jsonMapper->bStrictObjectTypeChecking = true;
		$jsonMapper->bEnforceMapType = false;

		$entries = [];
		foreach (Utils::promoteKeys($biomeEntries) as $biomeName => $entry) {
			if (!is_array($entry)) {
				throw new SavedDataLoadingException("$filePath should be an array of objects, got " . get_debug_type($entry));
			}

			try {
				/**
				 * @var BiomeDefinitionEntryData $biomeDefinition
				 */
				$biomeDefinition = $jsonMapper->map($entry, new BiomeDefinitionEntryData());

				$mapWaterColour = $biomeDefinition->mapWaterColour;
				$entries[] = new v844BiomeDefinitionEntry(
					(string) $biomeName,
					$biomeDefinition->id,
					$biomeDefinition->temperature,
					$biomeDefinition->downfall,
					$biomeDefinition->foliageSnow,
					$biomeDefinition->depth,
					$biomeDefinition->scale,
					new Color(
						$mapWaterColour->r,
						$mapWaterColour->g,
						$mapWaterColour->b,
						$mapWaterColour->a
					),
					$biomeDefinition->rain,
					count($biomeDefinition->tags) > 0 ? $biomeDefinition->tags : null,
				);
			} catch (JsonMapper_Exception $e) {
				throw new RuntimeException($e->getMessage(), 0, $e);
			}
		}

		return $entries;
	}
}
