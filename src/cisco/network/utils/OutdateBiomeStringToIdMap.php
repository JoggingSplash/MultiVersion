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

namespace cisco\network\utils;

use cisco\Loader;
use pocketmine\data\bedrock\LegacyToStringIdMap;
use pocketmine\utils\SingletonTrait;
use Symfony\Component\Filesystem\Path;

final class OutdateBiomeStringToIdMap extends LegacyToStringIdMap {
	use SingletonTrait;

	public function __construct(){
		parent::__construct(Path::join(Loader::getPluginResourcePath(), "outdate_biome_id_map.json"));
	}

}
