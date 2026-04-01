<?php

namespace cisco\network\proto\v419\packets;

use cisco\network\utils\RawPacketHelper;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v419ContainerOpenPacket extends ContainerOpenPacket {

    static public function fromLatest(ContainerOpenPacket $packet): self    {
        $npk = new self();
        $npk->windowId = $packet->windowId;
        $npk->windowType = $packet->windowType;
        $npk->blockPosition = $packet->blockPosition;
        $npk->actorUniqueId = $packet->actorUniqueId;
        return $npk;
    }


    protected function decodePayload(ByteBufferReader $in) : void{
        $this->windowId = Byte::readUnsigned($in);
        $this->windowType = Byte::readUnsigned($in);
        $this->blockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
        $this->actorUniqueId = CommonTypes::getActorUniqueId($in);
    }

    protected function encodePayload(ByteBufferWriter $out) : void{
        Byte::writeUnsigned($out, $this->windowId);
        Byte::writeUnsigned($out, $this->windowType);
        RawPacketHelper::putUnsignedYBlockPosition($out, $this->blockPosition);
        CommonTypes::putActorUniqueId($out, $this->actorUniqueId);
    }
}