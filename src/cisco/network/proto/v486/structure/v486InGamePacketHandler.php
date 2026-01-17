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

namespace cisco\network\proto\v486\structure;

use cisco\network\proto\v486\packets\types\v486ItemStackExecutor;
use cisco\network\utils\ReflectionUtils;
use pocketmine\inventory\transaction\InventoryTransaction;
use pocketmine\inventory\transaction\TransactionCancelledException;
use pocketmine\inventory\transaction\TransactionValidationException;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\handler\ItemStackRequestProcessException;
use pocketmine\network\mcpe\InventoryManager;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\ItemStackResponsePacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequest;
use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponse;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;
use pocketmine\utils\Utils;
use function array_push;
use function count;
use function fmod;
use function implode;
use function in_array;
use function is_infinite;
use function is_nan;

class v486InGamePacketHandler extends InGamePacketHandler
{

	protected ?Vector3 $_forceMoveSync = null;

	public function __construct(private Player $player, private NetworkSession $session, private InventoryManager $inventoryManager)
	{
		parent::__construct($player, $session, $inventoryManager);
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

	public function handleItemStackRequest(ItemStackRequestPacket $packet) : bool
	{
		#getEmoteLengthTicks
		$responses = [];
		if (count($packet->getRequests()) > 80) {
			//TODO: we can probably lower this limit, but this will do for now
			throw new PacketHandlingException("Too many requests in ItemStackRequestPacket");
		}
		foreach ($packet->getRequests() as $request) {
			$responses[] = $this->handleSingleItemStackRequest($request);
		}

		$this->session->sendDataPacket(ItemStackResponsePacket::create($responses));

		return true;
	}

	private function handleSingleItemStackRequest(ItemStackRequest $request) : ItemStackResponse
	{
		if (count($request->getActions()) > 60) {
			throw new PacketHandlingException("Too many actions in ItemStackRequest");
		}
		$executor = new v486ItemStackExecutor($this->player, $this->inventoryManager, $request);
		try {
			$transaction = $executor->generateInventoryTransaction();
			$result = $this->executeInventoryTransaction($transaction, $request->getRequestId());
		} catch (ItemStackRequestProcessException $e) {
			$result = false;
			$this->session->getLogger()->debug("ItemStackRequest #" . $request->getRequestId() . " failed: " . $e->getMessage());
			$this->session->getLogger()->debug(implode("\n", Utils::printableExceptionInfo($e)));
			$this->inventoryManager->requestSyncAll();
		}

		if (!$result) {
			return new ItemStackResponse(ItemStackResponse::RESULT_ERROR, $request->getRequestId());
		}
		return $executor->buildItemStackResponse();
	}

	private function executeInventoryTransaction(InventoryTransaction $transaction, int $requestId) : bool
	{
		$this->player->setUsingItem(false);

		$this->inventoryManager->setCurrentItemStackRequestId($requestId);
		$this->inventoryManager->addTransactionPredictedSlotChanges($transaction);
		try {
			$transaction->execute();
		} catch (TransactionValidationException $e) {
			$this->inventoryManager->requestSyncAll();
			$logger = $this->session->getLogger();
			$logger->debug("Invalid inventory transaction $requestId: " . $e->getMessage());

			return false;
		} catch (TransactionCancelledException) {
			$this->session->getLogger()->debug("Inventory transaction $requestId cancelled by a plugin");

			return false;
		} finally {
			$this->inventoryManager->syncMismatchedPredictedSlotChanges();
			$this->inventoryManager->setCurrentItemStackRequestId(null);
		}

		return true;
	}

	public function handlePlayerAction(PlayerActionPacket $packet) : bool
	{
		switch ($packet->action) {
			case PlayerAction::JUMP:
				$this->player->jump();
				break;
			case PlayerAction::START_SPRINT:
				$this->player->toggleSprint(true);
				break;
			case PlayerAction::STOP_SPRINT:
				$this->player->toggleSprint(false);
				break;
			case PlayerAction::START_SNEAK:
				$this->player->toggleSneak(true);
				break;
			case PlayerAction::STOP_SNEAK:
				$this->player->toggleSneak(false);
				break;
			case PlayerAction::START_SWIMMING:
				$this->player->toggleSwim(true);
				break;
			case PlayerAction::STOP_SWIMMING:
				$this->player->toggleSwim(false);
				break;
			case PlayerAction::START_GLIDE:
				$this->player->toggleGlide(true);
				break;
			case PlayerAction::STOP_GLIDE:
				$this->player->toggleGlide(false);
				break;
			default:
				return parent::handlePlayerAction($packet);
		}
		return true;
	}

	public function sendPosition(Vector3 $pos, float $yaw = null, float $pitch = null, int $mode = MovePlayerPacket::MODE_NORMAL, array $targets = null) : void
	{
		$player = $this->player;
		$yaw = $yaw ?? $player->getLocation()->yaw;
		$pitch = $pitch ?? $player->getLocation()->pitch;

		$pk = new MovePlayerPacket();
		$pk->actorRuntimeId = $player->getId();
		$pk->position = $player->getOffsetPosition($pos);
		$pk->pitch = $pitch;
		$pk->headYaw = $yaw;
		$pk->yaw = $yaw;
		$pk->mode = $mode;
		$pk->onGround = $player->onGround;

		if ($targets !== null) {
			if (in_array($player, $targets, true)) {
				$this->_forceMoveSync = $pos->asVector3();
				ReflectionUtils::setProperty(Player::class, $this, "ySize", 0);
			}
			$player->getNetworkSession()->getBroadcaster()->broadcastPackets($targets, [$pk]);
		} else {
			$this->_forceMoveSync = $pos->asVector3();
			ReflectionUtils::setProperty(Player::class, $this, "ySize", 0);
			$player->getNetworkSession()->sendDataPacket($pk);
		}
	}

	public function handleMovePlayer(MovePlayerPacket $packet) : bool
	{
		$rawPos = $packet->position;
		$player = $this->player;
		$logger = $player->getServer()->getLogger();
		foreach ([$rawPos->x, $rawPos->y, $rawPos->z, $packet->yaw, $packet->headYaw, $packet->pitch] as $float) {
			if (is_infinite($float) || is_nan($float)) {
				$logger->debug("Invalid movement from " . $player->getName() . ", contains NAN/INF components");
				return false;
			}
		}

		$newPos = $rawPos->subtract(0, 1.62, 0)->round(4);
		if ($this->_forceMoveSync !== null && $newPos->distanceSquared($this->_forceMoveSync) > 1) {  //Tolerate up to 1 block to avoid problems with client-sided physics when spawning in blocks
			$logger->debug("Got outdated pre-teleport movement from " . $player->getName() . ", received " . $newPos . ", expected " . $player->getPosition()->asVector3());
			//Still getting movements from before teleport, ignore them
		} elseif ((!$player->isAlive() || !$player->spawned) && $newPos->distanceSquared($player->getPosition()) > 0.01) {
			ReflectionUtils::invoke(Player::class, $player, "sendPosition", $player->getPosition(), null, null, MovePlayerPacket::MODE_RESET);
			$logger->debug("Reverted movement of " . $player->getName() . " due to not alive or not spawned, received " . $newPos . ", locked at " . $player->getPosition()->asVector3());
		} else {
			$this->_forceMoveSync = null;

			$packet->yaw = fmod($packet->yaw, 360);
			$packet->pitch = fmod($packet->pitch, 360);

			if ($packet->yaw < 0) {
				$packet->yaw += 360;
			}

			$player->setRotation($packet->yaw, $packet->pitch);
			$player->handleMovement($newPos);
		}

		return true;
	}

	/**
	 * Syncs blocks nearby to ensure that the client and server agree on the world's blocks after a block interaction.
	 */
	private function syncBlocksNearby(Vector3 $blockPos, ?int $face) : void
	{
		if ($blockPos->distanceSquared($this->player->getLocation()) < 10000) {
			$blocks = $blockPos->sidesArray();
			if ($face !== null) {
				$sidePos = $blockPos->getSide($face);

				/** @var Vector3[] $blocks */
				array_push($blocks, ...$sidePos->sidesArray()); //getAllSides() on each of these will include $blockPos and $sidePos because they are next to each other
			} else {
				$blocks[] = $blockPos;
			}
			foreach ($this->player->getWorld()->createBlockUpdatePackets($blocks) as $packet) {
				$this->session->sendDataPacket($packet);
			}
		}
	}

}
