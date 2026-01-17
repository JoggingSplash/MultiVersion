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

class CommandParameter
{

	public string $paramName;
	public int $paramType;
	public bool $isOptional;
	public int $flags = 0; //shows enum name if 1, always zero except for in /gamerule command
	public ?CommandEnum $enum = null;
	public ?string $postfix = null;

	public static function standard(string $name, int $flag_valid, int $type, int $flags = 0, bool $optional = false) : self
	{
		return self::baseline($name, $flag_valid | $type, $flags, $optional);
	}

	private static function baseline(string $name, int $type, int $flags, bool $optional) : self
	{
		$result = new self();
		$result->paramName = $name;
		$result->paramType = $type;
		$result->flags = $flags;
		$result->isOptional = $optional;
		return $result;
	}
}
