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

use cisco\network\proto\v419\structure\v419ProtocolInfo;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;

class v419BiomeDefinitionListPacket extends BiomeDefinitionListPacket
{

	public const NETWORK_ID = v419ProtocolInfo::BIOME_DEFINITION_LIST_PACKET;

	/** @phpstan-var CacheableNbt<CompoundTag> */
	public CacheableNbt $definitions;

	/**
	 * @generate-create-func
	 * @phpstan-param CacheableNbt<CompoundTag> $definitions
	 */
	public static function v419create(CacheableNbt $definitions) : self
	{
		$result = new self();
		$result->definitions = $definitions;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->definitions = new CacheableNbt(CommonTypes::getNbtCompoundRoot($in));
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		$out->writeByteArray($this->definitions->getEncodedNbt());
	}
}
