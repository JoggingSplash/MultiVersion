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

namespace cisco\network\proto\v419\packets;

use cisco\network\proto\v419\structure\v419ProtocolInfo;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v419MobEffectPacket extends MobEffectPacket
{

	public const NETWORK_ID = v419ProtocolInfo::MOB_EFFECT_PACKET;

	static public function fromLatest(MobEffectPacket $packet)
	{
		$npk = new self();
		$npk->actorRuntimeId = $packet->actorRuntimeId;
		$npk->eventId = $packet->eventId;
		$npk->effectId = $packet->effectId;
		$npk->amplifier = $packet->amplifier;
		$npk->particles = $packet->particles;
		$npk->duration = $packet->duration;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->eventId = Byte::readUnsigned($in);
		$this->effectId = VarInt::readSignedInt($in);
		$this->amplifier = VarInt::readSignedInt($in);
		$this->particles = CommonTypes::getBool($in);
		$this->duration = VarInt::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		Byte::writeUnsigned($out, $this->eventId);
		VarInt::writeSignedInt($out, $this->effectId);
		VarInt::writeSignedInt($out, $this->amplifier);
		CommonTypes::putBool($out, $this->particles);
		VarInt::writeSignedInt($out, $this->duration);
	}
}
