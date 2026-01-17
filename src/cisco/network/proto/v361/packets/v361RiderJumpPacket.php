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

use cisco\network\proto\v361\mapping\v361DataPacket;
use cisco\network\proto\v361\structure\v361ProtocolInfo;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ServerboundPacket;

class v361RiderJumpPacket extends v361DataPacket implements ServerboundPacket {

	public const NETWORK_ID = v361ProtocolInfo::RIDER_JUMP_PACKET;

	public int $strength; // pct

	protected function encodePayload(ByteBufferWriter $out) : void {
		VarInt::writeSignedInt($out, $this->strength);
	}

	protected function decodePayload(ByteBufferReader $in) : void {
		$this->strength = VarInt::readSignedInt($in);
	}

	public function handleInternal(PacketHandlerInterface $handler) : bool {
		return $handler->handleRiderJump($this);
	}
}
