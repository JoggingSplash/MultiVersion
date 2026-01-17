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
use cisco\network\proto\v486\structure\v486ProtocolInfo;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use function count;

class v486InventoryContentPacket extends InventoryContentPacket
{

	public const NETWORK_ID = v486ProtocolInfo::INVENTORY_CONTENT_PACKET;

	public static function fromLatest(InventoryContentPacket $packet) : self
	{
		$npk = new self();
		$npk->windowId = $packet->windowId;
		$npk->items = $packet->items;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->windowId = VarInt::readUnsignedInt($in);
		$count = VarInt::readUnsignedInt($in);
		for ($i = 0; $i < $count; ++$i) {
			$this->items[] = v486CommonTypes::getItemStackWrapper($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, $this->windowId);
		VarInt::writeUnsignedInt($out, count($this->items));
		foreach ($this->items as $item) {
			v486CommonTypes::putItemStackWrapper($out, $item);
		}
	}

}
