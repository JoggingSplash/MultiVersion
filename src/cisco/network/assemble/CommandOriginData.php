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

namespace cisco\network\assemble;

use pocketmine\network\mcpe\protocol\types\command\CommandOriginData as OriginData;
use pocketmine\network\PacketHandlingException;
use Ramsey\Uuid\UuidInterface;

class CommandOriginData
{
	public const ORIGIN_PLAYER = 0;
	public const ORIGIN_BLOCK = 1;
	public const ORIGIN_MINECART_BLOCK = 2;
	public const ORIGIN_DEV_CONSOLE = 3;
	public const ORIGIN_TEST = 4;
	public const ORIGIN_AUTOMATION_PLAYER = 5;
	public const ORIGIN_CLIENT_AUTOMATION = 6;
	public const ORIGIN_DEDICATED_SERVER = 7;
	public const ORIGIN_ENTITY = 8;
	public const ORIGIN_VIRTUAL = 9;
	public const ORIGIN_GAME_ARGUMENT = 10;
	public const ORIGIN_ENTITY_SERVER = 11; //???

	public int $type;
	public UuidInterface $uuid;
	public string $requestId;
	public int $playerActorUniqueId;

	static public function fromLatest(OriginData $data) : CommandOriginData{
		$result = new CommandOriginData();
		$result->playerActorUniqueId = $data->playerActorUniqueId;
		$result->requestId = $data->requestId;
		$result->uuid = $data->uuid;
		$result->type = match ($data->type) {
			OriginData::ORIGIN_PLAYER => self::ORIGIN_PLAYER,
			OriginData::ORIGIN_BLOCK => self::ORIGIN_BLOCK,
			OriginData::ORIGIN_MINECART_BLOCK => self::ORIGIN_MINECART_BLOCK,
			OriginData::ORIGIN_DEV_CONSOLE => self::ORIGIN_DEV_CONSOLE,
			OriginData::ORIGIN_TEST => self::ORIGIN_TEST,
			OriginData::ORIGIN_AUTOMATION_PLAYER => self::ORIGIN_AUTOMATION_PLAYER,
			OriginData::ORIGIN_ENTITY => self::ORIGIN_ENTITY,
			OriginData::ORIGIN_DEDICATED_SERVER => self::ORIGIN_DEDICATED_SERVER,
			OriginData::ORIGIN_CLIENT_AUTOMATION => self::ORIGIN_CLIENT_AUTOMATION,
			OriginData::ORIGIN_VIRTUAL => self::ORIGIN_VIRTUAL,
			OriginData::ORIGIN_GAME_ARGUMENT => self::ORIGIN_GAME_ARGUMENT,
			default => throw new PacketHandlingException("Unsupported type for CommandOriginData")
		};
		return $result;
	}

	/**
	 * Wraps the old CommandOriginData into the newest.
	 * Maybe we will need this
	 */
	final public function toLatest() : OriginData {
		$result = new OriginData();
		$result->playerActorUniqueId = $this->playerActorUniqueId;
		$result->requestId = $this->requestId;
		$result->uuid = $this->uuid;
		$result->type = match ($this->type) {
			self::ORIGIN_PLAYER => OriginData::ORIGIN_PLAYER,
			self::ORIGIN_BLOCK => OriginData::ORIGIN_BLOCK,
			self::ORIGIN_MINECART_BLOCK => OriginData::ORIGIN_MINECART_BLOCK,
			self::ORIGIN_DEV_CONSOLE => OriginData::ORIGIN_DEV_CONSOLE,
			self::ORIGIN_TEST => OriginData::ORIGIN_TEST,
			self::ORIGIN_AUTOMATION_PLAYER => OriginData::ORIGIN_AUTOMATION_PLAYER,
			self::ORIGIN_ENTITY => OriginData::ORIGIN_ENTITY,
			self::ORIGIN_DEDICATED_SERVER => OriginData::ORIGIN_DEDICATED_SERVER,
			self::ORIGIN_CLIENT_AUTOMATION => OriginData::ORIGIN_CLIENT_AUTOMATION,
			self::ORIGIN_VIRTUAL => OriginData::ORIGIN_VIRTUAL,
			self::ORIGIN_GAME_ARGUMENT => OriginData::ORIGIN_GAME_ARGUMENT,
			default => throw new PacketHandlingException("Unsupported type for CommandOriginData")
		};
		return $result;
	}
}
