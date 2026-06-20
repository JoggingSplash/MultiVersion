<?php

/*
 *
 *
 *      __  ___      ____  _ _    __               _
 *     /  |/  /_  __/ / /_(_) |  / /__  __________(_)___  ____
 *    / /|_/ / / / / / __/ /| | / / _ \/ ___/ ___/ / __ \/ __ \
 *   / /  / / /_/ / / /_/ / | |/ /  __/ /  (__  ) / /_/ / / / /
 *  /_/  /_/\__,_/_/\__/_/  |___/\___/_/  /____/_/\____/_/ /_/
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  @author JoggingSplash23
 *  @link https://www.github.com/JoggingSplash
 *
 *
 */

declare(strict_types=1);

namespace cisco\network\proto\v844\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\FullContainerName;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

class v844InventorySlotPacket extends InventorySlotPacket {

	static public function fromLatest(InventorySlotPacket $packet) : self {
		$result = new self();
		$result->windowId = $packet->windowId;
		$result->inventorySlot = $packet->inventorySlot;
		$result->containerName = $packet->containerName ?? new FullContainerName(0, null);
		$result->storage = $packet->storage ?? ItemStackWrapper::legacy(ItemStack::null());
		$result->item = $packet->item;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->windowId = VarInt::readUnsignedInt($in);
		$this->inventorySlot = VarInt::readUnsignedInt($in);
		$this->containerName = FullContainerName::read($in);
		$this->storage = CommonTypes::getItemStackWrapper($in);
		$this->item = CommonTypes::getItemStackWrapper($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, $this->windowId);
		VarInt::writeUnsignedInt($out, $this->inventorySlot);
		$this->containerName->write($out);
		CommonTypes::putItemStackWrapper($out, $this->storage);
		CommonTypes::putItemStackWrapper($out, $this->item);
	}
}
