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
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v419ModalFormResponsePacket extends ModalFormResponsePacket
{

	public const NETWORK_ID = v419ProtocolInfo::MODAL_FORM_RESPONSE_PACKET;

	//in 1.16.100 we got null as str

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, $this->formId);
		CommonTypes::putString($out, $this->formData);
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->formId = VarInt::readUnsignedInt($in);
		$this->formData = CommonTypes::getString($in);
	}
}
