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

namespace cisco\network\proto\v486\packets\types;

use cisco\network\proto\v486\structure\v486CommonTypes;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\DataDecodeException;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\EducationEditionOffer;
use pocketmine\network\mcpe\protocol\types\EducationUriResource;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\GameRule;
use pocketmine\network\mcpe\protocol\types\GeneratorType;
use pocketmine\network\mcpe\protocol\types\LevelSettings;
use pocketmine\network\mcpe\protocol\types\MultiplayerGameVisibility;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;
use pocketmine\network\mcpe\protocol\types\SpawnSettings;

final class v486LevelSettings
{

	public int $seed;
	public SpawnSettings $spawnSettings;
	public int $generator = GeneratorType::OVERWORLD;
	public int $worldGamemode;
	public int $difficulty;
	public BlockPosition $spawnPosition;
	public bool $hasAchievementsDisabled = true;
	public int $time = -1;
	public int $eduEditionOffer = EducationEditionOffer::NONE;
	public bool $hasEduFeaturesEnabled = false;
	public string $eduProductUUID = "";
	public float $rainLevel;
	public float $lightningLevel;
	public bool $hasConfirmedPlatformLockedContent = false;
	public bool $isMultiplayerGame = true;
	public bool $hasLANBroadcast = true;
	public int $xboxLiveBroadcastMode = MultiplayerGameVisibility::PUBLIC;
	public int $platformBroadcastMode = MultiplayerGameVisibility::PUBLIC;
	public bool $commandsEnabled;
	public bool $isTexturePacksRequired = true;
	/** @var GameRule[] */
	public array $gameRules = [];
	public Experiments $experiments;
	public bool $hasBonusChestEnabled = false;
	public bool $hasStartWithMapEnabled = false;
	public int $defaultPlayerPermission = PlayerPermissions::MEMBER; //TODO

	public int $serverChunkTickRadius = 4; //TODO (leave as default for now)

	public bool $hasLockedBehaviorPack = false;
	public bool $hasLockedResourcePack = false;
	public bool $isFromLockedWorldTemplate = false;
	public bool $useMsaGamertagsOnly = false;
	public bool $isFromWorldTemplate = false;
	public bool $isWorldTemplateOptionLocked = false;
	public bool $onlySpawnV1Villagers = false;
	public string $vanillaVersion = ProtocolInfo::MINECRAFT_VERSION_NETWORK;
	public int $limitedWorldWidth = 0;
	public int $limitedWorldLength = 0;
	public bool $isNewNether = true;
	public ?EducationUriResource $eduSharedUriResource = null;
	public ?bool $experimentalGameplayOverride = null;

	public static function fromLatest(LevelSettings $levelSettings) : self
	{
		$result = new self();
		$result->seed = $levelSettings->seed;
		$result->spawnSettings = $levelSettings->spawnSettings;
		$result->generator = $levelSettings->generator;
		$result->worldGamemode = $levelSettings->worldGamemode;
		$result->difficulty = $levelSettings->difficulty;
		$result->spawnPosition = $levelSettings->spawnPosition;
		$result->hasAchievementsDisabled = $levelSettings->hasAchievementsDisabled;
		$result->time = $levelSettings->time;
		$result->eduEditionOffer = $levelSettings->eduEditionOffer;
		$result->hasEduFeaturesEnabled = $levelSettings->hasEduFeaturesEnabled;
		$result->eduProductUUID = $levelSettings->eduProductUUID;
		$result->rainLevel = $levelSettings->rainLevel;
		$result->lightningLevel = $levelSettings->lightningLevel;
		$result->hasConfirmedPlatformLockedContent = $levelSettings->hasConfirmedPlatformLockedContent;
		$result->isMultiplayerGame = $levelSettings->isMultiplayerGame;
		$result->hasLANBroadcast = $levelSettings->hasLANBroadcast;
		$result->xboxLiveBroadcastMode = $levelSettings->xboxLiveBroadcastMode;
		$result->platformBroadcastMode = $levelSettings->platformBroadcastMode;
		$result->commandsEnabled = $levelSettings->commandsEnabled;
		$result->isTexturePacksRequired = $levelSettings->isTexturePacksRequired;
		$result->gameRules = $levelSettings->gameRules;
		$result->experiments = $levelSettings->experiments;
		$result->hasBonusChestEnabled = $levelSettings->hasBonusChestEnabled;
		$result->hasStartWithMapEnabled = $levelSettings->hasStartWithMapEnabled;
		$result->defaultPlayerPermission = $levelSettings->defaultPlayerPermission;
		$result->serverChunkTickRadius = $levelSettings->serverChunkTickRadius;
		$result->hasLockedBehaviorPack = $levelSettings->hasLockedBehaviorPack;
		$result->hasLockedResourcePack = $levelSettings->hasLockedResourcePack;
		$result->isFromLockedWorldTemplate = $levelSettings->isFromLockedWorldTemplate;
		$result->useMsaGamertagsOnly = $levelSettings->useMsaGamertagsOnly;
		$result->isFromWorldTemplate = $levelSettings->isFromWorldTemplate;
		$result->isWorldTemplateOptionLocked = $levelSettings->isWorldTemplateOptionLocked;
		$result->onlySpawnV1Villagers = $levelSettings->onlySpawnV1Villagers;
		$result->vanillaVersion = $levelSettings->vanillaVersion;
		$result->limitedWorldWidth = $levelSettings->limitedWorldWidth;
		$result->limitedWorldLength = $levelSettings->limitedWorldLength;
		$result->isNewNether = $levelSettings->isNewNether;
		$result->eduSharedUriResource = $levelSettings->eduSharedUriResource;
		$result->experimentalGameplayOverride = $levelSettings->experimentalGameplayOverride;
		return $result;
	}

	/**
	 * @throws DataDecodeException
	 * @throws PacketDecodeException
	 */
	public static function read(ByteBufferReader $in) : self
	{
		//TODO: in the future we'll use promoted properties + named arguments for decoding, but for now we stick with
		//this shitty way to limit BC breaks (needs more R&D)
		$result = new self();
		$result->internalRead($in);
		return $result;
	}

	/**
	 * @throws DataDecodeException
	 * @throws PacketDecodeException
	 */
	private function internalRead(ByteBufferReader $in) : void
	{
		$this->seed = VarInt::readSignedInt($in);
		$this->spawnSettings = SpawnSettings::read($in);
		$this->generator = VarInt::readSignedInt($in);
		$this->worldGamemode = VarInt::readSignedInt($in);
		$this->difficulty = VarInt::readSignedInt($in);
		$this->spawnPosition = CommonTypes::getBlockPosition($in);
		$this->hasAchievementsDisabled = CommonTypes::getBool($in);
		$this->time = VarInt::readSignedInt($in);
		$this->eduEditionOffer = VarInt::readSignedInt($in);
		$this->hasEduFeaturesEnabled = CommonTypes::getBool($in);
		$this->eduProductUUID = CommonTypes::getString($in);
		$this->rainLevel = LE::readFloat($in);
		$this->lightningLevel = LE::readFloat($in);
		$this->hasConfirmedPlatformLockedContent = CommonTypes::getBool($in);
		$this->isMultiplayerGame = CommonTypes::getBool($in);
		$this->hasLANBroadcast = CommonTypes::getBool($in);
		$this->xboxLiveBroadcastMode = VarInt::readSignedInt($in);
		$this->platformBroadcastMode = VarInt::readSignedInt($in);
		$this->commandsEnabled = CommonTypes::getBool($in);
		$this->isTexturePacksRequired = CommonTypes::getBool($in);
		$this->gameRules = v486CommonTypes::getGameRules($in, true);
		$this->experiments = Experiments::read($in);
		$this->hasBonusChestEnabled = CommonTypes::getBool($in);
		$this->hasStartWithMapEnabled = CommonTypes::getBool($in);
		$this->defaultPlayerPermission = VarInt::readSignedInt($in);
		$this->serverChunkTickRadius = LE::readSignedInt($in); //doesn't make sense for this to be signed, but that's what the spec says
		$this->hasLockedBehaviorPack = CommonTypes::getBool($in);
		$this->hasLockedResourcePack = CommonTypes::getBool($in);
		$this->isFromLockedWorldTemplate = CommonTypes::getBool($in);
		$this->useMsaGamertagsOnly = CommonTypes::getBool($in);
		$this->isFromWorldTemplate = CommonTypes::getBool($in);
		$this->isWorldTemplateOptionLocked = CommonTypes::getBool($in);
		$this->onlySpawnV1Villagers = CommonTypes::getBool($in);
		$this->vanillaVersion = CommonTypes::getString($in);
		$this->limitedWorldWidth = LE::readSignedInt($in); //doesn't make sense for this to be signed, but that's what the spec says
		$this->limitedWorldLength = LE::readSignedInt($in); //same as above
		$this->isNewNether = CommonTypes::getBool($in);
		$this->eduSharedUriResource = EducationUriResource::read($in);
		$this->experimentalGameplayOverride = CommonTypes::readOptional($in, CommonTypes::getBool(...));
	}

	public function write(ByteBufferWriter $out) : void
	{
		VarInt::writeSignedInt($out, $this->seed);
		$this->spawnSettings->write($out);
		VarInt::writeSignedInt($out, $this->generator);
		VarInt::writeSignedInt($out, $this->worldGamemode);
		VarInt::writeSignedInt($out, $this->difficulty);
		CommonTypes::putBlockPosition($out, $this->spawnPosition);
		CommonTypes::putBool($out, $this->hasAchievementsDisabled);
		VarInt::writeSignedInt($out, $this->time);
		VarInt::writeSignedInt($out, $this->eduEditionOffer);
		CommonTypes::putBool($out, $this->hasEduFeaturesEnabled);
		CommonTypes::putString($out, $this->eduProductUUID);
		LE::writeFloat($out, $this->rainLevel);
		LE::writeFloat($out, $this->lightningLevel);
		CommonTypes::putBool($out, $this->hasConfirmedPlatformLockedContent);
		CommonTypes::putBool($out, $this->isMultiplayerGame);
		CommonTypes::putBool($out, $this->hasLANBroadcast);
		VarInt::writeSignedInt($out, $this->xboxLiveBroadcastMode);
		VarInt::writeSignedInt($out, $this->platformBroadcastMode);
		CommonTypes::putBool($out, $this->commandsEnabled);
		CommonTypes::putBool($out, $this->isTexturePacksRequired);
		v486CommonTypes::putGameRules($out, $this->gameRules, true);
		$this->experiments->write($out);
		CommonTypes::putBool($out, $this->hasBonusChestEnabled);
		CommonTypes::putBool($out, $this->hasStartWithMapEnabled);
		VarInt::writeSignedInt($out, $this->defaultPlayerPermission);
		LE::writeSignedInt($out, $this->serverChunkTickRadius); //doesn't make sense for this to be signed, but that's what the spec says
		CommonTypes::putBool($out, $this->hasLockedBehaviorPack);
		CommonTypes::putBool($out, $this->hasLockedResourcePack);
		CommonTypes::putBool($out, $this->isFromLockedWorldTemplate);
		CommonTypes::putBool($out, $this->useMsaGamertagsOnly);
		CommonTypes::putBool($out, $this->isFromWorldTemplate);
		CommonTypes::putBool($out, $this->isWorldTemplateOptionLocked);
		CommonTypes::putBool($out, $this->onlySpawnV1Villagers);
		CommonTypes::putString($out, $this->vanillaVersion);
		LE::writeSignedInt($out, $this->limitedWorldWidth); //doesn't make sense for this to be signed, but that's what the spec says
		LE::writeSignedInt($out, $this->limitedWorldLength); //same as above
		CommonTypes::putBool($out, $this->isNewNether);
		($this->eduSharedUriResource ?? new EducationUriResource("", ""))->write($out);
		CommonTypes::writeOptional($out, $this->experimentalGameplayOverride, CommonTypes::putBool(...));
	}

}
