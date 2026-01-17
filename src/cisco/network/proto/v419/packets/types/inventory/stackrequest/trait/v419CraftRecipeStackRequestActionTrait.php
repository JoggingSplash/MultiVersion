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

namespace cisco\network\proto\v419\packets\types\inventory\stackrequest\trait;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;

trait v419CraftRecipeStackRequestActionTrait
{

	/** @var int */
	private $recipeId;

	final public function __construct(int $recipeId)
	{
		$this->recipeId = $recipeId;
	}

	public static function read(ByteBufferReader $in) : self
	{
		$recipeId = VarInt::readSignedInt($in);
		return new self($recipeId);
	}

	public function getRecipeId() : int
	{
		return $this->recipeId;
	}

	public function write(ByteBufferWriter $out) : void
	{
		VarInt::writeSignedInt($out, $this->recipeId);
	}
}
