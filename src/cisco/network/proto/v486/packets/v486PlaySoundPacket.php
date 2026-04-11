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
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\BlockPosition;

class v486PlaySoundPacket extends PlaySoundPacket {

	static public function fromLatest(PlaySoundPacket $packet) : self {
		$npk = new self();
		$npk->soundName = $packet->soundName;
		$npk->x = $packet->x;
		$npk->y = $packet->y;
		$npk->z = $packet->z;
		$npk->volume = $packet->volume;
		$npk->pitch = $packet->pitch;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->soundName = CommonTypes::getString($in);
		$blockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
		$this->x = $blockPosition->getX() / 8;
		$this->y = $blockPosition->getY() / 8;
		$this->z = $blockPosition->getZ() / 8;
		$this->volume = LE::readFloat($in);
		$this->pitch = LE::readFloat($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putString($out, $this->soundName);
		$blockPosition = new BlockPosition((int) ($this->x * 8), (int) ($this->y * 8), (int) ($this->z * 8));
		RawPacketHelper::putUnsignedYBlockPosition($out, $blockPosition);
		LE::writeFloat($out, $this->volume);
		LE::writeFloat($out, $this->pitch);
	}

}
