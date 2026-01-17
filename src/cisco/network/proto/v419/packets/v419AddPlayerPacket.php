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
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\AbilitiesData;
use pocketmine\network\mcpe\protocol\types\entity\PropertySyncData;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use function count;

class v419AddPlayerPacket extends AddPlayerPacket
{
	public const NETWORK_ID = v419ProtocolInfo::ADD_PLAYER_PACKET;

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

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->uuid = CommonTypes::getUUID($in);
		$this->username = CommonTypes::getString($in);
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->platformChatId = CommonTypes::getString($in);
		$this->position = CommonTypes::getVector3($in);
		$this->motion = CommonTypes::getVector3($in);
		$this->pitch = LE::readFloat($in);
		$this->yaw = LE::readFloat($in);
		$this->headYaw = LE::readFloat($in);
		$this->item = v419CommonTypes::getItemStackWrapper($in);
		$this->gameMode = VarInt::readSignedInt($in);
		$this->metadata = v419CommonTypes::getEntityMetadata($in);
		$this->syncedProperties = PropertySyncData::read($in);

		$packet = new v419AdventureSettingsPacket();
		$packet->decodePayload($in);

		$this->abilitiesPacket = UpdateAbilitiesPacket::create(
			new AbilitiesData(
				$packet->commandPermission,
				$packet->playerPermission,
				$packet->targetActorUniqueId,
				[]
			)
		);

		$linkCount = VarInt::readUnsignedInt($in);
		for ($i = 0; $i < $linkCount; ++$i) {
			$this->links[$i] = CommonTypes::getEntityLink($in);
		}

		$this->deviceId = CommonTypes::getString($in);
		$this->buildPlatform = LE::readSignedInt($in);
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
		v419CommonTypes::putItemStackWrapper($out, $this->item);
		v419CommonTypes::putEntityMetadata($out, $this->metadata);

		$packet = v419AdventureSettingsPacket::create(
			0,
			$data->getCommandPermission()->value,
			0,
			$data->getPlayerPermission(),
			0,
			$data->getTargetActorUniqueId()
		);
		$packet->encodePayload($out);

		VarInt::writeUnsignedInt($out, count($this->links));
		foreach ($this->links as $link) {
			CommonTypes::putEntityLink($out, $link);
		}

		CommonTypes::putString($out, $this->deviceId);
		LE::writeSignedInt($out, $this->buildPlatform);
	}

}
