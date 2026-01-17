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

use cisco\network\proto\v361\structure\v361CommonTypes;
use cisco\network\proto\v361\structure\v361ProtocolInfo;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

class v361AddActorPacket extends AddActorPacket {

	public const NETWORK_ID = v361ProtocolInfo::ADD_ACTOR_PACKET;

	static public function fromLatest(AddActorPacket $packet) : self    {
		$npk = new self();
		$npk->actorUniqueId = $packet->actorUniqueId;
		$npk->actorRuntimeId = $packet->actorRuntimeId;
		$npk->type = $packet->type;
		$npk->position = $packet->position;
		$npk->motion = $packet->motion;
		$npk->pitch = $packet->pitch;
		$npk->yaw = $packet->yaw;
		$npk->headYaw = $packet->headYaw;
		$npk->metadata = $packet->metadata;
		$npk->attributes = $packet->attributes;
		$npk->links = $packet->links;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->actorUniqueId = CommonTypes::getActorUniqueId($in);
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->type = CommonTypes::getString($in);
		$this->position = CommonTypes::getVector3($in);
		$this->motion = CommonTypes::readOptional($in, CommonTypes::getVector3(...));
		$this->pitch = LE::readFloat($in);
		$this->yaw = LE::readFloat($in);
		$this->headYaw = LE::readFloat($in);

		$this->attributes = v361CommonTypes::getAttributeList($in);
		$this->metadata = v361CommonTypes::getEntityMetadata($in);

		$linkCount = VarInt::readUnsignedInt($in);
		for($i = 0; $i < $linkCount; ++$i){
			$this->links[] = v361CommonTypes::getEntityLink($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putActorUniqueId($out, $this->actorUniqueId);
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		CommonTypes::putString($out, $this->type);
		CommonTypes::putVector3($out, $this->position);
		CommonTypes::putVector3Nullable($out, $this->motion);
		LE::writeFloat($out, $this->pitch);
		LE::writeFloat($out, $this->yaw);
		LE::writeFloat($out, $this->headYaw);

		v361CommonTypes::putAttributeList($out, ...$this->attributes);

		v361CommonTypes::putEntityMetadata($out, $this->metadata);

		VarInt::writeUnsignedInt($out, count($this->links));
		foreach($this->links as $link){
			v361CommonTypes::putEntityLink($out, $link);
		}
	}
}
