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
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v419AnimatePacket extends AnimatePacket
{

	public const NETWORK_ID = v419ProtocolInfo::ANIMATE_PACKET;

	public const ACTION_ROW_RIGHT = 128;
	public const ACTION_ROW_LEFT = 129;

	protected float $rowingTime = 0.0;

	static public function fromLatest(AnimatePacket $packet) : self
	{
		$npk = new self();
		$npk->action = $packet->action;
		$npk->actorRuntimeId = $packet->actorRuntimeId;
		$npk->rowingTime = 0.0;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->action = VarInt::readSignedInt($in);
		$this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
		if ($this->action === self::ACTION_ROW_LEFT || $this->action === self::ACTION_ROW_RIGHT) {
			$this->rowingTime = LE::readFloat($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		VarInt::writeSignedInt($out, $this->action);
		CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
		if ($this->action === self::ACTION_ROW_LEFT || $this->action === self::ACTION_ROW_RIGHT) {
			LE::writeFloat($out, $this->rowingTime);
		}
	}
}
