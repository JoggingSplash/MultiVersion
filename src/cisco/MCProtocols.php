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

namespace cisco;

use cisco\network\proto\latest\LatestProtocol;
use cisco\network\proto\TProtocol;
use cisco\network\proto\v361\v361Protocol;
use cisco\network\proto\v419\v419Protocol;
use cisco\network\proto\v486\v486Protocol;
use cisco\network\proto\v844\v844Protocol;
use cisco\network\proto\v860\v860Protocol;
use cisco\network\raknet\MVRakNetAcceptor;
use InvalidArgumentException;
use raklib\server\ProtocolAcceptor;
use function array_map;
use function array_unique;
use function mb_strtoupper;

final class MCProtocols {

	/** @var TProtocol[]|null $members */
	private static ?array $members = null;
	private static array $protocols = [];

	public static function checkInit() : void {
		if(self::$members === null) {
			self::$members = [];
			self::setup();
		}
	}

	/**
	 * @return TProtocol
	 * @throws InvalidArgumentException
	 */
	public static function getProtocolInstance(int $protocol) : object {
		self::checkInit();
		$upperName = self::preprocessName($protocol);
		if (!isset(self::$members[$upperName])) {
			throw new InvalidArgumentException("No such registry member: " . self::class . "::" . $upperName);
		}

		return self::$members[$upperName];
	}

	/**
	 * Returns an array of integers with the protocols supported
	 */
	public static function getProtocols() : array
	{
		return self::$protocols;
	}

	/**
	 * Returns all the instances of protocols registered
	 * @return TProtocol[]
	 */
	public static function getProtocolInstances() : array{
		self::checkInit();
		return self::$members;
	}

	/**
	 * Returns a ProtocolAcceptor instance with all the raknet versions registered.
	 */
	public static function getRaknetProtocolAcceptor() : ProtocolAcceptor {
		return new MVRakNetAcceptor(
			array_unique(array_map(fn(TProtocol $proto) => $proto->getRaknetVersion(), self::getProtocolInstances()))
		);
	}

	protected static function setup() : void {
		self::register(new LatestProtocol()); /** 1.21.131 */
		self::register(new v860Protocol()); /** 1.21.124 */
		// This is fine since the 1.21.120 version only changes the way how StartGamePacket item hashes are made
		self::register(new class extends v860Protocol {
			public function getProtocolId() : int
			{
				return 859;
			}

			public function __toString() : string
			{
				return "v1.21.120";
			}
		});
		self::register(new v844Protocol()); /** 1.21.114 */
		self::register(new v486Protocol()); /** 1.18.12 */
		self::register(new v419Protocol()); /** 1.16.100  */
		// self::register(new v361Protocol()); /** 1.12 */
	}

	private static function register(TProtocol $protocol) : void {
		if(self::$members === null) {
			throw new InvalidArgumentException("Cannot register protocols outside the " . self::class . "::setup() method");
		}

		self::$members[self::preprocessName($proto = $protocol->getProtocolId())] = $protocol;
		self::$protocols[self::preprocessName($proto)] = $proto;
	}

	/**
	 * Preprocess the protocol id and converts it to a readable id to this registry
	 */
	private static function preprocessName(int $protocolId) : string	{
		return mb_strtoupper("v$protocolId");
	}
}
