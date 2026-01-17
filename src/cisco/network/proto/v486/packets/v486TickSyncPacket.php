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

use cisco\network\proto\v486\structure\v486ProtocolInfo;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ServerboundPacket;

class v486TickSyncPacket extends DataPacket implements ClientboundPacket, ServerboundPacket
{
	public const NETWORK_ID = v486ProtocolInfo::TICK_SYNC_PACKET;

	private int $clientSendTime;
	private int $serverReceiveTime;

	public static function request(int $clientTime) : self
	{
		return self::create($clientTime, 0 /* useless, but always written anyway */);
	}

	private static function create(int $clientSendTime, int $serverReceiveTime) : self
	{
		$result = new self();
		$result->clientSendTime = $clientSendTime;
		$result->serverReceiveTime = $serverReceiveTime;
		return $result;
	}

	public static function response(int $clientSendTime, int $serverReceiveTime) : self
	{
		return self::create($clientSendTime, $serverReceiveTime);
	}

	public function handle(PacketHandlerInterface $handler) : bool
	{
		return true; //not handled
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->clientSendTime = LE::readUnsignedLong($in);
		$this->serverReceiveTime = LE::readUnsignedLong($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		LE::writeUnsignedLong($out, $this->clientSendTime);
		LE::writeUnsignedLong($out, $this->serverReceiveTime);
	}
}
