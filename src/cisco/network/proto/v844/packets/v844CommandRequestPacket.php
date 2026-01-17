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

namespace cisco\network\proto\v844\packets;

use cisco\network\assemble\CommandOriginData;
use cisco\network\proto\v844\structure\v844CommonTypes;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\CommandRequestPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844CommandRequestPacket extends CommandRequestPacket
{

	public int $newVersion;
	public CommandOriginData $_originData;

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->command = CommonTypes::getString($in);
		$this->_originData = v844CommonTypes::getCommandOriginData($in);
		$this->isInternal = CommonTypes::getBool($in);
		$this->newVersion = VarInt::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putString($out, $this->command);
		v844CommonTypes::putCommandOriginData($out, $this->_originData);
		CommonTypes::putBool($out, $this->isInternal);
		VarInt::writeSignedInt($out, $this->newVersion);
	}
}
