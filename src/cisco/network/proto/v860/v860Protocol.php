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

namespace cisco\network\proto\v860;

use cisco\network\proto\latest\LatestProtocol;
use cisco\network\proto\v860\packets\v860AnimatePacket;
use cisco\network\proto\v860\packets\v860InteractPacket;
use cisco\network\proto\v860\packets\v860MobEffectPacket;
use cisco\network\proto\v860\packets\v860ResourcePackStackPacket;
use cisco\network\proto\v860\packets\v860StartGamePacket;
use cisco\network\proto\v860\packets\v860TextPacket;
use cisco\network\proto\v860\structure\v860PacketPool;
use cisco\network\utils\RawPacketHelper;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\TextPacket;

class v860Protocol extends LatestProtocol
{

	public function __construct(){
		parent::__construct();
		unset($this->packetPool);
		$this->packetPool = new v860PacketPool();
	}

	public function getProtocolId() : int
	{
		return 860;
	}

	public function incoming(ServerboundPacket $packet) : ?ServerboundPacket
	{
		return match (true) {
			$packet instanceof v860InteractPacket => RawPacketHelper::translateInteractPacketToLatest($packet),
			$packet instanceof v860AnimatePacket => AnimatePacket::create($packet->action, $packet->action, 0.0, null),
			default => parent::incoming($packet)
		};
	}

	public function outcoming(ClientboundPacket $packet) : ?ClientboundPacket
	{
		return match (true) {
			$packet instanceof MobEffectPacket => v860MobEffectPacket::fromLatest($packet),
			$packet instanceof TextPacket => v860TextPacket::fromLatest($packet),
			$packet instanceof ResourcePackStackPacket => v860ResourcePackStackPacket::fromLatest($packet),
			$packet instanceof StartGamePacket => v860StartGamePacket::fromLatest($packet),
			$packet instanceof AnimatePacket => v860AnimatePacket::fromLatest($packet),
			default => parent::outcoming($packet)
		};
	}

	public function __toString() : string
	{
		return "v1.21.124";
	}
}
