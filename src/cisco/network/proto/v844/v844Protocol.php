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

namespace cisco\network\proto\v844;

use cisco\Loader;
use cisco\network\mcpe\MVRuntimeIdToStateId;
use cisco\network\proto\latest\LatestProtocol;
use cisco\network\proto\v844\packets\v844ActorEventPacket;
use cisco\network\proto\v844\packets\v844AddVolumeEntityPacket;
use cisco\network\proto\v844\packets\v844AnimatePacket;
use cisco\network\proto\v844\packets\v844AnvilDamagePacket;
use cisco\network\proto\v844\packets\v844BlockActorDataPacket;
use cisco\network\proto\v844\packets\v844BlockEventPacket;
use cisco\network\proto\v844\packets\v844ContainerOpenPacket;
use cisco\network\proto\v844\packets\v844DisconnectPacket;
use cisco\network\proto\v844\packets\v844InteractPacket;
use cisco\network\proto\v844\packets\v844InventorySlotPacket;
use cisco\network\proto\v844\packets\v844InventoryTransactionPacket;
use cisco\network\proto\v844\packets\v844LevelSoundEventPacket;
use cisco\network\proto\v844\packets\v844MobEffectPacket;
use cisco\network\proto\v844\packets\v844NetworkChunkPublisherUpdatePacket;
use cisco\network\proto\v844\packets\v844OpenSignPacket;
use cisco\network\proto\v844\packets\v844PlayerActionPacket;
use cisco\network\proto\v844\packets\v844PlaySoundPacket;
use cisco\network\proto\v844\packets\v844ResourcePackStackPacket;
use cisco\network\proto\v844\packets\v844SetSpawnPositionPacket;
use cisco\network\proto\v844\packets\v844StartGamePacket;
use cisco\network\proto\v844\packets\v844TextPacket;
use cisco\network\proto\v844\packets\v844UpdateBlockPacket;
use cisco\network\proto\v844\structure\v844PacketPool;
use cisco\network\proto\v844\structure\v844StaticPacketCache;
use cisco\network\utils\RawPacketHelper;
use pocketmine\crafting\CraftingManager;
use pocketmine\crafting\CraftingManagerFromDataHelper;
use pocketmine\network\mcpe\cache\CraftingDataCache;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AddVolumeEntityPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\AnvilDamagePacket;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\BlockEventPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\OpenSignPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\SetSpawnPositionPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use Symfony\Component\Filesystem\Path;

class v844Protocol extends LatestProtocol
{

	private v844StaticPacketCache $staticPacketCache;
	private CraftingManager $craftingManager;

	public function __construct()
	{
		parent::__construct();
		unset($this->packetPool);
		$this->packetPool = new v844PacketPool();
		unset($this->typeConverter);
		$this->typeConverter = v844TypeConverter::getInstance()->getConverter();
		$this->craftingManager = CraftingManagerFromDataHelper::make(Path::join(Loader::getPluginResourcePath(), "v844", "recipes"));
		$this->staticPacketCache = new v844StaticPacketCache($this);
	}

	public function getProtocolId() : int
	{
		return 844;
	}

	public function __toString() : string {
		return "v1.21.114";
	}

	public function hasDebug() : bool {
		return true;
	}

	public function getStaticPacketCache() : v844StaticPacketCache
	{
		return $this->staticPacketCache;
	}

	public function getCraftingManager() : CraftingManager
	{
		return $this->craftingManager;
	}

	public function incoming(ServerboundPacket $packet) : ?ServerboundPacket {
		return match (true) {
			$packet instanceof v844AnimatePacket => AnimatePacket::create($packet->actorRuntimeId, $packet->action, $packet->data, null),
			$packet instanceof v844InteractPacket => RawPacketHelper::translateInteractPacketToLatest($packet),
			$packet instanceof v844InventoryTransactionPacket => InventoryTransactionPacket::create($packet->requestId, $packet->requestChangedSlots, $packet->trData),
			$packet instanceof v844LevelSoundEventPacket => LevelSoundEventPacket::create($packet->sound, $packet->position, $packet->extraData, $packet->entityType, $packet->isBabyMob, $packet->disableRelativeVolume, $packet->actorUniqueId, null),
			$packet instanceof v844DisconnectPacket => DisconnectPacket::create($packet->reason, $packet->message, $packet->filteredMessage),
			$packet instanceof v844ActorEventPacket => ActorEventPacket::create($packet->actorRuntimeId, $packet->eventId, $packet->eventData, null),
			default => parent::incoming($packet)
		};
	}

	public function outcoming(ClientboundPacket $packet) : ?ClientboundPacket
	{

		if ($packet instanceof UpdateBlockPacket) {
			$packet->blockRuntimeId = $this->getTypeConverter()->getMVBlockTranslator()->internalIdToNetworkId(MVRuntimeIdToStateId::getInstance()->getStateIdFromRuntimeId($packet->blockRuntimeId));
			return v844UpdateBlockPacket::fromLatest($packet);
		}

		return match (true) {
			$packet instanceof LevelSoundEventPacket => v844LevelSoundEventPacket::fromLatest($packet),
			$packet instanceof PlayerActionPacket => v844PlayerActionPacket::fromLatest($packet),
			$packet instanceof PlaySoundPacket => v844PlaySoundPacket::fromLatest($packet),
			$packet instanceof SetSpawnPositionPacket => v844SetSpawnPositionPacket::fromLatest($packet),
			$packet instanceof ContainerOpenPacket => v844ContainerOpenPacket::fromLatest($packet),
			$packet instanceof BlockEventPacket => v844BlockEventPacket::fromLatest($packet),
			$packet instanceof BlockActorDataPacket => v844BlockActorDataPacket::fromLatest($packet),
			$packet instanceof AddVolumeEntityPacket => v844AddVolumeEntityPacket::fromLatest($packet),
			$packet instanceof AnvilDamagePacket => v844AnvilDamagePacket::fromLatest($packet),
			$packet instanceof OpenSignPacket => v844OpenSignPacket::fromLatest($packet),
			$packet instanceof InventoryTransactionPacket => v844InventoryTransactionPacket::fromLatest($packet),
			$packet instanceof AnimatePacket => v844AnimatePacket::fromLatest($packet),
			$packet instanceof BiomeDefinitionListPacket => $this->staticPacketCache->getBiomeDefinitionListPacket(),
			$packet instanceof ResourcePackStackPacket => v844ResourcePackStackPacket::fromLatest($packet),
			$packet instanceof StartGamePacket => v844StartGamePacket::fromLatest($packet),
			$packet instanceof TextPacket => v844TextPacket::fromLatest($packet),
			$packet instanceof MobEffectPacket => v844MobEffectPacket::fromLatest($packet),
			$packet instanceof NetworkChunkPublisherUpdatePacket => v844NetworkChunkPublisherUpdatePacket::fromLatest($packet),
			$packet instanceof CraftingDataPacket => CraftingDataCache::getInstance()->getCache($this->craftingManager),
			$packet instanceof AvailableCommandsPacket => null,
			$packet instanceof DisconnectPacket => v844DisconnectPacket::fromLatest($packet),
			$packet instanceof ActorEventPacket => v844ActorEventPacket::fromLatest($packet),
			$packet instanceof InventorySlotPacket => v844InventorySlotPacket::fromLatest($packet),
			default => parent::outcoming($packet)
		};

	}
}
