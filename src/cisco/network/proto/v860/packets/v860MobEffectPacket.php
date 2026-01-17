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

namespace cisco\network\proto\v860\packets;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v860MobEffectPacket extends MobEffectPacket
{

	public static function fromLatest(MobEffectPacket $pk) : self
	{
		$npk = new self();
		$npk->actorRuntimeId = $pk->actorRuntimeId;
		$npk->eventId = $pk->eventId;
		$npk->effectId = $pk->effectId;
		$npk->amplifier = $pk->amplifier;
		$npk->particles = $pk->particles;
		$npk->duration = $pk->duration;
		$npk->tick = $pk->tick;
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
		$this->tick = VarInt::readUnsignedLong($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		Byte::writeUnsigned($out, $this->eventId);
		VarInt::writeSignedInt($out, $this->effectId);
		VarInt::writeSignedInt($out, $this->amplifier);
		CommonTypes::putBool($out, $this->particles);
		VarInt::writeSignedInt($out, $this->duration);
		VarInt::writeUnsignedLong($out, $this->tick);
	}
}
