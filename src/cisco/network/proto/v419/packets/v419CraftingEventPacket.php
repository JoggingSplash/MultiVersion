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

namespace cisco\network\proto\v419\packets;

use cisco\network\proto\v419\structure\v419CommonTypes;
use cisco\network\proto\v419\structure\v419ProtocolInfo;
use pmmp\encoding\Byte;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\PacketHandlerInterface;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use Ramsey\Uuid\UuidInterface;
use function count;

class v419CraftingEventPacket extends DataPacket implements ServerboundPacket
{

	public const NETWORK_ID = v419ProtocolInfo::CRAFTING_EVENT_PACKET;

	public int $windowId;

	public int $type;

	public UuidInterface $id;
	/** @var ItemStack[] */
	public array $input = [];
	/** @var ItemStack[] */
	public array $output = [];

	public function handle(PacketHandlerInterface $handler) : bool
	{
		return true; //this is a broken useless packet
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		Byte::writeUnsigned($out, $this->windowId);
		VarInt::writeSignedInt($out, $this->type);
		CommonTypes::putUUID($out, $this->id);
		VarInt::writeUnsignedInt($out, count($this->input));
		foreach ($this->input as $input) {
			v419CommonTypes::putItemStackWithoutStackId($input, $out);
		}

		VarInt::writeSignedInt($out, count($this->output));
		foreach ($this->output as $output) {
			v419CommonTypes::putItemStackWithoutStackId($output, $out);
		}
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		$this->windowId = Byte::readUnsigned($in);
		$this->type = VarInt::readSignedInt($in);
		$this->id = CommonTypes::getUUID($in);
		$size = VarInt::readUnsignedInt($in);

		for ($i = 0; $i < $size && $i < 128; ++$i) {
			$this->input[] = v419CommonTypes::getItemStackWithoutStackId($in);
		}

		$size = VarInt::readUnsignedInt($in);
		for ($i = 0; $i < $size && $i < 128; ++$i) {
			$this->output[] = v419CommonTypes::getItemStackWithoutStackId($in);
		}
	}
}
