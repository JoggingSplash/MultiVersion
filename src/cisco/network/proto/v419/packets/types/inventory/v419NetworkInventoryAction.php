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
use InvalidArgumentException;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\inventory\NetworkInventoryAction;
use pocketmine\utils\BinaryDataException;

class v419NetworkInventoryAction extends NetworkInventoryAction
{
	public const SOURCE_CRAFT_SLOT = 100;
	public int $newItemStackId;

	/**
	 * @return $this
	 *
	 * @throws BinaryDataException
	 * @throws PacketDecodeException
	 */
	public function read(ByteBufferReader $in, bool $hasItemStackIds = false) : v419NetworkInventoryAction
	{
		$this->sourceType = VarInt::readUnsignedInt($in);

		switch ($this->sourceType) {
			case self::SOURCE_CONTAINER:
				$this->windowId = VarInt::readSignedInt($in);
				break;
			case self::SOURCE_WORLD:
				$this->sourceFlags = VarInt::readUnsignedInt($in);
				break;
			case self::SOURCE_CREATIVE:
				break;
			case self::SOURCE_CRAFT_SLOT:
			case self::SOURCE_TODO:
				$this->windowId = VarInt::readSignedInt($in);
				break;
			default:
				throw new PacketDecodeException("Unknown inventory action source type $this->sourceType");
		}

		$this->inventorySlot = VarInt::readUnsignedInt($in);

		$this->oldItem = ItemStackWrapper::legacy(v419CommonTypes::getItemStackWrapper($in)->getItemStack());
		$this->newItem = ItemStackWrapper::legacy(v419CommonTypes::getItemStackWrapper($in)->getItemStack());

		if ($hasItemStackIds) {
			$this->newItemStackId = VarInt::readSignedInt($in);
		}

		return $this;
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function write(ByteBufferWriter $out, bool $hasItemStackIds = false) : void
	{
		VarInt::writeUnsignedInt($out, $this->sourceType);

		switch ($this->sourceType) {
			case self::SOURCE_CONTAINER:
				VarInt::writeSignedInt($out, $this->windowId);
				break;
			case self::SOURCE_WORLD:
				VarInt::writeUnsignedInt($out, $this->sourceFlags);
				break;
			case self::SOURCE_CREATIVE:
				break;
			case self::SOURCE_CRAFT_SLOT:
			case self::SOURCE_TODO:
				VarInt::writeSignedInt($out, $this->windowId);
				break;
			default:
				/** @phpstan-ignore-next-line */
				throw new InvalidArgumentException("Unknown inventory action source type $this->sourceType");
		}

		VarInt::writeUnsignedInt($out, $this->inventorySlot);
		v419CommonTypes::putItemStackWrapper($out, $this->oldItem);
		v419CommonTypes::putItemStackWrapper($out, $this->newItem);
		if ($hasItemStackIds) {
			VarInt::writeSignedInt($out, $this->newItemStackId);
		}
	}
}
