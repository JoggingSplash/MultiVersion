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
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v486DisconnectPacket extends DisconnectPacket
{

	public const NETWORK_ID = v486ProtocolInfo::DISCONNECT_PACKET;

	public static function fromLatest(DisconnectPacket $pk) : self
	{
		$npk = new self();
		$npk->message = $pk->message;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$skipMessage = CommonTypes::getBool($in);
		$this->message = $skipMessage ? null : CommonTypes::getString($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putBool($out, $skipMessage = $this->message === null);
		if (!$skipMessage) {
			CommonTypes::putString($out, $this->message);
		}
	}
}
