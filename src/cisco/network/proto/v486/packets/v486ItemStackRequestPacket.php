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

namespace cisco\network\proto\v486\packets;

use cisco\network\proto\v486\packets\types\inventory\stackrequest\v486ItemStackRequest;
use cisco\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ItemStackRequestPacket;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequest;
use function count;

class v486ItemStackRequestPacket extends ItemStackRequestPacket
{

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$requests = [];
		for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
			$request = v486ItemStackRequest::read($in);
			$requests[] = new ItemStackRequest($request->getRequestId(), $request->getActions(), $request->getFilterStrings(), $request->getFilterStringCause());
		}
		ReflectionUtils::setProperty(ItemStackRequestPacket::class, $this, "requests", $requests);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, count($this->getRequests()));
		foreach ($this->getRequests() as $request) {
			$request->write($out);
		}
	}
}
