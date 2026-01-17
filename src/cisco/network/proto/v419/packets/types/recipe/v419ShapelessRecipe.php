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

namespace cisco\network\proto\v419\packets\types\recipe;

use cisco\network\proto\v419\structure\v419CommonTypes;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use Ramsey\Uuid\UuidInterface;
use function count;

final class v419ShapelessRecipe extends v419RecipeWithTypeId
{

	public function __construct(
		int                   $typeId,
		private string        $recipeId,
		private array         $inputs,
		private array         $outputs,
		private UuidInterface $uuid,
		private string        $name,
		private int           $priority,
		private int           $recipeNetId
	)
	{
		parent::__construct($typeId);
	}

	public static function read(int $recipeType, ByteBufferReader $in) : v419RecipeWithTypeId
	{
		$recipeId = CommonTypes::getString($in);
		$inputs = [];
		for ($j = 0, $ingredientCount = VarInt::readUnsignedInt($in); $j < $ingredientCount; ++$j) {
			$inputs[] = v419CommonTypes::getRecipeIngredient($in);
		}

		$output = [];

		for ($i = 0, $outputCount = VarInt::readUnsignedInt($in); $i < $outputCount; ++$i) {
			$output[] = v419CommonTypes::getItemStackWithoutStackId($in);
		}

		$uuid = CommonTypes::getUuid($in);
		$block = CommonTypes::getString($in);
		$priority = VarInt::readSignedInt($in);
		$recipeNetId = VarInt::readSignedInt($in);
		return new self($recipeType, $recipeId, $inputs, $output, $uuid, $block, $priority, $recipeNetId);
	}

	public function getUuid() : UuidInterface
	{
		return $this->uuid;
	}

	public function getName() : string
	{
		return $this->name;
	}

	public function getPriority() : int
	{
		return $this->priority;
	}

	public function getRecipeId() : string
	{
		return $this->recipeId;
	}

	public function getRecipeNetId() : int
	{
		return $this->recipeNetId;
	}

	public function getOutputs() : array
	{
		return $this->outputs;
	}

	public function getInputs() : array
	{
		return $this->inputs;
	}

	public function write(ByteBufferWriter $out) : void
	{
		CommonTypes::putString($out, $this->recipeId);
		VarInt::writeUnsignedInt($out, count($this->inputs));
		foreach ($this->inputs as $input) {
			v419CommonTypes::putRecipeIngredient($out, $input);
		}

		VarInt::writeUnsignedInt($out, count($this->outputs));
		foreach ($this->outputs as $output) {
			v419CommonTypes::putItemStackWithoutStackId($output, $out);
		}

		CommonTypes::putUUID($out, $this->uuid);
		CommonTypes::putString($out, $this->name);
		VarInt::writeSignedInt($out, $this->priority);
		VarInt::writeSignedInt($out, $this->recipeNetId);
	}
}
