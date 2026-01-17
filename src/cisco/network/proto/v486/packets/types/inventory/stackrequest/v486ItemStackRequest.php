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

namespace cisco\network\proto\v486\packets\types\inventory\stackrequest;

use cisco\network\proto\v486\packets\types\inventory\v486ContainerUIIds;
use cisco\network\utils\ReflectionUtils;
use InvalidArgumentException;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\BeaconPaymentStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftingConsumeInputStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftingCreateSpecificResultStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftRecipeAutoStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftRecipeOptionalStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CraftRecipeStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\CreativeCreateStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DeprecatedCraftingNonImplementedStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DeprecatedCraftingResultsStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DestroyStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\DropStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\GrindstoneStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\LabTableCombineStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\LoomStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\MineBlockStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\PlaceStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\SwapStackRequestAction;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\TakeStackRequestAction;
use pocketmine\utils\BinaryDataException;
use ReflectionException;
use function count;
use function get_class;

final class v486ItemStackRequest
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

	/**
	 * @throws BinaryDataException
	 * @throws PacketDecodeException
	 * @throws ReflectionException
	 */
	private static function readAction(ByteBufferReader $in, int $typeId) : ItemStackRequestAction
	{
		$action = match ($typeId) {
			TakeStackRequestAction::ID => v486TakeStackRequestAction::read($in),
			PlaceStackRequestAction::ID => v486PlaceStackRequestAction::read($in),
			SwapStackRequestAction::ID => v486SwapStackRequestAction::read($in),
			DropStackRequestAction::ID => v486DropStackRequestAction::read($in),
			DestroyStackRequestAction::ID => DestroyStackRequestAction::read($in),
			CraftingConsumeInputStackRequestAction::ID => CraftingConsumeInputStackRequestAction::read($in),
			CraftingCreateSpecificResultStackRequestAction::ID => CraftingCreateSpecificResultStackRequestAction::read($in),
			LabTableCombineStackRequestAction::ID => LabTableCombineStackRequestAction::read($in),
			BeaconPaymentStackRequestAction::ID => BeaconPaymentStackRequestAction::read($in),
			MineBlockStackRequestAction::ID => MineBlockStackRequestAction::read($in),
			CraftRecipeStackRequestAction::ID => CraftRecipeStackRequestAction::read($in),
			CraftRecipeAutoStackRequestAction::ID => v486CraftRecipeAutoStackRequestAction::read($in),
			CreativeCreateStackRequestAction::ID => CreativeCreateStackRequestAction::read($in),
			CraftRecipeOptionalStackRequestAction::ID => CraftRecipeOptionalStackRequestAction::read($in),
			GrindstoneStackRequestAction::ID => GrindstoneStackRequestAction::read($in),
			LoomStackRequestAction::ID => LoomStackRequestAction::read($in),
			DeprecatedCraftingNonImplementedStackRequestAction::ID => DeprecatedCraftingNonImplementedStackRequestAction::read($in),
			DeprecatedCraftingResultsStackRequestAction::ID => DeprecatedCraftingResultsStackRequestAction::read($in),
			default => throw new PacketDecodeException("Unhandled item stack request action type $typeId"),
		};
		if ($action instanceof v486SwapStackRequestAction) {
			if (($containerId = ($slot1 = $action->getSlot1())->getContainerId()) >= v486ContainerUIIds::RECIPE_BOOK) {
				$containerId++;
				ReflectionUtils::setProperty(get_class($action), $action, "slot1", new v486ItemStackRequestSlotInfo($containerId, $slot1->getSlotId(), $slot1->getStackId()));
			}
			if (($containerId = ($slot2 = $action->getSlot2())->getContainerId()) >= v486ContainerUIIds::RECIPE_BOOK) {
				$containerId++;
				ReflectionUtils::setProperty(get_class($action), $action, "slot2", new v486ItemStackRequestSlotInfo($containerId, $slot2->getSlotId(), $slot2->getStackId()));
			}
		} elseif ($action instanceof CraftingConsumeInputStackRequestAction |
			$action instanceof DestroyStackRequestAction |
			$action instanceof v486DropStackRequestAction
		) {
			if ($action instanceof v486DropStackRequestAction) {
				if (($containerId = ($source = $action->getSource())->getContainerId()) >= v486ContainerUIIds::RECIPE_BOOK) {
					$containerId++;
					ReflectionUtils::setProperty(get_class($action), $action, "source", new v486ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId()));
				}
			} else {
				if (($containerId = ($source = $action->getSource())->getContainerName()->getContainerId()) >= v486ContainerUIIds::RECIPE_BOOK) {
					$containerId++;
					ReflectionUtils::setProperty(get_class($action), $action, "source", new v486ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId()));
				}
			}
			# Unexpected container UI
		} elseif (
			$action instanceof v486PlaceStackRequestAction |
			$action instanceof v486TakeStackRequestAction
		) {
			if (($containerId = ($source = $action->getSource())->getContainerId()) >= v486ContainerUIIds::RECIPE_BOOK) {
				$containerId++;
				ReflectionUtils::setProperty(get_class($action), $action, "source", new v486ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId()));
			}
			if (($containerId = ($destination = $action->getDestination())->getContainerId()) >= v486ContainerUIIds::RECIPE_BOOK) {
				$containerId++;
				ReflectionUtils::setProperty(get_class($action), $action, "destination", new v486ItemStackRequestSlotInfo($containerId, $destination->getSlotId(), $destination->getStackId()));
			}
		} elseif ($action instanceof v486CraftRecipeAutoStackRequestAction) {
			$action = new CraftRecipeAutoStackRequestAction($action->getRecipeId(), $action->getRepetitions(), $action->getRepetitions(), $action->getIngredients());
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
		for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
			$filterStrings[] = CommonTypes::getString($in);
		}
		return new self($requestId, $actions, $filterStrings, $filterStringCause ?? 0);
	}

	/**
	 * @throws ReflectionException
	 */
	private static function writeAction(ByteBufferWriter $out, ItemStackRequestAction $action) : void
	{
		if ($action instanceof SwapStackRequestAction) {
			if (($containerId = ($slot1 = $action->getSlot1())->getContainerName()->getContainerId()) > v486ContainerUIIds::RECIPE_BOOK) {
				$containerId--;
				ReflectionUtils::setProperty(get_class($action), $action, "slot1", new v486ItemStackRequestSlotInfo($containerId, $slot1->getSlotId(), $slot1->getStackId()));
			} elseif ($containerId === v486ContainerUIIds::RECIPE_BOOK) {
				throw new InvalidArgumentException("Invalid container ID for protocol version 486");
			}
			if (($containerId = ($slot2 = $action->getSlot2())->getContainerName()->getContainerId()) > v486ContainerUIIds::RECIPE_BOOK) {
				$containerId--;
				ReflectionUtils::setProperty(get_class($action), $action, "slot2", new v486ItemStackRequestSlotInfo($containerId, $slot2->getSlotId(), $slot2->getStackId()));
			} elseif ($containerId === v486ContainerUIIds::RECIPE_BOOK) {
				throw new InvalidArgumentException("Invalid container ID for protocol version 486");
			}
		} elseif ($action instanceof CraftingConsumeInputStackRequestAction |
			$action instanceof DestroyStackRequestAction |
			$action instanceof DropStackRequestAction
		) {
			if (($containerId = ($source = $action->getSource())->getContainerName()->getContainerId()) > v486ContainerUIIds::RECIPE_BOOK) {
				$containerId--;
				ReflectionUtils::setProperty(get_class($action), $action, "source", new v486ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId()));
			} elseif ($containerId === v486ContainerUIIds::RECIPE_BOOK) {
				throw new InvalidArgumentException("Invalid container ID for protocol version 486");
			}
		} elseif (
			$action instanceof v486PlaceStackRequestAction | $action instanceof v486TakeStackRequestAction
		) {
			if (($containerId = ($source = $action->getSource())->getContainerId()) > v486ContainerUIIds::RECIPE_BOOK) {
				$containerId--;
				ReflectionUtils::setProperty(get_class($action), $action, "source", new v486ItemStackRequestSlotInfo($containerId, $source->getSlotId(), $source->getStackId()));
			} elseif ($containerId === v486ContainerUIIds::RECIPE_BOOK) {
				throw new InvalidArgumentException("Invalid container ID for protocol version 486");
			}
			if (($containerId = ($destination = $action->getDestination())->getContainerId()) > v486ContainerUIIds::RECIPE_BOOK) {
				$containerId--;
				ReflectionUtils::setProperty(get_class($action), $action, "destination", new v486ItemStackRequestSlotInfo($containerId, $destination->getSlotId(), $destination->getStackId()));
			} elseif ($containerId === v486ContainerUIIds::RECIPE_BOOK) {
				throw new InvalidArgumentException("Invalid container ID for protocol version 486");
			}
		} elseif ($action instanceof v486CraftRecipeAutoStackRequestAction) {
			$action = new CraftRecipeAutoStackRequestAction($action->getRecipeId(), $action->getRepetitions(), $action->getRepetitions(), $action->getIngredients());
		}
		$action->write($out);
	}

	public function write(ByteBufferWriter $out) : void
	{
		VarInt::writeSignedInt($out, $this->requestId);
		VarInt::writeUnsignedInt($out, count($this->actions));
		foreach ($this->actions as $action) {
			Byte::writeUnsigned($out, $action->getTypeId());
			self::writeAction($out, $action);
		}
		VarInt::writeUnsignedInt($out, count($this->filterStrings));
		foreach ($this->filterStrings as $string) {
			CommonTypes::putString($out, $string);
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
