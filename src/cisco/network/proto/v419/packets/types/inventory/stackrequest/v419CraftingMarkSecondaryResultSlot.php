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

namespace cisco\network\proto\v419\packets\types\inventory\stackrequest;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;

class v419CraftingMarkSecondaryResultSlot extends ItemStackRequestAction
{
	use GetTypeIdFromConstTrait;

	public const ID = v419ItemStackRequestActionType::CRAFTING_MARK_SECONDARY_RESULT_SLOT;

	public function __construct(
		private int $craftingSlot
	)
	{

	}

	static public function read(ByteBufferReader $in) : self
	{
		$craftingSlot = Byte::readUnsigned($in);
		return new self($craftingSlot);
	}

	public function getCraftingSlot() : int
	{
		return $this->craftingSlot;
	}

	public function write(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->craftingSlot);
	}
}
