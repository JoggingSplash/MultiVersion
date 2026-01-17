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

use cisco\network\proto\v419\structure\v419CommonTypes;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequestAction;
use function count;

class v419DeprecatedCraftingResultsStackRequestAction extends ItemStackRequestAction
{
	use GetTypeIdFromConstTrait;

	public const ID = v419ItemStackRequestActionType::CRAFTING_RESULTS_DEPRECATED_ASK_TY_LAING;

	/** @var ItemStack[] */
	private $results;
	/** @var int */
	private $iterations;

	/**
	 * @param ItemStack[] $results
	 */
	public function __construct(array $results, int $iterations)
	{
		$this->results = $results;
		$this->iterations = $iterations;
	}

	public static function read(ByteBufferReader $in) : self
	{
		$results = [];
		for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
			$results[] = v419CommonTypes::getItemStackWithoutStackId($in);
		}
		$iterations = Byte::readUnsigned($in);
		return new self($results, $iterations);
	}

	/** @return ItemStack[] */
	public function getResults() : array
	{
		return $this->results;
	}

	public function getIterations() : int
	{
		return $this->iterations;
	}

	public function write(ByteBufferWriter $out) : void
	{
		VarInt::writeUnsignedInt($out, count($this->results));
		foreach ($this->results as $result) {
			v419CommonTypes::putItemStackWithoutStackId($result, $out);
		}
		Byte::writeUnsigned($out, $this->iterations);
	}
}
