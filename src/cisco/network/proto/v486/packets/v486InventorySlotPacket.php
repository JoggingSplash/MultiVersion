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

namespace cisco\network\proto\v486\packets;

use cisco\network\proto\v486\structure\v486CommonTypes;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;

class v486InventorySlotPacket extends InventorySlotPacket
{

	/**
	 * @generate-create-func
	 */
	public static function fromLatest(InventorySlotPacket $packet) : self
	{
		$result = new self();
		$result->windowId = $packet->windowId;
		$result->inventorySlot = $packet->inventorySlot;
		$result->item = $packet->item;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->windowId = VarInt::readUnsignedInt($in);
		$this->inventorySlot = VarInt::readUnsignedInt($in);
		$this->item = v486CommonTypes::getItemStackWrapper($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, $this->windowId);
		VarInt::writeUnsignedInt($out, $this->inventorySlot);
		v486CommonTypes::putItemStackWrapper($out, $this->item);
	}

}
