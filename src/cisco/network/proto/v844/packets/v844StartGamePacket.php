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

namespace cisco\network\proto\v844\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\BlockPaletteEntry;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\network\mcpe\protocol\types\LevelSettings;
use pocketmine\network\mcpe\protocol\types\NetworkPermissions;
use pocketmine\network\mcpe\protocol\types\PlayerMovementSettings;
use function count;

class v844StartGamePacket extends StartGamePacket
{

	private bool $enableTickDeathSystems;

	static public function fromLatest(StartGamePacket $packet) : self {
		$npk = new self();
		$npk->actorUniqueId = $packet->actorUniqueId;
		$npk->actorRuntimeId = $packet->actorRuntimeId;
		$npk->playerGamemode = $packet->playerGamemode;
		$npk->playerPosition = $packet->playerPosition;
		$npk->pitch = $packet->pitch;
		$npk->yaw = $packet->yaw;
		$npk->levelSettings = $packet->levelSettings;
		$npk->levelId = $packet->levelId;
		$npk->worldName = $packet->worldName;
		$npk->premiumWorldTemplateId = $packet->premiumWorldTemplateId;
		$npk->isTrial = $packet->isTrial;
		$npk->playerMovementSettings = $packet->playerMovementSettings;
		$npk->currentTick = $packet->currentTick;
		$npk->enchantmentSeed = $packet->enchantmentSeed;
		$npk->blockPalette = $packet->blockPalette;
		$npk->multiplayerCorrelationId = $packet->multiplayerCorrelationId;
		$npk->enableNewInventorySystem = $packet->enableNewInventorySystem;
		$npk->serverSoftwareVersion = $packet->serverSoftwareVersion;
		$npk->playerActorProperties = $packet->playerActorProperties;
		$npk->blockPaletteChecksum = $packet->blockPaletteChecksum;
		$npk->worldTemplateId = $packet->worldTemplateId;
		$npk->enableClientSideChunkGeneration = $packet->enableClientSideChunkGeneration;
		$npk->blockNetworkIdsAreHashes = $packet->blockNetworkIdsAreHashes;
		$npk->enableTickDeathSystems = false;
		$npk->networkPermissions = $packet->networkPermissions;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void {
		$this->actorUniqueId = CommonTypes::getActorUniqueId($in);
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->playerGamemode = VarInt::readSignedInt($in);

		$this->playerPosition = CommonTypes::getVector3($in);

		$this->pitch = LE::readFloat($in);
		$this->yaw = LE::readFloat($in);

		$this->levelSettings = LevelSettings::read($in);

		$this->levelId = CommonTypes::getString($in);
		$this->worldName = CommonTypes::getString($in);
		$this->premiumWorldTemplateId = CommonTypes::getString($in);
		$this->isTrial = CommonTypes::getBool($in);
		$this->playerMovementSettings = PlayerMovementSettings::read($in);
		$this->currentTick = LE::readUnsignedLong($in);

		$this->enchantmentSeed = VarInt::readSignedInt($in);

		$this->blockPalette = [];
		for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
			$blockName = CommonTypes::getString($in);
			$state = CommonTypes::getNbtCompoundRoot($in);
			$this->blockPalette[] = new BlockPaletteEntry($blockName, new CacheableNbt($state));
		}

		$this->multiplayerCorrelationId = CommonTypes::getString($in);
		$this->enableNewInventorySystem = CommonTypes::getBool($in);
		$this->serverSoftwareVersion = CommonTypes::getString($in);
		$this->playerActorProperties = new CacheableNbt(nbtRoot: CommonTypes::getNbtCompoundRoot($in));
		$this->blockPaletteChecksum = LE::readUnsignedLong($in);
		$this->worldTemplateId = CommonTypes::getUUID($in);
		$this->enableClientSideChunkGeneration = CommonTypes::getBool($in);
		$this->blockNetworkIdsAreHashes = CommonTypes::getBool($in);
		$this->enableTickDeathSystems = CommonTypes::getBool($in);
		$this->networkPermissions = NetworkPermissions::decode($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putActorUniqueId($out, $this->actorUniqueId);
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		VarInt::writeSignedInt($out, $this->playerGamemode);

		CommonTypes::putVector3($out, $this->playerPosition);

		LE::writeFloat($out, $this->pitch);
		LE::writeFloat($out, $this->yaw);

		$this->levelSettings->write($out);

		CommonTypes::putString($out, $this->levelId);
		CommonTypes::putString($out, $this->worldName);
		CommonTypes::putString($out, $this->premiumWorldTemplateId);
		CommonTypes::putBool($out, $this->isTrial);
		$this->playerMovementSettings->write($out);
		LE::writeUnsignedLong($out, $this->currentTick);

		VarInt::writeSignedInt($out, $this->enchantmentSeed);

		VarInt::writeUnsignedInt($out, count($this->blockPalette));
		foreach ($this->blockPalette as $entry) {
			CommonTypes::putString($out, $entry->getName());
			$out->writeByteArray($entry->getStates()->getEncodedNbt());
		}

		CommonTypes::putString($out, $this->multiplayerCorrelationId);
		CommonTypes::putBool($out, $this->enableNewInventorySystem);
		CommonTypes::putString($out, $this->serverSoftwareVersion);
		$out->writeByteArray($this->playerActorProperties->getEncodedNbt());
		LE::writeUnsignedLong($out, $this->blockPaletteChecksum);
		CommonTypes::putUUID($out, $this->worldTemplateId);
		CommonTypes::putBool($out, $this->enableClientSideChunkGeneration);
		CommonTypes::putBool($out, $this->blockNetworkIdsAreHashes);
		CommonTypes::putBool($out, $this->enableTickDeathSystems);
		$this->networkPermissions->encode($out);
	}
}
