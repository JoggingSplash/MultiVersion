<?php

/*
 *
 *
 *      __  ___      ____  _ _    __               _
 *     /  |/  /_  __/ / /_(_) |  / /__  __________(_)___  ____
 *    / /|_/ / / / / / __/ /| | / / _ \/ ___/ ___/ / __ \/ __ \
 *   / /  / / /_/ / / /_/ / | |/ /  __/ /  (__  ) / /_/ / / / /
 *  /_/  /_/\__,_/_/\__/_/  |___/\___/_/  /____/_/\____/_/ /_/
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  @author JoggingSplash23
 *  @link https://www.github.com/JoggingSplash
 *
 *
 */

declare(strict_types=1);

namespace cisco\network\proto\v844\packets;

use cisco\network\utils\RawPacketHelper;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844PlayerActionPacket extends PlayerActionPacket {

	public static function fromLatest(PlayerActionPacket $packet) : self{
		$result = new self();
		$result->actorRuntimeId = $packet->actorRuntimeId;
		$result->action = $packet->action;
		$result->blockPosition = $packet->blockPosition;
		$result->resultPosition = $packet->resultPosition;
		$result->face = $packet->face;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->action = VarInt::readSignedInt($in);
		$this->blockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
		$this->resultPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
		$this->face = VarInt::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		VarInt::writeSignedInt($out, $this->action);
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->blockPosition);
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->resultPosition);
		VarInt::writeSignedInt($out, $this->face);
	}

}
