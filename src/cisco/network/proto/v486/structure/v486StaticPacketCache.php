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

namespace cisco\network\proto\v486\structure;

use cisco\Loader;
use cisco\network\proto\v486\packets\types\v486CreativeGroupEntry;
use cisco\network\proto\v486\packets\v486BiomeDefinitionListPacket;
use cisco\network\proto\v486\packets\v486CreativeContentPacket;
use cisco\network\utils\PacketCachedTrait;
use cisco\network\utils\ProtocolUtils;
use pocketmine\inventory\CreativeInventory;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use Symfony\Component\Filesystem\Path;

final class v486StaticPacketCache
{
	use PacketCachedTrait;

	private v486BiomeDefinitionListPacket $biomeDefinitionList;
	private v486CreativeContentPacket $creativeContentPacket;
	private AvailableActorIdentifiersPacket $actorIdentifiersPacket;

	public function getBiomeDefinitionList() : v486BiomeDefinitionListPacket
	{
		return $this->biomeDefinitionList;
	}

	public function getCreativeContentPacket() : v486CreativeContentPacket
	{
		return $this->creativeContentPacket;
	}

	public function getActorIdentifiersPacket() : AvailableActorIdentifiersPacket
	{
		return $this->actorIdentifiersPacket;
	}

	protected function load() : void
	{
		$this->biomeDefinitionList = v486BiomeDefinitionListPacket::v486create(ProtocolUtils::loadCacheableFromFile(self::path("biome_definitions.nbt")));
		$converter = $this->protocol->getTypeConverter();
		$entries = [];
		foreach (CreativeInventory::getInstance()->getAll() as $index => $item) {
			$entries[] = new v486CreativeGroupEntry($index, $converter->coreItemStackToNet($item));
		}
		$this->creativeContentPacket = v486CreativeContentPacket::v486create($entries);

		$this->actorIdentifiersPacket = AvailableActorIdentifiersPacket::create(ProtocolUtils::loadCacheableFromFile(self::path("entity_identifiers.nbt")));
	}

	private static function path(string $filename) : string
	{
		return Path::join(Loader::getPluginResourcePath(), "v486", $filename);
	}

}
