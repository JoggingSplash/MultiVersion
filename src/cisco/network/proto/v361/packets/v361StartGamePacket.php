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

namespace cisco\network\proto\v361\packets;

use cisco\network\proto\v361\structure\v361ProtocolInfo;
use pocketmine\network\mcpe\protocol\StartGamePacket;

class v361StartGamePacket extends StartGamePacket {
	public const NETWORK_ID = v361ProtocolInfo::START_GAME_PACKET;

	//TODO: https://github.com/pmmp/BedrockProtocol/blob/bedrock-1.12.0/src/StartGamePacket.php

	static public function fromLatest() : self    {
		$npk = new self();

		return $npk;
	}
}
