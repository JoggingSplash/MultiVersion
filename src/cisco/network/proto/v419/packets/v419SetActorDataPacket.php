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

use cisco\network\proto\v419\structure\v419CommonTypes;
use cisco\network\proto\v419\structure\v419ProtocolInfo;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;

class v419SetActorDataPacket extends SetActorDataPacket
{

	public const NETWORK_ID = v419ProtocolInfo::SET_ACTOR_DATA_PACKET;

	public static function fromLatest(SetActorDataPacket $pk) : self
	{
		$npk = new self();
		$npk->actorRuntimeId = $pk->actorRuntimeId;
		$npk->metadata = $pk->metadata;
		$npk->tick = $pk->tick;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->metadata = v419CommonTypes::getEntityMetadata($in);
		$this->tick = VarInt::readUnsignedLong($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		v419CommonTypes::putEntityMetadata($out, $this->metadata);
		VarInt::writeUnsignedLong($out, $this->tick);
	}
}
