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

class CommandData
{
	/**
	 * @param CommandOverload[]       $overloads
	 * @param ChainedSubCommandData[] $chainedSubCommandData
	 */
	public function __construct(
		public string       $name,
		public string       $description,
		public int          $flags,
		public int          $permission,
		public ?CommandEnum $aliases,
		public array        $overloads,
		public array        $chainedSubCommandData
	)
	{
		(function (CommandOverload ...$overloads) : void {
		})(...$overloads);
		(function (ChainedSubCommandData ...$chainedSubCommandData) : void {
		})(...$chainedSubCommandData);
	}

	public function getName() : string
	{
		return $this->name;
	}

	public function getDescription() : string
	{
		return $this->description;
	}

	public function getFlags() : int
	{
		return $this->flags;
	}

	public function getPermission() : int
	{
		return $this->permission;
	}

	public function getAliases() : ?CommandEnum
	{
		return $this->aliases;
	}

	/**
	 * @return CommandOverload[]
	 */
	public function getOverloads() : array
	{
		return $this->overloads;
	}

	/**
	 * @return ChainedSubCommandData[]
	 */
	public function getChainedSubCommandData() : array
	{
		return $this->chainedSubCommandData;
	}
}
