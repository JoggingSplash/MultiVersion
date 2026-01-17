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

use cisco\network\proto\v419\packets\types\inventory\stackrequest\v419ItemStackRequest;
use cisco\network\proto\v419\structure\v419ProtocolInfo;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use function count;

class v419ItemStackRequestPacket extends ItemStackRequestPacket
{
	public const NETWORK_ID = v419ProtocolInfo::ITEM_STACK_REQUEST_PACKET;

	/** @var v419ItemStackRequest[] */
	protected array $_requests = [];

	public function getRequests() : array
	{
		return $this->_requests;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
			$this->_requests[] = v419ItemStackRequest::read($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, count($this->_requests));
		foreach ($this->_requests as $request) {
			$request->write($out);
		}
	}
}
