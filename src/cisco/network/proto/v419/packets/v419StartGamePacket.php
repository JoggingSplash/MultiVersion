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

use cisco\network\proto\v419\packets\types\v419LevelSettings;
use cisco\network\proto\v419\packets\types\v419PlayerMovementSettings;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\BlockPaletteEntry;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use function count;

class v419StartGamePacket extends StartGamePacket
{

	public const NETWORK_ID = ProtocolInfo::START_GAME_PACKET;

	public v419LevelSettings $_levelSettings;
	public v419PlayerMovementSettings $_playerMovementSettings;

	public static function fromLatest(StartGamePacket $packet) : self
	{
		$npk = new self();
		$npk->actorUniqueId = $packet->actorUniqueId;
		$npk->actorRuntimeId = $packet->actorRuntimeId;
		$npk->playerGamemode = $packet->playerGamemode;
		$npk->playerPosition = $packet->playerPosition;
		$npk->pitch = $packet->pitch;
		$npk->yaw = $packet->yaw;
		$npk->_levelSettings = v419LevelSettings::fromLatest($packet->levelSettings);
		$npk->levelId = $packet->levelId;
		$npk->worldName = $packet->worldName;
		$npk->premiumWorldTemplateId = $packet->premiumWorldTemplateId;
		$npk->isTrial = $packet->isTrial;
		$npk->_playerMovementSettings = v419PlayerMovementSettings::fromLatest(); //NOOP
		$npk->currentTick = $packet->currentTick;
		$npk->enchantmentSeed = $packet->enchantmentSeed;
		$npk->blockPalette = $packet->blockPalette;
		$npk->multiplayerCorrelationId = $packet->multiplayerCorrelationId;
		$npk->enableNewInventorySystem = $packet->enableNewInventorySystem;
		$npk->serverSoftwareVersion = $packet->serverSoftwareVersion;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->actorUniqueId = CommonTypes::getActorUniqueId($in);
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->playerGamemode = VarInt::readSignedInt($in);

		$this->playerPosition = CommonTypes::getVector3($in);

		$this->pitch = LE::readFloat($in);
		$this->yaw = LE::readFloat($in);

		$this->_levelSettings = v419LevelSettings::read($in);

		$this->levelId = CommonTypes::getString($in);
		$this->worldName = CommonTypes::getString($in);
		$this->premiumWorldTemplateId = CommonTypes::getString($in);
		$this->isTrial = CommonTypes::getBool($in);
		$this->_playerMovementSettings = v419PlayerMovementSettings::read($in);
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
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putActorUniqueId($out, $this->actorUniqueId);
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		VarInt::writeSignedInt($out, $this->playerGamemode);

		CommonTypes::putVector3($out, $this->playerPosition);

		LE::writeFloat($out, $this->pitch);
		LE::writeFloat($out, $this->yaw);

		$this->_levelSettings->write($out);

		CommonTypes::putString($out, $this->levelId);
		CommonTypes::putString($out, $this->worldName);
		CommonTypes::putString($out, $this->premiumWorldTemplateId);
		CommonTypes::putBool($out, $this->isTrial);
		$this->_playerMovementSettings->write($out);
		LE::writeUnsignedLong($out, $this->currentTick);

		VarInt::writeSignedInt($out, $this->enchantmentSeed);

		VarInt::writeUnsignedInt($out, count($this->blockPalette));
		foreach ($this->blockPalette as $entry) {
			CommonTypes::putString($out, $entry->getName());
			$out->writeByteArray($entry->getStates()->getEncodedNbt());
		}

		VarInt::writeUnsignedInt($out, 0);
		CommonTypes::putString($out, $this->multiplayerCorrelationId);
		CommonTypes::putBool($out, $this->enableNewInventorySystem);
	}

}
