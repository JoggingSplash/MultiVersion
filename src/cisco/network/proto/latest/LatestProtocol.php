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

namespace cisco\network\proto\latest;

use cisco\network\etc\FormattedChunkSerializer;
use cisco\network\etc\GlobalLoginPacket;
use cisco\network\NetworkSession;
use cisco\network\proto\TProtocol;
use pocketmine\crafting\CraftingManager;
use pocketmine\lang\Language;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\player\Player;
use pocketmine\Server;

class LatestProtocol extends TProtocol
{

	public function __construct()
	{
		parent::__construct(LatestTypeConverter::getInstance()->getConverter(), PacketPool::getInstance(), new FormattedChunkSerializer(), new LatestSkinHelper());
	}

	public function getRaknetVersion() : int
	{
		return 11;
	}

	public function hasEncryption() : bool
	{
		return true;
	}

	public function getProtocolId() : int
	{
		return ProtocolInfo::CURRENT_PROTOCOL;
	}

	public function incoming(ServerboundPacket $packet) : ?ServerboundPacket
	{
		return $packet;
	}

	public function outcoming(ClientboundPacket $packet) : ?ClientboundPacket
	{
		if ($packet instanceof PlayerListPacket) {
			foreach ($packet->entries as $entry) {
				if (!isset($entry->skinData)) {
					continue;
				}

				$this->adaptSkinData($entry->skinData);
			}
		} elseif ($packet instanceof PlayerSkinPacket) {
			$this->adaptSkinData($packet->skin);
		}
		return $packet;
	}

	public function injectExtraData(array &$extraData) : void
	{

	}

	public function fetchPacketHandler(?PacketHandler $handler, NetworkSession $session) : ?PacketHandler
	{
		return null;
	}

	public function hasOldCompressionMethod() : bool
	{
		return false;
	}

	public function decodeConnection(string $buffer, GlobalLoginPacket $loginPacket) : void
	{
		$loginPacket->decodeConnectionRequest($buffer);
	}

	public function getCraftingManager() : CraftingManager
	{
		return Server::getInstance()->getCraftingManager();
	}

	public function assembleCommands(Server $server, Player $player, Language $lang) : ?ClientboundPacket
	{
		return null; //NOOP
	}

	public function __toString() : string
	{
		return ProtocolInfo::MINECRAFT_VERSION;
	}

}
