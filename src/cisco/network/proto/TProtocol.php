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

namespace cisco\network\proto;

use cisco\network\etc\GlobalLoginPacket;
use cisco\network\global\MVChunkSerializer;
use cisco\network\global\MVPacketBroadcaster;
use cisco\network\global\MVSkinHelper;
use cisco\network\global\MVTypeConverter;
use cisco\network\NetworkSession;
use Logger;
use pocketmine\crafting\CraftingManager;
use pocketmine\lang\Language;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\compression\ZlibCompressor;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\PacketBroadcaster;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\StandardEntityEventBroadcaster;
use pocketmine\player\Player;
use pocketmine\Server;
use Stringable;

abstract class TProtocol implements Stringable {

	protected StandardEntityEventBroadcaster $entityEventBroadcaster;
	protected PacketBroadcaster $broadcaster;
	protected Compressor $compressor;
	protected Logger $logger;

	public function __construct(
		protected MVTypeConverter   $typeConverter,
		protected PacketPool        $packetPool,
		protected MVChunkSerializer $chunkSerializer,
		protected MVSkinHelper      $skinHelper
	)
	{
		$this->compressor = ZlibCompressor::getInstance(); //TODO: find out if snappy compression is better
		$this->broadcaster = new MVPacketBroadcaster($this);
		$this->entityEventBroadcaster = new StandardEntityEventBroadcaster($this->broadcaster, $this->typeConverter);
		$this->logger = new ProtoLogger($this);
	}

	public function getBroadcaster() : PacketBroadcaster
	{
		return $this->broadcaster;
	}

	public function getEntityEventBroadcaster() : StandardEntityEventBroadcaster
	{
		return $this->entityEventBroadcaster;
	}

	public function getLogger() : Logger
	{
		return $this->logger;
	}

	public function getPacketPool() : PacketPool
	{
		return $this->packetPool;
	}

	public function getTypeConverter() : MVTypeConverter
	{
		return $this->typeConverter;
	}

	public function getCompressor() : Compressor
	{
		return $this->compressor;
	}

	public function getChunkSerializer() : MVChunkSerializer
	{
		return $this->chunkSerializer;
	}

	/**
	 * Returns if the protocol can debug into his logger
	 */
	public function hasDebug() : bool{
		return false;
	}

	/**
	 * Returns the protocol ID for the mc version
	 */
	public abstract function getProtocolId() : int;

	/**
	 * Returns the raknet version supported
	 */
	public abstract function getRaknetVersion() : int;

	/**
	 * Changes (or not) the given packet handler, returns NULL if we dont need to change it
	 */
	public abstract function fetchPacketHandler(?PacketHandler $handler, NetworkSession $session) : ?PacketHandler;

	/**
	 * Transforms the given packet if should be either override or unhandle
	 */
	public abstract function incoming(ServerboundPacket $packet) : ?ServerboundPacket;

	/**
	 * Transforms the given packet if should be either override or unhandle
	 */
	public abstract function outcoming(ClientboundPacket $packet) : ?ClientboundPacket;

	/**
	 * Some versions got missing parameters on the client data sent by login packet
	 */
	public abstract function injectExtraData(array &$extraData) : void;

	/**
	 * Decodes the LoginPacket string buffer
	 * @throws PacketDecodeException
	 */
	public abstract function decodeConnection(string $buffer, GlobalLoginPacket $loginPacket) : void;

	/**
	 * New mcpe compression buffer requires adding the network id as binary in the start
	 * of the buffer, this returns if the version requires it
	 */
	public abstract function hasOldCompressionMethod() : bool;

	/**
	 * Returns if we can use encryption to this protocol
	 */
	public abstract function hasEncryption() : bool;

	/**
	 * Returns a new AvailableCommandsPacket
	 * since in the newest pmmp version its impossible to override
	 */
	public abstract function assembleCommands(Server $server, Player $player, Language $lang) : ?ClientboundPacket;

	/**
	 * Returns a simple version for the crafting manager of that protocol.
	 */
	public abstract function getCraftingManager() : CraftingManager;

	/**
	 * This actually doest need to exist, since PMMP already validates the skin, kicking the player with invalid skin.
	 * Adapts the given 'SkinData' object to fix internal geometry-data.
	 */
	protected function adaptSkinData(SkinData &$skinData) : void {
		$this->skinHelper->adaptSkinData($skinData);
	}
}
