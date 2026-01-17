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

namespace cisco\network\assemble\command;

class CommandEnum
{

	/**
	 * @param string[] $enumValues
	 * @param bool     $isSoft     Whether the enum is dynamic, i.e. can be updated during the game session
	 *
	 * @phpstan-param list<string> $enumValues
	 */
	public function __construct(
		private string $enumName,
		private array  $enumValues,
		private bool   $isSoft = false
	)
	{
	}

	public function getName() : string
	{
		return $this->enumName;
	}

	/**
	 * @return string[]
	 * @phpstan-return list<string>
	 */
	public function getValues() : array
	{
		return $this->enumValues;
	}

	/**
	 * @return bool Whether the enum is dynamic, i.e. can be updated during the game session
	 */
	public function isSoft() : bool
	{
		return $this->isSoft;
	}
}
