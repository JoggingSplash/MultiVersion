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

namespace cisco\network\proto\v419\packets;

use cisco\network\utils\RawPacketHelper;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\AnvilDamagePacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;

class v419AnvilDamagePacket extends AnvilDamagePacket {

	private BlockPosition $blockPosition;
	private int $damageAmount;

	public static function fromLatest(AnvilDamagePacket $packet) : self {
		$npk = new self();
		$npk->blockPosition = $packet->getBlockPosition();
		$npk->damageAmount = $packet->getDamageAmount();
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->damageAmount = Byte::readUnsigned($in);
		$this->blockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		Byte::writeUnsigned($out, $this->damageAmount);
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->blockPosition);
	}
}
