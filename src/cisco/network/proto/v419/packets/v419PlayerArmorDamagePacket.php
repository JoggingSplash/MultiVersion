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
use pocketmine\network\mcpe\protocol\PlayerArmorDamagePacket;
use pocketmine\network\mcpe\protocol\types\ArmorSlotAndDamagePair;

class v419PlayerArmorDamagePacket extends PlayerArmorDamagePacket
{

	public const NETWORK_ID = v419ProtocolInfo::PLAYER_ARMOR_DAMAGE_PACKET;

	private const FLAG_HEAD = 0;
	private const FLAG_CHEST = 1;
	private const FLAG_LEGS = 2;
	private const FLAG_FEET = 3;

	private ?int $headSlotDamage;
	private ?int $chestSlotDamage;
	private ?int $legsSlotDamage;
	private ?int $feetSlotDamage;

	static public function fromLatest(PlayerArmorDamagePacket $packet) : v419PlayerArmorDamagePacket
	{
		$npk = new v419PlayerArmorDamagePacket();
		$npk->headSlotDamage = null;
		$npk->chestSlotDamage = null;
		$npk->legsSlotDamage = null;
		$npk->feetSlotDamage = null;

		foreach ($packet->getArmorSlotAndDamagePairs() as $pair) {
			self::readPaired($npk, $pair); // theres diff between LE and VarInt ??? (latest and this one r diff)
		}

		return $npk;
	}

	static private function readPaired(v419PlayerArmorDamagePacket $npk, ArmorSlotAndDamagePair $pair) : void
	{
		switch ($pair->getSlot()) {
			case self::FLAG_HEAD:
				$npk->headSlotDamage = $pair->getDamage();
				break;
			case self::FLAG_CHEST:
				$npk->chestSlotDamage = $pair->getDamage();
				break;
			case self::FLAG_LEGS:
				$npk->legsSlotDamage = $pair->getDamage();
				break;
			case self::FLAG_FEET:
				$npk->feetSlotDamage = $pair->getDamage();
				break;
		}
	}

	public function getHeadSlotDamage() : ?int
	{
		return $this->headSlotDamage;
	}

	public function getChestSlotDamage() : ?int
	{
		return $this->chestSlotDamage;
	}

	public function getLegsSlotDamage() : ?int
	{
		return $this->legsSlotDamage;
	}

	public function getFeetSlotDamage() : ?int
	{
		return $this->feetSlotDamage;
	}

	public function getBodySlotDamage() : ?int
	{
		return $this->bodySlotDamage;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$flags = Byte::readUnsigned($in);

		$this->headSlotDamage = $this->maybeReadDamage($flags, self::FLAG_HEAD, $in);
		$this->chestSlotDamage = $this->maybeReadDamage($flags, self::FLAG_CHEST, $in);
		$this->legsSlotDamage = $this->maybeReadDamage($flags, self::FLAG_LEGS, $in);
		$this->feetSlotDamage = $this->maybeReadDamage($flags, self::FLAG_FEET, $in);
	}

	private function maybeReadDamage(int $flags, int $flag, ByteBufferReader $in) : ?int
	{
		if (($flags & (1 << $flag)) !== 0) {
			return VarInt::readSignedInt($in);
		}
		return null;
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out,
			$this->composeFlag($this->headSlotDamage, self::FLAG_HEAD) |
			$this->composeFlag($this->chestSlotDamage, self::FLAG_CHEST) |
			$this->composeFlag($this->legsSlotDamage, self::FLAG_LEGS) |
			$this->composeFlag($this->feetSlotDamage, self::FLAG_FEET)
		);

		$this->maybeWriteDamage($this->headSlotDamage, $out);
		$this->maybeWriteDamage($this->chestSlotDamage, $out);
		$this->maybeWriteDamage($this->legsSlotDamage, $out);
		$this->maybeWriteDamage($this->feetSlotDamage, $out);
	}

	private function composeFlag(?int $field, int $flag) : int
	{
		return $field !== null ? (1 << $flag) : 0;
	}

	private function maybeWriteDamage(?int $field, ByteBufferWriter $out) : void
	{
		if ($field !== null) {
			VarInt::writeSignedInt($out, $field);
		}
	}
}
