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
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v419DisconnectPacket extends DisconnectPacket
{
	public const NETWORK_ID = v419ProtocolInfo::DISCONNECT_PACKET;
	protected bool $hideScreen = false;

	static public function fromLatest(DisconnectPacket $packet) : v419DisconnectPacket
	{
		$npk = new v419DisconnectPacket();
		$npk->message = $packet->message;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->message = ($this->hideScreen = CommonTypes::getBool($in)) ? null : CommonTypes::getString($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putBool($out, $this->hideScreen);
		if (!$this->hideScreen) {
			CommonTypes::putString($out, $this->message ?? "");
		}
	}
}
