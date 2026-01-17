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
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;

class v419SpawnParticleEffectPacket extends SpawnParticleEffectPacket
{
	public const NETWORK_ID = v419ProtocolInfo::SPAWN_PARTICLE_EFFECT_PACKET;

	public static function fromLatest(SpawnParticleEffectPacket $packet) : self
	{
		$npk = new self();
		$npk->dimensionId = $packet->dimensionId;
		$npk->actorUniqueId = $packet->actorUniqueId;
		$npk->position = $packet->position;
		$npk->particleName = $packet->particleName;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->dimensionId = Byte::readUnsigned($in);
		$this->actorUniqueId = CommonTypes::getActorUniqueId($in);
		$this->position = CommonTypes::getVector3($in);
		$this->particleName = CommonTypes::getString($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->dimensionId);
		CommonTypes::putActorUniqueId($out, $this->actorUniqueId);
		CommonTypes::putVector3($out, $this->position);
		CommonTypes::putString($out, $this->particleName);
	}
}
