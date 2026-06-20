<?php

namespace cisco\network\proto\v844\packets;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844MobEquipmentPacket extends MobEquipmentPacket {

    static public function fromLatest(MobEquipmentPacket $packet): self {
        $result = new self();
        $result->actorRuntimeId = $packet->actorRuntimeId;
        $result->item = $packet->item;
        $result->inventorySlot = $packet->inventorySlot;
        $result->hotbarSlot = $packet->hotbarSlot;
        $result->windowId = $packet->windowId;
        return $result;
    }


    protected function decodePayload(ByteBufferReader $in) : void{
        $this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
        $this->item = CommonTypes::getItemStackWrapper($in);
        $this->inventorySlot = Byte::readUnsigned($in);
        $this->hotbarSlot = Byte::readUnsigned($in);
        $this->windowId = Byte::readUnsigned($in);
    }

    protected function encodePayload(ByteBufferWriter $out) : void{
        CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
        CommonTypes::putItemStackWrapper($out, $this->item);
        Byte::writeUnsigned($out, $this->inventorySlot);
        Byte::writeUnsigned($out, $this->hotbarSlot);
        Byte::writeUnsigned($out, $this->windowId);
    }
}