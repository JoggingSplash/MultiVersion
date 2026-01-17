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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\TransferPacket;

class v486TransferPacket extends TransferPacket
{

	/**
	 * @generate-create-func
	 */
	public static function fromLatest(TransferPacket $packet) : self
	{
		$result = new self();
		$result->address = $packet->address;
		$result->port = $packet->port;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->address = CommonTypes::getString($in);
		$this->port = LE::readUnsignedShort($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putString($out, $this->address);
		LE::writeUnsignedShort($out, $this->port);
	}
}
