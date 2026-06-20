<?php

/*
 *
 *
 *      __  ___      ____  _ _    __               _
 *     /  |/  /_  __/ / /_(_) |  / /__  __________(_)___  ____
 *    / /|_/ / / / / / __/ /| | / / _ \/ ___/ ___/ / __ \/ __ \
 *   / /  / / /_/ / / /_/ / | |/ /  __/ /  (__  ) / /_/ / / / /
 *  /_/  /_/\__,_/_/\__/_/  |___/\___/_/  /____/_/\____/_/ /_/
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  @author JoggingSplash23
 *  @link https://www.github.com/JoggingSplash
 *
 *
 */

declare(strict_types=1);

namespace cisco\network\utils;

use cisco\Loader;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Filesystem;
use pocketmine\utils\SingletonTrait;
use Symfony\Component\Filesystem\Path;
use function is_array;
use function json_decode;

final class LevelSoundMap {
	use SingletonTrait;

	private static function make() : self{
		$maps = json_decode(Filesystem::fileGetContents(Path::join(Loader::getPluginResourcePath(), "level_sound_id_map.json")), true);
		if(!is_array($maps)){
			throw new AssumptionFailedError("expected level_soundmap to be array while decoded");
		}
		return new self($maps);
	}

	private array $intToStringMap = [];
	private array $stringToIntMap = [];

	public function __construct(array $map){
		foreach ($map as $key => $value) {
			$this->intToStringMap[$value] = $key;
			$this->stringToIntMap[$key] = $value;
		}
	}

	public function lookupInt(string $key) : int{
		return $this->stringToIntMap[$key] ??= 0;
	}

	public function lookupString(int $key) : string{
		return $this->intToStringMap[$key] ??= 'item.use.on';
	}

}
