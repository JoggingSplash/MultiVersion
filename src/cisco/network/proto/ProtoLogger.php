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

use pocketmine\utils\Terminal;
use SimpleLogger;
use function date;
use function strtoupper;
use function time;
use const PHP_EOL;

final class ProtoLogger extends SimpleLogger {

	protected string $prefix;
	protected string $postfix;
	protected bool $debug;

	public function __construct(TProtocol $protocol) {
		$this->prefix = "Protocol#{$protocol->getProtocolId()}: ";
		$this->postfix = (string) $protocol;
		$this->debug = $protocol->hasDebug();
	}

	public function log($level, $message) {
		echo Terminal::$COLOR_MINECOIN_GOLD . "[" . date("Y-m-d", time()) . "] [MultiVersion: " . strtoupper($level) . "] $this->prefix" . $message . " ($this->postfix)" . PHP_EOL;
	}

	public function debug($message) {
		if($this->debug) {
			echo Terminal::$COLOR_GRAY . "[" . date("Y-m-d", time()) . "] [MultiVersion: " . strtoupper(\LogLevel::DEBUG) . "] $this->prefix" . $message . " ($this->postfix)" . PHP_EOL;
		}
	}

	public function isDebug() : bool {
		return $this->debug;
	}

	public function undoDebug() : void {
		$this->debug = !$this->debug;
	}
}
