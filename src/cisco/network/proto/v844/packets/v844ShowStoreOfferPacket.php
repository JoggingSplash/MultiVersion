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

namespace cisco\network\proto\v844\packets;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\ShowStoreOfferPacket;
use pocketmine\network\mcpe\protocol\types\ShowStoreOfferRedirectType;

class v844ShowStoreOfferPacket extends ShowStoreOfferPacket
{

	public string $_offerId;

	static public function fromLatest(ShowStoreOfferPacket $packet) : self
	{
		$npk = new self();
		$npk->_offerId = $packet->offerId->toString();
		$npk->redirectType = $packet->redirectType;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->_offerId = CommonTypes::getString($in);
		$this->redirectType = ShowStoreOfferRedirectType::fromPacket(Byte::readUnsigned($in));
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putString($out, $this->_offerId);
		Byte::writeUnsigned($out, $this->redirectType->value);
	}
}
