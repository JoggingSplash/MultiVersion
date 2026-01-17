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
use cisco\network\utils\ReflectionUtils;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\CameraShakePacket;
use pq\Exception\BadMethodCallException;

class v419CameraShakePacket extends CameraShakePacket
{

	public const NETWORK_ID = v419ProtocolInfo::CAMERA_SHAKE_PACKET;

	public static function fromLatest(CameraShakePacket $pk) : self
	{
		$npk = new self();
		ReflectionUtils::setProperty(CameraShakePacket::class, $npk, "intensity", $pk->getIntensity());
		ReflectionUtils::setProperty(CameraShakePacket::class, $npk, "duration", $pk->getDuration());
		ReflectionUtils::setProperty(CameraShakePacket::class, $npk, "shakeType", $pk->getShakeType());
		return $npk;
	}

	public function getShakeAction() : int
	{
		throw new BadMethodCallException();
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		ReflectionUtils::setProperty(CameraShakePacket::class, $this, "intensity", LE::readFloat($in));
		ReflectionUtils::setProperty(CameraShakePacket::class, $this, "duration", LE::readFloat($in));
		ReflectionUtils::setProperty(CameraShakePacket::class, $this, "shakeType", Byte::readUnsigned($in));
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		LE::writeFloat($out, $this->getIntensity());
		LE::writeFloat($out, $this->getDuration());
		Byte::writeUnsigned($out, $this->getShakeType());
	}
}
