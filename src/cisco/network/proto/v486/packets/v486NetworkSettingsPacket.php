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

use cisco\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\NetworkSettingsPacket;
use ReflectionException;

class v486NetworkSettingsPacket extends NetworkSettingsPacket
{

	/**
	 * @throws ReflectionException
	 */
	public static function fromLatest(NetworkSettingsPacket $packet) : self
	{
		$npk = new self();
		ReflectionUtils::setProperty(NetworkSettingsPacket::class, $npk, "compressionThreshold", $packet->getCompressionThreshold());
		return $npk;
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		LE::writeUnsignedShort($out, $this->getCompressionThreshold());
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		ReflectionUtils::setProperty(NetworkSettingsPacket::class, $this, "compressionThreshold", LE::readUnsignedShort($in));
	}

}
