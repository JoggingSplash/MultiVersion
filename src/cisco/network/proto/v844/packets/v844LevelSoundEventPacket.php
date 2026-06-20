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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844LevelSoundEventPacket extends LevelSoundEventPacket {

	static public function fromLatest(LevelSoundEventPacket $packet) : self    {
		$result = new self();
		$result->sound = $packet->sound;
		$result->position = $packet->position;
		$result->extraData = $packet->extraData;
		$result->entityType = $packet->entityType;
		$result->isBabyMob = $packet->isBabyMob;
		$result->disableRelativeVolume = $packet->disableRelativeVolume;
		$result->actorUniqueId = $packet->actorUniqueId;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->sound = VarInt::readUnsignedInt($in);
		$this->position = CommonTypes::getVector3($in);
		$this->extraData = VarInt::readSignedInt($in);
		$this->entityType = CommonTypes::getString($in);
		$this->isBabyMob = CommonTypes::getBool($in);
		$this->disableRelativeVolume = CommonTypes::getBool($in);
		$this->actorUniqueId = LE::readSignedLong($in); //WHY IS THIS NON-STANDARD?
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, $this->sound);
		CommonTypes::putVector3($out, $this->position);
		VarInt::writeSignedInt($out, $this->extraData);
		CommonTypes::putString($out, $this->entityType);
		CommonTypes::putBool($out, $this->isBabyMob);
		CommonTypes::putBool($out, $this->disableRelativeVolume);
		LE::writeSignedLong($out, $this->actorUniqueId);
	}
}
