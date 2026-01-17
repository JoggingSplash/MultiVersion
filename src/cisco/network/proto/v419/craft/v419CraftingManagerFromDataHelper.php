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

namespace cisco\network\proto\v419\craft;

use cisco\Loader;
use pocketmine\crafting\CraftingManager;
use pocketmine\crafting\ExactRecipeIngredient;
use pocketmine\crafting\RecipeIngredient;
use pocketmine\crafting\ShapelessRecipe;
use pocketmine\crafting\ShapelessRecipeType;
use pocketmine\data\bedrock\item\ItemTypeDeserializeException;
use pocketmine\data\SavedDataLoadingException;
use pocketmine\errorhandler\ErrorToExceptionHandler;
use pocketmine\item\Item;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use Symfony\Component\Filesystem\Path;
use function base64_decode;
use function get_debug_type;
use function is_array;
use function is_object;
use function json_decode;

final class v419CraftingManagerFromDataHelper
{

	public static function make() : CraftingManager{
		$result = new CraftingManager();
		$directoryPath = Path::join(Loader::getPluginResourcePath(), "v419", "recipes");
		/**
		 * @var v419ShapelessRecipeData $recipe
		 */
		foreach(self::loadJsonArrayOfObjectsFile(Path::join($directoryPath, 'shapeless_crafting.json'), v419ShapelessRecipeData::class) as $recipe){
			self::registerShapeless($result, $recipe->input, $recipe->output, match($recipe->block){
				"crafting_table" => ShapelessRecipeType::CRAFTING,
				"stonecutter" => ShapelessRecipeType::STONECUTTER,
				"smithing_table" => ShapelessRecipeType::SMITHING,
				"cartography_table" => ShapelessRecipeType::CARTOGRAPHY,
				default => null
			});
		}

		/**
		 * @var v419ShapelessRecipeData $recipe
		 */
		foreach(self::loadJsonArrayOfObjectsFile(Path::join($directoryPath, 'shapeless_chemistry.json'), v419ShapelessRecipeData::class) as $recipe){
			self::registerShapeless($result, $recipe->input, $recipe->output, match($recipe->block){
				"crafting_table" => ShapelessRecipeType::CRAFTING,
				"stonecutter" => ShapelessRecipeType::STONECUTTER,
				"smithing_table" => ShapelessRecipeType::SMITHING,
				"cartography_table" => ShapelessRecipeType::CARTOGRAPHY,
				default => null
			});
		}

		/**
		 * @var v419ShapelessRecipeData $recipe
		 */
		foreach(self::loadJsonArrayOfObjectsFile(Path::join($directoryPath, 'shapeless_shulker_box.json'), v419ShapelessRecipeData::class) as $recipe){
			self::registerShapeless($result, $recipe->input, $recipe->output, match($recipe->block){
				"crafting_table" => ShapelessRecipeType::CRAFTING,
				"stonecutter" => ShapelessRecipeType::STONECUTTER,
				"smithing_table" => ShapelessRecipeType::SMITHING,
				"cartography_table" => ShapelessRecipeType::CARTOGRAPHY,
				default => null
			});
		}

		return $result;
	}

	private static function registerShapeless(CraftingManager $result, array $inputs_to_modify, array $outputs_to_modify, ShapelessRecipeType $recipeType) : void {
		$inputs = [];
		foreach($inputs_to_modify as $inputData){
			$input = self::deserializeIngredient($inputData);
			if($input === null){ //unknown input item
				continue;
			}
			$inputs[] = $input;
		}
		$outputs = [];
		foreach($outputs_to_modify as $outputData){
			$output = self::deserializeItemStack($outputData);
			if($output === null){ //unknown output item
				continue;
			}
			$outputs[] = $output;
		}

		$result->registerShapelessRecipe(new ShapelessRecipe(
			$inputs,
			$outputs,
			$recipeType
		));
	}

	/**
	 * @return object[]
	 */
	public static function loadJsonArrayOfObjectsFile(string $dir, string $modelCLass) : array{
		$recipes = json_decode(Filesystem::fileGetContents($dir));
		if(!is_array($recipes)){
			throw new AssumptionFailedError("array was not given");
		}
		$mapper = new \JsonMapper();
		$mapper->bStrictObjectTypeChecking = false;
		$mapper->bExceptionOnUndefinedProperty = true;
		$mapper->bExceptionOnMissingData = true;

		return self::loadJsonObjectListIntoModel($mapper, $modelCLass, $recipes);
	}

	/**
	 * @param mixed[] $data
	 * @return object[]
	 *
	 * @phpstan-template TRecipeData of object
	 * @phpstan-param class-string<TRecipeData> $modelClass
	 * @phpstan-return list<TRecipeData>
	 */
	private static function loadJsonObjectListIntoModel(\JsonMapper $mapper, string $modelClass, array $data) : array{
		$result = [];
		foreach(Utils::promoteKeys($data) as $i => $item){
			if(!is_object($item)){
				throw new SavedDataLoadingException("Invalid entry at index $i: expected object, got " . get_debug_type($item));
			}
			try{
				$result[] = self::loadJsonObjectIntoModel($mapper, $modelClass, $item);
			}catch(SavedDataLoadingException $e){
				throw new SavedDataLoadingException("Invalid entry at index $i: " . $e->getMessage(), 0, $e);
			}
		}
		return $result;
	}

	/**
	 * @phpstan-template TRecipeData of object
	 * @phpstan-param class-string<TRecipeData> $modelClass
	 * @phpstan-return TRecipeData
	 */
	private static function loadJsonObjectIntoModel(\JsonMapper $mapper, string $modelClass, object $data) : object{
		//JsonMapper does this for subtypes, but not for the base type :(
		try{
			return $mapper->map($data, (new \ReflectionClass($modelClass))->newInstanceWithoutConstructor());
		}catch(\JsonMapper_Exception $e){
			throw new SavedDataLoadingException($e->getMessage(), 0, $e);
		}
	}

	private static function deserializeIngredient(v419RecipeIngredientData $data) : ?RecipeIngredient{
		if(isset($data->count) && $data->count !== 1){
			//every case we've seen so far where this isn't the case, it's been a bug and the count was ignored anyway
			//e.g. gold blocks crafted from 9 ingots, but each input item individually had a count of 9
			throw new SavedDataLoadingException("Recipe inputs should have a count of exactly 1");
		}

		$meta = $data->damage ?? null;
		$itemStack = self::deserializeItemStackFromFields(
			$data->id,
			$meta,
			$data->count ?? null,
			$data->block_states ?? null,
			null,
			[],
			[]
		);

		if($itemStack === null){
			//probably unknown item
			return null;
		}

		return new ExactRecipeIngredient($itemStack);
	}

	public static function deserializeItemStack(v419ItemStackData $data) : ?Item{
		//count, name, block_name, block_states, meta, nbt, can_place_on, can_destroy
		return self::deserializeItemStackFromFields(
			$data->id,
			$data->damage ?? null,
			$data->count ?? null,
			null,
			$data->nbt_b64 ?? null,
			[],
			[]
		);
	}

	/**
	 * @throws \ErrorException
	 */
	private static function deserializeItemStackFromFields(int $id, ?int $meta, ?int $count, ?string $blockStatesRaw, ?string $nbtRaw, array $canPlaceOn, array $canDestroy) : ?Item{
		$meta ??= 0;
		$count ??= 1;

		$nbt = $nbtRaw === null ? null : (new LittleEndianNbtSerializer())
			->read(ErrorToExceptionHandler::trapAndRemoveFalse(fn() => base64_decode($nbtRaw, true)))
			->mustGetCompoundTag();

		$itemStackData = GlobalItemDataHandlers::getUpgrader()->upgradeItemTypeDataInt(
			$id,
			$meta,
			$count,
			$nbt
		);

		try{
			return GlobalItemDataHandlers::getDeserializer()->deserializeStack($itemStackData);
		}catch(ItemTypeDeserializeException){
			//probably unknown item
			return null;
		}
	}

}
