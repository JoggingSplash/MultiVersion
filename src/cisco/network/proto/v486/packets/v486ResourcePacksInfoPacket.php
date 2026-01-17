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

namespace cisco\network\proto\v486\packets;

use cisco\network\proto\v486\packets\types\resource\v486ResourcePackInfoEntry;
use cisco\network\proto\v486\structure\v486ProtocolInfo;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function array_map;
use function count;

class v486ResourcePacksInfoPacket extends ResourcePacksInfoPacket
{
	public const NETWORK_ID = v486ProtocolInfo::RESOURCE_PACKS_INFO_PACKET;
	protected bool $_forceServerPacks;
	private array $_resourcePackEntries = [];

	public static function fromLatest(ResourcePacksInfoPacket $pk) : self
	{
		$npk = new self();
		$npk->mustAccept = $pk->mustAccept;
		$npk->hasScripts = $pk->hasScripts;
		$npk->_forceServerPacks = true;
		$npk->_resourcePackEntries = array_map(v486ResourcePackInfoEntry::fromLatest(...), $pk->resourcePackEntries);
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->mustAccept = CommonTypes::getBool($in);
		$this->hasScripts = CommonTypes::getBool($in);
		$this->_forceServerPacks = CommonTypes::getBool($in);

		//behaviour but we dont handle it
		LE::readUnsignedShort($in);

		$resourcePackCount = LE::readUnsignedShort($in);
		while ($resourcePackCount-- > 0) {
			$this->_resourcePackEntries[] = v486ResourcePackInfoEntry::read($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putBool($out, $this->mustAccept);
		CommonTypes::putBool($out, $this->hasScripts);
		CommonTypes::putBool($out, $this->_forceServerPacks);
		LE::writeUnsignedShort($out, 0);

		LE::writeUnsignedShort($out, count($this->_resourcePackEntries));
		foreach ($this->_resourcePackEntries as $entry) {
			$entry->write($out);
		}
	}

}
