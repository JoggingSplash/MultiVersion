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

namespace cisco\network\proto\v361;

use cisco\network\assemble\auth\JwtChain;
use cisco\network\etc\GlobalLoginPacket;
use cisco\network\etc\PreFormattedChunkSerializer;
use cisco\network\NetworkSession;
use cisco\network\proto\latest\LatestSkinHelper;
use cisco\network\proto\TProtocol;
use cisco\network\proto\v361\packets\v361AddActorPacket;
use cisco\network\proto\v361\packets\v361DisconnectPacket;
use cisco\network\proto\v361\packets\v361LevelChunkPacket;
use cisco\network\proto\v361\packets\v361MovePlayerPacket;
use cisco\network\proto\v361\packets\v361NetworkChunkPublisherUpdatePacket;
use cisco\network\proto\v361\packets\v361RespawnPacket;
use cisco\network\proto\v361\packets\v361SetTitlePacket;
use cisco\network\proto\v361\packets\v361SpawnParticleEffectPacket;
use cisco\network\proto\v361\packets\v361StartGamePacket;
use cisco\network\proto\v361\packets\v361TextPacket;
use cisco\network\proto\v361\packets\v361TransferPacket;
use cisco\network\proto\v361\structure\v361PacketPool;
use cisco\network\proto\v361\structure\v361ProtocolInfo;
use cisco\network\proto\v361\structure\v361StaticPacketCache;
use JsonException;
use JsonMapper;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\LE;
use pocketmine\crafting\CraftingManager;
use pocketmine\lang\Language;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use function count;
use function is_array;
use function json_decode;
use const JSON_THROW_ON_ERROR;

class v361Protocol extends TProtocol {

	protected v361StaticPacketCache $staticPacketCache;

	public function __construct(){
		parent::__construct(v361TypeConverter::getInstance()->getConverter(), new v361PacketPool(), new PreFormattedChunkSerializer(), new LatestSkinHelper());
		$this->staticPacketCache = new v361StaticPacketCache($this);
	}

	public function hasEncryption() : bool {
		return false;
	}

	public function getRaknetVersion() : int {
		return 9;
	}

	public function hasDebug() : bool {
		return true;
	}

	public function decodeConnection(string $buffer, GlobalLoginPacket $loginPacket) : void{
		$reader = new ByteBufferReader($buffer);
		$chainDataJsonLen = LE::readUnsignedInt($reader);

		try {
			$chainDataJson = json_decode($reader->readByteArray($chainDataJsonLen), true, JSON_THROW_ON_ERROR);
		} catch (JsonException $exception) {
			throw PacketDecodeException::wrap($exception);
		}

		if (!is_array($chainDataJson) || count($chainDataJson) !== 1 || !isset($chainDataJson["chain"])) {
			throw new PacketDecodeException("Chain data must be a JSON object containing only the 'chain' element");
		}

		if (!is_array($chainDataJson["chain"])) {
			throw new PacketDecodeException("Chain data 'chain' element must be a list of strings");
		}

		$mapper = new JsonMapper();
		$mapper->bExceptionOnMissingData = true;
		$mapper->bExceptionOnUndefinedProperty = true;

		try{
			$wrapper = $mapper->map($chainDataJson["chain"], new JwtChain());
		}catch (\JsonMapper_Exception $exception) {
			throw PacketDecodeException::wrap($exception);
		}

		$loginPacket->setChainDataJwt($wrapper);
		$clientDataJwtLength = LE::readUnsignedInt($reader);
		$loginPacket->clientDataJwt = $reader->readByteArray($clientDataJwtLength);
	}

	public function fetchPacketHandler(?PacketHandler $handler, NetworkSession $session) : ?PacketHandler {
		return null; // TODO
	}

	public function incoming(ServerboundPacket $packet) : ?ServerboundPacket{
		return $packet; // TODO
	}

	public function outcoming(ClientboundPacket $packet) : ?ClientboundPacket{
		return match(true){
			$packet instanceof AvailableActorIdentifiersPacket => $this->staticPacketCache->getActorIdentifiersPacket(),
			$packet instanceof BiomeDefinitionListPacket => $this->staticPacketCache->getBiomeDefinitionListPacket(),
			$packet instanceof AddActorPacket => v361AddActorPacket::fromLatest($packet),
			$packet instanceof DisconnectPacket => v361DisconnectPacket::fromLatest($packet),
			$packet instanceof TextPacket => v361TextPacket::fromLatest($packet),
			$packet instanceof RespawnPacket => v361RespawnPacket::fromLatest($packet),
			$packet instanceof SetTitlePacket => v361SetTitlePacket::fromLatest($packet),
			$packet instanceof StartGamePacket => v361StartGamePacket::fromLatest($packet),
			$packet instanceof SpawnParticleEffectPacket => v361SpawnParticleEffectPacket::fromLatest($packet),
			$packet instanceof TransferPacket => v361TransferPacket::fromLatest($packet),
			$packet instanceof NetworkChunkPublisherUpdatePacket => v361NetworkChunkPublisherUpdatePacket::fromLatest($packet),
			$packet instanceof MovePlayerPacket => v361MovePlayerPacket::fromLatest($packet),
			$packet instanceof LevelChunkPacket => v361LevelChunkPacket::fromLatest($packet),
			default => $packet // No changes
		};
	}

	public function injectExtraData(array &$extraData) : void{
		$extraData["IsEditorMode"] = false;
		$extraData["PlayFabId"] = ""; // ???
		$extraData["SkinGeometryDataEngineVersion"] = "";
		$extraData["TrustedSkin"] = true;
		$extraData["CompatibleWithClientSideChunkGen"] = false;
		$extraData['MaxViewDistance'] = 7;
	}

	public function getProtocolId() : int{
		return v361ProtocolInfo::CURRENT_PROTOCOL;
	}

	public function hasOldCompressionMethod() : bool {
		return true;
	}

	public function assembleCommands(Server $server, Player $player, Language $lang) : ?ClientboundPacket{
		return null; // TODO
	}

	public function __toString() : string {
		return v361ProtocolInfo::MINECRAFT_VERSION;
	}

	public function getCraftingManager() : CraftingManager {
		return Server::getInstance()->getCraftingManager();
	}

}
