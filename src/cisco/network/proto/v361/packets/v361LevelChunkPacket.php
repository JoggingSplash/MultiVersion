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
use cisco\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\ChunkPosition;

class v361LevelChunkPacket extends LevelChunkPacket {

	public const NETWORK_ID = v361ProtocolInfo::LEVEL_CHUNK_PACKET;

	static public function fromLatest(LevelChunkPacket $packet) : self  {
		$npk = new self();
		ReflectionUtils::setProperty(LevelChunkPacket::class, $npk, "chunkPosition", $packet->getChunkPosition());
		ReflectionUtils::setProperty(LevelChunkPacket::class, $npk, "subChunkCount", $packet->getSubChunkCount());
		ReflectionUtils::setProperty(LevelChunkPacket::class, $npk, "usedBlobHashes", $packet->getUsedBlobHashes());
		ReflectionUtils::setProperty(LevelChunkPacket::class, $npk, "extraPayload", $packet->getExtraPayload());
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void {
		ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "chunkPosition", ChunkPosition::read($in));
		ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "subChunkCount", VarInt::readUnsignedInt($in));

		$cacheEnabled = CommonTypes::getBool($in);
		if ($cacheEnabled) {
			$usedBlobHashes = [];
			$count = VarInt::readUnsignedInt($in);
			for ($i = 0; $i < $count; ++$i) {
				$usedBlobHashes[] = LE::readUnsignedLong($in);
			}
			ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "usedBlobHashes", $usedBlobHashes);
		}
		ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "extraPayload", CommonTypes::getString($in));
	}

	protected function encodePayload(ByteBufferWriter $out) : void {
		$this->getChunkPosition()->write($out);
		VarInt::writeUnsignedInt($out, $this->getSubChunkCount());
		CommonTypes::writeOptional($out, $this->getUsedBlobHashes(), function(ByteBufferWriter $out, array $integers) : void {
			foreach ($integers as $integer) {
				LE::writeUnsignedLong($out, $integer);
			}
		});
		CommonTypes::putString($out, $this->getExtraPayload());
	}
}
