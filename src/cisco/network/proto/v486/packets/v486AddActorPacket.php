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

use pocketmine\network\mcpe\protocol\AddActorPacket;

class v486AddActorPacket extends AddActorPacket
{

	public static function fromLatest(AddActorPacket $pk) : self
	{
		$npk = new self();
		$npk->actorUniqueId = $pk->actorUniqueId;
		$npk->actorRuntimeId = $pk->actorRuntimeId;
		$npk->type = $pk->type;
		$npk->position = $pk->position;
		$npk->motion = $pk->motion;
		$npk->pitch = $pk->pitch;
		$npk->yaw = $pk->yaw;
		$npk->headYaw = $pk->headYaw;
		$npk->attributes = $pk->attributes;
		$npk->metadata = $pk->metadata;
		$npk->links = $pk->links;
		return $npk;
	}

}
