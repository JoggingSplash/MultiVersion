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

namespace cisco\network\proto\v419\packets\types\inventory;

use cisco\network\proto\v419\structure\v419CommonTypes;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;

final class v419CreativeContentEntry {

	public function __construct(
		private int $stackId,
		private ItemStack $itemStack
	){

	}

	public function getItemStack() : ItemStack {
		return $this->itemStack;
	}

	public function getStackId() : int{
		return $this->stackId;
	}

	static public function read(ByteBufferReader $in) : v419CreativeContentEntry    {
		$id = VarInt::readSignedInt($in);
		$stack = v419CommonTypes::getItemStackWithoutStackId($in);
		return new self($id, $stack);
	}

	public function write(ByteBufferWriter $out) : void    {
		VarInt::writeSignedInt($out, $this->stackId);
		v419CommonTypes::putItemStackWithoutStackId($this->itemStack, $out);
	}
}
