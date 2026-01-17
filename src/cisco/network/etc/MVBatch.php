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

namespace cisco\network\etc;

use cisco\network\async\MVCompressBatchTask;
use cisco\network\proto\TProtocol;
use cisco\network\utils\ReflectionUtils;
use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\protocol\types\CompressionAlgorithm;
use pocketmine\Server;
use pocketmine\timings\Timings;
use pocketmine\timings\TimingsHandler;
use function chr;
use function strlen;

final class MVBatch {

	private function __construct() {
		// NOOP
	}

	public static function prepareBatch(string $buffer, TProtocol $protocol, Compressor $compressor, ?bool $sync = null, ?TimingsHandler $timings = null) : CompressBatchPromise|string {
		$server = Server::getInstance();

		$timings ??= Timings::$playerNetworkSendCompress;
		$timings->startTiming();
		try {
			$threshold = $compressor->getCompressionThreshold();
			if ($threshold === null || strlen($buffer) < $threshold && !$protocol->hasOldCompressionMethod()) {
				$compressionType = CompressionAlgorithm::NONE;
				$compressed = $buffer;
			} else {
				$sync ??= !ReflectionUtils::getProperty(Server::class, $server, "networkCompressionAsync");

				if (!$sync && strlen($buffer) >= ReflectionUtils::getProperty(Server::class, $server, "networkCompressionAsyncThreshold")) {
					$promise = new CompressBatchPromise();
					$server->getAsyncPool()->submitTask(
						new MVCompressBatchTask($buffer, $promise, $compressor, $protocol->hasOldCompressionMethod())
					);
					return $promise;
				}

				$compressionType = $compressor->getNetworkId();
				$compressed = $compressor->compress($buffer);
			}

			return (!$protocol->hasOldCompressionMethod() ? chr($compressionType) : '') . $compressed;
		} finally {
			$timings->stopTiming();
		}
	}
}
