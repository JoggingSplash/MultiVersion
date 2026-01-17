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
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\SetTitlePacket;

class v361SetTitlePacket extends SetTitlePacket {

	public const NETWORK_ID = v361ProtocolInfo::SET_TITLE_PACKET;

	static public function fromLatest(SetTitlePacket $packet) : self {
		$npk = new self();
		$npk->type = $packet->type;
		$npk->text = $packet->text;
		$npk->fadeInTime = $packet->fadeInTime;
		$npk->stayTime = $packet->stayTime;
		$npk->fadeOutTime = $packet->fadeOutTime;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->type = VarInt::readSignedInt($in);
		$this->text = CommonTypes::getString($in);
		$this->fadeInTime = VarInt::readSignedInt($in);
		$this->stayTime = VarInt::readSignedInt($in);
		$this->fadeOutTime = VarInt::readSignedInt($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		VarInt::writeSignedInt($out, $this->type);
		CommonTypes::putString($out, $this->text);
		VarInt::writeSignedInt($out, $this->fadeInTime);
		VarInt::writeSignedInt($out, $this->stayTime);
		VarInt::writeSignedInt($out, $this->fadeOutTime);
	}
}
