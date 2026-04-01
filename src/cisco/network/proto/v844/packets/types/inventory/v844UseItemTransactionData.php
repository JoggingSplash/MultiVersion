<?php

namespace cisco\network\proto\v844\packets\types\inventory;

use cisco\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\PredictedResult;
use pocketmine\network\mcpe\protocol\types\inventory\TriggerType;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
class v844UseItemTransactionData extends UseItemTransactionData {

    protected function decodeData(ByteBufferReader $in) : void{
        ReflectionUtils::setProperty(UseItemTransactionData::class, $this, "actionType", VarInt::readUnsignedInt($in));
        ReflectionUtils::setProperty(UseItemTransactionData::class, $this, "triggerType", TriggerType::fromPacket(VarInt::readUnsignedInt($in)));
        ReflectionUtils::setProperty(UseItemTransactionData::class, $this, "blockPosition", CommonTypes::getBlockPosition($in));
        ReflectionUtils::setProperty(UseItemTransactionData::class, $this, "face", VarInt::readSignedInt($in));
        ReflectionUtils::setProperty(UseItemTransactionData::class, $this, "hotbarSlot", VarInt::readSignedInt($in));
        ReflectionUtils::setProperty(UseItemTransactionData::class, $this, "itemInHand", CommonTypes::getItemStackWrapper($in));
        ReflectionUtils::setProperty(UseItemTransactionData::class, $this, "playerPosition", CommonTypes::getVector3($in));
        ReflectionUtils::setProperty(UseItemTransactionData::class, $this, "clickPosition", CommonTypes::getVector3($in));
        ReflectionUtils::setProperty(UseItemTransactionData::class, $this, "blockRuntimeId", VarInt::readUnsignedInt($in));
        ReflectionUtils::setProperty(UseItemTransactionData::class, $this, "clientInteractPrediction", PredictedResult::fromPacket(VarInt::readUnsignedInt($in)));
    }

    protected function encodeData(ByteBufferWriter $out) : void{
        VarInt::writeUnsignedInt($out, $this->getActionType());
        VarInt::writeUnsignedInt($out, $this->getTriggerType()->value);
        CommonTypes::putBlockPosition($out, $this->getBlockPosition());
        VarInt::writeSignedInt($out, $this->getFace());
        VarInt::writeSignedInt($out, $this->getHotbarSlot());
        CommonTypes::putItemStackWrapper($out, $this->getItemInHand());
        CommonTypes::putVector3($out, $this->getPlayerPosition());
        CommonTypes::putVector3($out, $this->getClickPosition());
        VarInt::writeUnsignedInt($out, $this->getBlockRuntimeId());
        VarInt::writeUnsignedInt($out, $this->getClientInteractPrediction()->value);
    }
}