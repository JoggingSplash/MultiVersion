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
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v419AddItemActorPacket extends AddItemActorPacket
{

	static public function fromLatest(AddItemActorPacket $packet) : self
	{
		$npk = new self();
		$npk->actorRuntimeId = $packet->actorRuntimeId;
		$npk->actorUniqueId = $packet->actorUniqueId;
		$npk->item = $packet->item;
		$npk->position = $packet->position;
		$npk->metadata = $packet->metadata;
		$npk->motion = $packet->motion;
		$npk->isFromFishing = $packet->isFromFishing;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorUniqueId = CommonTypes::getActorUniqueId($in);
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->item = v419CommonTypes::getItemStackWrapper($in);
		$this->position = CommonTypes::getVector3($in);
		$this->motion = CommonTypes::getVector3($in);
		$this->metadata = v419CommonTypes::getEntityMetadata($in);
		$this->isFromFishing = CommonTypes::getBool($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putActorUniqueId($out, $this->actorUniqueId);
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		v419CommonTypes::putItemStackWrapper($out, $this->item);
		CommonTypes::putVector3($out, $this->position);
		CommonTypes::putVector3Nullable($out, $this->motion);
		v419CommonTypes::putEntityMetadata($out, $this->metadata);
		CommonTypes::putBool($out, $this->isFromFishing);
	}
}
