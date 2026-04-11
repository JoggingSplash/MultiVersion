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
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;

class v844SetSpawnPositionPacket extends SetSpawnPositionPacket {

	public static function fromLatest(SetSpawnPositionPacket $packet) : self{
		$result = new self();
		$result->spawnType = $packet->spawnType;
		$result->spawnPosition = $packet->spawnPosition;
		$result->dimension = $packet->dimension;
		$result->causingBlockPosition = $packet->causingBlockPosition;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->spawnType = VarInt::readSignedInt($in);
		$this->spawnPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
		$this->dimension = VarInt::readSignedInt($in);
		$this->causingBlockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		VarInt::writeSignedInt($out, $this->spawnType);
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->spawnPosition);
		VarInt::writeSignedInt($out, $this->dimension);
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->causingBlockPosition);
	}
}
