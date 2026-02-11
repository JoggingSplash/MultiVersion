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

namespace cisco\network\proto\v860\packets;

use cisco\network\proto\v860\packets\types\biome\v860BiomeDefinitionData;
use cisco\network\proto\v860\packets\types\biome\v860BiomeDefinitionEntry;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\biome\BiomeDefinitionEntry;
use function array_map;
use function count;

class v860BiomeDefinitionListPacket extends DataPacket implements ClientboundPacket {

	public const NETWORK_ID = ProtocolInfo::BIOME_DEFINITION_LIST_PACKET;

	/** @var v860BiomeDefinitionData[] */
	private array $definitionData = [];
	/**
	 * @var string[]
	 * @phpstan-var list<string>
	 */
	private array $strings = [];

	/**
	 * @phpstan-param list<BiomeDefinitionEntry> $definitions
	 */
	public static function fromDefinitions(array $definitions) : self{
		/**
		 * @var int[]                      $stringIndexLookup
		 * @phpstan-var array<string, int> $stringIndexLookup
		 */
		$stringIndexLookup = [];
		$strings = [];
		$addString = function(string $string) use (&$stringIndexLookup, &$strings) : int{
			if(isset($stringIndexLookup[$string])){
				return $stringIndexLookup[$string];
			}

			$stringIndexLookup[$string] = count($stringIndexLookup);
			$strings[] = $string;
			return $stringIndexLookup[$string];
		};

		$definitionData = array_map(fn(v860BiomeDefinitionEntry $entry) => new v860BiomeDefinitionData(
			$addString($entry->getBiomeName()),
			$entry->getId(),
			$entry->getTemperature(),
			$entry->getDownfall(),
			$entry->getFoliageSnow(),
			$entry->getDepth(),
			$entry->getScale(),
			$entry->getMapWaterColor(),
			$entry->hasRain(),
			$entry->getTags() === null ? null : array_map($addString, $entry->getTags()),
			$entry->getChunkGenData(),
		), $definitions);

		return self::create($definitionData, $strings);
	}

	public static function create(array $definitionData, array $strings) : self{
		$result = new self();
		$result->definitionData = $definitionData;
		$result->strings = $strings;
		return $result;
	}

	/**
	 * @throws PacketDecodeException
	 */
	private function locateString(int $index) : string{
		return $this->strings[$index] ?? throw new PacketDecodeException("Unknown string index $index");
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
			$this->definitionData[] = v860BiomeDefinitionData::read($in);
		}

		for($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i){
			$this->strings[] = CommonTypes::getString($in);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		VarInt::writeUnsignedInt($out, count($this->definitionData));
		foreach($this->definitionData as $data){
			$data->write($out);
		}

		VarInt::writeUnsignedInt($out, count($this->strings));
		foreach($this->strings as $string){
			CommonTypes::putString($out, $string);
		}
	}

	public function handle(PacketHandlerInterface $handler) : bool{
		return true;
	}
}
