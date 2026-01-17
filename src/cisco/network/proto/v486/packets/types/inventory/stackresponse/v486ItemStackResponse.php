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

namespace cisco\network\proto\v486\packets\types\inventory\stackresponse;

use InvalidArgumentException;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\types\inventory\stackresponse\ItemStackResponseContainerInfo;
use function count;

final class v486ItemStackResponse
{

	public const RESULT_OK = 0;
	public const RESULT_ERROR = 1;
	//TODO: there are a ton more possible result types but we don't need them yet and they are wayyyyyy too many for me
	//to waste my time on right now...

	/**
	 * @param ItemStackResponseContainerInfo[] $containerInfos
	 */
	public function __construct(
		private int   $result,
		private int   $requestId,
		private array $containerInfos = []
	)
	{
		if ($this->result !== self::RESULT_OK && count($this->containerInfos) !== 0) {
			throw new InvalidArgumentException("Container infos must be empty if rejecting the request");
		}
	}

	public static function read(ByteBufferReader $in) : self
	{
		$result = Byte::readUnsigned($in);
		$requestId = VarInt::readSignedInt($in);
		$containerInfos = [];
		if ($result === self::RESULT_OK) {
			for ($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i) {
				$containerInfos[] = v486ItemStackResponseContainerInfo::read($in);
			}
		}
		return new self($result, $requestId, $containerInfos);
	}

	public function getResult() : int
	{
		return $this->result;
	}

	public function getRequestId() : int
	{
		return $this->requestId;
	}

	/** @return ItemStackResponseContainerInfo[] */
	public function getContainerInfos() : array
	{
		return $this->containerInfos;
	}

	public function write(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->result);
		VarInt::writeSignedInt($out, $this->requestId);
		if ($this->result === self::RESULT_OK) {
			VarInt::writeUnsignedInt($out, count($this->containerInfos));
			foreach ($this->containerInfos as $containerInfo) {
				$containerInfo->write($out);
			}
		}
	}

}
