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

namespace cisco\network\proto\v486\packets\types;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\CreativeGroupEntry;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;

final class v486CreativeGroupEntry
{

	public function __construct(
		private int       $entryId,
		private ItemStack $item
	)
	{
	}

	static public function fromLatest(CreativeGroupEntry $entry) : self
	{
		$entryId = $entry->getCategoryId();
		$item = $entry->getIcon();
		return new self($entryId, $item);
	}

	public static function read(ByteBufferReader $in) : self
	{
		$entryId = VarInt::readUnsignedInt($in);
		$item = CommonTypes::getItemStackWithoutStackId($in);
		return new self($entryId, $item);
	}

	public function getEntryId() : int
	{
		return $this->entryId;
	}

	public function getItem() : ItemStack
	{
		return $this->item;
	}

	public function write(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, $this->entryId);
		CommonTypes::putItemStackWithoutStackId($out, $this->item);
	}
}
