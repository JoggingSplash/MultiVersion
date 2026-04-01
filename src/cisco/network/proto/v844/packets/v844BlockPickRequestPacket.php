<?php

namespace cisco\network\proto\v844\packets;

use cisco\network\utils\RawPacketHelper;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\BlockPickRequestPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844BlockPickRequestPacket extends BlockPickRequestPacket {


    protected function decodePayload(ByteBufferReader $in) : void{
        $this->blockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
        $this->addUserData = CommonTypes::getBool($in);
        $this->hotbarSlot = Byte::readUnsigned($in);
    }

    protected function encodePayload(ByteBufferWriter $out) : void{
        RawPacketHelper::putUnsignedYBlockPosition($out, $this->blockPosition);
        CommonTypes::putBool($out, $this->addUserData);
        Byte::writeUnsigned($out, $this->hotbarSlot);
    }
}