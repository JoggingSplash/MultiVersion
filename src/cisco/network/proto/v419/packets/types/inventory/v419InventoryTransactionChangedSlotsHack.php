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

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use function count;

class v419InventoryTransactionChangedSlotsHack
{

	/**
	 * @param int[] $changedSlotIndexes
	 */
	public function __construct(
		private int   $containerId,
		private array $changedSlotIndexes
	)
	{
	}

	public static function read(ByteBufferReader $in) : self
	{
		$containerId = v419ContainerUIIds::read($in);
		$changedSlots = [];
		for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
			$changedSlots[] = Byte::readUnsigned($in);
		}
		return new self($containerId, $changedSlots);
	}

	public function getContainerId() : int
	{
		return $this->containerId;
	}

	/** @return int[] */
	public function getChangedSlotIndexes() : array
	{
		return $this->changedSlotIndexes;
	}

	public function write(ByteBufferWriter $out) : void
	{
		v419ContainerUIIds::write($out, $this->containerId);
		VarInt::writeUnsignedInt($out, count($this->changedSlotIndexes));
		foreach ($this->changedSlotIndexes as $index) {
			Byte::writeUnsigned($out, $index);
		}
	}
}
