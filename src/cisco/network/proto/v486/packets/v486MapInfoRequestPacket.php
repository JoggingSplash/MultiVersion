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
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\MapInfoRequestPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v486MapInfoRequestPacket extends MapInfoRequestPacket
{

	public const NETWORK_ID = v486ProtocolInfo::MAP_INFO_REQUEST_PACKET;

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->mapId = CommonTypes::getActorUniqueId($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putActorUniqueId($out, $this->mapId);
	}

}
