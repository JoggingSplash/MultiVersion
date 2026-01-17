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

use cisco\network\etc\MVBatch;
use cisco\network\NetworkSession;
use cisco\network\proto\TProtocol;
use InvalidArgumentException;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\PacketBroadcaster;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\serializer\PacketBatch;
use pocketmine\timings\Timings;
use function count;
use function log;
use function spl_object_id;
use function strlen;

class MVPacketBroadcaster implements PacketBroadcaster
{
	public function __construct(
		private TProtocol $protocol,
	)
	{

	}

	/**
	 * @param NetworkSession[]    $recipients
	 * @param ClientboundPacket[] $packets
	 */
	public function broadcastPackets(array $recipients, array $packets) : void
	{
		if (DataPacketSendEvent::hasHandlers()) {
			$ev = new DataPacketSendEvent($recipients, $packets);
			$ev->call();

			if ($ev->isCancelled()) {
				return;
			}

			$packets = $ev->getPackets();
		}

		$compressors = [];

		/** @var NetworkSession[][] $targetsByCompressor */
		$targetsByCompressor = [];

		$protocol = $this->protocol;
		foreach ($recipients as $recipient) {
			if ($recipient->getProtocol() !== $protocol) {
				throw new InvalidArgumentException("Only recipients with the same protocol context as the broadcaster can be broadcast to by this broadcaster");
			}

			//TODO: different compressors might be compatible, it might not be necessary to split them up by object
			$compressor = $recipient->getCompressor();
			$compressors[spl_object_id($compressor)] = $compressor;

			$targetsByCompressor[spl_object_id($compressor)][] = $recipient;
		}

		$totalLength = 0;
		$packetBuffers = [];
		$writer = new ByteBufferWriter();
		foreach ($packets as $packet) {
			$writer->clear();
			$pk = $protocol->outcoming(clone $packet);

			if ($pk === null) {
				continue;
			}

			$buffer = NetworkSession::encodePacketTimed($writer, $pk);
			//varint length prefix + packet buffer
			$totalLength += (((int) log(strlen($buffer), 128)) + 1) + strlen($buffer);
			$packetBuffers[] = $buffer;
		}

		foreach ($targetsByCompressor as $compressorId => $compressorTargets) {
			$compressor = $compressors[$compressorId];

			$threshold = $compressor->getCompressionThreshold();
			if (count($compressorTargets) > 1 && $threshold !== null && $totalLength >= $threshold) {
				//do not prepare shared batch unless we're sure it will be compressed
				$stream = new ByteBufferWriter();
				PacketBatch::encodeRaw($stream, $packetBuffers);
				$batchBuffer = $stream->getData();

				$promise = MVBatch::prepareBatch($batchBuffer, $protocol, $compressor, timings: Timings::$playerNetworkSendCompressBroadcast);
				foreach ($compressorTargets as $target) {
					$target->queueCompressed($promise);
				}
			} else {
				foreach ($compressorTargets as $target) {
					foreach ($packetBuffers as $packetBuffer) {
						$target->addToSendBuffer($packetBuffer);
					}
				}
			}
		}
	}

}
