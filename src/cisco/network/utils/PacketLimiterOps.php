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

use pocketmine\network\mcpe\protocol\Packet;
use function microtime;
use function round;
use const PHP_ROUND_HALF_DOWN;

final class PacketLimiterOps {

	private array $responses = [];

	public function __construct(protected int $packetId) {}

	public function match(Packet $packet, int $index) : bool {
		if($packet->pid() !== $this->packetId) {
			return false;
		}

		if(!isset($this->responses[$index])) {
			$this->responses[$index] = microtime(true);
			return false;
		}

		$diff = round($now = microtime(true) - $this->responses[$index], 2, PHP_ROUND_HALF_DOWN);
		$this->responses[$index] = $now;
		return $diff > 0.95;
	}

}
