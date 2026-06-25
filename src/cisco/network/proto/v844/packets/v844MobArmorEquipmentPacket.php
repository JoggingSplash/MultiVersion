<?php

/*
 *
 *
 *      __  ___      ____  _ _    __               _
 *     /  |/  /_  __/ / /_(_) |  / /__  __________(_)___  ____
 *    / /|_/ / / / / / __/ /| | / / _ \/ ___/ ___/ / __ \/ __ \
 *   / /  / / /_/ / / /_/ / | |/ /  __/ /  (__  ) / /_/ / / / /
 *  /_/  /_/\__,_/_/\__/_/  |___/\___/_/  /____/_/\____/_/ /_/
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  @author JoggingSplash23
 *  @link https://www.github.com/JoggingSplash
 *
 *
 */

declare(strict_types=1);

namespace cisco\network\proto\v844\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844MobArmorEquipmentPacket extends MobArmorEquipmentPacket{

	static public function fromLatest(MobArmorEquipmentPacket $packet) : self {
		$result = new self();
		$result->actorRuntimeId = $packet->actorRuntimeId;
		$result->head = $packet->head;
		$result->body = $packet->body;
		$result->chest = $packet->chest;
		$result->legs = $packet->legs;
		$result->feet = $packet->feet;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->head = CommonTypes::getItemStackWrapper($in);
		$this->chest = CommonTypes::getItemStackWrapper($in);
		$this->legs = CommonTypes::getItemStackWrapper($in);
		$this->feet = CommonTypes::getItemStackWrapper($in);
		$this->body = CommonTypes::getItemStackWrapper($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		CommonTypes::putItemStackWrapper($out, $this->head);
		CommonTypes::putItemStackWrapper($out, $this->chest);
		CommonTypes::putItemStackWrapper($out, $this->legs);
		CommonTypes::putItemStackWrapper($out, $this->feet);
		CommonTypes::putItemStackWrapper($out, $this->body);
	}
}
