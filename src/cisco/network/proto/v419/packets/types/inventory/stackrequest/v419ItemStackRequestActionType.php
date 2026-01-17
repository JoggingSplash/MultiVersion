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

namespace cisco\network\proto\v419\packets\types\inventory\stackrequest;

final class v419ItemStackRequestActionType
{

	public const TAKE = 0;
	public const PLACE = 1;
	public const SWAP = 2;
	public const DROP = 3;
	public const DESTROY = 4;
	public const CRAFTING_CONSUME_INPUT = 5;
	public const CRAFTING_MARK_SECONDARY_RESULT_SLOT = 6;
	public const LAB_TABLE_COMBINE = 7;
	public const BEACON_PAYMENT = 8;
	public const CRAFTING_RECIPE = 9;
public const CRAFTING_RECIPE_AUTO = 10;
		public const CREATIVE_CREATE = 11; //recipe book?
public const CRAFTING_NON_IMPLEMENTED_DEPRECATED_ASK_TY_LAING = 12;
	public const CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING = 13; //anvils aren't fully implemented yet

	private function __construct()
	{
		// NOOP
	} //no idea what this is for
}
