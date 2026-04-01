<?php

namespace cisco\network\proto\v844\packets;

use cisco\network\utils\RawPacketHelper;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\LabTablePacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844LabTablePacket extends LabTablePacket{


    protected function decodePayload(ByteBufferReader $in) : void{
        $this->actionType = Byte::readUnsigned($in);
        $this->blockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
        $this->reactionType = Byte::readUnsigned($in);
    }

    protected function encodePayload(ByteBufferWriter $out) : void{
        Byte::writeUnsigned($out, $this->actionType);
        RawPacketHelper::putUnsignedYBlockPosition($out, $this->blockPosition);
        Byte::writeUnsigned($out, $this->reactionType);
    }
}