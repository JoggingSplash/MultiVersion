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

namespace cisco\network\proto\v486\packets\types\inventory\stackresponse;

use cisco\network\proto\v486\packets\types\inventory\v486ContainerUIIds;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use function count;

class v486ItemStackResponseContainerInfo
{

	/**
	 * @param v486ItemStackResponseSlotInfo[] $slots
	 */
	public function __construct(
		private int   $containerId,
		private array $slots
	)
	{

	}

	public static function read(ByteBufferReader $in) : self
	{
		$containerId = v486ContainerUIIds::read($in);
		$slots = [];
		for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
			$slots[] = v486ItemStackResponseSlotInfo::read($in);
		}
		return new self($containerId, $slots);
	}

	public function getContainerId() : int
	{
		return $this->containerId;
	}

	/** @return v486ItemStackResponseSlotInfo[] */
	public function getSlots() : array
	{
		return $this->slots;
	}

	public function write(ByteBufferWriter $out) : void
	{
		v486ContainerUIIds::write($out, $this->containerId);
		VarInt::writeUnsignedInt($out, count($this->slots));
		foreach ($this->slots as $slot) {
			$slot->write($out);
		}
	}
}
