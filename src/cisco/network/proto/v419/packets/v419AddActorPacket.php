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
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\entity\Attribute;
use function count;

class v419AddActorPacket extends AddActorPacket
{

	public const NETWORK_ID = v419ProtocolInfo::ADD_ACTOR_PACKET;

	/**
	 * @param AddActorPacket $packet
	 */
	public static function fromLatest(ClientboundPacket $packet) : self
	{
		$npk = new self();
		$npk->actorUniqueId = $packet->actorUniqueId;
		$npk->actorRuntimeId = $packet->actorRuntimeId;
		$npk->type = $packet->type;
		$npk->position = $packet->position;
		$npk->motion = $packet->motion;
		$npk->pitch = $packet->pitch;
		$npk->yaw = $packet->yaw;
		$npk->headYaw = $packet->headYaw;
		$npk->attributes = $packet->attributes;
		$npk->metadata = $packet->metadata;
		$npk->links = $packet->links;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorUniqueId = CommonTypes::getActorUniqueId($in);
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->type = CommonTypes::getString($in);
		$this->position = CommonTypes::getVector3($in);
		$this->motion = CommonTypes::getVector3($in);
		$this->pitch = LE::readFloat($in);
		$this->yaw = LE::readFloat($in);
		$this->headYaw = LE::readFloat($in);

		$attrCount = VarInt::readUnsignedInt($in);
		for ($i = 0; $i < $attrCount; ++$i) {
			$id = CommonTypes::getString($in);
			$min = LE::readFloat($in);
			$current = LE::readFloat($in);
			$max = LE::readFloat($in);
			$this->attributes[] = new Attribute($id, $min, $max, $current, $current, []);
		}

		$this->metadata = CommonTypes::getEntityMetadata($in);

		$linkCount = VarInt::readUnsignedInt($in);
		for ($i = 0; $i < $linkCount; ++$i) {
			$this->links[] = CommonTypes::getEntityLink($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putActorUniqueId($out, $this->actorUniqueId);
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		CommonTypes::putString($out, $this->type);
		CommonTypes::putVector3($out, $this->position);
		CommonTypes::putVector3Nullable($out, $this->motion);
		LE::writeFloat($out, $this->pitch);
		LE::writeFloat($out, $this->yaw);
		LE::writeFloat($out, $this->headYaw);

		VarInt::writeUnsignedInt($out, count($this->attributes));
		foreach ($this->attributes as $attribute) {
			CommonTypes::putString($out, $attribute->getId());
			LE::writeFloat($out, $attribute->getMin());
			LE::writeFloat($out, $attribute->getCurrent());
			LE::writeFloat($out, $attribute->getMax());
		}

		CommonTypes::putEntityMetadata($out, $this->metadata);

		VarInt::writeUnsignedInt($out, count($this->links));
		foreach ($this->links as $link) {
			CommonTypes::putEntityLink($out, $link);
		}
	}

}
