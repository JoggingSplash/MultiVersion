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

namespace cisco\network\proto\v361\packets;

use cisco\network\proto\v361\structure\v361ProtocolInfo;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\StopSoundPacket;

class v361StopSoundPacket extends StopSoundPacket{

	public const NETWORK_ID = v361ProtocolInfo::STOP_SOUND_PACKET;

	static public function fromLatest(StopSoundPacket $packet) : self {
		$npk = new self();
		$npk->soundName = $packet->soundName;
		$npk->stopAll = $packet->stopAll;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->soundName = CommonTypes::getString($in);
		$this->stopAll = CommonTypes::getBool($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putString($out, $this->soundName);
		CommonTypes::putBool($out, $this->stopAll);
	}
}
