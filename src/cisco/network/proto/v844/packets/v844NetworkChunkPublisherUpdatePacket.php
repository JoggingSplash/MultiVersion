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

namespace cisco\network\proto\v844\packets;

use cisco\network\utils\RawPacketHelper;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\types\ChunkPosition;
use function count;

class v844NetworkChunkPublisherUpdatePacket extends NetworkChunkPublisherUpdatePacket {

	static public function fromLatest(NetworkChunkPublisherUpdatePacket $packet) : self    {
		$npk = new self();
		$npk->blockPosition = $packet->blockPosition;
		$npk->radius = $packet->radius;
		$npk->savedChunks = $packet->savedChunks;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->blockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
		$this->radius = VarInt::readUnsignedInt($in);

		$count = LE::readUnsignedInt($in);
		if($count > self::MAX_SAVED_CHUNKS){
			throw new PacketDecodeException("Expected at most " . self::MAX_SAVED_CHUNKS . " saved chunks, got " . $count);
		}
		for($i = 0, $this->savedChunks = []; $i < $count; $i++){
			$this->savedChunks[] = ChunkPosition::read($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->blockPosition);
		VarInt::writeUnsignedInt($out, $this->radius);

		LE::writeUnsignedInt($out, count($this->savedChunks));
		foreach($this->savedChunks as $chunk){
			$chunk->write($out);
		}
	}
}
