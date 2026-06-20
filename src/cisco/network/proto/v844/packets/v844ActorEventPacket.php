<?php

namespace cisco\network\proto\v844\packets;

use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844ActorEventPacket extends ActorEventPacket {

    static public function fromLatest(ActorEventPacket $packet): self {
        $result = new self();
        $result->actorRuntimeId = $packet->actorRuntimeId;
        $result->eventId = $packet->eventId;
        $result->eventData = $packet->eventData;
        return $result;
    }


    protected function decodePayload(ByteBufferReader $in) : void{
        $this->actorRuntimeId = CommonTypes::getActorRuntimeId($in);
        $this->eventId = Byte::readUnsigned($in);
        $this->eventData = VarInt::readSignedInt($in);
    }

    protected function encodePayload(ByteBufferWriter $out) : void{
        CommonTypes::putActorRuntimeId($out, $this->actorRuntimeId);
        Byte::writeUnsigned($out, $this->eventId);
        VarInt::writeSignedInt($out, $this->eventData);
    }

}