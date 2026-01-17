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

namespace cisco\network\proto\v860\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackStackEntry;
use function count;

class v860ResourcePackStackPacket extends ResourcePackStackPacket
{

	protected array $behaviorPackStack = []; // NOOP, Since 1.21.130 this is no longer in the packet

	public static function fromLatest(ResourcePackStackPacket $pk) : self
	{
		$npk = new self();
		$npk->resourcePackStack = $pk->resourcePackStack;
		$npk->mustAccept = $pk->mustAccept;
		$npk->baseGameVersion = $pk->baseGameVersion;
		$npk->experiments = $pk->experiments;
		$npk->useVanillaEditorPacks = $pk->useVanillaEditorPacks;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->mustAccept = CommonTypes::getBool($in);
		$behaviorPackCount = VarInt::readUnsignedInt($in);
		while ($behaviorPackCount-- > 0) {
			$this->behaviorPackStack[] = ResourcePackStackEntry::read($in);
		}

		$resourcePackCount = VarInt::readUnsignedInt($in);
		while ($resourcePackCount-- > 0) {
			$this->resourcePackStack[] = ResourcePackStackEntry::read($in);
		}

		$this->baseGameVersion = CommonTypes::getString($in);
		$this->experiments = Experiments::read($in);
		$this->useVanillaEditorPacks = CommonTypes::getBool($in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putBool($out, $this->mustAccept);

		VarInt::writeUnsignedInt($out, count($this->behaviorPackStack));
		foreach ($this->behaviorPackStack as $entry) {
			$entry->write($out);
		}

		VarInt::writeUnsignedInt($out, count($this->resourcePackStack));
		foreach ($this->resourcePackStack as $entry) {
			$entry->write($out);
		}

		CommonTypes::putString($out, $this->baseGameVersion);
		$this->experiments->write($out);
		CommonTypes::putBool($out, $this->useVanillaEditorPacks);
	}
}
