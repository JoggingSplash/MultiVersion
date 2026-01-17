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

namespace cisco\network\legacy;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class LegacyInteractPacket extends InteractPacket {

	public const NETWORK_ID = -1; // this should be override

	public float $x;
	public float $y;
	public float $z;

	protected function decodePayload(ByteBufferReader $in) : void {
		$this->action = Byte::readUnsigned($in);
		$this->targetActorRuntimeId = CommonTypes::getActorRuntimeId($in);
		if ($this->action === self::ACTION_MOUSEOVER) {
			$this->x = LE::readFloat($in);
			$this->y = LE::readFloat($in);
			$this->z = LE::readFloat($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void {
		Byte::writeUnsigned($out, $this->action);
		CommonTypes::putActorRuntimeId($out, $this->targetActorRuntimeId);
		if ($this->action === self::ACTION_MOUSEOVER) {
			LE::writeFloat($out, $this->x);
			LE::writeFloat($out, $this->y);
			LE::writeFloat($out, $this->z);
		}
	}
}
