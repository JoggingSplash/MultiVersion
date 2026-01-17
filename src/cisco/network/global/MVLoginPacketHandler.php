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

namespace cisco\network\global;

use cisco\MCProtocols;
use cisco\network\assemble\auth\AuthenticationData;
use cisco\network\assemble\auth\JwtChain;
use cisco\network\etc\GlobalLoginPacket;
use cisco\network\NetworkSession;
use cisco\network\proto\v419\structure\v419ProtocolInfo;
use cisco\network\proto\v486\structure\v486ProtocolInfo;
use Closure;
use InvalidArgumentException;
use JsonMapper;
use JsonMapper_Exception;
use pocketmine\entity\InvalidSkinException;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\network\mcpe\handler\LoginPacketHandler;
use pocketmine\network\mcpe\JwtException;
use pocketmine\network\mcpe\JwtUtils;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\NetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\RequestNetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\types\login\clientdata\ClientData;
use pocketmine\network\mcpe\protocol\types\login\clientdata\ClientDataToSkinDataHelper;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\Player;
use pocketmine\player\PlayerInfo;
use pocketmine\player\XboxLivePlayerInfo;
use pocketmine\Server;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use function assert;
use function in_array;
use function is_array;

class MVLoginPacketHandler extends LoginPacketHandler
{

	public function __construct(private Server $server, private NetworkSession $session, private Closure $playerInfoConsumer, private Closure $authCallback, private Closure $onSucess)
	{
		parent::__construct($server, $session, $playerInfoConsumer, $authCallback);
	}

	public function handleRequestNetworkSettings(RequestNetworkSettingsPacket $packet) : bool
	{
		$protocolVersion = $packet->getProtocolVersion();
		$session = $this->session;
		if (!in_array($protocolVersion, MCProtocols::getProtocols(), true)) {
			$session->disconnectIncompatibleProtocol($protocolVersion);
			return true;
		}

		$protocol = MCProtocols::getProtocolInstance($protocolVersion);
		$session->setProtocol($protocol);
		$session->getProtocol()->getLogger()->info("Translating packets from {$session->getIp()}:{$session->getPort()}");
		$session->sendDataPacket(NetworkSettingsPacket::create(
			NetworkSettingsPacket::COMPRESS_EVERYTHING,
			$this->session->getCompressor()->getNetworkId(),
			false,
			0,
			0
		));

		($this->onSucess)();

		return true;
	}

	public function handleLogin(LoginPacket $packet) : bool
	{
		assert($packet instanceof GlobalLoginPacket);
		$protocol = $packet->protocol;

		if ($packet->protocol !== ProtocolInfo::CURRENT_PROTOCOL) {
			$session = $this->session;
			if (!in_array($protocol, MCProtocols::getProtocols(), true)) {
				$session->disconnectIncompatibleProtocol($protocol);
				return false;
			}

			$session->setProtocol(MCProtocols::getProtocolInstance($protocol));
			$this->session->getProtocol()->getLogger()->info("Translating packets from {$this->session->getIp()}:{$this->session->getPort()}");
			$packet->protocol = ProtocolInfo::CURRENT_PROTOCOL;
			if ($protocol === v419ProtocolInfo::CURRENT_PROTOCOL) {
				return $this->handle419Login($packet);
			} elseif ($protocol === v486ProtocolInfo::CURRENT_PROTOCOL) {
				return $this->handle486Login($packet);
			}
		}

		return parent::handleLogin($packet);
	}

	protected function handle419Login(GlobalLoginPacket $packet) : bool
	{
		$extraData = $this->mvFetchAuthData($packet->getChainDataJwt());

		if (!Player::isValidUserName($extraData->displayName)) {
			$this->session->disconnectWithError(KnownTranslationFactory::disconnectionScreen_invalidName());
			return true;
		}

		$clientData = $this->mvParseClientData($packet->clientDataJwt);

		try {
			$skin = $this->session->getTypeConverter()->getSkinAdapter()->fromSkinData(ClientDataToSkinDataHelper::fromClientData($clientData));
		} catch (InvalidArgumentException|InvalidSkinException $e) {
			$this->session->disconnectWithError(
				reason: "Invalid skin: " . $e->getMessage(),
				disconnectScreenMessage: KnownTranslationFactory::disconnectionScreen_invalidSkin()
			);

			return true;
		}

		if (!Uuid::isValid($extraData->identity)) {
			throw new PacketHandlingException("Invalid login UUID");
		}

		$uuid = Uuid::fromString($extraData->identity);
		$arrClientData = (array) $clientData;
		$arrClientData["TitleID"] = $extraData->titleId;

		$playerInfo = $extraData->XUID !== "" ? new XboxLivePlayerInfo(
			$extraData->XUID,
			$extraData->displayName,
			$uuid,
			$skin,
			$clientData->LanguageCode,
			$arrClientData
		) : new PlayerInfo(
			$extraData->displayName,
			$uuid,
			$skin,
			$clientData->LanguageCode,
			$arrClientData
		);

		($this->playerInfoConsumer)($playerInfo);

		$ev = new PlayerPreLoginEvent(
			$playerInfo,
			$this->session->getIp(),
			$this->session->getPort(),
			$this->server->requiresAuthentication()
		);

		if ($this->server->getNetwork()->getValidConnectionCount() > $this->server->getMaxPlayers()) {
			$ev->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_SERVER_FULL, KnownTranslationFactory::disconnectionScreen_serverFull());
		}

		if (!$this->server->isWhitelisted($playerInfo->getUsername())) {
			$ev->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_SERVER_WHITELISTED, KnownTranslationFactory::pocketmine_disconnect_whitelisted());
		}

		$banMessage = null;
		if (($banEntry = $this->server->getNameBans()->getEntry($playerInfo->getUsername())) !== null) {
			$banReason = $banEntry->getReason();
			$banMessage = $banReason === "" ? KnownTranslationFactory::pocketmine_disconnect_ban_noReason() : KnownTranslationFactory::pocketmine_disconnect_ban($banReason);
		} elseif (($banEntry = $this->server->getIPBans()->getEntry($this->session->getIp())) !== null) {
			$banReason = $banEntry->getReason();
			$banMessage = KnownTranslationFactory::pocketmine_disconnect_ban($banReason !== "" ? $banReason : KnownTranslationFactory::pocketmine_disconnect_ban_ip());
		}
		if ($banMessage !== null) {
			$ev->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_BANNED, $banMessage);
		}

		$ev->call();
		if (!$ev->isAllowed()) {
			$this->session->disconnect($ev->getFinalDisconnectReason(), $ev->getFinalDisconnectScreenMessage());
			return false;
		}

		$this->mvProcessLogin($packet, $ev->isAuthRequired());

		return true;
	}

	/**
	 * @throws PacketHandlingException
	 */
	protected function mvFetchAuthData(JwtChain $chain) : AuthenticationData
	{
		/** @var AuthenticationData|null $extraData */
		$extraData = null;
		foreach ($chain->chain as $jwt) {
			//validate every chain element
			try {
				[, $claims,] = JwtUtils::parse($jwt);
			} catch (JwtException $e) {
				throw PacketHandlingException::wrap($e);
			}
			if (isset($claims["extraData"])) {
				if ($extraData !== null) {
					throw new PacketHandlingException("Found 'extraData' more than once in chainData");
				}

				if (!is_array($claims["extraData"])) {
					throw new PacketHandlingException("'extraData' key should be an array");
				}
				$mapper = new JsonMapper();
				$mapper->bEnforceMapType = false; //TODO: we don't really need this as an array, but right now we don't have enough models
				$mapper->bExceptionOnMissingData = true;
				$mapper->bExceptionOnUndefinedProperty = true;
				$mapper->bStrictObjectTypeChecking = true;
				try {
					/** @var AuthenticationData $extraData */
					$extraData = $mapper->map($claims["extraData"], new AuthenticationData());
				} catch (JsonMapper_Exception $e) {
					throw PacketHandlingException::wrap($e);
				}
			}
		}
		if ($extraData === null) {
			throw new PacketHandlingException("'extraData' not found in chain data");
		}
		return $extraData;
	}

	/**
	 * @throws PacketHandlingException
	 */
	protected function mvParseClientData(string $clientDataJwt) : ClientData
	{
		try {
			[, $clientDataClaims,] = JwtUtils::parse($clientDataJwt);
		} catch (JwtException $e) {
			throw PacketDecodeException::wrap($e);
		}

		$this->session->getProtocol()->injectExtraData($clientDataClaims);

		$mapper = new JsonMapper();
		$mapper->bEnforceMapType = false; //TODO: we don't really need this as an array, but right now we don't have enough models
		$mapper->bExceptionOnMissingData = false;
		$mapper->bExceptionOnUndefinedProperty = false;
		try {
			$clientData = $mapper->map($clientDataClaims, new ClientData());
		} catch (JsonMapper_Exception $e) {
			throw PacketDecodeException::wrap($e);
		}
		return $clientData;
	}

	/**
	 * @throws ReflectionException
	 */
	protected function mvProcessLogin(GlobalLoginPacket $packet, bool $authRequired) : void
	{
		$this->server->getAsyncPool()->submitTask(new MVPrepareLoginTask($packet->getChainDataJwt()->chain, $packet->clientDataJwt, $authRequired, $this->authCallback));
		$this->session->setHandler(null); //drop packets received during login verification
	}

	protected function handle486Login(GlobalLoginPacket $packet) : bool
	{
		$extraData = $this->mvFetchAuthData($packet->getChainDataJwt());

		if (!Player::isValidUserName($extraData->displayName)) {
			$this->session->disconnectWithError(KnownTranslationFactory::disconnectionScreen_invalidName());
			return true;
		}

		$clientData = $this->mvParseClientData($packet->clientDataJwt);

		try {
			$skin = $this->session->getTypeConverter()->getSkinAdapter()->fromSkinData(ClientDataToSkinDataHelper::fromClientData($clientData));
		} catch (InvalidArgumentException|InvalidSkinException $e) {
			$this->session->disconnectWithError(
				reason: "Invalid skin: " . $e->getMessage(),
				disconnectScreenMessage: KnownTranslationFactory::disconnectionScreen_invalidSkin()
			);

			return true;
		}

		if (!Uuid::isValid($extraData->identity)) {
			throw new PacketHandlingException("Invalid login UUID");
		}

		$uuid = Uuid::fromString($extraData->identity);
		$arrClientData = (array) $clientData;
		$arrClientData["TitleID"] = $extraData->titleId;

		$playerInfo = $extraData->XUID !== "" ? new XboxLivePlayerInfo(
			$extraData->XUID,
			$extraData->displayName,
			$uuid,
			$skin,
			$clientData->LanguageCode,
			$arrClientData
		) : new PlayerInfo(
			$extraData->displayName,
			$uuid,
			$skin,
			$clientData->LanguageCode,
			$arrClientData
		);

		($this->playerInfoConsumer)($playerInfo);

		$ev = new PlayerPreLoginEvent(
			$playerInfo,
			$this->session->getIp(),
			$this->session->getPort(),
			$this->server->requiresAuthentication()
		);

		if ($this->server->getNetwork()->getValidConnectionCount() > $this->server->getMaxPlayers()) {
			$ev->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_SERVER_FULL, KnownTranslationFactory::disconnectionScreen_serverFull());
		}

		if (!$this->server->isWhitelisted($playerInfo->getUsername())) {
			$ev->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_SERVER_WHITELISTED, KnownTranslationFactory::pocketmine_disconnect_whitelisted());
		}

		$banMessage = null;
		if (($banEntry = $this->server->getNameBans()->getEntry($playerInfo->getUsername())) !== null) {
			$banReason = $banEntry->getReason();
			$banMessage = $banReason === "" ? KnownTranslationFactory::pocketmine_disconnect_ban_noReason() : KnownTranslationFactory::pocketmine_disconnect_ban($banReason);
		} elseif (($banEntry = $this->server->getIPBans()->getEntry($this->session->getIp())) !== null) {
			$banReason = $banEntry->getReason();
			$banMessage = KnownTranslationFactory::pocketmine_disconnect_ban($banReason !== "" ? $banReason : KnownTranslationFactory::pocketmine_disconnect_ban_ip());
		}
		if ($banMessage !== null) {
			$ev->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_BANNED, $banMessage);
		}

		$ev->call();
		if (!$ev->isAllowed()) {
			$this->session->disconnect($ev->getFinalDisconnectReason(), $ev->getFinalDisconnectScreenMessage());
			return false;
		}

		$this->mvProcessLogin($packet, $ev->isAuthRequired());
		return true;
	}

	public function handleCraftingData(CraftingDataPacket $packet) : bool
	{
		return true;
	}

	protected function parseClientData(string $clientDataJwt) : ClientData
	{
		try {
			[, $clientDataClaims,] = JwtUtils::parse($clientDataJwt);
		} catch (JwtException $e) {
			throw PacketHandlingException::wrap($e);
		}

		$this->session->getProtocol()->injectExtraData($clientDataClaims);

		$mapper = new JsonMapper();
		$mapper->bEnforceMapType = false; //TODO: we don't really need this as an array, but right now we don't have enough models
		$mapper->bExceptionOnMissingData = true;
		$mapper->bExceptionOnUndefinedProperty = true;
		$mapper->bStrictObjectTypeChecking = true;
		try {
			$clientData = $mapper->map($clientDataClaims, new ClientData());
		} catch (JsonMapper_Exception $e) {
			throw PacketHandlingException::wrap($e);
		}
		return $clientData;
	}
}
