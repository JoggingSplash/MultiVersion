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

namespace cisco\network\proto\v419\packets\types\inventory\stackrequest;

use cisco\network\proto\v419\packets\types\inventory\v419ContainerUIIds;
use cisco\network\utils\ReflectionUtils;
use InvalidArgumentException;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\BeaconPaymentStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftRecipeAutoStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DestroyStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DropStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\SwapStackRequestAction;
use ReflectionException;
use function count;
use function get_class;

final class v419ItemStackRequest
{

	/**
	 * @param ItemStackRequestAction[] $actions
	 * @param string[]                 $filterStrings
	 *
	 * @phpstan-param list<string> $filterStrings
	 */
	public function __construct(
		private int   $requestId,
		private array $actions,
		private array $filterStrings,
		private int   $filterStringCause
	)
	{
	}

	private static function readAction(ByteBufferReader $in, int $typeId) : ItemStackRequestAction
	{
		$action = match ($typeId) {
			v419ItemStackRequestActionType::TAKE => v419TakeStackRequestAction::read($in),
			v419ItemStackRequestActionType::PLACE => v419PlaceStackRequestAction::read($in),
			v419ItemStackRequestActionType::SWAP => v419SwapStackRequestAction::read($in),
			v419ItemStackRequestActionType::DROP => v419DropStackRequestAction::read($in),
			v419ItemStackRequestActionType::DESTROY => v419DestroyStackRequestAction::read($in),
			v419ItemStackRequestActionType::CRAFTING_CONSUME_INPUT => v419CraftingConsumeInputStackRequestAction::read($in),
			v419ItemStackRequestActionType::CRAFTING_MARK_SECONDARY_RESULT_SLOT => v419CraftingMarkSecondaryResultSlot::read($in),
			v419ItemStackRequestActionType::LAB_TABLE_COMBINE => v419LabTableCombineStackRequestAction::read($in),
			v419ItemStackRequestActionType::BEACON_PAYMENT => BeaconPaymentStackRequestAction::read($in),
			v419ItemStackRequestActionType::CRAFTING_RECIPE_AUTO => v419CraftRecipeAutoStackRequestAction::read($in),
			v419ItemStackRequestActionType::CRAFTING_RECIPE => v419CraftRecipeStackRequestAction::read($in),
			v419ItemStackRequestActionType::CREATIVE_CREATE => v419CreativeCreateRequestAction::read($in),
			v419ItemStackRequestActionType::CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING => v419DeprecatedCraftingNonImplementedStackRequestAction::read($in),
			v419ItemStackRequestActionType::CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING => v419DeprecatedCraftingResultsStackRequestAction::read($in),
			default => new v419NullStackRequestAction(),
		};

		if ($action instanceof v419SwapStackRequestAction) {
			if (($containerId = ($slot1 = $action->getSlot1())->getContainerId()) >= v419ContainerUIIds::RECIPE_BOOK) {
				$containerId++;
				ReflectionUtils::setProperty(get_class($action), $action, "slot1", new v419ItemStackRequestSlotInfo($containerId, $slot1->getSlotId(), $slot1->getStackId()));
			}
			if (($containerId = ($slot2 = $action->getSlot2())->getContainerId()) >= v419ContainerUIIds::RECIPE_BOOK) {
				$containerId++;
				ReflectionUtils::setProperty(get_class($action), $action, "slot2", new v419ItemStackRequestSlotInfo($containerId, $slot2->getSlotId(), $slot2->getStackId()));
			}
		} elseif ($action instanceof v419CraftingConsumeInputStackRequestAction ||
			$action instanceof DestroyStackRequestAction ||
			$action instanceof v419DropStackRequestAction
		) {
			if ($action instanceof v419DropStackRequestAction) {
				if (($containerId = ($source = $action->getSource())->getContainerId()) >= v419ContainerUIIds::RECIPE_BOOK) {
					$containerId++;
					ReflectionUtils::setProperty(get_class($action), $action, "source", new v419ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId()));
				}
			} else {
				if (($containerId = ($source = $action->getSource())->getContainerId()) >= v419ContainerUIIds::RECIPE_BOOK) {
					$containerId++;
					$new_source = new v419ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId());
					ReflectionUtils::setProperty(get_class($action), $action, "source", $new_source);
				}
			}
		} elseif (
			$action instanceof v419PlaceStackRequestAction ||
			$action instanceof v419TakeStackRequestAction
		) {
			if (($containerId = ($source = $action->getSource())->getContainerId()) >= v419ContainerUIIds::RECIPE_BOOK) {
				$containerId++;
				ReflectionUtils::setProperty(get_class($action), $action, "source", new v419ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId()));
			}
			if (($containerId = ($destination = $action->getDestination())->getContainerId()) >= v419ContainerUIIds::RECIPE_BOOK) {
				$containerId++;
				ReflectionUtils::setProperty(get_class($action), $action, "destination", new v419ItemStackRequestSlotInfo($containerId, $destination->getSlotId(), $destination->getStackId()));
			}
		}
		return $action;

	}

	public static function read(ByteBufferReader $in) : self
	{
		$requestId = VarInt::readSignedInt($in);
		$actions = [];
		for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
			$typeId = Byte::readUnsigned($in);
			$actions[] = self::readAction($in, $typeId);
		}
		$filterStrings = [];
		$filterStringCause = 0;
		return new self($requestId, $actions, $filterStrings, $filterStringCause);
	}

	/**
	 * @throws ReflectionException
	 */
	private static function writeAction(ByteBufferWriter $out, ItemStackRequestAction $action) : void
	{
		if ($action instanceof SwapStackRequestAction) {
			if (($containerId = ($slot1 = $action->getSlot1())->getContainerName()->getContainerId()) > v419ContainerUIIds::RECIPE_BOOK) {
				$containerId--;
				ReflectionUtils::setProperty(get_class($action), $action, "slot1", new v419ItemStackRequestSlotInfo($containerId, $slot1->getSlotId(), $slot1->getStackId()));
			} elseif ($containerId === v419ContainerUIIds::RECIPE_BOOK) {
				throw new InvalidArgumentException("Invalid container ID for protocol version 419");
			}
			if (($containerId = ($slot2 = $action->getSlot2())->getContainerName()->getContainerId()) > v419ContainerUIIds::RECIPE_BOOK) {
				$containerId--;
				ReflectionUtils::setProperty(get_class($action), $action, "slot2", new v419ItemStackRequestSlotInfo($containerId, $slot2->getSlotId(), $slot2->getStackId()));
			} elseif ($containerId === v419ContainerUIIds::RECIPE_BOOK) {
				throw new InvalidArgumentException("Invalid container ID for protocol version 419");
			}
		} elseif ($action instanceof v419CraftingConsumeInputStackRequestAction ||
			$action instanceof DestroyStackRequestAction ||
			$action instanceof DropStackRequestAction
		) {
			if (($containerId = ($source = $action->getSource())->getContainerName()->getContainerId()) > v419ContainerUIIds::RECIPE_BOOK) {
				$containerId--;
				ReflectionUtils::setProperty(get_class($action), $action, "source", new v419ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId()));
			} elseif ($containerId === v419ContainerUIIds::RECIPE_BOOK) {
				throw new InvalidArgumentException("Invalid container ID for protocol version 419");
			}
		} elseif ($action instanceof v419PlaceStackRequestAction ||
			$action instanceof v419TakeStackRequestAction
		) {
			if (($containerId = ($source = $action->getSource())->getContainerId()) > v419ContainerUIIds::RECIPE_BOOK) {
				$containerId--;
				ReflectionUtils::setProperty(get_class($action), $action, "source", new v419ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId()));
			} elseif ($containerId === v419ContainerUIIds::RECIPE_BOOK) {
				throw new InvalidArgumentException("Invalid container ID for protocol version 419");
			}
			if (($containerId = ($destination = $action->getDestination())->getContainerId()) > v419ContainerUIIds::RECIPE_BOOK) {
				$containerId--;
				ReflectionUtils::setProperty(get_class($action), $action, "destination", new v419ItemStackRequestSlotInfo($containerId, $destination->getSlotId(), $destination->getStackId()));
			} elseif ($containerId === v419ContainerUIIds::RECIPE_BOOK) {
				throw new InvalidArgumentException("Invalid container ID for protocol version 419");
			}
		} elseif ($action instanceof v419CraftRecipeAutoStackRequestAction) {
			$action = new CraftRecipeAutoStackRequestAction($action->getRecipeId(), $action->getRepetitions(), $action->getRepetitions(), $action->getIngredients());
		}
		$action->write($out);
	}

	/**
	 * @throws ReflectionException
	 */
	public function write(ByteBufferWriter $out) : void
	{
		VarInt::writeSignedInt($out, $this->requestId);
		VarInt::writeUnsignedInt($out, count($this->actions));
		foreach ($this->actions as $action) {
			Byte::writeUnsigned($out, $action->getTypeId());
			self::writeAction($out, $action);
		}
	}

	public function getRequestId() : int
	{
		return $this->requestId;
	}

	/** @return ItemStackRequestAction[] */
	public function getActions() : array
	{
		return $this->actions;
	}

	/**
	 * @return string[]
	 * @phpstan-return list<string>
	 */
	public function getFilterStrings() : array
	{
		return $this->filterStrings;
	}

	public function getFilterStringCause() : int
	{
		return $this->filterStringCause;
	}
}
