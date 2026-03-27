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

namespace cisco\network\chunk\io;

use pocketmine\world\format\io\exception\CorruptedChunkException;
use function str_repeat;
use function strlen;

final class SubChunkDatum {

	private static function assignData(string $data, int $length, bool $preserve) : string{
		if(strlen($data) !== $length){
			if($preserve && $data === ""){
				throw new CorruptedChunkException("Invalid non-zero length given, expected $length, got " . strlen($data));
			}
			return str_repeat("\x00", $length);
		}
		return $data;
	}

	static public function empty() : self    {
		return new self(
			self::assignData("", 4096, false),
			self::assignData("", 2048, false),
		);
	}

	public function __construct(
		public string $blocksId,
		public string $blocksData,
	){
		$this->blocksId = self::assignData($this->blocksId, 4096, true);
		$this->blocksData = self::assignData($this->blocksData, 2048, true);
	}
}
