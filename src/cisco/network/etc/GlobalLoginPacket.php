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

namespace cisco\network\etc;

use BadMethodCallException;
use cisco\MCProtocols;
use cisco\network\assemble\auth\JwtChain;
use pmmp\encoding\BE;
use pmmp\encoding\ByteBufferReader;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function in_array;

/**
 * This override of the login packet allow us to decode the packet buffer by the given protocol
 */
final class GlobalLoginPacket extends LoginPacket {
	private JwtChain $chainDataJwt;

	public function getChainDataJwt() : JwtChain
	{
		if (!isset($this->chainDataJwt)) {
			throw new BadMethodCallException(self::class . "::$\chainDataJwt has not been provided either is not available in the protocol");
		}

		return $this->chainDataJwt;
	}

	public function setChainDataJwt(JwtChain $chainDataJwt) : void
	{
		$this->chainDataJwt = $chainDataJwt;
	}

	protected function decodePayload(ByteBufferReader $in) : void {
		$this->protocol = BE::readUnsignedInt($in);
		$buffer = CommonTypes::getString($in);

		if (!in_array($this->protocol, MCProtocols::getProtocols(), true)) {
			//if is not registered, we will continue on default
			$this->decodeConnectionRequest($buffer);
			return;
		}

		//decode properly
		MCProtocols::getProtocolInstance($this->protocol)->decodeConnection($buffer, $this);
	}

	public function decodeConnectionRequest(string $binary) : void {
		parent::decodeConnectionRequest($binary);
	}

}
