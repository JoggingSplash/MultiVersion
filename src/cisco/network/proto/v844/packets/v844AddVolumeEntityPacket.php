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

use cisco\network\utils\RawPacketHelper;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\AddVolumeEntityPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;

class v844AddVolumeEntityPacket extends AddVolumeEntityPacket {

	private int $entityNetId;
	/** @phpstan-var CacheableNbt<\pocketmine\nbt\tag\CompoundTag> */
	private CacheableNbt $data;
	private string $jsonIdentifier;
	private string $instanceName;
	private BlockPosition $minBound;
	private BlockPosition $maxBound;
	private int $dimension;
	private string $engineVersion;

	static public function fromLatest(AddVolumeEntityPacket $packet) : self {
		$result = new self();
		$result->entityNetId = $packet->getEntityNetId();
		$result->data = $packet->getData();
		$result->jsonIdentifier = $packet->getJsonIdentifier();
		$result->instanceName = $packet->getInstanceName();
		$result->minBound = $packet->getMinBound();
		$result->maxBound = $packet->getMaxBound();
		$result->dimension = $packet->getDimension();
		$result->engineVersion = $packet->getEngineVersion();
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->entityNetId = VarInt::readUnsignedInt($in);
		$this->data = new CacheableNbt(CommonTypes::getNbtCompoundRoot($in));
		$this->jsonIdentifier = CommonTypes::getString($in);
		$this->instanceName = CommonTypes::getString($in);
		$this->minBound = RawPacketHelper::getUnsignedYBlockPosition($in);
		$this->maxBound = RawPacketHelper::getUnsignedYBlockPosition($in);
		$this->dimension = VarInt::readSignedInt($in);
		$this->engineVersion = CommonTypes::getString($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, $this->entityNetId);
		$out->writeByteArray($this->data->getEncodedNbt());
		CommonTypes::putString($out, $this->jsonIdentifier);
		CommonTypes::putString($out, $this->instanceName);
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->minBound);
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->maxBound);
		VarInt::writeSignedInt($out, $this->dimension);
		CommonTypes::putString($out, $this->engineVersion);
	}
}
