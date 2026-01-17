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

use cisco\network\proto\v419\v419TypeConverter;
use pocketmine\crafting\CraftingManager;
use pocketmine\crafting\CraftingRecipe;
use pocketmine\crafting\ExactRecipeIngredient;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\item\Item;
use pocketmine\player\Player;
use function array_map;
use function array_pop;
use function count;
use function intdiv;

class v419CraftingTransaction extends InventoryTransaction {

	protected ?CraftingRecipe $recipe = null;
	protected ?int $repetitions = null;

	/** @var Item[] */
	protected array $inputs = [];
	/** @var Item[] */
	protected array $outputs = [];

	protected CraftingManager $craftingManager;

	 public function __construct(Player $source, array $actions = []){
		 parent::__construct($source, $actions);
		 $this->craftingManager = $source->getNetworkSession()->getProtocol()->getCraftingManager();
	 }

	public function validate() : void {
		$this->squashDuplicateSlotChanges();
		if (count($this->actions) < 1) {
			throw new TransactionValidationException("Transaction must have at least one action to be executable");
		}

		$this->matchItems($this->outputs, $this->inputs);

		$failed = 0;
		foreach ($this->craftingManager->matchRecipeByOutputs($this->outputs) as $recipe) {
			try {
				//compute number of times recipe was crafted
				$this->repetitions = $this->matchRecipeItems($this->outputs, $recipe->getResultsFor($this->source->getCraftingGrid()), false);
				//assert that $repetitions x recipe ingredients should be consumed

				$this->matchRecipeItems($this->inputs, array_map(fn(ExactRecipeIngredient $recipe) => $recipe->getItem(), $recipe->getIngredientList()), true, $this->repetitions);

				//Success!
				$this->recipe = $recipe;
				break;
			} catch (TransactionValidationException $e) {
				//failed
				++$failed;
			}
		}

		if ($this->recipe === null) {
			throw new TransactionValidationException("Unable to match a recipe to transaction (tried to match against $failed recipes)");
		}
	}

	/**
	 * @param Item[] $txItems
	 * @param Item[] $recipeItems
	 *
	 * @throws TransactionValidationException
	 */
	protected function matchRecipeItems(array $txItems, array $recipeItems, bool $wildcards, int $iterations = 0) : int {
		if (count($recipeItems) === 0) {
			throw new TransactionValidationException("No recipe items given");
		}
		if (count($txItems) === 0) {
			throw new TransactionValidationException("No transaction items given");
		}

		while (count($recipeItems) > 0) {
			/** @var Item $recipeItem */
			$recipeItem = array_pop($recipeItems);
			$needCount = $recipeItem->getCount();
			foreach ($recipeItems as $i => $otherRecipeItem) {
				if ($otherRecipeItem->equals($recipeItem)) { //make sure they have the same wildcards set
					$needCount += $otherRecipeItem->getCount();
					unset($recipeItems[$i]);
				}
			}

			$converter = v419TypeConverter::getInstance()->getConverter();
			[, $meta, $block] = $converter->getMVItemTranslator()->toNetworkId($recipeItem);

			if ($block !== null) {
				$meta = $converter->getMVBlockTranslator()->getBlockStateDictionary()->getMetaFromStateId($block);
			}

			$haveCount = 0;
			foreach ($txItems as $j => $txItem) {

				if ($txItem->equals($recipeItem, !$wildcards || ($meta !== -1), !$wildcards || $recipeItem->getNamedTag()->count() > 0)) {
					$haveCount += $txItem->getCount();
					unset($txItems[$j]);
				}
			}

			if ($haveCount % $needCount !== 0) {
				//wrong count for this output, should divide exactly
				throw new TransactionValidationException("Expected an exact multiple of required $recipeItem (given: $haveCount, needed: $needCount)");
			}

			$multiplier = intdiv($haveCount, $needCount);
			if ($multiplier < 1) {
				throw new TransactionValidationException("Expected more than zero items matching $recipeItem (given: $haveCount, needed: $needCount)");
			}
			if ($iterations === 0) {
				$iterations = $multiplier;
			} elseif ($multiplier !== $iterations) {
				//wrong count for this output, should match previous outputs
				throw new TransactionValidationException("Expected $recipeItem x$iterations, but found x$multiplier");
			}
		}

		if (count($txItems) > 0) {
			//all items should be destroyed in this process
			throw new TransactionValidationException("Expected 0 ingredients left over, have " . count($txItems));
		}

		return $iterations;
	}

}
