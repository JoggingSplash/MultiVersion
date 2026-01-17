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

namespace cisco\network\proto\v361\handler;

use cisco\network\proto\v361\packets\v361ActorFallPacket;
use cisco\network\proto\v361\packets\v361PlayerInputPacket;
use cisco\network\proto\v361\packets\v361RiderJumpPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;

interface v361PacketHandler extends PacketHandlerInterface {

	public function handlePlayerInput(v361PlayerInputPacket $packet) : bool;

	public function handleActorFall(v361ActorFallPacket $packet) : bool;

	public function handleRiderJump(v361RiderJumpPacket $packet) : bool;
}
