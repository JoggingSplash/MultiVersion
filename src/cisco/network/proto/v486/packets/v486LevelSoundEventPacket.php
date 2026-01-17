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

use cisco\network\proto\v486\structure\v486ProtocolInfo;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v486LevelSoundEventPacket extends LevelSoundEventPacket
{

	public const NETWORK_ID = v486ProtocolInfo::LEVEL_SOUND_EVENT_PACKET;

	static public function fromLatest(LevelSoundEventPacket $packet) : self
	{
		$npk = new self();
		$npk->sound = $packet->sound;
		$npk->position = $packet->position;
		$npk->extraData = $packet->extraData;
		$npk->entityType = $packet->entityType;
		$npk->isBabyMob = $packet->isBabyMob;
		$npk->disableRelativeVolume = $packet->disableRelativeVolume;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->sound = VarInt::readUnsignedInt($in);
		$this->position = CommonTypes::getVector3($in);
		$this->extraData = VarInt::readSignedInt($in);
		$this->entityType = CommonTypes::getString($in);
		$this->isBabyMob = CommonTypes::getBool($in);
		$this->disableRelativeVolume = CommonTypes::getBool($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, $this->sound);
		CommonTypes::putVector3($out, $this->position);
		VarInt::writeSignedInt($out, $this->extraData);
		CommonTypes::putString($out, $this->entityType);
		CommonTypes::putBool($out, $this->isBabyMob);
		CommonTypes::putBool($out, $this->disableRelativeVolume);
	}
}
