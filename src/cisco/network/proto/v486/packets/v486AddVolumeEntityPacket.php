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
use cisco\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\AddVolumeEntityPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;

class v486AddVolumeEntityPacket extends AddVolumeEntityPacket
{

	public const NETWORK_ID = v486ProtocolInfo::ADD_VOLUME_ENTITY_PACKET;

	public static function fromLatest(AddVolumeEntityPacket $pk) : self
	{
		$npk = new self();
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $npk, "entityNetId", $pk->getEntityNetId());
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $npk, "data", $pk->getData());
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $npk, "jsonIdentifier", $pk->getJsonIdentifier());
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $npk, "instanceName", $pk->getInstanceName());
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $npk, "engineVersion", $pk->getEngineVersion());
		return $npk;
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, $this->getEntityNetId());
		$out->writeByteArray($this->getData()->getEncodedNbt());
		CommonTypes::putString($out, $this->getJsonIdentifier());
		CommonTypes::putString($out, $this->getInstanceName());
		CommonTypes::putString($out, $this->getEngineVersion());
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $this, "entityNetId", VarInt::readUnsignedInt($in));
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $this, "data", new CacheableNbt(CommonTypes::getNbtCompoundRoot($in)));
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $this, "jsonIdentifier", CommonTypes::getString($in));
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $this, "instanceName", CommonTypes::getString($in));
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $this, "engineVersion", CommonTypes::getString($in));
	}

}
