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

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class v486ItemStackResponseSlotInfo
{

	private int $slot;
	private int $hotbarSlot;
	private int $count;
	private int $itemStackId;
	private string $customName;
	private int $durabilityCorrection;

	public function __construct(int $slot, int $hotbarSlot, int $count, int $itemStackId, string $customName, int $durabilityCorrection)
	{
		$this->slot = $slot;
		$this->hotbarSlot = $hotbarSlot;
		$this->count = $count;
		$this->itemStackId = $itemStackId;
		$this->customName = $customName;
		$this->durabilityCorrection = $durabilityCorrection;
	}

	public static function read(ByteBufferReader $in) : self
	{
		$slot = Byte::readUnsigned($in);
		$hotbarSlot = Byte::readUnsigned($in);
		$count = Byte::readUnsigned($in);
		$itemStackId = VarInt::readSignedInt($in);
		$customName = CommonTypes::getString($in);
		$durabilityCorrection = VarInt::readSignedInt($in);
		return new self($slot, $hotbarSlot, $count, $itemStackId, $customName, $durabilityCorrection);
	}

	public function getSlot() : int
	{
		return $this->slot;
	}

	public function getHotbarSlot() : int
	{
		return $this->hotbarSlot;
	}

	public function getCount() : int
	{
		return $this->count;
	}

	public function getItemStackId() : int
	{
		return $this->itemStackId;
	}

	public function getCustomName() : string
	{
		return $this->customName;
	}

	public function getDurabilityCorrection() : int
	{
		return $this->durabilityCorrection;
	}

	public function write(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->slot);
		Byte::writeUnsigned($out, $this->hotbarSlot);
		Byte::writeUnsigned($out, $this->count);
		VarInt::writeSignedInt($out, $this->itemStackId);
		CommonTypes::putString($out, $this->customName);
		VarInt::writeSignedInt($out, $this->durabilityCorrection);
	}

}
