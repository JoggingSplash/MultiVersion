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
use InvalidArgumentException;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use Ramsey\Uuid\UuidInterface;
use function count;

final class v419ShapedRecipe extends v419RecipeWithTypeId
{

	public function __construct(
		int                   $typeId,
		private string        $recipeId,
		private array         $input,
		private array         $output,
		private UuidInterface $uuid,
		private string        $blockType,
		private int           $priority,
		private int           $recipeNetId
	)
	{
		parent::__construct($typeId);
		$rows = count($this->input);

		if ($rows < 1 || $rows > 3) {
			throw new InvalidArgumentException("Expected rows in range 1-3, got " . $rows);
		}
		$columns = null;
		foreach ($this->input as $k => $rows) {
			$rowCount = count($rows);
			if ($columns === null) {
				$columns = $rowCount;
			} elseif ($rowCount !== $columns) {
				throw new InvalidArgumentException("Expected each row to be $columns columns, but have " . $rowCount . " in row $k");
			}
		}
	}

	public static function read(int $recipeType, ByteBufferReader $in) : v419RecipeWithTypeId
	{
		$recipeId = CommonTypes::getString($in);
		$width = VarInt::readSignedInt($in);
		$height = VarInt::readSignedInt($in);
		$input = [];
		for ($row = 0; $row < $height; ++$row) {
			for ($column = 0; $column < $width; ++$column) {
				$input[$row][$column] = v419RecipeIngredient::read($in);
			}
		}

		$output = [];
		for ($k = 0, $resultCount = VarInt::readUnsignedInt($in); $k < $resultCount; ++$k) {
			$output[] = v419CommonTypes::getItemStackWithoutStackId($in);
		}

		$uuid = CommonTypes::getUUID($in);
		$block = CommonTypes::getString($in);
		$priority = VarInt::readSignedInt($in);
		$recipeNetId = VarInt::readSignedInt($in);

		return new self($recipeType, $recipeId, $input, $output, $uuid, $block, $priority, $recipeNetId);
	}

	public function getUuid() : UuidInterface
	{
		return $this->uuid;
	}

	public function getRecipeNetId() : int
	{
		return $this->recipeNetId;
	}

	public function getRecipeId() : string
	{
		return $this->recipeId;
	}

	public function getPriority() : int
	{
		return $this->priority;
	}

	public function getBlockType() : string
	{
		return $this->blockType;
	}

	/**
	 * @return v419RecipeIngredient[][]
	 */
	public function getInput() : array
	{
		return $this->input;
	}

	/**
	 * @return ItemStack[]
	 */
	public function getOutput() : array
	{
		return $this->output;
	}

	public function write(ByteBufferWriter $out) : void
	{
		CommonTypes::putString($out, $this->recipeId);
		VarInt::writeSignedInt($out, $this->getWidth());
		VarInt::writeSignedInt($out, $this->getHeight());
		foreach ($this->input as $row) {
			foreach ($row as $ingredient) {
				v419CommonTypes::putRecipeIngredient($out, $ingredient);
			}
		}

		VarInt::writeUnsignedInt($out, count($this->output));
		foreach ($this->output as $item) {
			v419CommonTypes::putItemStackWithoutStackId($item, $out);
		}

		CommonTypes::putUUID($out, $this->uuid);
		CommonTypes::putString($out, $this->blockType);// also know as blockName
		VarInt::writeSignedInt($out, $this->priority);
		VarInt::writeSignedInt($out, $this->recipeNetId);
	}

	public function getWidth() : int
	{
		return count($this->input[0]);
	}

	public function getHeight() : int
	{
		return count($this->input);
	}

}
