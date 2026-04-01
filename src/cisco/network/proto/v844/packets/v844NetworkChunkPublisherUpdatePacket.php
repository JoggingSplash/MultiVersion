<?php

namespace cisco\network\proto\v844\packets;

use cisco\network\utils\RawPacketHelper;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\ChunkPosition;

class v844NetworkChunkPublisherUpdatePacket extends NetworkChunkPublisherUpdatePacket {

    static public function fromLatest(NetworkChunkPublisherUpdatePacket $packet): self    {
        $npk = new self();
        $npk->blockPosition = $packet->blockPosition;
        $npk->radius = $packet->radius;
        $npk->savedChunks = $packet->savedChunks;
        return $npk;
    }

    protected function decodePayload(ByteBufferReader $in) : void{
        $this->blockPosition = RawPacketHelper::getUnsignedYBlockPosition($in);
        $this->radius = VarInt::readUnsignedInt($in);

        $count = LE::readUnsignedInt($in);
        if($count > self::MAX_SAVED_CHUNKS){
            throw new PacketDecodeException("Expected at most " . self::MAX_SAVED_CHUNKS . " saved chunks, got " . $count);
        }
        for($i = 0, $this->savedChunks = []; $i < $count; $i++){
            $this->savedChunks[] = ChunkPosition::read($in);
        }
    }

    protected function encodePayload(ByteBufferWriter $out) : void{
        RawPacketHelper::putUnsignedYBlockPosition($out, $this->blockPosition);
        VarInt::writeUnsignedInt($out, $this->radius);

        LE::writeUnsignedInt($out, count($this->savedChunks));
        foreach($this->savedChunks as $chunk){
            $chunk->write($out);
        }
    }
}