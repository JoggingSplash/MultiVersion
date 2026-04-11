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
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\LabTablePacket;

class v844LabTablePacket extends LabTablePacket{

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->actionType = Byte::readUnsigned($in);
		$this->blockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
		$this->reactionType = Byte::readUnsigned($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		Byte::writeUnsigned($out, $this->actionType);
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->blockPosition);
		Byte::writeUnsigned($out, $this->reactionType);
	}
}
