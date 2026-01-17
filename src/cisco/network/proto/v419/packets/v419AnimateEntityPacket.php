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
use cisco\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\AnimateEntityPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

class v419AnimateEntityPacket extends AnimateEntityPacket
{

	static public function fromLatest(AnimateEntityPacket $packet) : v419AnimateEntityPacket
	{
		$npk = new self();
		ReflectionUtils::setProperty(AnimateEntityPacket::class, $npk, "animation", $packet->getAnimation());
		ReflectionUtils::setProperty(AnimateEntityPacket::class, $npk, "nextState", $packet->getNextState());
		ReflectionUtils::setProperty(AnimateEntityPacket::class, $npk, "stopExpression", $packet->getStopExpression());
		ReflectionUtils::setProperty(AnimateEntityPacket::class, $npk, "controller", $packet->getController());
		ReflectionUtils::setProperty(AnimateEntityPacket::class, $npk, "blendOutTime", $packet->getBlendOutTime());
		ReflectionUtils::setProperty(AnimateEntityPacket::class, $npk, "actorRuntimeIds", $packet->getActorRuntimeIds());
		return $npk;
	}

	public function getStopExpressionVersion() : int
	{
		throw new BadMethodCallException();
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		ReflectionUtils::setProperty(AnimateEntityPacket::class, $this, "animation", CommonTypes::getString($in));
		ReflectionUtils::setProperty(AnimateEntityPacket::class, $this, "nextState", CommonTypes::getString($in));
		ReflectionUtils::setProperty(AnimateEntityPacket::class, $this, "stopExpression", CommonTypes::getString($in));
		ReflectionUtils::setProperty(AnimateEntityPacket::class, $this, "controller", CommonTypes::getString($in));
		ReflectionUtils::setProperty(AnimateEntityPacket::class, $this, "blendOutTime", LE::readFloat($in));

		$actorRuntimeIds = [];
		for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
			$actorRuntimeIds[] = CommonTypes::getActorRuntimeId($in);
		}

		ReflectionUtils::setProperty(AnimateEntityPacket::class, $this, "actorRuntimeIds", $actorRuntimeIds);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putString($out, $this->getAnimation());
		CommonTypes::putString($out, $this->getNextState());
		CommonTypes::putString($out, $this->getStopExpression());
		CommonTypes::putString($out, $this->getController());
		LE::writeFloat($out, $this->getBlendOutTime());
		VarInt::writeUnsignedInt($out, count($this->getActorRuntimeIds()));
		foreach ($this->getActorRuntimeIds() as $id) {
			CommonTypes::putActorRuntimeId($out, $id);
		}
	}
}
