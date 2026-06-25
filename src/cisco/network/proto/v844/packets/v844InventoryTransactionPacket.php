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

namespace cisco\network\proto\v844\packets;

use cisco\network\legacy\types\transactions\LegacyReleaseItemTransactionData;
use cisco\network\legacy\types\transactions\LegacyUseItemOnEntityTransactionData;
use cisco\network\proto\v844\packets\types\inventory\v844UseItemTransactionData;
use cisco\network\proto\v844\packets\types\v844NetworkInventoryAction;
use cisco\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\InventoryTransactionChangedSlotsHack;
use pocketmine\network\mcpe\protocol\types\inventory\MismatchTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\NormalTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\ReleaseItemTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemTransactionData;
use function count;
use function get_class;

class v844InventoryTransactionPacket extends InventoryTransactionPacket {

	public static function fromLatest(InventoryTransactionPacket $packet) : self{
		$result = new self();
		$result->requestId = $packet->requestId;
		$result->requestChangedSlots = $packet->requestChangedSlots ?? [];
		$result->trData = $packet->trData;
		return $result;
	}

	protected function decodePayload(ByteBufferReader $in) : void{
		$this->requestId = CommonTypes::readLegacyItemStackRequestId($in);
		$this->requestChangedSlots = [];
		if($this->requestId !== 0){
			for($i = 0, $len = VarInt::readUnsignedInt($in); $i < $len; ++$i){
				$this->requestChangedSlots[] = InventoryTransactionChangedSlotsHack::read($in);
			}
		}

		$transactionType = VarInt::readUnsignedInt($in);

		$this->trData = match($transactionType){
			NormalTransactionData::ID => new NormalTransactionData(),
			MismatchTransactionData::ID => new MismatchTransactionData(),
			UseItemTransactionData::ID => new v844UseItemTransactionData(),
			UseItemOnEntityTransactionData::ID => new LegacyUseItemOnEntityTransactionData(),
			ReleaseItemTransactionData::ID => new LegacyReleaseItemTransactionData(),
			default => throw new PacketDecodeException("Unknown transaction type $transactionType"),
		};

		$actionCount = VarInt::readUnsignedInt($in);
		$actions = [];
		for($i = 0; $i < $actionCount; ++$i){
			$actions[] = (new v844NetworkInventoryAction())->read($in);
		}

		ReflectionUtils::setProperty(get_class($this->trData), $this->trData, "actions", $actions);
		ReflectionUtils::invoke(get_class($this->trData), $this->trData, "decodeData", $in);
	}

	protected function encodePayload(ByteBufferWriter $out) : void{
		CommonTypes::writeLegacyItemStackRequestId($out, $this->requestId);
		if($this->requestId !== 0){
			VarInt::writeUnsignedInt($out, count($this->requestChangedSlots));
			foreach($this->requestChangedSlots as $changedSlots){
				$changedSlots->write($out);
			}
		}

		VarInt::writeUnsignedInt($out, $this->trData->getTypeId());

		$this->trData->encodeTransaction($out);
	}
}
