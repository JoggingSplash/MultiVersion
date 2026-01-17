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

use cisco\network\mcpe\MVItemTranslator;
use cisco\network\proto\v419\craft\v419CraftingTransaction;
use cisco\network\proto\v419\packets\types\v419ItemStackExecutor;
use cisco\network\utils\ReflectionUtils;
use pocketmine\entity\Attribute;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\inventory\transaction\TransactionCancelledException;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\handler\ItemStackContainerIdTranslator;
use pocketmine\network\mcpe\handler\ItemStackRequestProcessException;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\ItemStackResponsePacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ContainerIds;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequest;
use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponse;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Utils;
use ReflectionException;
use function array_push;
use function count;
use function implode;
use function in_array;
use function microtime;

class v419InGamePacketHandler extends InGamePacketHandler
{

	public ?v419CraftingTransaction $craftingTransaction = null;

	public function __construct(private Player $player, private NetworkSession $session, private InventoryManager $inventoryManager) {
		parent::__construct($player, $session, $inventoryManager);
	}

	public function handleInventoryTransaction(InventoryTransactionPacket $packet) : bool
	{
		if (count($packet->trData->getActions()) > 50) {
			throw new PacketHandlingException("Too many actions in inventory transaction");
		}

		if (count($packet->requestChangedSlots) > 10) {
			throw new PacketHandlingException("Too many slot sync requests in inventory transaction");
		}

		$this->inventoryManager->setCurrentItemStackRequestId($packet->requestId);
		$this->inventoryManager->addRawPredictedSlotChanges($packet->trData->getActions());

		// TODO: crafting inventory transaction

		$result = true;
		try {
			if ($packet->trData instanceof NormalTransactionData) {
				$result = ReflectionUtils::invoke(InGamePacketHandler::class, $this, "handleNormalTransaction", $packet->trData, $packet->requestId);
			} elseif ($packet->trData instanceof MismatchTransactionData) {
				$this->session->getLogger()->debug("Mismatch transaction received");
				$this->inventoryManager->requestSyncAll();
			} elseif ($packet->trData instanceof UseItemTransactionData) {
				$result = $this->handleUseItemTransaction($packet->trData);
			} elseif ($packet->trData instanceof UseItemOnEntityTransactionData) {
				$result = ReflectionUtils::invoke(InGamePacketHandler::class, $this, "handleUseItemOnEntityTransaction", $packet->trData);
			} elseif ($packet->trData instanceof ReleaseItemTransactionData) {
				$result = ReflectionUtils::invoke(InGamePacketHandler::class, $this, "handleReleaseItemTransaction", $packet->trData);
			}
		} catch (ReflectionException $reflectionException) {
			$result = false;
		}

		$this->inventoryManager->syncMismatchedPredictedSlotChanges();

		//requestChangedSlots asks the server to always send out the contents of the specified slots, even if they
		//haven't changed. Handling these is necessary to ensure the client inventory stays in sync if the server
		//rejects the transaction. The most common example of this is equipping armor by right-click, which doesn't send
		//a legacy prediction action for the destination armor slot.
		foreach ($packet->requestChangedSlots as $containerInfo) {
			foreach ($containerInfo->getChangedSlotIndexes() as $netSlot) {
				[$windowId, $slot] = ItemStackContainerIdTranslator::translate($containerInfo->getContainerId(), $this->inventoryManager->getCurrentWindowId(), $netSlot);
				$inventoryAndSlot = $this->inventoryManager->locateWindowAndSlot($windowId, $slot);
				if ($inventoryAndSlot !== null) { //trigger the normal slot sync logic
					$this->inventoryManager->onSlotChange($inventoryAndSlot[0], $inventoryAndSlot[1]);
				}
			}
		}

		$this->inventoryManager->setCurrentItemStackRequestId(null);
		return $result;
	}

	private function handleUseItemTransaction(UseItemTransactionData $data) : bool
	{
		$this->player->selectHotbarSlot($data->getHotbarSlot());

		switch ($data->getActionType()) {
			case UseItemTransactionData::ACTION_CLICK_BLOCK:
				//TODO: start hack for client spam bug
				$clickPos = $data->getClickPosition();
				$spamBug = ($this->lastRightClickData !== null &&
					microtime(true) - $this->lastRightClickTime < 0.1 && //100ms
					$this->lastRightClickData->getPlayerPosition()->distanceSquared($data->getPlayerPosition()) < 0.00001 &&
					$this->lastRightClickData->getBlockPosition()->equals($data->getBlockPosition()) &&
					$this->lastRightClickData->getClickPosition()->distanceSquared($clickPos) < 0.00001 //signature spam bug has 0 distance, but allow some error
				);
				//get rid of continued spam if the player clicks and holds right-click
				$this->lastRightClickData = $data;
				$this->lastRightClickTime = microtime(true);
				if ($spamBug) {
					return true;
				}
				//TODO: end hack for client spam bug

				self::validateFacing($data->getFace());

				$blockPos = $data->getBlockPosition();
				$vBlockPos = new Vector3($blockPos->getX(), $blockPos->getY(), $blockPos->getZ());
				$this->player->interactBlock($vBlockPos, $data->getFace(), $clickPos);

				$syncAdjacentFace = null;
				if($data->getItemInHand()->getItemStack()->getBlockRuntimeId() === MVItemTranslator::NO_BLOCK_RUNTIME_ID){
					$syncAdjacentFace = $data->getFace();
				}

				$this->syncBlocksNearby($vBlockPos, $syncAdjacentFace);
				return true;
			case UseItemTransactionData::ACTION_BREAK_BLOCK:
				$blockPos = $data->getBlockPosition();
				$vBlockPos = new Vector3($blockPos->getX(), $blockPos->getY(), $blockPos->getZ());
				if (!$this->player->breakBlock($vBlockPos)) {
					$this->syncBlocksNearby($vBlockPos, null);
				}
				return true;
			case UseItemTransactionData::ACTION_CLICK_AIR:
				if ($this->player->isUsingItem()) {
					if (!$this->player->consumeHeldItem()) {
						$hungerAttr = $this->player->getAttributeMap()->get(Attribute::HUNGER) ?? throw new AssumptionFailedError();
						$hungerAttr->markSynchronized(false);
					}
					$this->player->useHeldItem();
					return true;
				}
				$this->player->useHeldItem();
				return true;
		}

		return false;
	}

	/**
	 * @throws PacketHandlingException
	 */
	private static function validateFacing(int $facing) : void
	{
		if (!in_array($facing, Facing::ALL, true)) {
			throw new PacketHandlingException("Invalid facing value $facing");
		}
	}

	/**
	 * Syncs blocks nearby to ensure that the client and server agree on the world's blocks after a block interaction.
	 */
	private function syncBlocksNearby(Vector3 $blockPos, ?int $face) : void
	{
		if ($blockPos->distanceSquared($this->player->getLocation()) < 10000) {
			$blocks = $blockPos->sidesArray();
			if ($face === null) {
				$blocks[] = $blockPos;
			} else {
				$sidePos = $blockPos->getSide($face);

				array_push($blocks, ...$sidePos->sidesArray()); //getAllSides() on each of these will include $blockPos and $sidePos because they are next to each other
			}

			foreach ($this->player->getWorld()->createBlockUpdatePackets($blocks) as $packet) {
				$this->session->sendDataPacket($packet);
			}
		}
	}

	public function handlePlayerAction(PlayerActionPacket $packet) : bool {
		switch ($packet->action) {
			case PlayerAction::JUMP:
				$this->getPlayer()->jump();
				break;
			case PlayerAction::START_SPRINT:
				$this->getPlayer()->toggleSprint(true);
				break;
			case PlayerAction::STOP_SPRINT:
				$this->getPlayer()->toggleSprint(false);
				break;
			case PlayerAction::START_SNEAK:
				$this->getPlayer()->toggleSneak(true);
				break;
			case PlayerAction::STOP_SNEAK:
				$this->getPlayer()->toggleSneak(false);
				break;
			case PlayerAction::START_SWIMMING:
				$this->getPlayer()->toggleSwim(true);
				break;
			case PlayerAction::STOP_SWIMMING:
				$this->getPlayer()->toggleSwim(false);
				break;
			case PlayerAction::START_GLIDE:
				$this->getPlayer()->toggleGlide(true);
				break;
			case PlayerAction::STOP_GLIDE:
				$this->getPlayer()->toggleGlide(false);
				break;
			default:
				return parent::handlePlayerAction($packet);
		}
		return true;
	}

	private function getPlayer() : Player
	{
		return $this->player;
	}

	public function handleNetworkChunkPublisherUpdate(NetworkChunkPublisherUpdatePacket $packet) : bool{
		return true;
	}

	public function handleItemStackRequest(ItemStackRequestPacket $packet) : bool{
		$responses = [];
		if (count($packet->getRequests()) > 80) {
			throw new PacketHandlingException("Too many requests in ItemStackRequestPacket");
		}
		foreach ($packet->getRequests() as $request) {
			$responses[] = $this->handleSingleItemStackRequest(new ItemStackRequest(
				$request->getRequestId(),
				$request->getActions(),
				$request->getFilterStrings(),
				$request->getFilterStringCause()
			));
		}

		$this->session->sendDataPacket(ItemStackResponsePacket::create($responses));

		return true;
	}

	private function handleSingleItemStackRequest(ItemStackRequest $request) : ItemStackResponse
	{
		if (count($request->getActions()) > 60) {
			throw new PacketHandlingException("Too many actions in ItemStackRequest");
		}

		$executor = new v419ItemStackExecutor($this->getPlayer(), $this->inventoryManager, $request);

		try {
			$transaction = $executor->generateInventoryTransaction();
			$result = $this->executeInventoryTransaction($transaction, $request->getRequestId());
		} catch (ItemStackRequestProcessException $e) {
			$result = false;
			$this->getSession()->getLogger()->debug("ItemStackRequest #" . $request->getRequestId() . " failed: " . $e->getMessage());
			$this->getSession()->getLogger()->debug(implode("\n", Utils::printableExceptionInfo($e)));
			$this->inventoryManager->requestSyncAll();
		}

		return $result ? $executor->buildItemStackResponse() : new ItemStackResponse(ItemStackResponse::RESULT_ERROR, $request->getRequestId());
	}

	private function executeInventoryTransaction(InventoryTransaction $transaction, int $requestId) : bool
	{
		$this->getPlayer()->setUsingItem(false);
		$this->inventoryManager->setCurrentItemStackRequestId($requestId);
		$this->inventoryManager->addTransactionPredictedSlotChanges($transaction);
		try {
			$transaction->execute();
		} catch (TransactionValidationException $e) {
			$this->inventoryManager->requestSyncAll();
			$logger = $this->getSession()->getLogger();
			$logger->debug("Invalid inventory transaction $requestId: " . $e->getMessage());

			return false;
		} catch (TransactionCancelledException) {
			$this->getSession()->getLogger()->debug("Inventory transaction $requestId cancelled by a plugin");

			return false;
		} finally {
			$this->inventoryManager->syncMismatchedPredictedSlotChanges();
			$this->inventoryManager->setCurrentItemStackRequestId(null);
		}

		return true;
	}

	private function getSession() : NetworkSession
	{
		return $this->session;
	}

	public function handleEmote(EmotePacket $packet) : bool
	{
		$this->getPlayer()->emote($packet->getEmoteId());
		return true;
	}

	public function handleMovePlayer(MovePlayerPacket $packet) : bool {
		$player = $this->player;
		if ($player->isConnected() && $player->isAlive() && $player->isSurvival()) {
			$realPosition = $packet->position->subtract(0, 1.62, 0)->round(3);

			if ($realPosition->getFloorY() <= 0.0001) {
				$event = new EntityDamageEvent($player, EntityDamageEvent::CAUSE_VOID, 200); // TODO: this shouldnt be insta-kill but since the min y in 1.16 is y=0 we insta-kill him
				$player->attack($event);
				return $event->isCancelled();
			}
		}

		return true;
	}

	public function handleMobEquipment(MobEquipmentPacket $packet) : bool {
		if ($packet->windowId === ContainerIds::INVENTORY) {
			$this->inventoryManager->onClientSelectHotbarSlot($packet->hotbarSlot);
			if (!$this->player->selectHotbarSlot($packet->hotbarSlot)) {
				$this->inventoryManager->syncSelectedHotbarSlot();
			}
			return true;
		}

		return $packet->windowId === ContainerIds::OFFHAND; //this happens when we put an item into the offhand
	}
}
