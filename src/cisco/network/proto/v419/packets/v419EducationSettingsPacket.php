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

use cisco\network\proto\v419\structure\v419ProtocolInfo;
use cisco\network\utils\ReflectionUtils;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\network\mcpe\protocol\EducationSettingsPacket;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;

class v419EducationSettingsPacket extends EducationSettingsPacket
{

	public const NETWORK_ID = v419ProtocolInfo::EDUCATION_SETTINGS_PACKET;

	public static function fromLatest(EducationSettingsPacket $pk) : self
	{
		$npk = new self();
		ReflectionUtils::setProperty(EducationSettingsPacket::class, $npk, "codeBuilderDefaultUri", $pk->getCodeBuilderDefaultUri());
		ReflectionUtils::setProperty(EducationSettingsPacket::class, $npk, "codeBuilderTitle", $pk->getCodeBuilderTitle());
		ReflectionUtils::setProperty(EducationSettingsPacket::class, $npk, "canResizeCodeBuilder", $pk->canResizeCodeBuilder());
		ReflectionUtils::setProperty(EducationSettingsPacket::class, $npk, "codeBuilderOverrideUri", $pk->getCodeBuilderOverrideUri());
		ReflectionUtils::setProperty(EducationSettingsPacket::class, $npk, "hasQuiz", $pk->getHasQuiz());
		return $npk;
	}

	protected function decodePayload(ByteBufferReader $in) : void
	{
		ReflectionUtils::setProperty(EducationSettingsPacket::class, $this, "codeBuilderDefaultUri", CommonTypes::getString($in));
		ReflectionUtils::setProperty(EducationSettingsPacket::class, $this, "codeBuilderTitle", CommonTypes::getString($in));
		ReflectionUtils::setProperty(EducationSettingsPacket::class, $this, "canResizeCodeBuilder", CommonTypes::getBool($in));
		ReflectionUtils::setProperty(EducationSettingsPacket::class, $this, "codeBuilderOverrideUri", CommonTypes::readOptional($in, CommonTypes::getString(...)));
		ReflectionUtils::setProperty(EducationSettingsPacket::class, $this, "hasQuiz", CommonTypes::getBool($in));
	}

	protected function encodePayload(ByteBufferWriter $out) : void
	{
		CommonTypes::putString($out, $this->getCodeBuilderDefaultUri());
		CommonTypes::putString($out, $this->getCodeBuilderTitle());
		CommonTypes::putBool($out, $this->canResizeCodeBuilder());
		CommonTypes::writeOptional($out, $this->getCodeBuilderOverrideUri(), CommonTypes::putString(...));
		CommonTypes::putBool($out, $this->getHasQuiz());
	}
}
