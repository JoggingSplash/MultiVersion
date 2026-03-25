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
use cisco\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

class v419LevelChunkPacket extends LevelChunkPacket
{

	public const NETWORK_ID = v419ProtocolInfo::LEVEL_CHUNK_PACKET;

	public static function fromLatest(LevelChunkPacket $pk) : self
	{
		$npk = new self();
		ReflectionUtils::setProperty(LevelChunkPacket::class, $npk, "chunkPosition", $pk->getChunkPosition());
		ReflectionUtils::setProperty(LevelChunkPacket::class, $npk, "subChunkCount", $pk->getSubChunkCount());
		ReflectionUtils::setProperty(LevelChunkPacket::class, $npk, "clientSubChunkRequestsEnabled", $pk->isClientSubChunkRequestEnabled());
		ReflectionUtils::setProperty(LevelChunkPacket::class, $npk, "usedBlobHashes", $pk->getUsedBlobHashes());
		ReflectionUtils::setProperty(LevelChunkPacket::class, $npk, "extraPayload", $pk->getExtraPayload());
		return $npk;
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		$this->getChunkPosition()->write($out);
		VarInt::writeUnsignedInt($out, $this->getSubChunkCount()); // https://github.com/pmmp/BedrockProtocol/blob/bedrock-1.16.100/src/LevelChunkPacket.php
		CommonTypes::putBool($out, $this->getUsedBlobHashes() !== null);
		if($this->getUsedBlobHashes() !== null) {
			VarInt::writeUnsignedInt($out, count($this->getUsedBlobHashes()));
			foreach ($this->getUsedBlobHashes() as $blobHash) {
				LE::writeUnsignedLong($out, $blobHash);
			}
		}
		CommonTypes::putString($out, $this->getExtraPayload());
	}

}
