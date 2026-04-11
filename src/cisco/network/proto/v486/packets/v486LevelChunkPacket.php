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

namespace cisco\network\proto\v486\packets;

use cisco\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\utils\Limits;
use const PHP_INT_MAX;

class v486LevelChunkPacket extends LevelChunkPacket
{

	private const CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT = Limits::UINT32_MAX;
	private const CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT = Limits::UINT32_MAX - 1;
	private const MAX_BLOB_HASHES = 64;

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

		if (!$this->isClientSubChunkRequestEnabled()) {
			VarInt::writeUnsignedInt($out, $this->getSubChunkCount());
		} else {
			if ($this->getSubChunkCount() === PHP_INT_MAX) {
				VarInt::writeUnsignedInt($out, self::CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT);
			} else {
				VarInt::writeUnsignedInt($out, self::CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT);
				LE::writeUnsignedShort($out, $this->getSubChunkCount());
			}
		}

		CommonTypes::putBool($out, $this->getUsedBlobHashes() !== null);
		if($this->getUsedBlobHashes() !== null){
			foreach ($this->getUsedBlobHashes() as $hash){

			}
		}
		CommonTypes::putString($out, $this->getExtraPayload());
	}
}
