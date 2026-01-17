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
use cisco\network\proto\v419\structure\v419ProtocolInfo;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\entity\Attribute;
use pocketmine\network\mcpe\protocol\types\entity\UpdateAttribute;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use function array_map;

class v419UpdateAttributesPacket extends UpdateAttributesPacket
{
	public const NETWORK_ID = v419ProtocolInfo::UPDATE_ATTRIBUTES_PACKET;

	/** @var Attribute[] */
	public array $attributes = [];

	public static function fromLatest(UpdateAttributesPacket $packet) : self
	{
		$result = new self();
		$result->actorRuntimeId = $packet->actorRuntimeId;
		$result->attributes = array_map(function (UpdateAttribute $attribute) {
			return new Attribute($attribute->getId(), $attribute->getMin(), $attribute->getMax(), $attribute->getCurrent(), $attribute->getDefault(), []);
		}, $packet->entries);
		$result->tick = $packet->tick;
		return $result;
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		v419CommonTypes::putAttributeList($out, ...$this->attributes);
		VarInt::writeUnsignedLong($out, $this->tick);
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->attributes = v419CommonTypes::getAttributeList($in);
		$this->tick = VarInt::readUnsignedLong($in);
	}

}
