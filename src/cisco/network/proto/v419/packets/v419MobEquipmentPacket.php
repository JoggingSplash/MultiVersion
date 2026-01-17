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

use cisco\network\proto\v419\structure\v419CommonTypes;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;

class v419MobEquipmentPacket extends MobEquipmentPacket
{

	protected ItemStack $_item;

	static public function fromLatest(MobEquipmentPacket $packet) : self
	{
		$npk = new self();
		$npk->actorRuntimeId = $packet->actorRuntimeId;
		$npk->_item = $packet->item->getItemStack();
		$npk->inventorySlot = $packet->inventorySlot;
		$npk->hotbarSlot = $packet->hotbarSlot;
		$npk->windowId = $packet->windowId;
		return $npk;
	}

	public function handle(PacketHandlerInterface $handler) : bool
	{
		return $handler->handleMobEquipment($this);
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->_item = v419CommonTypes::getItemStackWithoutStackId($in);
		$this->inventorySlot = Byte::readUnsigned($in);
		$this->hotbarSlot = Byte::readUnsigned($in);
		$this->windowId = Byte::readUnsigned($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		v419CommonTypes::putItemStackWithoutStackId($this->_item, $out);
		Byte::writeUnsigned($out, $this->inventorySlot);
		Byte::writeUnsigned($out, $this->hotbarSlot);
		Byte::writeUnsigned($out, $this->windowId);
	}
}
