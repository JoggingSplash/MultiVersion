<?php

namespace cisco\network\legacy\types;

use cisco\network\utils\RawPacketHelper;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\types\BlockPosition;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataTypes;
use pocketmine\network\mcpe\protocol\types\entity\MetadataProperty;
use pocketmine\network\mcpe\protocol\types\GetTypeIdFromConstTrait;

final class LegacyBlockPosMetadataProperty implements MetadataProperty {
    use GetTypeIdFromConstTrait;

    public const ID = EntityMetadataTypes::POS;

    public function __construct(
        private BlockPosition $value
    ){}

    public function getValue() : BlockPosition{
        return $this->value;
    }

    public static function read(ByteBufferReader $in) : self{
        return new self(RawPacketHelper::getUnsignedYBlockPosition($in));
    }

    public function write(ByteBufferWriter $out) : void{
        RawPacketHelper::putUnsignedYBlockPosition($out, $this->value);
    }

    public function equals(MetadataProperty $other) : bool{
        return $other instanceof self and $other->value->equals($this->value);
    }
}