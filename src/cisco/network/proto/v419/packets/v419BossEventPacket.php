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

namespace cisco\network\proto\v419\packets;

use cisco\network\proto\v419\structure\v419ProtocolInfo;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v419BossEventPacket extends BossEventPacket
{

	public const NETWORK_ID = v419ProtocolInfo::BOSS_EVENT_PACKET;

	public int $unknownShort;

	public static function fromLatest(BossEventPacket $packet) : v419BossEventPacket
	{
		$npk = new self();
		$npk->bossActorUniqueId = $packet->bossActorUniqueId;
		$npk->eventType = $packet->eventType;

		if (isset($packet->playerActorUniqueId)) {
			$npk->playerActorUniqueId = $packet->playerActorUniqueId;
		}
		if (isset($packet->title)) {
			$npk->title = $packet->title;
		}
		if (isset($packet->healthPercent)) {
			$npk->healthPercent = $packet->healthPercent;
		}
		if (isset($packet->color)) {
			$npk->color = $packet->color;
		}
		if (isset($packet->overlay)) {
			$npk->overlay = $packet->overlay;
		}

		if (isset($packet->darkenScreen)) {
			$npk->unknownShort = $packet->darkenScreen ? 1 : 0;
		}

		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->bossActorUniqueId = CommonTypes::getActorUniqueId($in);
		$this->eventType = VarInt::readUnsignedInt($in);
		switch ($this->eventType) {
			case self::TYPE_REGISTER_PLAYER:
			case self::TYPE_UNREGISTER_PLAYER:
				$this->playerActorUniqueId = CommonTypes::getActorUniqueId($in);
				break;
			/** @noinspection PhpMissingBreakStatementInspection */
			case self::TYPE_SHOW:
				$this->title = CommonTypes::getString($in);
				$this->healthPercent = LE::readFloat($in);
			/** @noinspection PhpMissingBreakStatementInspection */
			case self::TYPE_PROPERTIES:
				$this->unknownShort = LE::readUnsignedShort($in);
			case self::TYPE_TEXTURE:
				$this->color = VarInt::readUnsignedInt($in);
				$this->overlay = VarInt::readUnsignedInt($in);
				break;
			case self::TYPE_HEALTH_PERCENT:
				$this->healthPercent = LE::readFloat($in);
				break;
			case self::TYPE_TITLE:
				$this->title = CommonTypes::getString($in);
				break;
			default:
				//NOOP
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putActorUniqueId($out, $this->bossActorUniqueId);
		VarInt::writeUnsignedInt($out, $this->eventType);
		switch ($this->eventType) {
			case self::TYPE_REGISTER_PLAYER:
			case self::TYPE_UNREGISTER_PLAYER:
				CommonTypes::putActorUniqueId($out, $this->playerActorUniqueId);
				break;
			/** @noinspection PhpMissingBreakStatementInspection */
			case self::TYPE_SHOW:
				CommonTypes::putString($out, $this->title);
				LE::writeFloat($out, $this->healthPercent);
			/** @noinspection PhpMissingBreakStatementInspection */
			case self::TYPE_PROPERTIES:
				LE::writeUnsignedShort($out, $this->unknownShort);
			case self::TYPE_TEXTURE:
				VarInt::writeUnsignedInt($out, $this->color);
				VarInt::writeUnsignedInt($out, $this->overlay);
				break;
			case self::TYPE_HEALTH_PERCENT:
				LE::writeFloat($out, $this->healthPercent);
				break;
			case self::TYPE_TITLE:
				CommonTypes::putString($out, $this->title);
				break;
			default:
				break;
		}
	}

}
