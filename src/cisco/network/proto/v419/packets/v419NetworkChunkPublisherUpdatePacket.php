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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v419NetworkChunkPublisherUpdatePacket extends NetworkChunkPublisherUpdatePacket
{

	public const NETWORK_ID = v419ProtocolInfo::NETWORK_CHUNK_PUBLISHER_UPDATE_PACKET;

	public static function fromLatest(NetworkChunkPublisherUpdatePacket $packet) : self
	{
		$result = new self();
		$result->blockPosition = $packet->blockPosition;
		$result->radius = $packet->radius;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->blockPosition = CommonTypes::getBlockPosition($in);
		$this->radius = VarInt::readUnsignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putBlockPosition($out, $this->blockPosition);
		VarInt::writeUnsignedInt($out, $this->radius);
	}
}
