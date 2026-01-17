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
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\serializer\BitSet;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v419PlayerAuthInputPacket extends PlayerAuthInputPacket
{

	public const NETWORK_ID = v419ProtocolInfo::PLAYER_AUTH_INPUT_PACKET;

	public ?Vector3 $vzGazeDirection = null; //useless

	protected function decodePayload(ByteBufferReader $in) : void
	{
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "pitch", LE::readFloat($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "yaw", LE::readFloat($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "position", CommonTypes::getVector3($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "moveVecX", LE::readFloat($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "moveVecZ", LE::readFloat($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "headYaw", LE::readFloat($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "inputFlags", BitSet::read($in, 65)); // 1.16 has other length of reading
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "inputMode", VarInt::readUnsignedInt($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "playMode", VarInt::readUnsignedInt($in));
		if ($this->getPlayMode() === 4) { // fuck 1.21.120 they removed 4 PlayModes and i need to hardcode this
			$this->vzGazeDirection = CommonTypes::getVector3($in);
		}
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "tick", VarInt::readUnsignedLong($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "delta", CommonTypes::getVector3($in));
	}
}
