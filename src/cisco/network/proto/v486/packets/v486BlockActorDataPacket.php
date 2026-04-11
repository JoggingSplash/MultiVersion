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
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;

class v486BlockActorDataPacket extends BlockActorDataPacket {

	public static function fromLatest(BlockActorDataPacket $packet) : self{
		$result = new self();
		$result->blockPosition = $packet->blockPosition;
		$result->nbt = $packet->nbt;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->blockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
		$this->nbt = new CacheableNbt(CommonTypes::getNbtCompoundRoot($in));
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->blockPosition);
		$out->writeByteArray($this->nbt->getEncodedNbt());
	}
}
