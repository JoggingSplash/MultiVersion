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

namespace cisco\network\proto\v486\structure;

use cisco\network\assemble\CommandOriginData;
use cisco\network\proto\v486\v486TypeConverter;
use Closure;
use InvalidArgumentException;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\DataDecodeException;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\nbt\LittleEndianNbtSerializer;
use pocketmine\nbt\NbtDataException;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\convert\ItemTranslator;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
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
use pocketmine\network\mcpe\protocol\types\recipe\IntIdMetaItemDescriptor;
use pocketmine\network\mcpe\protocol\types\recipe\RecipeIngredient;
use pocketmine\network\mcpe\protocol\types\skin\PersonaPieceTintColor;
use pocketmine\network\mcpe\protocol\types\skin\PersonaSkinPiece;
use pocketmine\network\mcpe\protocol\types\skin\SkinAnimation;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use pocketmine\network\mcpe\protocol\types\skin\SkinImage;
use function count;
use const PHP_INT_MAX;

final class v486CommonTypes
{

	private const PM_BLOCK_RUNTIME_ID_TAG = "___block_runtime_id___";

	private function __construct()
	{
		// NOOP
	}

	public static function getSkin(ByteBufferReader $in) : SkinData
	{
		$skinId = CommonTypes::getString($in);
		$skinPlayFabId = CommonTypes::getString($in);
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
		$geometryDataVersion = CommonTypes::getString($in);
		$animationData = CommonTypes::getString($in);
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

		$premium = CommonTypes::getBool($in);
		$persona = CommonTypes::getBool($in);
		$capeOnClassic = CommonTypes::getBool($in);
		$isPrimaryUser = CommonTypes::getBool($in);

		return new SkinData(
			$skinId,
			$skinPlayFabId,
			$skinResourcePatch,
			$skinData,
			$animations,
			$capeData,
			$geometryData,
			$geometryDataVersion,
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
			$isPrimaryUser,
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
		CommonTypes::putString($out, $skin->getPlayFabId());
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
		CommonTypes::putString($out, $skin->getGeometryDataEngineVersion());
		CommonTypes::putString($out, $skin->getAnimationData());

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
		CommonTypes::putBool($out, $skin->isPremium());
		CommonTypes::putBool($out, $skin->isPersona());
		CommonTypes::putBool($out, $skin->isPersonaCapeOnClassic());
		CommonTypes::putBool($out, $skin->isPrimaryUser());
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

	public static function getRecipeIngredient(ByteBufferReader $in) : RecipeIngredient
	{
		$id = VarInt::readSignedInt($in);
		if ($id === 0) {
			return new RecipeIngredient(new IntIdMetaItemDescriptor(0, 0), 0);
		}
		$meta = VarInt::readSignedInt($in);
		if ($meta === 0x7fff) {
			$meta = -1;
		}
		$count = VarInt::readSignedInt($in);
		return new RecipeIngredient(new IntIdMetaItemDescriptor($id, $meta), $count);
	}

	public static function putRecipeIngredient(ByteBufferWriter $out, RecipeIngredient $item) : void
	{
		$descriptor = $item->getDescriptor();
		if ($descriptor?->getTypeId() === IntIdMetaItemDescriptor::ID) {
			/** @var IntIdMetaItemDescriptor $descriptor */
			if ($descriptor->getId() === 0) {
				VarInt::writeSignedInt($out, 0);
			} else {
				VarInt::writeSignedInt($out, $descriptor->getId());
				VarInt::writeSignedInt($out, $descriptor->getMeta() & 0x7fff);
				VarInt::writeSignedInt($out, $item->getCount());
			}
		} else {
			VarInt::writeSignedInt($out, 0);
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
			default => throw new PacketDecodeException("Unknown gamerule type $type")
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
			CommonTypes::putBool($out, $rule->isPlayerModifiable());
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

	static public function getItemStackWrapper(ByteBufferReader $in) : ItemStackWrapper
	{
		$stackId = 0;
		$itemStack = self::getItemStack($in, function (ByteBufferReader $in) use (&$stackId) : void {
			$hasNetId = CommonTypes::getBool($in);
			if ($hasNetId) {
				$stackId = VarInt::readSignedInt($in);
			}
		});
		return new ItemStackWrapper($stackId, $itemStack);
	}

	static public function getItemStack(ByteBufferReader $in, Closure $readExtraCrapInTheMiddle) : ItemStack
	{
		$id = VarInt::readSignedInt($in);

		if ($id === 0) {
			return ItemStack::null();
		}

		$count = LE::readUnsignedShort($in);
		$netData = VarInt::readUnsignedInt($in);

		$type_converter = v486TypeConverter::getInstance()->getConverter();

		[$id, $meta] = $type_converter->getMVItemTranslator()->fromNetworkId($id, $netData, ItemTranslator::NO_BLOCK_RUNTIME_ID);

		$readExtraCrapInTheMiddle($in);

		$blockRuntimeId = VarInt::readSignedInt($in);
		$newIn = new ByteBufferReader(CommonTypes::getString($in)); // FUCK MOJANG WHY THIS SHIT IS LIKE THIS LMFAO

		$nbtLen = LE::readUnsignedShort($newIn);
		/** @var CompoundTag|null $compound */
		$compound = null;
		if ($nbtLen === 0xffff) {
			$nbtDataVersion = Byte::readUnsigned($newIn);
			if ($nbtDataVersion !== 1) {
				throw new PacketDecodeException("Unexpected NBT data version $nbtDataVersion");
			}
			$offset = $newIn->getOffset();
			try {
				$compound = (new LittleEndianNbtSerializer())->read($newIn->getData(), $offset, 512)->mustGetCompoundTag();
			} catch (NbtDataException $e) {
				throw PacketDecodeException::wrap($e, "Failed decoding NBT root");
			} finally {
				$newIn->setOffset($offset);
			}
		} elseif ($nbtLen !== 0) {
			throw new PacketDecodeException("Unexpected fake NBT length $nbtLen");
		}

		$canPlaceOn = [];
		for ($i = 0, $canPlaceOnCount = LE::readUnsignedInt($newIn); $i < $canPlaceOnCount; ++$i) {
			$canPlaceOn[] = $newIn->readByteArray(LE::readUnsignedShort($newIn));
		}

		$canDestroy = [];
		for ($i = 0, $canDestroyCount = LE::readUnsignedInt($newIn); $i < $canDestroyCount; ++$i) {
			$canDestroy[] = $newIn->readByteArray(LE::readUnsignedShort($newIn));
		}

		$shieldBlockingTick = null;
		if ($id === $type_converter->getShieldRuntimeId()) {
			$shieldBlockingTick = LE::readUnsignedLong($newIn);
		}

		if ($newIn->getUnreadLength() > 0) {
			throw new PacketDecodeException("Still {$newIn->getUnreadLength()} bytes unread in ItemStack");
		}

		$extraData = $shieldBlockingTick !== null ?
			new ItemStackExtraDataShield($compound, canPlaceOn: $canPlaceOn, canDestroy: $canDestroy, blockingTick: $shieldBlockingTick) :
			new ItemStackExtraData($compound, canPlaceOn: $canPlaceOn, canDestroy: $canDestroy);
		$extraDataSerializer = new ByteBufferWriter();
		$extraData->write($extraDataSerializer);

		return new ItemStack($id, $meta, $count, $blockRuntimeId, $extraDataSerializer->getData());
	}

	static public function putItemStackWrapper(ByteBufferWriter $out, ItemStackWrapper $itemStack) : void {
		self::putItemStack($out, $itemStack->getItemStack(), function (ByteBufferWriter $out) use ($itemStack) : void {
			$stackId = $itemStack->getStackId();
			CommonTypes::putBool($out, $stackId !== 0);
			if ($stackId !== 0) {
				VarInt::writeSignedInt($out, $stackId);
			}
		});
	}

	static public function putItemStack(ByteBufferWriter $out, ItemStack $itemStack, Closure $writeExtraCrapInTheMiddle) : void
	{
		if ($itemStack->getId() === 0) {
			VarInt::writeSignedInt($out, 0);
			return;
		}

		$type_converter = v486TypeConverter::getInstance()->getConverter();

		[$netId, $netData] = $type_converter->getMVItemTranslator()->toNetworkId(TypeConverter::getInstance()->getItemTranslator()->fromNetworkId($itemStack->getId(), $itemStack->getMeta(), ItemTranslator::NO_BLOCK_RUNTIME_ID));

		VarInt::writeSignedInt($out, $netId);
		LE::writeUnsignedShort($out, $itemStack->getCount());
		VarInt::writeUnsignedInt($out, $netData);

		$writeExtraCrapInTheMiddle($out);

		VarInt::writeSignedInt($out, $itemStack->getBlockRuntimeId());

		$newOut = new ByteBufferWriter(); // FUCK MOJANG WTF
		$decoder = new ByteBufferReader($itemStack->getRawExtraData());

		if ($itemStack->getId() === $type_converter->getShieldRuntimeId()) {
			$extraData = ItemStackExtraDataShield::read($decoder);
		} else {
			$extraData = ItemStackExtraData::read($decoder);
		}

		$extraData->write($newOut);

		CommonTypes::putString($out, $newOut->getData()); // SHIT MOJANG
	}
}
