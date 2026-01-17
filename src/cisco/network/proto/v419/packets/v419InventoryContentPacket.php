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
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use function count;

class v419InventoryContentPacket extends InventoryContentPacket
{

	/** @var v419ItemStackWrapper[] */
	private array $_items = [];

	public static function fromLatest(InventoryContentPacket $pk) : self
	{
		$npk = new self();
		$npk->windowId = $pk->windowId;
		foreach ($pk->items as $key => $item) {
			$npk->_items[$key] = new v419ItemStackWrapper($item->getStackId(), $item->getItemStack());
		}
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->windowId = VarInt::readUnsignedInt($in);
		$count = VarInt::readUnsignedInt($in);
		for ($i = 0; $i < $count; $i++) {
			$this->_items[] = v419ItemStackWrapper::read($in, true);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, $this->windowId);
		VarInt::writeUnsignedInt($out, count($this->_items));
		foreach ($this->_items as $item) {
			$item->write($out, true);
		}
	}
}
