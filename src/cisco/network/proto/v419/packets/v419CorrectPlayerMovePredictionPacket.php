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

use BadMethodCallException;
use cisco\network\proto\v419\structure\v419ProtocolInfo;
use cisco\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\math\Vector2;
use pocketmine\network\mcpe\protocol\CorrectPlayerMovePredictionPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v419CorrectPlayerMovePredictionPacket extends CorrectPlayerMovePredictionPacket
{
	public const NETWORK_ID = v419ProtocolInfo::CORRECT_PLAYER_MOVE_PREDICTION_PACKET;

	static public function fromLatest(CorrectPlayerMovePredictionPacket $packet)
	{
		$npk = new self();
		ReflectionUtils::setProperty(CorrectPlayerMovePredictionPacket::class, $npk, "position", $packet->getPosition());
		ReflectionUtils::setProperty(CorrectPlayerMovePredictionPacket::class, $npk, "delta", $packet->getDelta());
		ReflectionUtils::setProperty(CorrectPlayerMovePredictionPacket::class, $npk, "onGround", $packet->isOnGround());
		ReflectionUtils::setProperty(CorrectPlayerMovePredictionPacket::class, $npk, "tick", $packet->getTick());
		return $npk;
	}

	public function getPredictionType() : int
	{
		throw new BadMethodCallException("PredictionType in protocol v419 is not supported at" . self::class);
	}

	public function getVehicleRotation() : Vector2
	{
		throw new BadMethodCallException("VehicleRotation in protocol v419 is not supported at" . self::class);
	}

	public function getVehicleAngularVelocity() : float
	{
		throw new BadMethodCallException("VehicleAngularVelocity in protocol v419 is not supported at" . self::class);
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		ReflectionUtils::setProperty(v419CorrectPlayerMovePredictionPacket::class, $this, "position", CommonTypes::getVector3($in));
		ReflectionUtils::setProperty(v419CorrectPlayerMovePredictionPacket::class, $this, "delta", CommonTypes::getVector3($in));
		ReflectionUtils::setProperty(v419CorrectPlayerMovePredictionPacket::class, $this, "onGround", CommonTypes::getBool($in));
		ReflectionUtils::setProperty(v419CorrectPlayerMovePredictionPacket::class, $this, "tick", VarInt::readUnsignedLong($in));
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putVector3($out, $this->getPosition());
		CommonTypes::putVector3($out, $this->getDelta());
		CommonTypes::putBool($out, $this->isOnGround());
		VarInt::writeUnsignedLong($out, $this->getTick());
	}
}
