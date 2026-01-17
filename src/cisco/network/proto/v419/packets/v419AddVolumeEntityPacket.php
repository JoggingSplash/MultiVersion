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

use cisco\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\AddVolumeEntityPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;

class v419AddVolumeEntityPacket extends AddVolumeEntityPacket
{

	public static function fromLatest(AddVolumeEntityPacket $pk) : self
	{
		$npk = new self();
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $npk, "entityNetId", $pk->getEntityNetId());
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $npk, "data", $pk->getData());
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $this, "entityNetId", VarInt::readUnsignedInt($in));
		ReflectionUtils::setProperty(AddVolumeEntityPacket::class, $this, "data", new CacheableNbt(CommonTypes::getNbtCompoundRoot($in)));
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, $this->getEntityNetId());
		$out->writeByteArray($this->getData()->getEncodedNbt());
	}

}
