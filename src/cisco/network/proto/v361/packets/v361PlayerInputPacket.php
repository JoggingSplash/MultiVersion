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

use cisco\network\proto\v361\handler\v361PacketHandler;
use cisco\network\proto\v361\mapping\v361DataPacket;
use cisco\network\proto\v361\structure\v361ProtocolInfo;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\ServerboundPacket;

class v361PlayerInputPacket extends v361DataPacket implements ServerboundPacket {

	public const NETWORK_ID = v361ProtocolInfo::PLAYER_INPUT_PACKET;

	public float $motionX;
	public float $motionY;
	public bool $jumping;
	public bool $sneaking;

	protected function encodePayload(ByteBufferWriter $out) : void {
		LE::writeFloat($out, $this->motionX);
		LE::writeFloat($out, $this->motionY);
		CommonTypes::putBool($out, $this->jumping);
		CommonTypes::putBool($out, $this->sneaking);
	}

	protected function decodePayload(ByteBufferReader $in) : void {
		$this->motionX = LE::readFloat($in);
		$this->motionY = LE::readFloat($in);
		$this->jumping = CommonTypes::getBool($in);
		$this->sneaking = CommonTypes::getBool($in);
	}

	protected function handleInternal(v361PacketHandler $handler) : bool {
		return $handler->handlePlayerInput($this);
	}
}
