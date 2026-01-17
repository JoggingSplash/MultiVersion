<?php

/*
 *     __  ___      ____  _ _    __               _
 *    /  |/  /_  __/ / /_(_) |  / /__  __________(_)___  ____
 *   / /|_/ / / / / / __/ /| | / / _ \/ ___/ ___/ / __ \/ __ \
 *  / /  / / /_/ / / /_/ / | |/ /  __/ /  (__  ) / /_/ / / / /
 * /_/  /_/\__,_/_/\__/_/  |___/\___/_/  /____/_/\____/_/ /_/
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author JoggingSplash23
 * @link https://www.github.com/JoggingSplash
 *
 *
 */

declare(strict_types=1);

namespace cisco\network\proto\v486\packets;

use cisco\network\proto\v486\structure\v486ProtocolInfo;
use cisco\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\LE;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\serializer\BitSet;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\inventory\stackrequest\ItemStackRequest;
use pocketmine\network\mcpe\protocol\types\ItemInteractionData;
use pocketmine\network\mcpe\protocol\types\PlayerAction;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionStopBreak;
use pocketmine\network\mcpe\protocol\types\PlayerBlockActionWithBlockInfo;

class v486PlayerAuthInputPacket extends PlayerAuthInputPacket
{

	public const NETWORK_ID = v486ProtocolInfo::PLAYER_AUTH_INPUT_PACKET;

	protected function decodePayload(ByteBufferReader $in) : void
	{
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "pitch", LE::readFloat($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "yaw", LE::readFloat($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "position", CommonTypes::getVector3($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "moveVecX", LE::readFloat($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "moveVecZ", LE::readFloat($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "headYaw", LE::readFloat($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "inputFlags", BitSet::read($in, 65));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "inputMode", VarInt::readUnsignedInt($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "playMode", VarInt::readUnsignedInt($in));
		if ($this->getPlayMode() === 4) { // fuck 1.21.120 they removed 4 PlayModes and i need to hardcode this
			ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "vrGazeDirection", CommonTypes::getVector3($in));
		}
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "tick", VarInt::readUnsignedLong($in));
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "delta", CommonTypes::getVector3($in));

		if ($this->getInputFlags()->get(PlayerAuthInputFlags::PERFORM_ITEM_INTERACTION)) {
			$d = v486ItemInteractionData::read($in);
			ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, 'itemInteractionData', new ItemInteractionData($d->getRequestId(), $d->getRequestChangedSlots(), $d->getTransactionData()));
		}
		if ($this->getInputFlags()->get(PlayerAuthInputFlags::PERFORM_ITEM_STACK_REQUEST)) {
			$request = v486ItemStackRequest::read($in);
			ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "itemStackRequest", new ItemStackRequest($request->getRequestId(), $request->getActions(), $request->getFilterStrings(), $request->getFilterStringCause()));
		}
		if ($this->getInputFlags()->get(PlayerAuthInputFlags::PERFORM_BLOCK_ACTIONS)) {
			$blockActions = [];
			$max = VarInt::readSignedInt($in);
			for ($i = 0; $i < $max; ++$i) {
				$actionType = VarInt::readSignedInt($in);
				$blockActions[] = match (true) {
					PlayerBlockActionWithBlockInfo::isValidActionType($actionType) => PlayerBlockActionWithBlockInfo::read($in, $actionType),
					$actionType === PlayerAction::STOP_BREAK => new PlayerBlockActionStopBreak(),
					default => throw new PacketDecodeException("Unexpected block action type $actionType")
				};
			}
			ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "blockActions", $blockActions);
		}
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "analogMoveVecX", 0);
		ReflectionUtils::setProperty(PlayerAuthInputPacket::class, $this, "analogMoveVecZ", 0);
	}
}
