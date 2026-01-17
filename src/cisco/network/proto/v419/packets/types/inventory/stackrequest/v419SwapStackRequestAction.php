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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;

class v419SwapStackRequestAction extends ItemStackRequestAction
{
	use GetTypeIdFromConstTrait;

	public const ID = v419ItemStackRequestActionType::SWAP;

	public function __construct(
		private v419ItemStackRequestSlotInfo $slot1,
		private v419ItemStackRequestSlotInfo $slot2
	)
	{
	}

	public static function read(ByteBufferReader $in) : self
	{
		$slot1 = v419ItemStackRequestSlotInfo::read($in);
		$slot2 = v419ItemStackRequestSlotInfo::read($in);
		return new self($slot1, $slot2);
	}

	public function getSlot1() : v419ItemStackRequestSlotInfo
	{
		return $this->slot1;
	}

	public function getSlot2() : v419ItemStackRequestSlotInfo
	{
		return $this->slot2;
	}

	public function write(ByteBufferWriter $out) : void
	{
		$this->slot1->write($out);
		$this->slot2->write($out);
	}
}
