<?php

namespace cisco\network\proto\v844\packets;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\FullContainerName;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

class v844InventorySlotPacket extends InventorySlotPacket {

    static public function fromLatest(InventorySlotPacket $packet): self {
        $result = new self();
        $result->windowId = $packet->windowId;
        $result->inventorySlot = $packet->inventorySlot;
        $result->containerName = $packet->containerName ?? new FullContainerName(0, null);
        $result->storage = $packet->storage ?? ItemStackWrapper::legacy(ItemStack::null());
        $result->item = $packet->item;
        return $result;
    }

    protected function decodePayload(ByteBufferReader $in) : void{
        $this->windowId = VarInt::readUnsignedInt($in);
        $this->inventorySlot = VarInt::readUnsignedInt($in);
        $this->containerName = FullContainerName::read($in);
        $this->storage = CommonTypes::getItemStackWrapper($in);
        $this->item = CommonTypes::getItemStackWrapper($in);
    }

    protected function encodePayload(ByteBufferWriter $out) : void{
        VarInt::writeUnsignedInt($out, $this->windowId);
        VarInt::writeUnsignedInt($out, $this->inventorySlot);
        $this->containerName->write($out);
        CommonTypes::putItemStackWrapper($out, $this->storage);
        CommonTypes::putItemStackWrapper($out, $this->item);
    }
}