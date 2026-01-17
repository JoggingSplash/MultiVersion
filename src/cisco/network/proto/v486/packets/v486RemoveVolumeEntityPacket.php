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

use BadMethodCallException;
use cisco\network\proto\v486\structure\v486ProtocolInfo;
use cisco\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\RemoveVolumeEntityPacket;
use ReflectionException;

class v486RemoveVolumeEntityPacket extends RemoveVolumeEntityPacket
{

	public const NETWORK_ID = v486ProtocolInfo::REMOVE_VOLUME_ENTITY_PACKET;

	/**
	 * @throws ReflectionException
	 */
	public static function fromLatest(RemoveVolumeEntityPacket $packet) : self
	{
		$npk = new self();
		ReflectionUtils::setProperty(RemoveVolumeEntityPacket::class, $npk, "entityNetId", $packet->getEntityNetId());
		return $npk;
	}

	public function getDimension() : int
	{
		throw new BadMethodCallException("Dimension field does not exist on 1.18.12");
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		ReflectionUtils::setProperty(RemoveVolumeEntityPacket::class, $this, "entityNetId", VarInt::readUnsignedInt($in));
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, $this->getEntityNetId());
	}
}
