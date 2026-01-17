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

namespace cisco\network\global;

use cisco\network\mcpe\MVBlockTranslator;
use cisco\network\mcpe\MVItemTranslator;
use cisco\network\utils\ReflectionUtils;
use LogicException;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\block\VanillaBlocks;
use pocketmine\crafting\ExactRecipeIngredient;
use pocketmine\crafting\MetaWildcardRecipeIngredient;
use pocketmine\crafting\RecipeIngredient;
use pocketmine\crafting\TagWildcardRecipeIngredient;
use pocketmine\data\bedrock\item\BlockItemIdMap;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\ItemTranslator;
use pocketmine\network\mcpe\convert\SkinAdapter;
use pocketmine\network\mcpe\convert\TypeConversionException;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\serializer\ItemTypeDictionary;
use pocketmine\network\mcpe\protocol\types\GameMode as ProtocolGameMode;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackExtraData;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackExtraDataShield;
use pocketmine\network\mcpe\protocol\types\recipe\IntIdMetaItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient as ProtocolRecipeIngredient;
use pocketmine\network\mcpe\protocol\types\recipe\StringIdMetaItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\TagItemDescriptor;
use pocketmine\player\GameMode;
use pocketmine\utils\AssumptionFailedError;
use function get_class;

class MVTypeConverter extends TypeConverter
{

	private const PM_ID_TAG = "___Id___";

	private const RECIPE_INPUT_WILDCARD_META = 0x7fff;

	private BlockItemIdMap $blockItemIdMap;
	private MVBlockTranslator $blockTranslator;
	private MVItemTranslator $itemTranslator;
	private ItemTypeDictionary $itemTypeDictionary;
	private SkinAdapter $skinAdapter;

	public function __construct(BlockItemIdMap $blockItemIdMap, MVBlockTranslator $blockTranslator, ItemTypeDictionary $itemTypeDictionary, MVItemTranslator $ItemTranslator, SkinAdapter $skinAdapter)
	{
		parent::__construct();
		$this->blockItemIdMap = $blockItemIdMap;

		$this->blockTranslator = $blockTranslator;

		$this->itemTypeDictionary = $itemTypeDictionary;
		ReflectionUtils::setProperty(TypeConverter::class, $this, "shieldRuntimeId", $this->itemTypeDictionary->fromStringId("minecraft:shield"));

		$this->itemTranslator = $ItemTranslator;

		$this->skinAdapter = $skinAdapter;
	}

	public function getMVBlockTranslator() : MVBlockTranslator
	{
		return $this->blockTranslator;
	}

	public function getItemTypeDictionary() : ItemTypeDictionary
	{
		return $this->itemTypeDictionary;
	}

	public function getMVItemTranslator() : MVItemTranslator
	{
		return $this->itemTranslator;
	}

	public function getSkinAdapter() : SkinAdapter
	{
		return $this->skinAdapter;
	}

	public function setSkinAdapter(SkinAdapter $skinAdapter) : void
	{
		$this->skinAdapter = $skinAdapter;
	}

	/**
	 * Returns a client-friendly gamemode of the specified real gamemode
	 * This function takes care of handling gamemodes known to MCPE (as of 1.1.0.3, that includes Survival, Creative and Adventure)
	 *
	 * @internal
	 */
	public function coreGameModeToProtocol(GameMode $gamemode) : int
	{
		return match ($gamemode) {
			GameMode::SURVIVAL() => ProtocolGameMode::SURVIVAL,
			GameMode::CREATIVE(), GameMode::SPECTATOR() => ProtocolGameMode::CREATIVE,
			GameMode::ADVENTURE() => ProtocolGameMode::ADVENTURE,
			default => throw new AssumptionFailedError("Unknown game mode"),
		};
	}

	public function protocolGameModeToCore(int $gameMode) : ?GameMode
	{
		return match ($gameMode) {
			ProtocolGameMode::SURVIVAL => GameMode::SURVIVAL(),
			ProtocolGameMode::CREATIVE => GameMode::CREATIVE(),
			ProtocolGameMode::ADVENTURE => GameMode::ADVENTURE(),
			ProtocolGameMode::CREATIVE_VIEWER, ProtocolGameMode::SURVIVAL_VIEWER => GameMode::SPECTATOR(),
			default => null,
		};
	}

	public function coreRecipeIngredientToNet(?RecipeIngredient $ingredient) : ProtocolRecipeIngredient
	{
		if ($ingredient === null) {
			return new ProtocolRecipeIngredient(null, 0);
		}
		if ($ingredient instanceof MetaWildcardRecipeIngredient) {
			$id = $this->itemTypeDictionary->fromStringId($ingredient->getItemId());
			$meta = self::RECIPE_INPUT_WILDCARD_META;
			$descriptor = new IntIdMetaItemDescriptor($id, $meta);
		} elseif ($ingredient instanceof ExactRecipeIngredient) {
			$item = $ingredient->getItem();
			[$id, $meta, $blockRuntimeId] = $this->itemTranslator->toNetworkId($item);
			if ($blockRuntimeId !== null) {
				$meta = $this->blockTranslator->getBlockStateDictionary()->getMetaFromStateId($blockRuntimeId);
				if ($meta === null) {
					throw new AssumptionFailedError("Every block state should have an associated meta value");
				}
			}
			$descriptor = new IntIdMetaItemDescriptor($id, $meta);
		} elseif ($ingredient instanceof TagWildcardRecipeIngredient) {
			$descriptor = new TagItemDescriptor($ingredient->getTagName());
		} else {
			throw new LogicException("Unsupported recipe ingredient type " . get_class($ingredient) . ", only " . ExactRecipeIngredient::class . " and " . MetaWildcardRecipeIngredient::class . " are supported");
		}

		return new ProtocolRecipeIngredient($descriptor, 1);
	}

	public function netRecipeIngredientToCore(ProtocolRecipeIngredient $ingredient) : ?RecipeIngredient
	{
		$descriptor = $ingredient->getDescriptor();
		if ($descriptor === null) {
			return null;
		}

		if ($descriptor instanceof TagItemDescriptor) {
			return new TagWildcardRecipeIngredient($descriptor->getTag());
		}

		if ($descriptor instanceof IntIdMetaItemDescriptor) {
			$stringId = $this->itemTypeDictionary->fromIntId($descriptor->getId());
			$meta = $descriptor->getMeta();
		} elseif ($descriptor instanceof StringIdMetaItemDescriptor) {
			$stringId = $descriptor->getId();
			$meta = $descriptor->getMeta();
		} else {
			throw new LogicException("Unsupported conversion of recipe ingredient to core item stack");
		}

		if ($meta === self::RECIPE_INPUT_WILDCARD_META) {
			return new MetaWildcardRecipeIngredient($stringId);
		}

		$blockRuntimeId = null;
		if (($blockId = $this->blockItemIdMap->lookupBlockId($stringId)) !== null) {
			$blockRuntimeId = $this->blockTranslator->getBlockStateDictionary()->lookupStateIdFromIdMeta($blockId, $meta);
			if ($blockRuntimeId !== null) {
				$meta = 0;
			}
		}
		$result = $this->itemTranslator->fromNetworkId(
			$this->itemTypeDictionary->fromStringId($stringId),
			$meta,
			$blockRuntimeId ?? ItemTranslator::NO_BLOCK_RUNTIME_ID
		);
		return new ExactRecipeIngredient($result);
	}

	public function coreItemStackToNet(Item $itemStack) : ItemStack
	{
		if ($itemStack->isNull()) {
			return ItemStack::null();
		}
		$nbt = $itemStack->getNamedTag();

		$nbt = $nbt->count() > 0 ? clone $nbt : null;

		$idMeta = $this->itemTranslator->toNetworkIdQuiet($itemStack);
		if ($idMeta !== null) {
			[$id, $meta] = $idMeta;
			$blockRuntimeId = 0;
		} else {
			//Display unmapped items as INFO_UPDATE, but stick something in their NBT to make sure they don't stack with
			//other unmapped items.
			[$id, $meta, $blockRuntimeId] = $this->itemTranslator->toNetworkId(VanillaBlocks::INFO_UPDATE()->asItem());
			$nbt = $nbt ?? CompoundTag::create();
			$nbt->setLong(self::PM_ID_TAG, $itemStack->getStateId());
		}

		$extraData = $id === $this->getShieldRuntimeId() ?
			new ItemStackExtraDataShield($nbt, canPlaceOn: [], canDestroy: [], blockingTick: 0) :
			new ItemStackExtraData($nbt, canPlaceOn: [], canDestroy: []);
		$extraDataSerializer = new ByteBufferWriter();
		$extraData->write($extraDataSerializer);

		return new ItemStack(
			$id,
			$meta,
			$itemStack->getCount(),
			$blockRuntimeId ?? ItemTranslator::NO_BLOCK_RUNTIME_ID,
			$extraDataSerializer->getData(),
		);
	}

	public function getShieldRuntimeId() : int
	{
		return ReflectionUtils::getProperty(TypeConverter::class, $this, "shieldRuntimeId");
	}

	/**
	 * @throws TypeConversionException
	 */
	public function netItemStackToCore(ItemStack $itemStack) : Item
	{
		if ($itemStack->getId() === 0) {
			return VanillaItems::AIR();
		}

		$itemResult = $this->itemTranslator->fromNetworkId($itemStack->getId(), $itemStack->getMeta(), $itemStack->getBlockRuntimeId());
		$itemResult->setCount($itemStack->getCount());
		return $itemResult;
	}
}
