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

use cisco\network\proto\v419\packets\types\inventory\stackresponse\v419ItemStackResponse;
use cisco\network\proto\v419\structure\v419ProtocolInfo;
use cisco\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ItemStackResponsePacket;
use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponse;
use function count;

class v419ItemStackResponsePacket extends ItemStackResponsePacket
{

	public const NETWORK_ID = v419ProtocolInfo::ITEM_STACK_RESPONSE_PACKET;

	public static function fromLatest(ItemStackResponsePacket $pk) : self
	{
		$npk = new self();
		ReflectionUtils::setProperty(ItemStackResponsePacket::class, $npk, "responses", $pk->getResponses());
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$responses = [];
		for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
			$response = v419ItemStackResponse::read($in);
			$responses[] = new ItemStackResponse($response->getResult(), $response->getRequestId(), $response->getContainerInfos());
		}
		ReflectionUtils::setProperty(ItemStackResponsePacket::class, $this, "responses", $responses);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedLong($out, count($this->getResponses()));
		foreach ($this->getResponses() as $response) {
			(new v419ItemStackResponse($response->getResult(), $response->getRequestId(), $response->getContainerInfos()))->write($out);
		}
	}
}
