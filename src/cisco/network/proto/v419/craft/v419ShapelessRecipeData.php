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

namespace cisco\network\proto\v419\craft;

final class v419ShapelessRecipeData implements \JsonSerializable {

	/**
	 * @required
	 * @var v419RecipeIngredientData[]
	 */
	public array $input;

	/**
	 * @required
	 * @var v419ItemStackData[]
	 */
	public array $output;

	/** @required */
	public string $block;

	/** @required */
	public int $priority;

	/**
	 * @param v419RecipeIngredientData[] $input
	 * @param v419ItemStackData[]        $output
	 */
	public function __construct(array $input, array $output, string $block, int $priority){
		$this->block = $block;
		$this->priority = $priority;
		$this->input = $input;
		$this->output = $output;
	}

	public function jsonSerialize() : array{
		return (array) $this;
	}
}
