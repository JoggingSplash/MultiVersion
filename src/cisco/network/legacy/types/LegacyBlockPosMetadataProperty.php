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

namespace cisco\network\legacy\types;

use cisco\network\utils\RawPacketHelper;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataTypes;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;

final class LegacyBlockPosMetadataProperty implements MetadataProperty {
	use GetTypeIdFromConstTrait;

	public const ID = EntityMetadataTypes::POS;

	public function __construct(
		private BlockPosition $value
	){}

	public function getValue() : BlockPosition{
		return $this->value;
	}

	public static function read(ByteBufferReader $in) : self{
		return new self(RawPacketHelper::getUnsignedYBlockPosition($in));
	}

	public function write(ByteBufferWriter $out) : void{
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->value);
	}

	public function equals(MetadataProperty $other) : bool{
		return $other instanceof self && $other->value->equals($this->value);
	}
}
