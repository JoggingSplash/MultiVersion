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

namespace cisco\network\raknet;

use raklib\server\ProtocolAcceptor;
use function max;

final class MVRakNetAcceptor implements ProtocolAcceptor {

	/** @var array<int, bool> */
	protected array $versions;

	/**
	 * @param int[] $versions
	 */
	public function __construct(
		array $versions
	)
	{
		//f*ck array_map
		foreach ($versions as $v) {
			$this->versions[$v] = true;
		}
	}

	public function accepts(int $protocolVersion) : bool {
		return isset($this->versions[$protocolVersion]);
	}

	public function getPrimaryVersion() : int
	{
		return max($this->versions);
	}
}
