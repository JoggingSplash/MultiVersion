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

namespace cisco\network\proto\v844\packets;

use cisco\network\utils\RawPacketHelper;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\BlockPickRequestPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844BlockPickRequestPacket extends BlockPickRequestPacket {

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->blockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
		$this->addUserData = CommonTypes::getBool($in);
		$this->hotbarSlot = Byte::readUnsigned($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->blockPosition);
		CommonTypes::putBool($out, $this->addUserData);
		Byte::writeUnsigned($out, $this->hotbarSlot);
	}
}
