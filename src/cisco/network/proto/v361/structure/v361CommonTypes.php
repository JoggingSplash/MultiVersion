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

namespace cisco\network\proto\v361\structure;

use cisco\network\proto\v361\v361TypeConverter;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\DataDecodeException;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\entity\Attribute;
use pocketmine\network\mcpe\protocol\types\entity\BlockPosMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\ByteMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\CompoundTagMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\network\mcpe\protocol\types\entity\FloatMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\IntMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\ShortMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\Vec3MetadataProperty;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackExtraData;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackExtraDataShield;
use UnexpectedValueException;
use function count;

final class v361CommonTypes {

	private const PM_BLOCK_RUNTIME_ID_TAG = "___block_runtime_id___";

	private function __construct(){

	}

	/**
	 * @return int[]
	 * @phpstan-return array{0: int, 1: int, 2: int}
	 * @throws PacketDecodeException
	 */
	private static function getItemStackHeader(ByteBufferReader $in) : array {
		$id = VarInt::readSignedInt($in);
		if ($id === 0) {
			return [0, 0, 0];
		}

		$auxValue = VarInt::readSignedInt($in);
		$count = $auxValue & 0xff;
		$meta = $auxValue >> 8;

		return [$id, $count, $meta];
	}

	private static function getItemStackFooter(ByteBufferReader $in, int $id, int $meta, int $count) : ItemStack {
		$nbtLen = LE::readUnsignedShort($in);

		/** @var CompoundTag|null $compound */
		$compound = null;
		if ($nbtLen === 0xffff) {
			$nbtDataVersion = Byte::readUnsigned($in);
			if ($nbtDataVersion !== 1) {
				throw new UnexpectedValueException("Unexpected NBT count $nbtDataVersion");
			}
			$compound = CommonTypes::getNbtCompoundRoot($in);
		}

		$blockRuntimeId = 0;
		if ($compound !== null) {
			$blockRuntimeId = $compound->getInt(self::PM_BLOCK_RUNTIME_ID_TAG, 0);
			$compound->removeTag(self::PM_BLOCK_RUNTIME_ID_TAG);
		}

		$canBePlacedOn = [];
		for ($i = 0, $canPlaceOn = VarInt::readUnsignedInt($in); $i < $canPlaceOn; ++$i) {
			$canBePlacedOn[] = CommonTypes::getString($in);
		}

		$canDestroyBlocks = [];
		for ($i = 0, $canDestroy = VarInt::readUnsignedInt($in); $i < $canDestroy; ++$i) {
			$canDestroyBlocks[] = CommonTypes::getString($in);
		}

		$blockingTick = null;
		if ($id === v361TypeConverter::getInstance()->getConverter()->getShieldRuntimeId()) {
			$blockingTick = VarInt::readUnsignedLong($in); //"blocking tick" (ffs mojang)
		}

		$extraData = $blockingTick !== null ?
			new ItemStackExtraDataShield($compound, canPlaceOn: $canBePlacedOn, canDestroy: $canDestroyBlocks, blockingTick: $blockingTick) :
			new ItemStackExtraData($compound, canPlaceOn: $canBePlacedOn, canDestroy: $canDestroyBlocks);
		$extraDataSerializer = new ByteBufferWriter();
		$extraData->write($extraDataSerializer);

		return new ItemStack($id, $meta, $count, $blockRuntimeId, $extraDataSerializer->getData());
	}

	static public function getEntityMetadata(ByteBufferReader $in) : array {
		$count = VarInt::readUnsignedInt($in);
		$metadata = [];
		for ($i = 0; $i < $count; ++$i) {
			$key = VarInt::readUnsignedInt($in);
			$type = VarInt::readUnsignedInt($in);

			$metadata[$key] = self::readMetadataProperty($in, $type);
		}

		return $metadata;
	}

	private static function readMetadataProperty(ByteBufferReader $in, int $type) : MetadataProperty {
		return match ($type) {
			ByteMetadataProperty::ID => ByteMetadataProperty::read($in),
			ShortMetadataProperty::ID => ShortMetadataProperty::read($in),
			IntMetadataProperty::ID => IntMetadataProperty::read($in),
			FloatMetadataProperty::ID => FloatMetadataProperty::read($in),
			StringMetadataProperty::ID => StringMetadataProperty::read($in),
			CompoundTagMetadataProperty::ID => CompoundTagMetadataProperty::read($in),
			BlockPosMetadataProperty::ID => BlockPosMetadataProperty::read($in),
			LongMetadataProperty::ID => LongMetadataProperty::read($in),
			Vec3MetadataProperty::ID => Vec3MetadataProperty::read($in),
			default => throw new PacketDecodeException("Unknown entity metadata type " . $type),
		};
	}

	public static function getAttributeList(ByteBufferReader $in) : array {
		$attrs = [];

		for($i = 0; $i < VarInt::readUnsignedInt($in); $i++){
			$id = CommonTypes::getString($in);
			$min = LE::readFloat($in);
			$current = LE::readFloat($in);
			$max = LE::readFloat($in);

			$attrs[] = new Attribute($id, $min, $max, $current, $current, []);
		}

		return $attrs;
	}

	public static function putAttributeList(ByteBufferWriter $out, Attribute ...$attributes) : void {
		VarInt::writeUnsignedInt($out, count($attributes));
		foreach ($attributes as $attr) {
			CommonTypes::putString($out, $attr->getId());
			LE::writeFloat($out, $attr->getMin());
			LE::writeFloat($out, $attr->getCurrent());
			LE::writeFloat($out, $attr->getMax());
		}
	}

	/** @throws DataDecodeException */
	public static function getEntityLink(ByteBufferReader $in) : EntityLink{
		$fromActorUniqueId = CommonTypes::getActorUniqueId($in);
		$toActorUniqueId = CommonTypes::getActorUniqueId($in);
		$type = Byte::readUnsigned($in);
		$immediate = CommonTypes::getBool($in);
		return new EntityLink($fromActorUniqueId, $toActorUniqueId, $type, $immediate, false, 0.0);
	}

	public static function putEntityLink(ByteBufferWriter $out, EntityLink $link) : void{
		CommonTypes::putActorUniqueId($out, $link->fromActorUniqueId);
		CommonTypes::putActorUniqueId($out, $link->toActorUniqueId);
		Byte::writeUnsigned($out, $link->type);
		CommonTypes::putBool($out, $link->immediate);
	}

}
