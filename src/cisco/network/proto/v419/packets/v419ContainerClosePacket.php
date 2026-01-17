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
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v419ContainerClosePacket extends ContainerClosePacket
{

	public const NETWORK_ID = v419ProtocolInfo::CONTAINER_CLOSE_PACKET;

	public static function fromLatest(ContainerClosePacket $pk) : self
	{
		$result = new self();
		$result->windowId = $pk->windowId;
		$result->server = $pk->server;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->windowId = Byte::readUnsigned($in);
		$this->server = CommonTypes::getBool($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->windowId);
		CommonTypes::putBool($out, $this->server);
	}
}
