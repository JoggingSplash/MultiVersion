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

namespace cisco\network\proto\v486\packets;

use cisco\network\utils\RawPacketHelper;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;

class v486UpdateBlockPacket extends UpdateBlockPacket {

	static public function fromLatest(UpdateBlockPacket $packet) : self {
		$npk = new self();
		$npk->blockPosition = $packet->blockPosition;
		$npk->flags = $packet->flags;
		$npk->dataLayerId = $packet->dataLayerId;
		$npk->blockRuntimeId = $packet->blockRuntimeId;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->blockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
		$this->blockRuntimeId = VarInt::readUnsignedInt($in);
		$this->flags = VarInt::readUnsignedInt($in);
		$this->dataLayerId = VarInt::readUnsignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->blockPosition);
		VarInt::writeUnsignedInt($out, $this->blockRuntimeId);
		VarInt::writeUnsignedInt($out, $this->flags);
		VarInt::writeUnsignedInt($out, $this->dataLayerId);
	}
}
