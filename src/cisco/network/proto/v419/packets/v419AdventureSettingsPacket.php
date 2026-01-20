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
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\types\command\CommandPermissions;
use pocketmine\network\mcpe\protocol\types\PlayerPermissions;

class v419AdventureSettingsPacket extends DataPacket implements ServerboundPacket, ClientboundPacket
{

	public const NETWORK_ID = v419ProtocolInfo::ADVENTURE_SETTINGS_PACKET;

	/**
	 * This constant is used to identify flags that should be set on the second field. In a sensible world, these
	 * flags would all be set on the same packet field, but as of MCPE 1.2, the new abilities flags have for some
	 * reason been assigned a separate field.
	 */
	public const BITFLAG_SECOND_SET = 1 << 16;

	public const WORLD_IMMUTABLE = 0x01;
	public const NO_PVP = 0x02;

	public const AUTO_JUMP = 0x20;
	public const ALLOW_FLIGHT = 0x40;
	public const NO_CLIP = 0x80;
	public const WORLD_BUILDER = 0x100;
	public const FLYING = 0x200;
	public const MUTED = 0x400;

	public const MINE = 0x01 | self::BITFLAG_SECOND_SET;
	public const DOORS_AND_SWITCHES = 0x02 | self::BITFLAG_SECOND_SET;
	public const OPEN_CONTAINERS = 0x04 | self::BITFLAG_SECOND_SET;
	public const ATTACK_PLAYERS = 0x08 | self::BITFLAG_SECOND_SET;
	public const ATTACK_MOBS = 0x10 | self::BITFLAG_SECOND_SET;
	public const OPERATOR = 0x20 | self::BITFLAG_SECOND_SET;
	public const TELEPORT = 0x80 | self::BITFLAG_SECOND_SET;
	public const BUILD = 0x100 | self::BITFLAG_SECOND_SET;
	public const DEFAULT = 0x200 | self::BITFLAG_SECOND_SET;

	public int $flags = 0;
	public int $commandPermission = 0; // CommandPermissions::NORMAL->value
	public int $flags2 = -1;
	public int $playerPermission = PlayerPermissions::MEMBER;
	public int $customFlags = 0; //...
	public int $targetActorUniqueId; //This is a little-endian long, NOT a var-long. (WTF Mojang)

	/**
	 * @generate-create-func
	 */
	public static function create(int $flags, int $commandPermission, int $flags2, int $playerPermission, int $customFlags, int $targetActorUniqueId) : self
	{
		$result = new self();
		$result->flags = $flags;
		$result->commandPermission = $commandPermission;
		$result->flags2 = $flags2;
		$result->playerPermission = $playerPermission;
		$result->customFlags = $customFlags;
		$result->targetActorUniqueId = $targetActorUniqueId;
		return $result;
	}

	public function getFlag(int $flag) : bool
	{
		if (($flag & self::BITFLAG_SECOND_SET) !== 0) {
			return ($this->flags2 & $flag) !== 0;
		}

		return ($this->flags & $flag) !== 0;
	}

	public function setFlag(int $flag, bool $value) : void
	{
		$property = ($flag & self::BITFLAG_SECOND_SET) !== 0
			? 'flags2'
			: 'flags';

		if ($value) {
			$this->$property |= $flag;
			return;
		}

		$this->$property &= ~$flag;
	}

	public function handle(PacketHandlerInterface $handler) : bool
	{
		return true;
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, $this->flags);
		VarInt::writeUnsignedInt($out, $this->commandPermission);
		VarInt::writeUnsignedInt($out, $this->flags2);
		VarInt::writeUnsignedInt($out, $this->playerPermission);
		VarInt::writeUnsignedInt($out, $this->customFlags);
		LE::writeUnsignedLong($out, $this->targetActorUniqueId);
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->flags = VarInt::readUnsignedInt($in);
		$this->commandPermission = VarInt::readUnsignedInt($in);
		$this->flags2 = VarInt::readUnsignedInt($in);
		$this->playerPermission = VarInt::readUnsignedInt($in);
		$this->customFlags = VarInt::readUnsignedInt($in);
		$this->targetActorUniqueId = LE::readUnsignedLong($in);
	}
}
