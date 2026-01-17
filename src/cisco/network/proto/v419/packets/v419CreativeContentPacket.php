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
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use function count;

class v419CreativeContentPacket extends CreativeContentPacket
{

	public const NETWORK_ID = v419ProtocolInfo::CREATIVE_CONTENT_PACKET;

	public array $entries = [];

	static public function v419create(array $entries) : v419CreativeContentPacket {
		$npk = new self();
		$npk->entries = $entries;
		return $npk;
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, count($this->entries));
		foreach ($this->entries as $entry) {
			$entry->write($out);
		}
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$count = VarInt::readUnsignedInt($in);

		while ($count-- > 0) {
			//$this->entries[] = read
		}
	}
}
