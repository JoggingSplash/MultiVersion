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

use cisco\network\proto\v419\structure\v419CommonTypes;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v419MobArmorEquipmentPacket extends MobArmorEquipmentPacket
{

	public static function fromLatest(MobArmorEquipmentPacket $packet) : self
	{
		$npk = new self();
		$npk->actorRuntimeId = $packet->actorRuntimeId;
		$npk->head = $packet->head;
		$npk->chest = $packet->chest;
		$npk->legs = $packet->legs;
		$npk->feet = $packet->feet;
		return $npk;
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		v419CommonTypes::putItemStackWrapper($out, $this->head);
		v419CommonTypes::putItemStackWrapper($out, $this->chest);
		v419CommonTypes::putItemStackWrapper($out, $this->legs);
		v419CommonTypes::putItemStackWrapper($out, $this->feet);
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->head = v419CommonTypes::getItemStackWrapper($in);
		$this->chest = v419CommonTypes::getItemStackWrapper($in);
		$this->legs = v419CommonTypes::getItemStackWrapper($in);
		$this->feet = v419CommonTypes::getItemStackWrapper($in);
	}

}
