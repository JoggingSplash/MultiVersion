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

namespace cisco\network\proto\v486\packets;

use cisco\network\proto\v486\structure\v486ProtocolInfo;
use InvalidArgumentException;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\color\Color;
use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\MapDecoration;
use pocketmine\network\mcpe\protocol\types\MapImage;
use pocketmine\network\mcpe\protocol\types\MapTrackedObject;
use pocketmine\utils\Binary;
use function count;

class v486ClientboundMapItemDataPacket extends ClientboundMapItemDataPacket
{

	public const NETWORK_ID = v486ProtocolInfo::CLIENTBOUND_MAP_ITEM_DATA_PACKET;

	public static function fromLatest(ClientboundMapItemDataPacket $pk) : self
	{
		$npk = new self();
		$npk->mapId = $pk->mapId;
		$npk->type = $pk->type;
		$npk->dimensionId = $pk->dimensionId;
		$npk->isLocked = $pk->isLocked;
		$npk->parentMapIds = $pk->parentMapIds;
		$npk->scale = $pk->scale;
		$npk->trackedEntities = $pk->trackedEntities;
		$npk->decorations = $pk->decorations;
		$npk->xOffset = $pk->xOffset;
		$npk->yOffset = $pk->yOffset;
		$npk->colors = $pk->colors;
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->mapId = CommonTypes::getActorUniqueId($in);
		$this->type = VarInt::readUnsignedInt($in);
		$this->dimensionId = Byte::readUnsigned($in);
		$this->isLocked = CommonTypes::getBool($in);

		if (($this->type & self::BITFLAG_MAP_CREATION) !== 0) {
			$count = VarInt::readUnsignedInt($in);
			for ($i = 0; $i < $count; ++$i) {
				$this->parentMapIds[] = CommonTypes::getActorUniqueId($in);
			}
		}

		if (($this->type & (self::BITFLAG_MAP_CREATION | self::BITFLAG_DECORATION_UPDATE | self::BITFLAG_TEXTURE_UPDATE)) !== 0) { //Decoration bitflag or colour bitflag
			$this->scale = Byte::readUnsigned($in);
		}

		if (($this->type & self::BITFLAG_DECORATION_UPDATE) !== 0) {
			for ($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i) {
				$object = new MapTrackedObject();
				$object->type = LE::readUnsignedInt($in);
				if ($object->type === MapTrackedObject::TYPE_BLOCK) {
					$object->blockPosition = CommonTypes::getBlockPosition($in);
				} elseif ($object->type === MapTrackedObject::TYPE_ENTITY) {
					$object->actorUniqueId = CommonTypes::getActorUniqueId($in);
				} else {
					throw new PacketDecodeException("Unknown map object type $object->type");
				}
				$this->trackedEntities[] = $object;
			}

			for ($i = 0, $count = VarInt::readUnsignedInt($in); $i < $count; ++$i) {
				$icon = Byte::readUnsigned($in);
				$rotation = Byte::readUnsigned($in);
				$xOffset = Byte::readUnsigned($in);
				$yOffset = Byte::readUnsigned($in);
				$label = CommonTypes::getString($in);
				$color = Color::fromRGBA(Binary::flipIntEndianness(VarInt::readUnsignedInt($in)));
				$this->decorations[] = new MapDecoration($icon, $rotation, $xOffset, $yOffset, $label, $color);
			}
		}

		if (($this->type & self::BITFLAG_TEXTURE_UPDATE) !== 0) {
			$width = VarInt::readSignedInt($in);
			$height = VarInt::readSignedInt($in);
			$this->xOffset = VarInt::readSignedInt($in);
			$this->yOffset = VarInt::readSignedInt($in);

			$count = VarInt::readUnsignedInt($in);
			if ($count !== $width * $height) {
				throw new PacketDecodeException("Expected colour count of " . ($height * $width) . " (height $height * width $width), got $count");
			}

			$this->colors = MapImage::decode($in, $height, $width);
		}
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putActorUniqueId($out, $this->mapId);

		$type = 0;
		if (($parentMapIdsCount = count($this->parentMapIds)) > 0) {
			$type |= self::BITFLAG_MAP_CREATION;
		}
		if (($decorationCount = count($this->decorations)) > 0) {
			$type |= self::BITFLAG_DECORATION_UPDATE;
		}
		if ($this->colors !== null) {
			$type |= self::BITFLAG_TEXTURE_UPDATE;
		}

		VarInt::writeUnsignedInt($out, $type);
		Byte::writeUnsigned($out, $this->dimensionId);
		CommonTypes::putBool($out, $this->isLocked);

		if (($type & self::BITFLAG_MAP_CREATION) !== 0) {
			VarInt::writeUnsignedInt($out, $parentMapIdsCount);
			foreach ($this->parentMapIds as $parentMapId) {
				CommonTypes::putActorUniqueId($out, $parentMapId);
			}
		}

		if (($type & (self::BITFLAG_MAP_CREATION | self::BITFLAG_TEXTURE_UPDATE | self::BITFLAG_DECORATION_UPDATE)) !== 0) {
			Byte::writeUnsigned($out, $this->scale);
		}

		if (($type & self::BITFLAG_DECORATION_UPDATE) !== 0) {
			VarInt::writeUnsignedInt($out, count($this->trackedEntities));
			foreach ($this->trackedEntities as $object) {
				LE::writeUnsignedInt($out, $object->type);
				if ($object->type === MapTrackedObject::TYPE_BLOCK) {
					CommonTypes::putBlockPosition($out, $object->blockPosition);
				} elseif ($object->type === MapTrackedObject::TYPE_ENTITY) {
					CommonTypes::putActorUniqueId($out, $object->actorUniqueId);
				} else {
					throw new InvalidArgumentException("Unknown map object type $object->type");
				}
			}

			VarInt::writeUnsignedInt($out, $decorationCount);
			foreach ($this->decorations as $decoration) {
				Byte::writeUnsigned($out, $decoration->getIcon());
				Byte::writeUnsigned($out, $decoration->getRotation());
				Byte::writeUnsigned($out, $decoration->getXOffset());
				Byte::writeUnsigned($out, $decoration->getYOffset());
				CommonTypes::putString($out, $decoration->getLabel());
				VarInt::writeUnsignedInt($out, Binary::flipIntEndianness($decoration->getColor()->toRGBA()));
			}
		}

		if ($this->colors !== null) {
			VarInt::writeSignedInt($out, $this->colors->getWidth());
			VarInt::writeSignedInt($out, $this->colors->getHeight());
			VarInt::writeSignedInt($out, $this->xOffset);
			VarInt::writeSignedInt($out, $this->yOffset);

			VarInt::writeUnsignedInt($out, $this->colors->getWidth() * $this->colors->getHeight()); //list count, but we handle it as a 2D array... thanks for the confusion mojang

			$this->colors->encode($out);
		}
	}
}
