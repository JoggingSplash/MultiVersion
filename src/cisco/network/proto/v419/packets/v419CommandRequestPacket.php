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

use cisco\network\assemble\CommandOriginData;
use cisco\network\proto\v419\structure\v419CommonTypes;
use cisco\network\proto\v419\structure\v419ProtocolInfo;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\CommandRequestPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v419CommandRequestPacket extends CommandRequestPacket
{

	public const NETWORK_ID = v419ProtocolInfo::COMMAND_REQUEST_PACKET;

	protected CommandOriginData $_originData;

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->command = CommonTypes::getString($in);
		$this->_originData = v419CommonTypes::getCommandOriginData($in);
		$this->isInternal = CommonTypes::getBool($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putString($out, $this->command);
		v419CommonTypes::putCommandOriginData($out, $this->_originData);
		CommonTypes::putBool($out, $this->isInternal);
	}
}
