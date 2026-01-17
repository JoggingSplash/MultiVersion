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

namespace cisco\network\proto\v486\packets\types\inventory\stackrequest;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;

final class v486ItemStackRequestSlotInfo
{

	public function __construct(
		private int $containerId,
		private int $slotId,
		private int $stackId
	)
	{
	}

	public static function read(ByteBufferReader $in) : self
	{
		$containerId = Byte::readUnsigned($in);
		$slotId = Byte::readUnsigned($in);
		$stackId = VarInt::readSignedInt($in);
		return new self($containerId, $slotId, $stackId);
	}

	public function getContainerId() : int
	{
		return $this->containerId;
	}

	public function getSlotId() : int
	{
		return $this->slotId;
	}

	public function getStackId() : int
	{
		return $this->stackId;
	}

	public function write(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->containerId);
		Byte::writeUnsigned($out, $this->slotId);
		VarInt::writeSignedInt($out, $this->stackId);
	}
}
