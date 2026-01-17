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

namespace cisco\network\mcpe;

use pocketmine\block\RuntimeBlockStateRegistry;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\utils\SingletonTrait;

class MVRuntimeIdToStateId
{
	use SingletonTrait;

	private array $runtimeIdToStateId = [];

	public function __construct()
	{
		$blockTranslator = TypeConverter::getInstance()->getBlockTranslator();
		foreach (RuntimeBlockStateRegistry::getInstance()->getAllKnownStates() as $state) {
			$blockRuntimeId = $blockTranslator->internalIdToNetworkId($stateId = $state->getStateId());
			$this->runtimeIdToStateId[$blockRuntimeId] = $stateId;
		}
	}

	public function getStateIdFromRuntimeId(int $blockRuntimeId) : int
	{
		return $this->runtimeIdToStateId[$blockRuntimeId] ??= 0;
	}
}
