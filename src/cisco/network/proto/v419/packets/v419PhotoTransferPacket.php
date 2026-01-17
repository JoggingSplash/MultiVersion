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

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\PhotoTransferPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v419PhotoTransferPacket extends PhotoTransferPacket
{

	static public function fromLatest(PhotoTransferPacket $packet) : self
	{
		$npk = new v419PhotoTransferPacket();
		$npk->photoName = $packet->photoName;
		$npk->photoData = $packet->photoData;
		$npk->bookId = $packet->bookId;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->photoName = CommonTypes::getString($in);
		$this->photoData = CommonTypes::getString($in);
		$this->bookId = CommonTypes::getString($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putString($out, $this->photoName);
		CommonTypes::putString($out, $this->photoData);
		CommonTypes::putString($out, $this->bookId);
	}
}
