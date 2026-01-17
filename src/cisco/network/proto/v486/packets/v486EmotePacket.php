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
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use ReflectionException;

class v486EmotePacket extends EmotePacket
{

	public const NETWORK_ID = v486ProtocolInfo::EMOTE_PACKET;

	/**
	 * @throws ReflectionException
	 */
	public static function fromLatest(EmotePacket $pk) : self
	{
		$npk = new self();
		ReflectionUtils::setProperty(EmotePacket::class, $npk, "actorRuntimeId", $pk->getActorRuntimeId());
		ReflectionUtils::setProperty(EmotePacket::class, $npk, "emoteId", $pk->getEmoteId());
		ReflectionUtils::setProperty(EmotePacket::class, $npk, "flags", $pk->getFlags());
		return $npk;
	}

	public function getEmoteLengthTicks() : int
	{
		throw new BadMethodCallException();
	}

	public function getPlatformChatId() : string
	{
		throw new BadMethodCallException();
	}

	public function getXboxUserId() : string
	{
		throw new BadMethodCallException();
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putActorRuntimeId($out, $this->getActorRuntimeId());
		CommonTypes::putString($out, $this->getEmoteId());
		Byte::writeUnsigned($out, $this->getFlags());
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		ReflectionUtils::setProperty(EmotePacket::class, $this, "actorRuntimeId", CommonTypes::getActorRuntimeId($in));
		ReflectionUtils::setProperty(EmotePacket::class, $this, "emoteId", CommonTypes::getString($in));
		ReflectionUtils::setProperty(EmotePacket::class, $this, "flags", Byte::readUnsigned($in));
	}
}
