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

namespace cisco\network\proto\v361\structure;

use cisco\Loader;
use cisco\network\proto\v361\packets\v361AvailableActorIdentifiersPacket;
use cisco\network\proto\v361\packets\v361BiomeDefinitionListPacket;
use cisco\network\utils\PacketCachedTrait;
use cisco\network\utils\ProtocolUtils;
use Symfony\Component\Filesystem\Path;

final class v361StaticPacketCache {
	use PacketCachedTrait;

	protected v361AvailableActorIdentifiersPacket $actorIdentifiersPacket;
	protected v361BiomeDefinitionListPacket $biomeDefinitionListPacket;

	protected function load() : void {
		$this->actorIdentifiersPacket = v361AvailableActorIdentifiersPacket::create(ProtocolUtils::loadCacheableFromFile(
			Path::join(Loader::getPluginResourcePath(), "v361", "actor_identifiers.nbt")
		));
		$this->biomeDefinitionListPacket = v361BiomeDefinitionListPacket::create(ProtocolUtils::loadCacheableFromFile(
			Path::join(Loader::getPluginResourcePath(), "v361", "biome_definitions.nbt")
		));
	}

	public function getBiomeDefinitionListPacket() : v361BiomeDefinitionListPacket {
		return $this->biomeDefinitionListPacket;
	}

	public function getActorIdentifiersPacket() : v361AvailableActorIdentifiersPacket {
		return $this->actorIdentifiersPacket;
	}
}
