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

use cisco\network\proto\v486\structure\v486CommonTypes;
use cisco\network\proto\v486\structure\v486ProtocolInfo;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use function count;

class v486PlayerListPacket extends PlayerListPacket
{

	public const NETWORK_ID = v486ProtocolInfo::PLAYER_LIST_PACKET;

	public static function fromLatest(PlayerListPacket $pk) : self
	{
		$npk = new self();
		$npk->type = $pk->type;
		$npk->entries = $pk->entries;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->type = Byte::readUnsigned($in);
		$count = VarInt::readUnsignedInt($in);
		for ($i = 0; $i < $count; ++$i) {
			$entry = new PlayerListEntry();
			if ($this->type === self::TYPE_ADD) {
				$entry->uuid = CommonTypes::getUUID($in);
				$entry->actorUniqueId = CommonTypes::getActorUniqueId($in);
				$entry->username = CommonTypes::getString($in);
				$entry->xboxUserId = CommonTypes::getString($in);
				$entry->platformChatId = CommonTypes::getString($in);
				$entry->buildPlatform = LE::readSignedInt($in);
				$entry->skinData = v486CommonTypes::getSkin($in);
				$entry->isTeacher = CommonTypes::getBool($in);
				$entry->isHost = CommonTypes::getBool($in);
			} else {
				$entry->uuid = CommonTypes::getUUID($in);
			}

			$this->entries[$i] = $entry;
		}
		if ($this->type === self::TYPE_ADD) {
			for ($i = 0; $i < $count; ++$i) {
				$this->entries[$i]->skinData->setVerified(CommonTypes::getBool($in));
			}
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->type);
		VarInt::writeUnsignedInt($out, count($this->entries));
		foreach ($this->entries as $entry) {

			if ($this->type === self::TYPE_ADD) {
				CommonTypes::putUUID($out, $entry->uuid);
				CommonTypes::putActorUniqueId($out, $entry->actorUniqueId);
				CommonTypes::putString($out, $entry->username);
				CommonTypes::putString($out, $entry->xboxUserId);
				CommonTypes::putString($out, $entry->platformChatId);
				LE::writeSignedInt($out, $entry->buildPlatform);
				v486CommonTypes::putSkin($out, $entry->skinData);
				CommonTypes::putBool($out, $entry->isTeacher);
				CommonTypes::putBool($out, $entry->isHost);
			} else {
				CommonTypes::putUUID($out, $entry->uuid);
			}
		}
		if ($this->type === self::TYPE_ADD) {
			foreach ($this->entries as $entry) {
				CommonTypes::putBool($out, $entry->skinData->isVerified());
			}
		}
	}

}
