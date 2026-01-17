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

namespace cisco\network\proto\v419\packets;

use cisco\network\assemble\command\ChainedSubCommandData;
use cisco\network\assemble\command\CommandData;
use cisco\network\assemble\command\CommandEnum;
use cisco\network\assemble\command\CommandEnumConstraint;
use cisco\network\assemble\command\CommandOverload;
use cisco\network\assemble\command\CommandParameter;
use cisco\network\proto\v419\structure\v419ProtocolInfo;
use LogicException;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\DataDecodeException;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use function array_search;
use function count;
use function dechex;

class v419AvailableCommandsPacket extends DataPacket implements ClientboundPacket
{

	public const NETWORK_ID = v419ProtocolInfo::AVAILABLE_COMMANDS_PACKET;

	/**
	 * This flag is set on all types EXCEPT the POSTFIX type. Not completely sure what this is for, but it is required
	 * for the argtype to work correctly. VALID seems as good a name as any.
	 */
	public const ARG_FLAG_VALID = 0x100000;

	/**
	 * Basic parameter types. These must be combined with the ARG_FLAG_VALID constant.
	 * ARG_FLAG_VALID | (type const)
	 */
	public const ARG_TYPE_INT = 0x01;
	public const ARG_TYPE_FLOAT = 0x03;
	public const ARG_TYPE_VALUE = 0x04;
	public const ARG_TYPE_WILDCARD_INT = 0x05;
	public const ARG_TYPE_OPERATOR = 0x06;
	public const ARG_TYPE_COMPARE_OPERATOR = 0x07;
	public const ARG_TYPE_TARGET = 0x08;

	public const ARG_TYPE_WILDCARD_TARGET = 0x0a;

	public const ARG_TYPE_FILEPATH = 0x11;

	public const ARG_TYPE_FULL_INTEGER_RANGE = 0x17;

	public const ARG_TYPE_EQUIPMENT_SLOT = 0x26;
	public const ARG_TYPE_STRING = 0x27;

	public const ARG_TYPE_INT_POSITION = 0x2f;
	public const ARG_TYPE_POSITION = 0x30;

	public const ARG_TYPE_MESSAGE = 0x33;

	public const ARG_TYPE_RAWTEXT = 0x35;

	public const ARG_TYPE_JSON = 0x39;

	public const ARG_TYPE_BLOCK_STATES = 0x43;

	public const ARG_TYPE_COMMAND = 0x46;

	/**
	 * Enums are a little different: they are composed as follows:
	 * ARG_FLAG_ENUM | ARG_FLAG_VALID | (enum index)
	 */
	public const ARG_FLAG_ENUM = 0x200000;

	/** This is used for /xp <level: int>L. It can only be applied to integer parameters. */
	public const ARG_FLAG_POSTFIX = 0x1000000;

	public const HARDCODED_ENUM_NAMES = [
		"CommandName" => true
	];

	/**
	 * @var CommandData[]
	 * List of command data, including name, description, alias indexes and parameters.
	 */
	public array $commandData = [];

	/**
	 * @var CommandEnum[]
	 * List of enums which aren't directly referenced by any vanilla command.
	 * This is used for the `CommandName` enum, which is a magic enum used by the `command` argument type.
	 */
	public array $hardcodedEnums = [];

	/**
	 * @var CommandEnum[]
	 * List of dynamic command enums, also referred to as "soft" enums. These can by dynamically updated mid-game
	 * without resending this packet.
	 */
	public array $softEnums = [];

	/**
	 * @var CommandEnumConstraint[]
	 * List of constraints for enum members. Used to constrain gamerules that can bechanged in nocheats mode and more.
	 */
	public array $enumConstraints = [];

	public static function create(array $commandData, array $hardEnums, array $softEnums, array $enumConstraints) : self
	{
		$result = new self();
		$result->commandData = $commandData;
		$result->hardcodedEnums = $hardEnums;
		$result->softEnums = $softEnums;
		$result->enumConstraints = $enumConstraints;
		return $result;
	}

	public function handle(PacketHandlerInterface $handler) : bool
	{
		return true;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		/** @var string[] $enumValues */
		$enumValues = [];
		for ($i = 0, $enumValuesCount = VarInt::readUnsignedInt($in); $i < $enumValuesCount; ++$i) {
			$enumValues[] = CommonTypes::getString($in);
		}

		/** @var string[] $postfixes */
		$postfixes = [];
		for ($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i) {
			$postfixes[] = CommonTypes::getString($in);
		}

		/** @var CommandEnum[] $enums */
		$enums = [];
		for ($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i) {
			$enums[] = $enum = $this->getEnum($enumValues, $in);
			if (isset(self::HARDCODED_ENUM_NAMES[$enum->getName()])) {
				$this->hardcodedEnums[] = $enum;
			}
		}

		for ($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i) {
			$this->commandData[] = $this->getCommandData($enums, $postfixes, [], $in);
		}

		for ($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i) {
			$this->softEnums[] = $this->getSoftEnum($in);
		}

		for ($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i) {
			$this->enumConstraints[] = $this->getEnumConstraint($enums, $enumValues, $in);
		}
	}

	/**
	 * @param string[] $enumValueList
	 *
	 * @throws PacketDecodeException
	 * @throws DataDecodeException
	 */
	protected function getEnum(array $enumValueList, ByteBufferReader $in) : CommandEnum
	{
		$enumName = CommonTypes::getString($in);
		$enumValues = [];

		$listSize = count($enumValueList);

		for ($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i) {
			$index = $this->getEnumValueIndex($listSize, $in);
			if (!isset($enumValueList[$index])) {
				throw new PacketDecodeException("Invalid enum value index $index");
			}
			//Get the enum value from the initial pile of mess
			$enumValues[] = $enumValueList[$index];
		}

		return new CommandEnum($enumName, $enumValues);
	}

	/**
	 * @throws DataDecodeException
	 */
	protected function getEnumValueIndex(int $valueCount, ByteBufferReader $in) : int
	{
		if ($valueCount < 256) {
			return Byte::readUnsigned($in);
		} elseif ($valueCount < 65536) {
			return LE::readUnsignedShort($in);
		} else {
			return LE::readUnsignedInt($in);
		}
	}

	/**
	 * @param CommandEnum[]           $enums
	 * @param string[]                $postfixes
	 * @param ChainedSubCommandData[] $allChainedSubCommandData
	 *
	 * @throws PacketDecodeException
	 * @throws DataDecodeException
	 */
	protected function getCommandData(array $enums, array $postfixes, array $allChainedSubCommandData, ByteBufferReader $in) : CommandData
	{
		$name = CommonTypes::getString($in);
		$description = CommonTypes::getString($in);
		$flags = Byte::readUnsigned($in);
		$permission = Byte::readUnsigned($in);
		$aliases = $enums[LE::readSignedInt($in)] ?? null;

		$overloads = [];

		for ($overloadIndex = 0, $overloadCount = VarInt::readUnsignedInt($in); $overloadIndex < $overloadCount; ++$overloadIndex) {
			$parameters = [];
			for ($paramIndex = 0, $paramCount = VarInt::readUnsignedInt($in); $paramIndex < $paramCount; ++$paramIndex) {
				$parameter = new CommandParameter();
				$parameter->paramName = CommonTypes::getString($in);
				$parameter->paramType = LE::readUnsignedInt($in);
				$parameter->isOptional = CommonTypes::getBool($in);
				$parameter->flags = Byte::readUnsigned($in);

				if (($parameter->paramType & self::ARG_FLAG_ENUM) !== 0) {
					$index = ($parameter->paramType & 0xffff);
					$parameter->enum = $enums[$index] ?? null;
					if ($parameter->enum === null) {
						throw new PacketDecodeException("deserializing $name parameter $parameter->paramName: expected enum at $index, but got none");
					}
				} elseif (($parameter->paramType & self::ARG_FLAG_POSTFIX) !== 0) {
					$index = ($parameter->paramType & 0xffff);
					$parameter->postfix = $postfixes[$index] ?? null;
					if ($parameter->postfix === null) {
						throw new PacketDecodeException("deserializing $name parameter $parameter->paramName: expected postfix at $index, but got none");
					}
				} elseif (($parameter->paramType & self::ARG_FLAG_VALID) === 0) {
					throw new PacketDecodeException("deserializing $name parameter $parameter->paramName: Invalid parameter type 0x" . dechex($parameter->paramType));
				}

				$parameters[$paramIndex] = $parameter;
			}
			$overloads[$overloadIndex] = new CommandOverload(false, $parameters);
		}

		return new CommandData($name, $description, $flags, $permission, $aliases, $overloads, []);
	}

	/**
	 * @throws DataDecodeException
	 */
	protected function getSoftEnum(ByteBufferReader $in) : CommandEnum
	{
		$enumName = CommonTypes::getString($in);
		$enumValues = [];

		for ($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i) {
			//Get the enum value from the initial pile of mess
			$enumValues[] = CommonTypes::getString($in);
		}

		return new CommandEnum($enumName, $enumValues, true);
	}

	/**
	 * @param CommandEnum[] $enums
	 * @param string[]      $enumValues
	 *
	 * @throws PacketDecodeException
	 * @throws DataDecodeException
	 */
	protected function getEnumConstraint(array $enums, array $enumValues, ByteBufferReader $in) : CommandEnumConstraint
	{
		//wtf, what was wrong with an offset inside the enum? :(
		$valueIndex = LE::readUnsignedInt($in);
		if (!isset($enumValues[$valueIndex])) {
			throw new PacketDecodeException("Enum constraint refers to unknown enum value index $valueIndex");
		}
		$enumIndex = LE::readUnsignedInt($in);
		if (!isset($enums[$enumIndex])) {
			throw new PacketDecodeException("Enum constraint refers to unknown enum index $enumIndex");
		}
		$enum = $enums[$enumIndex];
		$valueOffset = array_search($enumValues[$valueIndex], $enum->getValues(), true);
		if ($valueOffset === false) {
			throw new PacketDecodeException("Value \"" . $enumValues[$valueIndex] . "\" does not belong to enum \"" . $enum->getName() . "\"");
		}

		$constraintIds = [];
		for ($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i) {
			$constraintIds[] = Byte::readUnsigned($in);
		}

		return new CommandEnumConstraint($enum, $valueOffset, $constraintIds);
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		/** @var int[] $enumValueIndexes */
		$enumValueIndexes = [];
		/** @var int[] $postfixIndexes */
		$postfixIndexes = [];
		/** @var int[] $enumIndexes */
		$enumIndexes = [];
		/** @var CommandEnum[] $enums */
		$enums = [];

		$addEnumFn = static function (CommandEnum $enum) use (&$enums, &$enumIndexes, &$enumValueIndexes) : void {
			if (!isset($enumIndexes[$enum->getName()])) {
				$enums[$enumIndexes[$enum->getName()] = count($enumIndexes)] = $enum;
			}
			foreach ($enum->getValues() as $str) {
				$enumValueIndexes[$str] = $enumValueIndexes[$str] ?? count($enumValueIndexes); //latest index
			}
		};

		foreach ($this->hardcodedEnums as $enum) {
			$addEnumFn($enum);
		}

		foreach ($this->commandData as $commandData) {
			if ($commandData->aliases !== null) {
				$addEnumFn($commandData->aliases);
			}
			foreach ($commandData->overloads as $overload) {
				foreach ($overload->getParameters() as $parameter) {
					if ($parameter->enum !== null) {
						$addEnumFn($parameter->enum);
					}

					if ($parameter->postfix !== null) {
						$postfixIndexes[$parameter->postfix] = $postfixIndexes[$parameter->postfix] ?? count($postfixIndexes);
					}
				}
			}
		}

		VarInt::writeUnsignedInt($out, count($enumValueIndexes));
		foreach ($enumValueIndexes as $enumValue => $index) {
			CommonTypes::putString($out, $enumValue);
		}

		VarInt::writeUnsignedInt($out, count($postfixIndexes));
		foreach ($postfixIndexes as $postfix => $index) {
			CommonTypes::putString($out, $postfix);
		}

		VarInt::writeUnsignedInt($out, count($enums));
		foreach ($enums as $enum) {
			$this->putEnum($enum, $enumValueIndexes, $out);
		}

		VarInt::writeUnsignedInt($out, count($this->commandData));
		foreach ($this->commandData as $data) {
			$this->putCommandData($data, $enumIndexes, [], $postfixIndexes, [], $out);
		}

		VarInt::writeUnsignedInt($out, count($this->softEnums));
		foreach ($this->softEnums as $enum) {
			$this->putSoftEnum($enum, $out);
		}

		VarInt::writeUnsignedInt($out, count($this->enumConstraints));
		foreach ($this->enumConstraints as $constraint) {
			$this->putEnumConstraint($constraint, $enumIndexes, $enumValueIndexes, $out);
		}
	}

	/**
	 * @param int[] $enumValueMap
	 */
	protected function putEnum(CommandEnum $enum, array $enumValueMap, ByteBufferWriter $out) : void
	{
		CommonTypes::putString($out, $enum->getName());

		$values = $enum->getValues();
		VarInt::writeUnsignedInt($out, count($values));
		$listSize = count($enumValueMap);
		foreach ($values as $value) {
			if (!isset($enumValueMap[$value])) {
				throw new LogicException("Enum value '$value' doesn't have a value index");
			}
			$this->putEnumValueIndex($enumValueMap[$value], $listSize, $out);
		}
	}

	protected function putEnumValueIndex(int $index, int $valueCount, ByteBufferWriter $out) : void
	{
		if ($valueCount < 256) {
			Byte::writeUnsigned($out, $index);
		} elseif ($valueCount < 65536) {
			LE::writeUnsignedShort($out, $index);
		} else {
			LE::writeUnsignedInt($out, $index);
		}
	}

	/**
	 * @param int[] $enumIndexes                  string enum name -> int index
	 * @param int[] $softEnumIndexes
	 * @param int[] $postfixIndexes
	 * @param int[] $chainedSubCommandDataIndexes
	 */
	protected function putCommandData(CommandData $data, array $enumIndexes, array $softEnumIndexes, array $postfixIndexes, array $chainedSubCommandDataIndexes, ByteBufferWriter $out) : void
	{
		CommonTypes::putString($out, $data->name);
		CommonTypes::putString($out, $data->description);
		Byte::writeUnsigned($out, $data->flags);
		Byte::writeUnsigned($out, $data->permission);

		if ($data->aliases !== null) {
			LE::writeSignedInt($out, $enumIndexes[$data->aliases->getName()] ?? -1);
		} else {
			LE::writeSignedInt($out, -1);
		}

		VarInt::writeUnsignedInt($out, count($data->overloads));
		foreach ($data->overloads as $overload) {
			VarInt::writeUnsignedInt($out, count($overload->getParameters()));
			foreach ($overload->getParameters() as $parameter) {
				CommonTypes::putString($out, $parameter->paramName);

				if ($parameter->enum !== null) {
					$type = self::ARG_FLAG_ENUM | self::ARG_FLAG_VALID | ($enumIndexes[$parameter->enum->getName()] ?? -1);
				} elseif ($parameter->postfix !== null) {
					if (!isset($postfixIndexes[$parameter->postfix])) {
						throw new LogicException("Postfix '$parameter->postfix' not in postfixes array");
					}
					$type = self::ARG_FLAG_POSTFIX | $postfixIndexes[$parameter->postfix];
				} else {
					$type = $parameter->paramType;
				}

				LE::writeUnsignedInt($out, $type);
				CommonTypes::putBool($out, $parameter->isOptional);
				Byte::writeUnsigned($out, $parameter->flags);
			}
		}
	}

	protected function putSoftEnum(CommandEnum $enum, ByteBufferWriter $out) : void
	{
		CommonTypes::putString($out, $enum->getName());

		$values = $enum->getValues();
		VarInt::writeUnsignedInt($out, count($values));
		foreach ($values as $value) {
			CommonTypes::putString($out, $value);
		}
	}

	/**
	 * @param int[] $enumIndexes      string enum name -> int index
	 * @param int[] $enumValueIndexes string value -> int index
	 */
	protected function putEnumConstraint(CommandEnumConstraint $constraint, array $enumIndexes, array $enumValueIndexes, ByteBufferWriter $out) : void
	{
		LE::writeUnsignedInt($out, $enumValueIndexes[$constraint->getAffectedValue()]);
		LE::writeUnsignedInt($out, $enumIndexes[$constraint->getEnum()->getName()]);
		VarInt::writeUnsignedInt($out, count($constraint->getConstraints()));
		foreach ($constraint->getConstraints() as $v) {
			Byte::writeUnsigned($out, $v);
		}
	}
}
