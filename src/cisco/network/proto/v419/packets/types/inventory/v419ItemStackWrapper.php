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

final class v419ItemStackWrapper
{
	public function __construct(
		private int       $stackId,
		private ItemStack $itemStack
	)
	{
	}

	public static function legacy(ItemStack $itemStack) : self
	{
		return new self($itemStack->getId() === 0 ? 0 : 1, $itemStack);
	}

	public static function read(ByteBufferReader $in, bool $hasLegacyNetId = false) : self
	{
		$stackId = VarInt::readSignedInt($in);
		$stack = v419CommonTypes::getItemStackWithoutStackId($in);
		return new self($stackId, $stack);
	}

	public function getStackId() : int
	{
		return $this->stackId;
	}

	public function getItemStack() : ItemStack
	{
		return $this->itemStack;
	}

	public function write(ByteBufferWriter $out, bool $hasLegacyNetId = false) : void
	{
		VarInt::writeSignedInt($out, $this->stackId);
		v419CommonTypes::putItemStackWithoutStackId($this->itemStack, $out);
	}

}
