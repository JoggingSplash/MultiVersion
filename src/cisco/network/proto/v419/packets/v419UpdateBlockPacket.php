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

use cisco\network\mcpe\MVRuntimeIdToStateId;
use cisco\network\proto\v419\structure\v419ProtocolInfo;
use cisco\network\proto\v419\v419TypeConverter;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;

class v419UpdateBlockPacket extends UpdateBlockPacket
{

	public const NETWORK_ID = v419ProtocolInfo::UPDATE_BLOCK_PACKET;

	public static function fromLatest(UpdateBlockPacket $packet) : v419UpdateBlockPacket
	{
		$npk = new v419UpdateBlockPacket();
		$npk->blockPosition = $packet->blockPosition;
		$npk->flags = $packet->flags;
		$npk->dataLayerId = $packet->dataLayerId;
		$npk->blockRuntimeId = v419TypeConverter::getInstance()
			->getConverter()
			->getMVBlockTranslator()
			->internalIdToNetworkId(
				MVRuntimeIdToStateId::getInstance()
					->getStateIdFromRuntimeId(
						$packet->blockRuntimeId
					)
			);
		return $npk;
	}

	//no need to override payload
}
