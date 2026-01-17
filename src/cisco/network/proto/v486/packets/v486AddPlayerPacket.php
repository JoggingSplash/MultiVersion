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
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function count;

class v486AddPlayerPacket extends AddPlayerPacket
{

	public const NETWORK_ID = v486ProtocolInfo::ADD_PLAYER_PACKET;

	public static function fromLatest(AddPlayerPacket $pk) : self
	{
		$npk = new self();
		$npk->uuid = $pk->uuid;
		$npk->username = $pk->username;
		$npk->actorRuntimeId = $pk->actorRuntimeId;
		$npk->platformChatId = $pk->platformChatId;
		$npk->position = $pk->position;
		$npk->motion = $pk->motion;
		$npk->pitch = $pk->pitch;
		$npk->yaw = $pk->yaw;
		$npk->headYaw = $pk->headYaw;
		$npk->item = $pk->item;
		$npk->gameMode = $pk->gameMode;
		$npk->metadata = $pk->metadata;
		$npk->syncedProperties = $pk->syncedProperties;
		$npk->abilitiesPacket = $pk->abilitiesPacket;
		$npk->links = $pk->links;
		$npk->deviceId = $pk->deviceId;
		$npk->buildPlatform = $pk->buildPlatform;
		return $npk;
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		$data = $this->abilitiesPacket->getData();
		CommonTypes::putUUID($out, $this->uuid);
		CommonTypes::putString($out, $this->username);
		CommonTypes::putActorUniqueId($out, $data->getTargetActorUniqueId());
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		CommonTypes::putString($out, $this->platformChatId);
		CommonTypes::putVector3($out, $this->position);
		CommonTypes::putVector3Nullable($out, $this->motion);
		LE::writeFloat($out, $this->pitch);
		LE::writeFloat($out, $this->yaw);
		LE::writeFloat($out, $this->headYaw);
		CommonTypes::putItemStackWrapper($out, $this->item);
		VarInt::writeSignedInt($out, $this->gameMode);
		v486CommonTypes::putEntityMetadata($out, $this->metadata);

		$pk = v486AdventureSettingsPacket::create(
			0,
			$data->getCommandPermission()->value,
			0,
			$data->getPlayerPermission(),
			0,
			$data->getTargetActorUniqueId()
		);
		$pk->encodePayload($out);

		VarInt::writeUnsignedInt($out, count($this->links));
		foreach ($this->links as $link) {
			CommonTypes::putEntityLink($out, $link);
		}

		CommonTypes::putString($out, $this->deviceId);
		LE::writeSignedInt($out, $this->buildPlatform);
	}
}
