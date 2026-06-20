<?php

namespace cisco\network\proto\v844\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v844DisconnectPacket extends DisconnectPacket {

    public static function fromLatest(DisconnectPacket $packet): self {
        $result = new self();
        $result->reason = $packet->reason;
        $result->message = $packet->message;
        $result->filteredMessage = $packet->filteredMessage;
        return $result;
    }


    protected function decodePayload(ByteBufferReader $in) : void{
        $this->reason = VarInt::readSignedInt($in);
        $skipMessage = CommonTypes::getBool($in);
        $this->message = $skipMessage ? null : CommonTypes::getString($in);
        $this->filteredMessage = $skipMessage ? null : CommonTypes::getString($in);
    }

    protected function encodePayload(ByteBufferWriter $out) : void{
        VarInt::writeSignedInt($out, $this->reason);
        CommonTypes::putBool($out, $skipMessage = $this->message === null && $this->filteredMessage === null);
        if(!$skipMessage){
            CommonTypes::putString($out, $this->message ?? "");
            CommonTypes::putString($out, $this->filteredMessage ?? "");
        }
    }
}