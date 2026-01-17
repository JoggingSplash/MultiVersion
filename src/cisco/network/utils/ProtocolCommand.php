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

namespace cisco\network\utils;

use cisco\network\NetworkSession;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\permission\DefaultPermissions;
use pocketmine\utils\TextFormat;
use function assert;

final class ProtocolCommand extends Command {

	public function __construct() {
		parent::__construct("protocol", "Get the current player protocol.", "/protocol [playerName]");
		$this->setPermission(DefaultPermissions::ROOT_USER);
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args) : void {
		if (empty($args[0])) {
			$sender->sendMessage("Usage: /protocol [playerName]");
			return;
		}

		$player = $sender->getServer()->getPlayerByPrefix($args[0]);

		if ($player === null || !$player->isOnline()) {
			$sender->sendMessage(TextFormat::RED . "Player not found");
			return;
		}

		$mvSession = $player->getNetworkSession();

		assert($mvSession instanceof NetworkSession);
		$protocol = $mvSession->getProtocol();
		$sender->sendMessage(TextFormat::colorize("&r&g{$player->getName()} is playing on protocol version: $protocol &d(&f{$protocol->getProtocolId()}&d)"));
	}
}
