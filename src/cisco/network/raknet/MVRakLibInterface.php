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

namespace cisco\network\raknet;

use cisco\MCProtocols;
use cisco\network\NetworkSession;
use cisco\network\utils\ReflectionUtils;
use pmmp\thread\ThreadSafeArray;
use pocketmine\network\mcpe\compression\ZlibCompressor;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\raklib\PthreadsChannelReader;
use pocketmine\network\mcpe\raklib\PthreadsChannelWriter;
use pocketmine\network\mcpe\raklib\RakLibInterface;
use pocketmine\network\mcpe\raklib\RakLibPacketSender;
use pocketmine\network\mcpe\StandardEntityEventBroadcaster;
use pocketmine\network\mcpe\StandardPacketBroadcaster;
use pocketmine\Server;
use pocketmine\timings\Timings;
use raklib\server\ipc\RakLibToUserThreadMessageReceiver;
use raklib\server\ipc\UserToRakLibThreadMessageSender;
use raklib\utils\InternetAddress;
use ReflectionException;

class MVRakLibInterface extends RakLibInterface
{

	/**
	 * @throws ReflectionException
	 */
	public function __construct(Server $server, string $ip, int $port, bool $ipV6)
	{
		$typeConverter = TypeConverter::getInstance();
		$packetBroadcaster = new StandardPacketBroadcaster($server);
		$entityEventBroadcaster = new StandardEntityEventBroadcaster($packetBroadcaster, $typeConverter);

		if ($ipV6) {
			$ip = "[$ip]";
		}

		parent::__construct($server, $ip, $port, $ipV6, $packetBroadcaster, $entityEventBroadcaster, $typeConverter);
		$server->getTickSleeper()->removeNotifier(ReflectionUtils::getProperty(RakLibInterface::class, $this, "sleeperNotifierId"));
		$sleeperEntry = $server->getTickSleeper()->addNotifier(function () : void {
			Timings::$connection->startTiming();
			try {
				while (ReflectionUtils::getProperty(RakLibInterface::class, $this, "eventReceiver")->handle($this)) ;
			} finally {
				Timings::$connection->stopTiming();
			}
		});

		ReflectionUtils::setProperty(RakLibInterface::class, $this, "sleeperNotifierId", $sleeperEntry->getNotifierId());
		/** @phpstan-var ThreadSafeArray<int, string> $mainToThreadBuffer */
		$mainToThreadBuffer = new ThreadSafeArray();
		/** @phpstan-var ThreadSafeArray<int, string> $threadToMainBuffer */
		$threadToMainBuffer = new ThreadSafeArray();
		ReflectionUtils::setProperty(RakLibInterface::class, $this, "rakLib", new MVRakLibServer(
			$server->getLogger(),
			$mainToThreadBuffer,
			$threadToMainBuffer,
			new InternetAddress($ip, $port, $ipV6 ? 6 : 4),
			ReflectionUtils::getProperty(RakLibInterface::class, $this, "rakServerId"),
			$server->getConfigGroup()->getProperty("network.max-mtu-size", 1492),
			MCProtocols::getRaknetProtocolAcceptor(),
			$sleeperEntry
		));
		ReflectionUtils::setProperty(RakLibInterface::class, $this, "eventReceiver", new RakLibToUserThreadMessageReceiver(
			new PthreadsChannelReader($threadToMainBuffer)
		));
		ReflectionUtils::setProperty(RakLibInterface::class, $this, "interface", new UserToRakLibThreadMessageSender(
			new PthreadsChannelWriter($mainToThreadBuffer)
		));
	}

	/**
	 * @throws ReflectionException
	 */
	public function onClientConnect(int $sessionId, string $address, int $port, int $clientID) : void
	{
		$server = Server::getInstance();
		$session = new NetworkSession(
			$server,
			$server->getNetwork()->getSessionManager(),
			PacketPool::getInstance(),
			new RakLibPacketSender($sessionId, $this),
			ReflectionUtils::getProperty(RakLibInterface::class, $this, "packetBroadcaster"),
			ReflectionUtils::getProperty(RakLibInterface::class, $this, "entityEventBroadcaster"),
			ZlibCompressor::getInstance(),
			TypeConverter::getInstance(),
			$address,
			$port
		);

		$sessions = ReflectionUtils::getProperty(RakLibInterface::class, $this, "sessions");
		$sessions[$sessionId] = $session;
		ReflectionUtils::setProperty(RakLibInterface::class, $this, "sessions", $sessions);
	}
}
