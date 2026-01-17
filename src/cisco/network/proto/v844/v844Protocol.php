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

namespace cisco\network\proto\v844;

use cisco\Loader;
use cisco\network\proto\latest\LatestProtocol;
use cisco\network\proto\v844\packets\v844AnimatePacket;
use cisco\network\proto\v844\packets\v844InteractPacket;
use cisco\network\proto\v844\packets\v844MobEffectPacket;
use cisco\network\proto\v844\packets\v844ResourcePackStackPacket;
use cisco\network\proto\v844\packets\v844StartGamePacket;
use cisco\network\proto\v844\packets\v844TextPacket;
use cisco\network\proto\v844\structure\v844PacketPool;
use cisco\network\proto\v844\structure\v844StaticPacketCache;
use cisco\network\utils\RawPacketHelper;
use pocketmine\crafting\CraftingManager;
use pocketmine\crafting\CraftingManagerFromDataHelper;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\TextPacket;
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

	public function __toString() : string
	{
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
			default => parent::incoming($packet)
		};
	}

	public function outcoming(ClientboundPacket $packet) : ?ClientboundPacket
	{
		return match (true) {
			$packet instanceof AnimatePacket => v844AnimatePacket::fromLatest($packet),
			$packet instanceof BiomeDefinitionListPacket => $this->staticPacketCache->getBiomeDefinitionListPacket(),
			$packet instanceof ResourcePackStackPacket => v844ResourcePackStackPacket::fromLatest($packet),
			$packet instanceof StartGamePacket => v844StartGamePacket::fromLatest($packet),
			$packet instanceof TextPacket => v844TextPacket::fromLatest($packet),
			$packet instanceof MobEffectPacket => v844MobEffectPacket::fromLatest($packet),
			$packet instanceof AvailableCommandsPacket => null,
			default => parent::outcoming($packet)
		};
	}
}
