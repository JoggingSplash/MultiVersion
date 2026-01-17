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

namespace cisco\network\proto\v419\structure;

use cisco\network\assemble\CommandOriginData;
use cisco\network\proto\v419\packets\types\recipe\v419RecipeIngredient;
use cisco\network\proto\v419\v419TypeConverter;
use InvalidArgumentException;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\DataDecodeException;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\TreeRoot;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\serializer\NetworkNbtSerializer;
use pocketmine\network\mcpe\protocol\types\BoolGameRule;
use pocketmine\network\mcpe\protocol\types\entity\Attribute;
use pocketmine\network\mcpe\protocol\types\entity\BlockPosMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\ByteMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\CompoundTagMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;
use pocketmine\network\mcpe\protocol\types\entity\FloatMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\IntMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\ShortMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\StringMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\Vec3MetadataProperty;
use pocketmine\network\mcpe\protocol\types\FloatGameRule;
use pocketmine\network\mcpe\protocol\types\GameRule;
use pocketmine\network\mcpe\protocol\types\IntGameRule;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackExtraData;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackExtraDataShield;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\skin\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\skin\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\skin\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use UnexpectedValueException;
use function count;
use const PHP_INT_MAX;

/**
 * Class related to convert packet fields
 * see {@see CommonTypes}
 */
final class v419CommonTypes
{

	private const PM_BLOCK_RUNTIME_ID_TAG = "___block_runtime_id___";

	private function __construct()
	{
	}

	static function getItemStackWithoutStackId(ByteBufferReader $in) : ItemStack
	{
		[$id, $count, $meta] = self::getItemStackHeader($in);

		return $id !== 0 ? self::getItemStackFooter($in, $id, $meta, $count) : ItemStack::null();
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
		if ($id === v419TypeConverter::getInstance()->getConverter()->getShieldRuntimeId()) {
			$blockingTick = VarInt::readUnsignedLong($in); //"blocking tick" (ffs mojang)
		}

		$extraData = $blockingTick !== null ?
			new ItemStackExtraDataShield($compound, canPlaceOn: $canBePlacedOn, canDestroy: $canDestroyBlocks, blockingTick: $blockingTick) :
			new ItemStackExtraData($compound, canPlaceOn: $canBePlacedOn, canDestroy: $canDestroyBlocks);
		$extraDataSerializer = new ByteBufferWriter();
		$extraData->write($extraDataSerializer);

		return new ItemStack($id, $meta, $count, $blockRuntimeId, $extraDataSerializer->getData());
	}

	static public function getRecipeIngredient(ByteBufferReader $in) : v419RecipeIngredient
	{
		$id = VarInt::readSignedInt($in);
		if ($id === 0) {
			return new v419RecipeIngredient(0, 0, 0);
		}

		$meta = VarInt::readSignedInt($in);
		$count = VarInt::readSignedInt($in);
		return new v419RecipeIngredient($id, $meta, $count);
	}

	static public function putRecipeIngredient(ByteBufferWriter $out, v419RecipeIngredient $ingredient) : bool
	{
		$id = $ingredient->getId();
		if ($id === 0) {
			VarInt::writeSignedInt($out, 0);
		} else {
			VarInt::writeSignedInt($out, $id);
			VarInt::writeSignedInt($out, $ingredient->getMeta());
			VarInt::writeSignedInt($out, $ingredient->getCount());
		}

		return true;
	}

	static public function getItemStackWrapper(ByteBufferReader $in) : ItemStackWrapper
	{
		[$id, $count, $meta] = self::getItemStackHeader($in);
		if ($id === 0) {
			return new ItemStackWrapper(0, ItemStack::null());
		}

		$itemStack = self::getItemStackFooter($in, $id, $meta, $count);

		return new ItemStackWrapper(0, $itemStack);
	}

	static public function putItemStackWrapper(ByteBufferWriter $out, ItemStackWrapper $stackWrapper) : void
	{
		self::putItemStackWithoutStackId($stackWrapper->getItemStack(), $out);
	}

	public static function putItemStackWithoutStackId(ItemStack $stack, ByteBufferWriter $out) : void
	{
		if (self::putItemStackHeader($out, $stack)) {
			self::putItemStackFooter($out, $stack);
		}
	}

	private static function putItemStackHeader(ByteBufferWriter $out, ItemStack $stack) : bool
	{
		$id = $stack->getId();
		if ($id === 0) {
			VarInt::writeSignedInt($out, 0);
			return false;
		}

		VarInt::writeSignedInt($out, $id);
		VarInt::writeSignedInt($out, (($stack->getMeta() & 0x7fff) << 8) | $stack->getCount());
		return true;
	}

	private static function putItemStackFooter(ByteBufferWriter $out, ItemStack $stack) : void
	{
		$runtimeId = $stack->getBlockRuntimeId();
		$shield = false;
		$decoder = new ByteBufferReader($stack->getRawExtraData());

		if ($stack->getId() !== 0 && $stack->getId() === v419TypeConverter::getInstance()->getConverter()->getShieldRuntimeId()) {
			$shield = true;
			$extraData = ItemStackExtraDataShield::read($decoder);
		} else {
			$extraData = ItemStackExtraData::read($decoder);
		}

		$compound = new CompoundTag();
		if ($extraData->getNbt() !== null) {
			$compound = clone $extraData->getNbt();
		}

		$compound->setInt(self::PM_BLOCK_RUNTIME_ID_TAG, $runtimeId);
		LE::writeUnsignedShort($out, 0xffff);
		Byte::writeUnsigned($out, 1); //TODO: some kind of count field? always 1 as of 1.9.0}

		$out->writeByteArray((new NetworkNbtSerializer())->write(new TreeRoot($compound)));
		VarInt::writeUnsignedInt($out, count($extraData->getCanPlaceOn()));
		foreach ($extraData->getCanPlaceOn() as $toWrite) {
			CommonTypes::putString($out, $toWrite);
		}
		VarInt::writeUnsignedInt($out, count($extraData->getCanDestroy()));
		foreach ($extraData->getCanDestroy() as $toWrite) {
			VarInt::writeUnsignedInt($out, $toWrite);
		}

		if ($shield) {
			VarInt::writeUnsignedLong($out, $extraData->getBlockingTick());
		}
	}

	static public function getSkin(ByteBufferReader $in) : SkinData
	{
		$skinId = CommonTypes::getString($in);
		$skinResourcePatch = CommonTypes::getString($in);
		$skinData = self::getSkinImage($in);
		$animationCount = LE::readUnsignedInt($in);
		$animations = [];
		for ($i = 0; $i < $animationCount; ++$i) {
			$skinImage = self::getSkinImage($in);
			$animationType = LE::readUnsignedInt($in);
			$animationFrames = LE::readFloat($in);
			$expressionType = LE::readUnsignedInt($in);
			$animations[] = new SkinAnimation($skinImage, $animationType, $animationFrames, $expressionType);
		}
		$capeData = self::getSkinImage($in);
		$geometryData = CommonTypes::getString($in);
		$animationData = CommonTypes::getString($in);
		$premium = CommonTypes::getBool($in);
		$persona = CommonTypes::getBool($in);
		$capeOnClassic = CommonTypes::getBool($in);
		$capeId = CommonTypes::getString($in);
		$fullSkinId = CommonTypes::getString($in);
		$armSize = CommonTypes::getString($in);
		$skinColor = CommonTypes::getString($in);
		$personaPieceCount = LE::readUnsignedInt($in);
		$personaPieces = [];
		for ($i = 0; $i < $personaPieceCount; ++$i) {
			$pieceId = CommonTypes::getString($in);
			$pieceType = CommonTypes::getString($in);
			$packId = CommonTypes::getString($in);
			$isDefaultPiece = CommonTypes::getBool($in);
			$productId = CommonTypes::getString($in);
			$personaPieces[] = new PersonaSkinPiece($pieceId, $pieceType, $packId, $isDefaultPiece, $productId);
		}
		$pieceTintColorCount = LE::readUnsignedInt($in);
		$pieceTintColors = [];
		for ($i = 0; $i < $pieceTintColorCount; ++$i) {
			$pieceType = CommonTypes::getString($in);
			$colorCount = LE::readUnsignedInt($in);
			$colors = [];
			for ($j = 0; $j < $colorCount; ++$j) {
				$colors[] = CommonTypes::getString($in);
			}
			$pieceTintColors[] = new PersonaPieceTintColor(
				$pieceType,
				$colors
			);
		}

		return new SkinData(
			$skinId,
			"",
			$skinResourcePatch,
			$skinData,
			$animations,
			$capeData,
			$geometryData,
			ProtocolInfo::MINECRAFT_VERSION_NETWORK,
			$animationData,
			$capeId,
			$fullSkinId,
			$armSize,
			$skinColor,
			$personaPieces,
			$pieceTintColors,
			true,
			$premium,
			$persona,
			$capeOnClassic,
			false,
			$override ?? true
		);
	}

	/** @throws DataDecodeException */
	private static function getSkinImage(ByteBufferReader $in) : SkinImage
	{
		$width = LE::readUnsignedInt($in);
		$height = LE::readUnsignedInt($in);
		$data = CommonTypes::getString($in);
		try {
			return new SkinImage($height, $width, $data);
		} catch (InvalidArgumentException $e) {
			throw new PacketDecodeException($e->getMessage(), 0, $e);
		}
	}

	public static function putSkin(ByteBufferWriter $out, SkinData $skin) : void
	{
		CommonTypes::putString($out, $skin->getSkinId());
		CommonTypes::putString($out, $skin->getResourcePatch());
		self::putSkinImage($out, $skin->getSkinImage());

		LE::writeUnsignedInt($out, count($skin->getAnimations()));
		foreach ($skin->getAnimations() as $animation) {
			self::putSkinImage($out, $animation->getImage());
			LE::writeUnsignedInt($out, $animation->getType());
			LE::writeFloat($out, $animation->getFrames());
			LE::writeUnsignedInt($out, $animation->getExpressionType());
		}

		self::putSkinImage($out, $skin->getCapeImage());
		CommonTypes::putString($out, $skin->getGeometryData());
		CommonTypes::putString($out, $skin->getAnimationData());

		CommonTypes::putBool($out, $skin->isPremium());
		CommonTypes::putBool($out, $skin->isPersona());
		CommonTypes::putBool($out, $skin->isPersonaCapeOnClassic());

		CommonTypes::putString($out, $skin->getCapeId());
		CommonTypes::putString($out, $skin->getFullSkinId());
		CommonTypes::putString($out, $skin->getArmSize());
		CommonTypes::putString($out, $skin->getSkinColor());

		LE::writeUnsignedInt($out, count($skin->getPersonaPieces()));
		foreach ($skin->getPersonaPieces() as $piece) {
			CommonTypes::putString($out, $piece->getPieceId());
			CommonTypes::putString($out, $piece->getPieceType());
			CommonTypes::putString($out, $piece->getPackId());
			CommonTypes::putBool($out, $piece->isDefaultPiece());
			CommonTypes::putString($out, $piece->getProductId());
		}

		LE::writeUnsignedInt($out, count($skin->getPieceTintColors()));
		foreach ($skin->getPieceTintColors() as $tint) {
			CommonTypes::putString($out, $tint->getPieceType());
			LE::writeUnsignedInt($out, count($tint->getColors()));
			foreach ($tint->getColors() as $color) {
				CommonTypes::putString($out, $color);
			}
		}
	}

	private static function putSkinImage(ByteBufferWriter $out, SkinImage $image) : void
	{
		LE::writeUnsignedInt($out, $image->getWidth());
		LE::writeUnsignedInt($out, $image->getHeight());
		CommonTypes::putString($out, $image->getData());
	}

	/**
	 * Reads a list of Attributes from the stream.
	 * @return Attribute[]
	 **/
	public static function getAttributeList(ByteBufferReader $in) : array
	{
		$list = [];
		$count = VarInt::readUnsignedInt($in);

		for ($i = 0; $i < $count; ++$i) {
			$min = LE::readFloat($in);
			$max = LE::readFloat($in);
			$current = LE::readFloat($in);
			$default = LE::readFloat($in);
			$id = CommonTypes::getString($in);

			$list[] = new Attribute($id, $min, $max, $current, $default, []);
		}

		return $list;
	}

	public static function putAttributeList(ByteBufferWriter $out, Attribute ...$attributes) : void
	{
		VarInt::writeUnsignedInt($out, count($attributes));
		foreach ($attributes as $attribute) {

			LE::writeFloat($out, $attribute->getMin());
			LE::writeFloat($out, $attribute->getMax());
			LE::writeFloat($out, $attribute->getCurrent());
			LE::writeFloat($out, $attribute->getDefault());
			CommonTypes::putString($out, $attribute->getId());
		}
	}

	static public function getEntityMetaData(ByteBufferReader $in) : array
	{
		$count = VarInt::readUnsignedInt($in);
		$metadata = [];
		for ($i = 0; $i < $count; ++$i) {
			$key = VarInt::readUnsignedInt($in);
			$type = VarInt::readUnsignedInt($in);

			$metadata[$key] = self::readMetadataProperty($in, $type);
		}

		/** @var LongMetadataProperty $flag1Property */
		$flag1Property = $metadata[EntityMetadataProperties::FLAGS] ?? new LongMetadataProperty(0);
		/** @var LongMetadataProperty $flag2Property */
		$flag2Property = $metadata[EntityMetadataProperties::FLAGS2] ?? new LongMetadataProperty(0);
		$flag1 = $flag1Property->getValue();
		$flag2 = $flag2Property->getValue();

		$flag2 <<= 1; // shift left by 1, leaving a 0 at the end
		$flag2 |= (($flag1 >> 63) & 1); // push the last bit from flag1 to the first bit of flag2

		$newFlag1 = $flag1 & ~(~0 << (EntityMetadataFlags::CAN_DASH - 1)); // don't include CAN_DASH and above
		$lastHalf = $flag1 & (~0 << (EntityMetadataFlags::CAN_DASH - 1)); // include everything after where CAN_DASH would be
		$lastHalf <<= 1; // shift left by 1, CAN_DASH is now 0
		$newFlag1 |= $lastHalf; // combine the two halves

		$metadata[EntityMetadataProperties::FLAGS2] = new LongMetadataProperty($flag2);
		$metadata[EntityMetadataProperties::FLAGS] = new LongMetadataProperty($newFlag1);

		return $metadata;
	}

	/** @throws DataDecodeException */
	private static function readMetadataProperty(ByteBufferReader $in, int $type) : MetadataProperty
	{
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

	static public function putEntityMetadata(ByteBufferWriter $out, array $metadata) : void
	{
		$data = $metadata;
		foreach ($data as $type => $val) {
			$metadata[match ($type) {
				EntityMetadataProperties::AREA_EFFECT_CLOUD_RADIUS => 60,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_WAITING => 61,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_PARTICLE_ID => 62,
				EntityMetadataProperties::SHULKER_ATTACH_FACE => 64,
				EntityMetadataProperties::SHULKER_ATTACH_POS => 66,
				EntityMetadataProperties::TRADING_PLAYER_EID => 67,
				EntityMetadataProperties::COMMAND_BLOCK_COMMAND => 70,
				EntityMetadataProperties::COMMAND_BLOCK_LAST_OUTPUT => 71,
				EntityMetadataProperties::COMMAND_BLOCK_TRACK_OUTPUT => 72,
				EntityMetadataProperties::CONTROLLING_RIDER_SEAT_NUMBER => 73,
				EntityMetadataProperties::STRENGTH => 74,
				EntityMetadataProperties::MAX_STRENGTH => 75,
				EntityMetadataProperties::LIMITED_LIFE => 77,
				EntityMetadataProperties::ARMOR_STAND_POSE_INDEX => 78,
				EntityMetadataProperties::ENDER_CRYSTAL_TIME_OFFSET => 79,
				EntityMetadataProperties::ALWAYS_SHOW_NAMETAG => 80,
				EntityMetadataProperties::COLOR_2 => 81,
				EntityMetadataProperties::SCORE_TAG => 83,
				EntityMetadataProperties::BALLOON_ATTACHED_ENTITY => 84,
				EntityMetadataProperties::PUFFERFISH_SIZE => 85,
				EntityMetadataProperties::BOAT_BUBBLE_TIME => 86,
				EntityMetadataProperties::PLAYER_AGENT_EID => 87,
				EntityMetadataProperties::EAT_COUNTER => 90,
				EntityMetadataProperties::FLAGS2 => 91,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_DURATION => 94,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_SPAWN_TIME => 95,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_RADIUS_PER_TICK => 96,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_RADIUS_CHANGE_ON_PICKUP => 97,
				EntityMetadataProperties::AREA_EFFECT_CLOUD_PICKUP_COUNT => 98,
				EntityMetadataProperties::INTERACTIVE_TAG => 99,
				EntityMetadataProperties::TRADE_TIER => 100,
				EntityMetadataProperties::MAX_TRADE_TIER => 101,
				EntityMetadataProperties::TRADE_XP => 102,
				EntityMetadataProperties::SKIN_ID => 104,
				EntityMetadataProperties::COMMAND_BLOCK_TICK_DELAY => 105,
				EntityMetadataProperties::COMMAND_BLOCK_EXECUTE_ON_FIRST_TICK => 106,
				EntityMetadataProperties::AMBIENT_SOUND_INTERVAL_MIN => 107,
				EntityMetadataProperties::AMBIENT_SOUND_INTERVAL_RANGE => 108,
				EntityMetadataProperties::AMBIENT_SOUND_EVENT => 109,
				default => $type,
			}] = $val;
		}

		/** @var LongMetadataProperty $flag1Property */
		$flag1Property = $metadata[EntityMetadataProperties::FLAGS] ?? new LongMetadataProperty(0);
		/** @var LongMetadataProperty $flag2Property */
		$flag2Property = $metadata[EntityMetadataProperties::FLAGS2] ?? new LongMetadataProperty(0);
		$flag1 = $flag1Property->getValue();
		$flag2 = $flag2Property->getValue();

		if ($flag1 !== 0 || $flag2 !== 0) {
			$newFlag1 = $flag1 & ~(~0 << (EntityMetadataFlags::CAN_DASH - 1));
			$lastHalf = $flag1 & (~0 << EntityMetadataFlags::CAN_DASH);
			$lastHalf >>= 1;
			$lastHalf &= PHP_INT_MAX;

			$newFlag1 |= $lastHalf;

			if ($flag2 !== 0) {
				$flag2 = $flag2Property->getValue();
				$newFlag1 ^= ($flag2 & 1) << 63;
				$flag2 >>= 1;
				$flag2 &= PHP_INT_MAX;

				$metadata[EntityMetadataProperties::FLAGS2] = new LongMetadataProperty($flag2);
			}

			$metadata[EntityMetadataProperties::FLAGS] = new LongMetadataProperty($newFlag1);
		}

		VarInt::writeUnsignedInt($out, count($metadata));
		foreach ($metadata as $key => $d) {
			VarInt::writeUnsignedInt($out, $key);
			VarInt::writeUnsignedInt($out, $d->getTypeId());
			$d->write($out);
		}
	}

	public static function getGameRules(ByteBufferReader $in, bool $isStartPacket = false) : array
	{
		$count = VarInt::readUnsignedInt($in);
		$rules = [];
		for ($i = 0; $i < $count; ++$i) {
			$name = CommonTypes::getString($in);
			$type = VarInt::readUnsignedInt($in);
			$rules[$name] = self::readGameRule($in, $type, false, $isStartPacket);
		}

		return $rules;
	}

	/** @throws DataDecodeException */
	private static function readGameRule(ByteBufferReader $in, int $type, bool $isPlayerModifiable, bool $isStartGamePacket) : GameRule
	{
		return match ($type) {
			BoolGameRule::ID => BoolGameRule::decode($in, $isPlayerModifiable),
			IntGameRule::ID => IntGameRule::decode($in, $isPlayerModifiable, $isStartGamePacket),
			FloatGameRule::ID => FloatGameRule::decode($in, $isPlayerModifiable),
			default => throw new PacketDecodeException("Unknown gamerule type $type"),
		};
	}

	/**
	 * @param GameRule[] $rules
	 */
	static public function putGameRules(ByteBufferWriter $out, array $rules, bool $isStartGame = false) : void
	{
		VarInt::writeUnsignedInt($out, count($rules));
		foreach ($rules as $name => $rule) {
			CommonTypes::putString($out, $name);
			VarInt::writeUnsignedInt($out, $rule->getTypeId());
			$rule->encode($out, $isStartGame);
		}
	}

	static public function getCommandOriginData(ByteBufferReader $in) : CommandOriginData
	{
		$result = new CommandOriginData();
		$result->type = VarInt::readUnsignedInt($in);
		$result->uuid = CommonTypes::getUUID($in);
		$result->requestId = CommonTypes::getString($in);
		if ($result->type === CommandOriginData::ORIGIN_DEV_CONSOLE || $result->type === CommandOriginData::ORIGIN_TEST) {
			$result->playerActorUniqueId = VarInt::readSignedLong($in);
		}
		return $result;
	}

	static public function putCommandOriginData(ByteBufferWriter $out, CommandOriginData $data) : void
	{
		VarInt::writeUnsignedInt($out, $data->type);
		CommonTypes::putUUID($out, $data->uuid);
		CommonTypes::putString($out, $data->requestId);

		if ($data->type === CommandOriginData::ORIGIN_DEV_CONSOLE || $data->type === CommandOriginData::ORIGIN_TEST) {
			VarInt::writeSignedLong($out, $data->playerActorUniqueId);
		}
	}
}
