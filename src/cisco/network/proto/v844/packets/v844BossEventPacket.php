<?php

/*
 *
 *
 *      __  ___      ____  _ _    __               _
 *     /  |/  /_  __/ / /_(_) |  / /__  __________(_)___  ____
 *    / /|_/ / / / / / __/ /| | / / _ \/ ___/ ___/ / __ \/ __ \
 *   / /  / / /_/ / / /_/ / | |/ /  __/ /  (__  ) / /_/ / / / /
 *  /_/  /_/\__,_/_/\__/_/  |___/\___/_/  /____/_/\____/_/ /_/
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  @author JoggingSplash23
 *  @link https://www.github.com/JoggingSplash
 *
 *
 */

declare(strict_types=1);

namespace cisco\network\proto\v844\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class v844BossEventPacket extends BossEventPacket {

	public bool $darkenScreen = false;

	static public function fromLatest(BossEventPacket $packet) : v844BossEventPacket{
		$result = new self();
		$result->bossActorUniqueId = $packet->bossActorUniqueId;
		$result->eventType = $packet->eventType;
		$result->playerActorUniqueId = $packet->playerActorUniqueId;
		$result->title = $packet->title;
		$result->filteredTitle = $packet->filteredTitle;
		$result->healthPercent = $packet->healthPercent;
		$result->color = $packet->color;
		$result->overlay = $packet->overlay;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void {
		$this->bossActorUniqueId = CommonTypes::getActorUniqueId($in);
		$this->eventType = VarInt::readUnsignedInt($in);
		switch($this->eventType){
			case self::TYPE_REGISTER_PLAYER:
			case self::TYPE_UNREGISTER_PLAYER:
			case self::TYPE_QUERY:
				$this->playerActorUniqueId = CommonTypes::getActorUniqueId($in);
				break;
			/** @noinspection PhpMissingBreakStatementInspection */
			case self::TYPE_SHOW:
				$this->title = CommonTypes::getString($in);
				$this->filteredTitle = CommonTypes::getString($in);
				$this->healthPercent = LE::readFloat($in);
			/** @noinspection PhpMissingBreakStatementInspection */
			case self::TYPE_PROPERTIES:
				$this->darkenScreen = match($raw = LE::readUnsignedShort($in)){
					0 => false,
					1 => true,
					default => throw new PacketDecodeException("Invalid darkenScreen value $raw"),
				};
			case self::TYPE_TEXTURE:
				$this->color = VarInt::readUnsignedInt($in);
				$this->overlay = VarInt::readUnsignedInt($in);
				break;
			case self::TYPE_HEALTH_PERCENT:
				$this->healthPercent = LE::readFloat($in);
				break;
			case self::TYPE_TITLE:
				$this->title = CommonTypes::getString($in);
				$this->filteredTitle = CommonTypes::getString($in);
				break;
			default:
				break;
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void {
		CommonTypes::putActorUniqueId($out, $this->bossActorUniqueId);
		VarInt::writeUnsignedInt($out, $this->eventType);
		switch($this->eventType){
			case self::TYPE_REGISTER_PLAYER:
			case self::TYPE_UNREGISTER_PLAYER:
			case self::TYPE_QUERY:
				CommonTypes::putActorUniqueId($out, $this->playerActorUniqueId);
				break;
			/** @noinspection PhpMissingBreakStatementInspection */
			case self::TYPE_SHOW:
				CommonTypes::putString($out, $this->title);
				CommonTypes::putString($out, $this->filteredTitle);
				LE::writeFloat($out, $this->healthPercent);
			/** @noinspection PhpMissingBreakStatementInspection */
			case self::TYPE_PROPERTIES:
				LE::writeUnsignedShort($out, $this->darkenScreen ? 1 : 0);
			case self::TYPE_TEXTURE:
				VarInt::writeUnsignedInt($out, $this->color);
				VarInt::writeUnsignedInt($out, $this->overlay);
				break;
			case self::TYPE_HEALTH_PERCENT:
				LE::writeFloat($out, $this->healthPercent);
				break;
			case self::TYPE_TITLE:
				CommonTypes::putString($out, $this->title);
				CommonTypes::putString($out, $this->filteredTitle);
				break;
			default:
				break;
		}
	}
}
