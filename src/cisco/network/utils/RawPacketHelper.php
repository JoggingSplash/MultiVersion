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

namespace cisco\network\utils;

use cisco\network\legacy\LegacyActorEventPacket;
use cisco\network\legacy\LegacyInteractPacket;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\utils\Binary;

final class RawPacketHelper
{

	private function __construct(){

	}

	/**
	 * Translates the interact packet to latest.
	 */
	public static function translateInteractPacketToLatest(LegacyInteractPacket $packet) : InteractPacket {
		$result = new InteractPacket();
		$result->action = $packet->action;
		$result->targetActorRuntimeId = $packet->targetActorRuntimeId;
		if ($packet->action === InteractPacket::ACTION_MOUSEOVER) {
			$result->position = new Vector3($packet->x, $packet->y, $packet->z);
		}
		return $result;
	}

	static public function translateActorEventToLatest(LegacyActorEventPacket $packet) : ActorEventPacket    {
		return ActorEventPacket::create($packet->actorRuntimeId, $packet->eventId, $packet->eventData, null);
	}

	public static function getUnsignedYBlockPosition(ByteBufferReader $in) : BlockPosition {
		$x = VarInt::readSignedInt($in);
		$y = Binary::signInt(VarInt::readUnsignedInt($in)); //Y coordinate may be signed, but it's written unsigned :<
		$z = VarInt::readSignedInt($in);
		return new BlockPosition($x, $y, $z);
	}

	public static function putUnsignedYBlockPosition(ByteBufferWriter $out, BlockPosition $blockPosition) : void    {
		VarInt::writeSignedInt($out, $blockPosition->getX());
		VarInt::writeUnsignedInt($out, Binary::unsignInt($blockPosition->getY())); //Y coordinate may be signed, but it's written unsigned :<
		VarInt::writeSignedInt($out, $blockPosition->getZ());
	}

}
