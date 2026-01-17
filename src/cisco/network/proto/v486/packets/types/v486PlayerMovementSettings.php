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

namespace cisco\network\proto\v486\packets\types;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\PlayerMovementSettings;

final class v486PlayerMovementSettings
{

	// MovementType:
	// 0 => MovePlayerPacket
	// 1 => PlayerAuthInputPacket
	// 2 => IDGAF

	private int $movementType;
	private int $rewindHistorySize;
	private bool $serverAuthoritativeBlockBreaking;

	public function __construct(int $movementType, int $rewindHistorySize, bool $serverAuthoritativeBlockBreaking)
	{
		$this->movementType = $movementType;
		$this->rewindHistorySize = $rewindHistorySize;
		//do not ask me what the F this is doing here
		$this->serverAuthoritativeBlockBreaking = $serverAuthoritativeBlockBreaking;
	}

	static public function fromLatest(PlayerMovementSettings $movementSettings) : self
	{
		return new self(
			0/** MovePlayerPacket fuck 1.18.12 */,
			0,
			false
		);
	}

	public static function read(ByteBufferReader $in) : self
	{
		$movementType = VarInt::readSignedInt($in);
		$rewindHistorySize = VarInt::readSignedInt($in);
		$serverAuthBlockBreaking = CommonTypes::getBool($in);
		return new self($movementType, $rewindHistorySize, $serverAuthBlockBreaking);
	}

	public function getMovementType() : int
	{
		return $this->movementType;
	}

	public function getRewindHistorySize() : int
	{
		return $this->rewindHistorySize;
	}

	public function isServerAuthoritativeBlockBreaking() : bool
	{
		return $this->serverAuthoritativeBlockBreaking;
	}

	public function write(ByteBufferWriter $out) : void
	{
		VarInt::writeSignedInt($out, $this->movementType);
		VarInt::writeSignedInt($out, $this->rewindHistorySize);
		CommonTypes::putBool($out, $this->serverAuthoritativeBlockBreaking);
	}
}
