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

namespace cisco\network\proto\v486\packets;

use cisco\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\ChunkPosition;
use const PHP_INT_MAX;

class v486LevelChunkPacket extends LevelChunkPacket
{

	private const CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT = 0xff_ff_ff_ff;
	private const CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT = 0xff_ff_ff_fe;
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

	protected function decodePayload(ByteBufferReader $in) : void
	{
		ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "chunkPosition", ChunkPosition::read($in));
		$subChunkCountButNotReally = VarInt::readUnsignedInt($in);
		if ($subChunkCountButNotReally === self::CLIENT_REQUEST_FULL_COLUMN_FAKE_COUNT) {
			ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "clientSubChunkRequestsEnabled", true);
			ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "subChunkCount", PHP_INT_MAX);
		} elseif ($subChunkCountButNotReally === self::CLIENT_REQUEST_TRUNCATED_COLUMN_FAKE_COUNT) {
			ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "clientSubChunkRequestsEnabled", true);
			ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "subChunkCount", LE::readUnsignedShort($in));
		} else {
			ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "clientSubChunkRequestsEnabled", false);
			ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "subChunkCount", $subChunkCountButNotReally);
		}

		$cacheEnabled = CommonTypes::getBool($in);
		if ($cacheEnabled) {
			$usedBlobHashes = [];
			$count = VarInt::readUnsignedInt($in);
			if ($count > self::MAX_BLOB_HASHES) {
				throw new PacketDecodeException("Expected at most " . self::MAX_BLOB_HASHES . " blob hashes, got " . $count);
			}
			for ($i = 0; $i < $count; ++$i) {
				$usedBlobHashes[] = LE::readUnsignedLong($in);
			}
			ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "usedBlobHashes", $usedBlobHashes);
		}
		ReflectionUtils::setProperty(LevelChunkPacket::class, $this, "extraPayload", CommonTypes::getString($in));
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

		CommonTypes::putBool($out, false);
		CommonTypes::putString($out, $this->getExtraPayload());
	}
}
