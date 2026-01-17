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

use GlobalLogger;
use pmmp\thread\ThreadSafeArray;
use pocketmine\network\mcpe\raklib\PthreadsChannelReader;
use pocketmine\network\mcpe\raklib\RakLibServer;
use pocketmine\network\mcpe\raklib\SnoozeAwarePthreadsChannelWriter;
use pocketmine\snooze\SleeperHandlerEntry;
use pocketmine\thread\log\ThreadSafeLogger;
use pocketmine\thread\NonThreadSafeValue;
use raklib\server\ipc\RakLibToUserThreadMessageSender;
use raklib\server\ipc\UserToRakLibThreadMessageReceiver;
use raklib\server\ProtocolAcceptor;
use raklib\server\Server;
use raklib\server\ServerSocket;
use raklib\utils\ExceptionTraceCleaner;
use raklib\utils\InternetAddress;
use function gc_enable;
use function ini_set;
use const pocketmine\PATH;

class MVRakLibServer extends RakLibServer
{

	protected NonThreadSafeValue $acceptor;

	/**
	 * @phpstan-param ThreadSafeArray<int, string> $mainToThreadBuffer
	 * @phpstan-param ThreadSafeArray<int, string> $threadToMainBuffer
	 */
	public function __construct(
		protected ThreadSafeLogger    $logger,
		protected ThreadSafeArray     $mainToThreadBuffer,
		protected ThreadSafeArray     $threadToMainBuffer,
		InternetAddress               $address,
		protected int                 $serverId,
		protected int                 $maxMtuSize,
		ProtocolAcceptor              $acceptor,
		protected SleeperHandlerEntry $sleeperEntry
	)
	{
		$this->mainPath = PATH;
		$this->address = new NonThreadSafeValue($address);
		$this->acceptor = new NonThreadSafeValue($acceptor);
	}

	protected function onRun() : void
	{
		gc_enable();
		ini_set("display_errors", '1');
		ini_set("display_startup_errors", '1');
		GlobalLogger::set($this->logger);

		$socket = new ServerSocket($this->address->deserialize());
		$manager = new Server(
			$this->serverId,
			$this->logger,
			$socket,
			$this->maxMtuSize,
			$this->acceptor->deserialize(),
			new UserToRakLibThreadMessageReceiver(new PthreadsChannelReader($this->mainToThreadBuffer)),
			new RakLibToUserThreadMessageSender(new SnoozeAwarePthreadsChannelWriter($this->threadToMainBuffer, $this->sleeperEntry->createNotifier())),
			new ExceptionTraceCleaner($this->mainPath)
		);
		$this->synchronized(function () : void {
			$this->ready = true;
			$this->notify();
		});
		while (!$this->isKilled) {
			$manager->tickProcessor();
		}
		$manager->waitShutdown();
	}
}
