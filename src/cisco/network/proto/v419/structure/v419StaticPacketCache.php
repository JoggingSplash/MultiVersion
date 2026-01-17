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

namespace cisco\network\proto\v419\structure;

use cisco\Loader;
use cisco\network\proto\v419\packets\types\inventory\v419CreativeContentEntry;
use cisco\network\proto\v419\packets\types\recipe\v419RecipeIngredient;
use cisco\network\proto\v419\packets\types\recipe\v419ShapedRecipe;
use cisco\network\proto\v419\packets\types\recipe\v419ShapelessRecipe;
use cisco\network\proto\v419\packets\v419BiomeDefinitionListPacket;
use cisco\network\proto\v419\packets\v419CraftingDataPacket;
use cisco\network\proto\v419\packets\v419CreativeContentPacket;
use cisco\network\utils\PacketCachedTrait;
use cisco\network\utils\ProtocolUtils;
use pmmp\encoding\BE;
use pocketmine\crafting\ExactRecipeIngredient;
use pocketmine\crafting\MetaWildcardRecipeIngredient;
use pocketmine\crafting\RecipeIngredient;
use pocketmine\crafting\ShapedRecipe;
use pocketmine\crafting\ShapelessRecipe;
use pocketmine\crafting\ShapelessRecipeType;
use pocketmine\data\bedrock\item\ItemTypeDeserializeException;
use pocketmine\data\bedrock\item\ItemTypeSerializeException;
use pocketmine\inventory\CreativeInventory;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\types\recipe\CraftingRecipeBlockName;
use pocketmine\timings\Timings;
use pocketmine\utils\AssumptionFailedError;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Filesystem\Path;
use function array_map;

final class v419StaticPacketCache
{
	use PacketCachedTrait;

	private v419BiomeDefinitionListPacket $biomeDefinitions;
	private AvailableActorIdentifiersPacket $actorIdentifiersPacket;
	private CreativeContentPacket $creativeContent;
	private v419CraftingDataPacket $craftingDataPacket;

	public function getBiomeDefinitions() : v419BiomeDefinitionListPacket
	{
		return clone $this->biomeDefinitions;
	}

	public function getActorIdentifiersPacket() : AvailableActorIdentifiersPacket
	{
		return clone $this->actorIdentifiersPacket;
	}

	public function getCreativeContent() : CreativeContentPacket
	{
		return clone $this->creativeContent;
	}

	public function getCraftingDataPacket() : v419CraftingDataPacket
	{
		return $this->craftingDataPacket;
	}

	protected function load() : void
	{
		$this->biomeDefinitions = v419BiomeDefinitionListPacket::v419create(
			ProtocolUtils::loadCacheableFromFile(self::join("biome_definitions.nbt"))
		);
		$this->actorIdentifiersPacket = AvailableActorIdentifiersPacket::create(
			ProtocolUtils::loadCacheableFromFile(self::join("entity_identifiers.nbt"))
		);

		$entries = [];
		$converter = $this->protocol->getTypeConverter();
		foreach (CreativeInventory::getInstance()->getAll() as $index => $item){
			$entries[] = new v419CreativeContentEntry($index, $converter->coreItemStackToNet($item));
		}
		$this->creativeContent = v419CreativeContentPacket::v419create($entries);
		$this->craftingDataPacket = $this->buildCraftingDataCache();
	}

	private static function join(string $filename) : string
	{
		return Path::join(Loader::getPluginResourcePath(), "v419", $filename);
	}

	private function buildCraftingDataCache() : v419CraftingDataPacket
	{
		Timings::$craftingDataCacheRebuild->startTiming();
		$manager = $this->protocol->getCraftingManager();

		$nullUUID = Uuid::fromString(Uuid::NIL);
		$converter = $this->protocol->getTypeConverter();
		$recipesWithTypeIds = [];

		foreach ($manager->getCraftingRecipeIndex() as $index => $recipe) {
			//the client doesn't like recipes with an ID of 0, so we need to offset them
			$recipeNetId = $index + 1;
			if ($recipe instanceof ShapelessRecipe) {
				$typeTag = match ($recipe->getType()) {
					ShapelessRecipeType::CRAFTING => CraftingRecipeBlockName::CRAFTING_TABLE,
					ShapelessRecipeType::STONECUTTER => CraftingRecipeBlockName::STONECUTTER,
					ShapelessRecipeType::CARTOGRAPHY => CraftingRecipeBlockName::CARTOGRAPHY_TABLE,
					ShapelessRecipeType::SMITHING => CraftingRecipeBlockName::SMITHING_TABLE,
				};
				$recipesWithTypeIds[] = new v419ShapelessRecipe(
					CraftingDataPacket::ENTRY_SHAPELESS,
					BE::packUnsignedInt($recipeNetId), //TODO: this should probably be changed to something human-readable
					array_map(self::exactRecipeTo116(...), $recipe->getIngredientList()),
					array_map($converter->coreItemStackToNet(...), $recipe->getResults()),
					$nullUUID,
					$typeTag,
					50,
					$recipeNetId
				);
			} elseif ($recipe instanceof ShapedRecipe) {
				$inputs = [];

				for ($row = 0, $height = $recipe->getHeight(); $row < $height; ++$row) {
					for ($column = 0, $width = $recipe->getWidth(); $column < $width; ++$column) {
						$inputs[$row][$column] = self::exactRecipeTo116($recipe->getIngredient($column, $row));
					}
				}
				$recipesWithTypeIds[] = new v419ShapedRecipe(
					CraftingDataPacket::ENTRY_SHAPED,
					BE::packUnsignedInt($recipeNetId), //TODO: this should probably be changed to something human-readable
					$inputs,
					array_map($converter->coreItemStackToNet(...), $recipe->getResults()),
					$nullUUID,
					CraftingRecipeBlockName::CRAFTING_TABLE,
					50,
					$recipeNetId
				);
			}
		}

		Timings::$craftingDataCacheRebuild->stopTiming();
		return v419CraftingDataPacket::v419create($recipesWithTypeIds); // TODO
	}

	public static function exactRecipeTo116(?RecipeIngredient $ingredient) : v419RecipeIngredient {
		if ($ingredient === null) {
			return new v419RecipeIngredient(0, 0, 0);
		}

		$converter = TypeConverter::getInstance();
		if ($ingredient instanceof MetaWildcardRecipeIngredient) {
			try {
				$id = $converter->getItemTypeDictionary()->fromStringId($ingredient->getItemId());
			}catch (\InvalidArgumentException $exception){
				$id = 0;
			}
			$meta = 0x7fff;
		} elseif ($ingredient instanceof ExactRecipeIngredient) {
			try {
				$item = $ingredient->getItem();
				[$id, $meta, $blockRuntimeId] = $converter->getItemTranslator()->toNetworkId($item);
				if ($blockRuntimeId !== null) {
					$meta = $converter->getBlockTranslator()->getBlockStateDictionary()->getMetaFromStateId($blockRuntimeId);
					if ($meta === null) {
						throw new AssumptionFailedError("Every block state should have an associated meta value");
					}
				}
			}catch (ItemTypeDeserializeException|ItemTypeSerializeException $exception){
				$id = 0;
				$meta = 0;
			}

		}else {
			$id = 0;
			$meta = 0;
		}

		return new v419RecipeIngredient($id, $meta, 1);
	}
}
