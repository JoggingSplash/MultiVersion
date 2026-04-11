<?php

/*
 *
 *
 *      __  ___      ____  _ _    __               _
 *     /  |/  /_  __/ / /_(_) |  / /__  __________(_)___  ____
 *    / /|_/ / / / / / __/ /| | / / _ \/ ___/ ___/ / __ \/ __ \
 *   / /  / / /_/ / / /_/ / | |/ /  __/ /  (__  ) / /_/ / / / /
 *  /_/  /_/\__,_/_/\__/_/  |___/\___/_/  /____/_/\____/_/ /_/
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Lesser General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  @author JoggingSplash23
 *  @link https://www.github.com/JoggingSplash
 *
 *
 */

declare(strict_types=1);

namespace cisco\network\proto\v844\packets\types\inventory;

use cisco\network\utils\RawPacketHelper;
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
		ReflectionUtils::setProperty(UseItemTransactionData::class, $this, "blockPosition", RawPacketHelper::getUnsignedYBlockPosition($in));
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
		RawPacketHelper::putUnsignedYBlockPosition($out, $this->getBlockPosition());
		VarInt::writeSignedInt($out, $this->getFace());
		VarInt::writeSignedInt($out, $this->getHotbarSlot());
		CommonTypes::putItemStackWrapper($out, $this->getItemInHand());
		CommonTypes::putVector3($out, $this->getPlayerPosition());
		CommonTypes::putVector3($out, $this->getClickPosition());
		VarInt::writeUnsignedInt($out, $this->getBlockRuntimeId());
		VarInt::writeUnsignedInt($out, $this->getClientInteractPrediction()->value);
	}
}
