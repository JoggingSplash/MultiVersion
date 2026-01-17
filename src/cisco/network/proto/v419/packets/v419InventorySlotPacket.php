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

namespace cisco\network\proto\v419\packets;

use cisco\network\proto\v419\packets\types\inventory\v419ItemStackWrapper;
use cisco\network\proto\v419\structure\v419ProtocolInfo;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;

class v419InventorySlotPacket extends InventorySlotPacket
{

	public const NETWORK_ID = v419ProtocolInfo::INVENTORY_SLOT_PACKET;

	public v419ItemStackWrapper $_item;

	public static function fromLatest(InventorySlotPacket $pk) : self
	{
		$npk = new self();
		$npk->windowId = $pk->windowId;
		$npk->inventorySlot = $pk->inventorySlot;
		$npk->_item = v419ItemStackWrapper::legacy($pk->item->getItemStack());
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->windowId = VarInt::readUnsignedInt($in);
		$this->inventorySlot = VarInt::readUnsignedInt($in);
		$this->_item = v419ItemStackWrapper::read($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, $this->windowId);
		VarInt::writeUnsignedInt($out, $this->inventorySlot);
		$this->_item->write($out);
	}
}
