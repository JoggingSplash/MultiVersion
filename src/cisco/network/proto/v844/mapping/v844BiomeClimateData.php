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

namespace cisco\network\proto\v844\mapping;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;

final class v844BiomeClimateData
{
	public function __construct(
		private float $temperature,
		private float $downfall,
		private float $snowAccumulationMin,
		private float $snowAccumulationMax,
	)
	{
	}

	public static function read(ByteBufferReader $in) : self
	{
		$temperature = LE::readFloat($in);
		$downfall = LE::readFloat($in);
		$snowAccumulationMin = LE::readFloat($in);
		$snowAccumulationMax = LE::readFloat($in);

		return new self(
			$temperature,
			$downfall,
			$snowAccumulationMin,
			$snowAccumulationMax
		);
	}

	public function getTemperature() : float
	{
		return $this->temperature;
	}

	public function getDownfall() : float
	{
		return $this->downfall;
	}

	public function getSnowAccumulationMin() : float
	{
		return $this->snowAccumulationMin;
	}

	public function getSnowAccumulationMax() : float
	{
		return $this->snowAccumulationMax;
	}

	public function write(ByteBufferWriter $out) : void
	{
		LE::writeFloat($out, $this->temperature);
		LE::writeFloat($out, $this->downfall);
		LE::writeFloat($out, $this->snowAccumulationMin);
		LE::writeFloat($out, $this->snowAccumulationMax);
	}
}
