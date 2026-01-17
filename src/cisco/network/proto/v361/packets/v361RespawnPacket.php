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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v361RespawnPacket extends RespawnPacket {

	static public function fromLatest(RespawnPacket $packet) : self {
		$npk = new self();
		$npk->position = $packet->position;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->position = CommonTypes::getVector3($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putVector3($out, $this->position);
	}
}
