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

namespace cisco\network\proto\v419\packets\types;

use cisco\network\proto\v419\structure\v419CommonTypes;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;

class v419UseItemTransactionData extends UseItemTransactionData
{
	use GetTypeIdFromConstTrait;

	public const ID = InventoryTransactionPacket::TYPE_USE_ITEM;

	public const ACTION_CLICK_BLOCK = 0;
	public const ACTION_CLICK_AIR = 1;
	public const ACTION_BREAK_BLOCK = 2;

	private int $actionType;
	private BlockPosition $blockPosition;
	private int $face;
	private int $hotbarSlot;
	private ItemStackWrapper $itemInHand;
	private Vector3 $playerPosition;
	private Vector3 $clickPosition;
	private int $blockRuntimeId;

	public function getActionType() : int
	{
		return $this->actionType;
	}

	public function getFace() : int
	{
		return $this->face;
	}

	public function getHotbarSlot() : int
	{
		return $this->hotbarSlot;
	}

	public function getItemInHand() : ItemStackWrapper
	{
		return $this->itemInHand;
	}

	public function getPlayerPosition() : Vector3
	{
		return $this->playerPosition;
	}

	public function getClickPosition() : Vector3
	{
		return $this->clickPosition;
	}

	public function getBlockRuntimeId() : int
	{
		return $this->blockRuntimeId;
	}

	protected function decodeData(ByteBufferReader $in) : void
	{
		$this->actionType = VarInt::readUnsignedInt($in);
		$this->blockPosition = CommonTypes::getBlockPosition($in);
		$this->face = VarInt::readSignedInt($in);
		$this->hotbarSlot = VarInt::readSignedInt($in);
		$this->itemInHand = v419CommonTypes::getItemStackWrapper($in);
		$this->playerPosition = CommonTypes::getVector3($in);
		$this->clickPosition = CommonTypes::getVector3($in);
		$this->blockRuntimeId = VarInt::readUnsignedInt($in);
	}

	public function getBlockPosition() : BlockPosition
	{
		return $this->blockPosition;
	}

	protected function encodeData(ByteBufferWriter $out) : void
	{
		# Invalid raw value
		VarInt::writeUnsignedInt($out, $this->actionType);
		CommonTypes::putBlockPosition($out, $this->blockPosition);
		VarInt::writeSignedInt($out, $this->face);
		VarInt::writeSignedInt($out, $this->hotbarSlot);
		v419CommonTypes::putItemStackWrapper($out, $this->itemInHand);
		CommonTypes::putVector3($out, $this->playerPosition);
		CommonTypes::putVector3($out, $this->clickPosition);
		VarInt::writeUnsignedInt($out, $this->blockRuntimeId);
	}
}
