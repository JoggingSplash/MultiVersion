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

namespace cisco\network\proto\v844\structure;

use cisco\network\assemble\CommandOriginData;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

final class v844CommonTypes
{

	private function __construct()
	{

	}

	static public function getCommandOriginData(ByteBufferReader $in) : CommandOriginData {
		$result = new CommandOriginData();
		$result->type = VarInt::readUnsignedInt($in);
		$result->uuid = CommonTypes::getUUID($in);
		$result->requestId = CommonTypes::getString($in);
		if ($result->type === CommandOriginData::ORIGIN_DEV_CONSOLE || $result->type === CommandOriginData::ORIGIN_TEST) {
			$result->playerActorUniqueId = VarInt::readSignedLong($in);
		}
		return $result;
	}

	static public function putCommandOriginData(ByteBufferWriter $out, CommandOriginData $data) : void
	{
		VarInt::writeUnsignedInt($out, $data->type);
		CommonTypes::putUUID($out, $data->uuid);
		CommonTypes::putString($out, $data->requestId);

		if ($data->type === CommandOriginData::ORIGIN_DEV_CONSOLE || $data->type === CommandOriginData::ORIGIN_TEST) {
			VarInt::writeSignedLong($out, $data->playerActorUniqueId);
		}
	}
}
