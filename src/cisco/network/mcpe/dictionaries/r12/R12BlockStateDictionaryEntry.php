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

namespace cisco\network\mcpe\dictionaries\r12;

use pocketmine\nbt\tag\CompoundTag;

final class R12BlockStateDictionaryEntry {

	public function __construct(
		private string $id,
		private int $meta,
		private CompoundTag $blockState){
	}

	public function getId() : string{
		return $this->id;
	}

	public function getMeta() : int{
		return $this->meta;
	}

	public function getBlockState() : CompoundTag{
		return $this->blockState;
	}

	public function __toString(){
		return "id=$this->id, meta=$this->meta, nbt=$this->blockState";
	}
}
