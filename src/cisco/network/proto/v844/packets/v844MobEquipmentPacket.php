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

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844MobEquipmentPacket extends MobEquipmentPacket {

	static public function fromLatest(MobEquipmentPacket $packet) : self {
		$result = new self();
		$result->actorRuntimeId = $packet->actorRuntimeId;
		$result->item = $packet->item;
		$result->inventorySlot = $packet->inventorySlot;
		$result->hotbarSlot = $packet->hotbarSlot;
		$result->windowId = $packet->windowId;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->item = CommonTypes::getItemStackWrapper($in);
		$this->inventorySlot = Byte::readUnsigned($in);
		$this->hotbarSlot = Byte::readUnsigned($in);
		$this->windowId = Byte::readUnsigned($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		CommonTypes::putItemStackWrapper($out, $this->item);
		Byte::writeUnsigned($out, $this->inventorySlot);
		Byte::writeUnsigned($out, $this->hotbarSlot);
		Byte::writeUnsigned($out, $this->windowId);
	}
}
