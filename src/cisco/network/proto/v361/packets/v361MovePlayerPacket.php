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

namespace cisco\network\proto\v361\packets;

use cisco\network\proto\v361\structure\v361ProtocolInfo;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v361MovePlayerPacket extends MovePlayerPacket {

	public const NETWORK_ID = v361ProtocolInfo::NETWORK_CHUNK_PUBLISHER_UPDATE_PACKET;

	static public function fromLatest(MovePlayerPacket $packet) : self {
		$npk = new self();
		$npk->actorRuntimeId = $packet->actorRuntimeId;
		$npk->position = $packet->position;
		$npk->yaw = $packet->yaw;
		$npk->pitch = $packet->pitch;
		$npk->headYaw = $packet->headYaw;
		$npk->mode = $packet->mode;
		$npk->onGround = $packet->onGround;
		$npk->ridingActorRuntimeId = $packet->ridingActorRuntimeId;
		$npk->teleportCause = $packet->teleportCause;
		$npk->teleportItem = $packet->teleportItem;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		$this->position = CommonTypes::getVector3($in);
		$this->pitch = LE::readFloat($in);
		$this->yaw = LE::readFloat($in);
		$this->headYaw = LE::readFloat($in);
		$this->mode = Byte::readUnsigned($in);
		$this->onGround = CommonTypes::getBool($in);
		$this->ridingActorRuntimeId = CommonTypes::getActorRuntimeId($in);
		if($this->mode === MovePlayerPacket::MODE_TELEPORT){
			$this->teleportCause = LE::readSignedInt($in);
			$this->teleportItem = LE::readSignedInt($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		CommonTypes::putVector3($out, $this->position);
		LE::writeFloat($out, $this->pitch);
		LE::writeFloat($out, $this->yaw);
		LE::writeFloat($out, $this->headYaw);
		Byte::writeUnsigned($out, $this->mode);
		CommonTypes::putBool($out, $this->onGround);
		CommonTypes::putActorRuntimeId($out, $this->ridingActorRuntimeId);
		if($this->mode === MovePlayerPacket::MODE_TELEPORT){
			LE::writeSignedInt($out, $this->teleportCause);
			LE::writeSignedInt($out, $this->teleportItem);
		}
	}
}
