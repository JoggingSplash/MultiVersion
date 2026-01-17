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

use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\protocol\types\CacheableNbt;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Filesystem;
use pocketmine\utils\Utils;
use ReflectionClass;
use function class_exists;
use function dirname;
use function is_dir;
use function is_file;
use function pathinfo;
use function scandir;
use const PATHINFO_EXTENSION;
use const PATHINFO_FILENAME;

final class ProtocolUtils
{

	private function __construct() {

	}

	/**
	 * Easy way to register packets into the protocol
	 */
	static public function registerPackets(PacketPool $pool, int $protocol) : void{
		$phar = \Phar::running();
		$packetDir = ($phar === "" ? dirname(__DIR__) : "$phar/src/cisco/network") . "/proto/v$protocol/packets/";
		if (!is_dir($packetDir)) throw new AssumptionFailedError("packets directory does not exist");
		$namespace = "cisco\\network\\proto\\v$protocol\\packets\\";

		foreach (self::scanAndGetPackets($namespace, $packetDir) as $packet) {
			$pool->registerPacket($packet);
		}
	}

	/**
	 * Scans into the dir and fetchs the packets creating the instance
	 */
	static private function scanAndGetPackets(string $namespace, string $packetDir) : \Generator {
		foreach (Utils::assumeNotFalse(scandir($packetDir)) as $file) {
			if ($file === "." || $file === ".." || pathinfo($file, PATHINFO_EXTENSION) !== "php") {
				continue;
			}

			$className = $namespace . pathinfo($file, PATHINFO_FILENAME);

			if (!class_exists($className)) {
				continue;
			}

			// This should not throw
			$ref = new ReflectionClass($className);
			$objectOrString = $ref->newInstanceWithoutConstructor();

			if($objectOrString instanceof DataPacket){
				yield $objectOrString;
			}
		}
	}

	public static function loadCacheableFromFile(string $path) : CacheableNbt {
		if(!is_file($path)) throw new \InvalidArgumentException("file $path does not exist");
		$rawNbt = Filesystem::fileGetContents($path);
		return new CacheableNbt((new NetworkNbtSerializer())->read($rawNbt)->mustGetCompoundTag());
	}

}
