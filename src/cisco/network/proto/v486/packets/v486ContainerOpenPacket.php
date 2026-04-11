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

namespace cisco\network\proto\v486\packets;

use cisco\network\utils\RawPacketHelper;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v486ContainerOpenPacket extends ContainerOpenPacket{

	static public function fromLatest(ContainerOpenPacket $packet) : self    {
		$npk = new self();
		$npk->windowId = $packet->windowId;
		$npk->windowType = $packet->windowType;
		$npk->blockPosition = $packet->blockPosition;
		$npk->actorUniqueId = $packet->actorUniqueId;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->windowId = Byte::readUnsigned($in);
		$this->windowType = Byte::readUnsigned($in);
		$this->blockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
		$this->actorUniqueId = CommonTypes::getActorUniqueId($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		Byte::writeUnsigned($out, $this->windowId);
		Byte::writeUnsigned($out, $this->windowType);
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->blockPosition);
		CommonTypes::putActorUniqueId($out, $this->actorUniqueId);
	}
}
