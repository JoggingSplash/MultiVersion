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
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\TextPacket;
use function count;

class v419TextPacket extends TextPacket
{
	public const NETWORK_ID = v419ProtocolInfo::TEXT_PACKET;

	public static function fromLatest(TextPacket $pk) : self
	{
		$npk = new self();
		$npk->type = $pk->type;
		if(isset($pk->sourceName)){
			$npk->sourceName = $pk->sourceName;
		}
		if(isset($pk->message)){
			$npk->message = $pk->message;
		}
		$npk->needsTranslation = $pk->needsTranslation;
		$npk->xboxUserId = $pk->xboxUserId;
		$npk->platformChatId = $pk->platformChatId;
		$npk->parameters = $pk->parameters;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->type = Byte::readUnsigned($in);
		$this->needsTranslation = CommonTypes::getBool($in);
		switch ($this->type) {
			case self::TYPE_CHAT:
			case self::TYPE_WHISPER:
				/** @noinspection PhpMissingBreakStatementInspection */
			case self::TYPE_ANNOUNCEMENT:
				$this->sourceName = CommonTypes::getString($in);
			case self::TYPE_RAW:
			case self::TYPE_TIP:
			case self::TYPE_SYSTEM:
			case self::TYPE_JSON_WHISPER:
			case self::TYPE_JSON:
			case self::TYPE_JSON_ANNOUNCEMENT:
				$this->message = CommonTypes::getString($in);
				break;

			case self::TYPE_TRANSLATION:
			case self::TYPE_POPUP:
			case self::TYPE_JUKEBOX_POPUP:
				$this->message = CommonTypes::getString($in);
				$count = VarInt::readUnsignedInt($in);
				for ($i = 0; $i < $count; ++$i) {
					$this->parameters[] = CommonTypes::getString($in);
				}
				break;
		}

		$this->xboxUserId = CommonTypes::getString($in);
		$this->platformChatId = CommonTypes::getString($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->type);
		CommonTypes::putBool($out, $this->needsTranslation);
		switch ($this->type) {
			case self::TYPE_CHAT:
			case self::TYPE_WHISPER:
				/** @noinspection PhpMissingBreakStatementInspection */
			case self::TYPE_ANNOUNCEMENT:
				CommonTypes::putString($out, $this->sourceName);
			case self::TYPE_RAW:
			case self::TYPE_TIP:
			case self::TYPE_SYSTEM:
			case self::TYPE_JSON_WHISPER:
			case self::TYPE_JSON:
			case self::TYPE_JSON_ANNOUNCEMENT:
				CommonTypes::putString($out, $this->message);
				break;

			case self::TYPE_TRANSLATION:
			case self::TYPE_POPUP:
			case self::TYPE_JUKEBOX_POPUP:
				CommonTypes::putString($out, $this->message);
				VarInt::writeUnsignedInt($out, count($this->parameters));
				foreach ($this->parameters as $p) {
					CommonTypes::putString($out, $p);
				}
				break;
		}

		CommonTypes::putString($out, $this->xboxUserId);
		CommonTypes::putString($out, $this->platformChatId);
	}
}
