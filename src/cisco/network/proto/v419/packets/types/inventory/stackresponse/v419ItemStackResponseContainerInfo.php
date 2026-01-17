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

namespace cisco\network\proto\v419\packets\types\inventory\stackresponse;

use cisco\network\proto\v419\packets\types\inventory\v419ContainerUIIds;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponseSlotInfo;
use function count;

final class v419ItemStackResponseContainerInfo
{
	/**
	 * @param ItemStackResponseSlotInfo[] $slots
	 */
	public function __construct(
		private int   $containerId,
		private array $slots
	)
	{
	}

	public static function read(ByteBufferReader $in) : self
	{
		$containerId = v419ContainerUIIds::read($in);
		$slots = [];
		for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
			$slot = v419ItemStackResponseSlotInfo::read($in);
			$slots[] = new ItemStackResponseSlotInfo($slot->getSlot(), $slot->getHotbarSlot(), $slot->getCount(), $slot->getItemStackId(), $slot->getCustomName(), $slot->getCustomName(), $slot->getDurabilityCorrection());
		}
		return new self($containerId, $slots);
	}

	public function getContainerId() : int
	{
		return $this->containerId;
	}

	/** @return ItemStackResponseSlotInfo[] */
	public function getSlots() : array
	{
		return $this->slots;
	}

	public function write(ByteBufferWriter $out) : void
	{
		v419ContainerUIIds::write($out, $this->containerId);
		VarInt::writeUnsignedInt($out, count($this->slots));
		foreach ($this->slots as $slot) {
			(new v419ItemStackResponseSlotInfo($slot->getSlot(), $slot->getHotbarSlot(), $slot->getCount(), $slot->getItemStackId(), $slot->getCustomName(), $slot->getDurabilityCorrection()))->write($out);
		}
	}
}
