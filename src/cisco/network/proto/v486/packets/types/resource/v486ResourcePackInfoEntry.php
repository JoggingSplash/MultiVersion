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

namespace cisco\network\proto\v486\packets\types\resource;

use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\LE;
use pocketmine\network\mcpe\protocol\serializer\CommonTypes;
use pocketmine\network\mcpe\protocol\types\resourcepacks\ResourcePackInfoEntry;

final class v486ResourcePackInfoEntry
{

	/** @var string */
	private $packId;
	/** @var string */
	private $version;
	/** @var int */
	private $sizeBytes;
	/** @var string */
	private $encryptionKey;
	/** @var string */
	private $subPackName;
	/** @var string */
	private $contentId;
	/** @var bool */
	private $hasScripts;
	/** @var bool */
	private $rtxCapable;

	public function __construct(string $packId, string $version, int $sizeBytes, string $encryptionKey = "", string $subPackName = "", string $contentId = "", bool $hasScripts = false, bool $rtxCapable = false)
	{
		$this->packId = $packId;
		$this->version = $version;
		$this->sizeBytes = $sizeBytes;
		$this->encryptionKey = $encryptionKey;
		$this->subPackName = $subPackName;
		$this->contentId = $contentId;
		$this->hasScripts = $hasScripts;
		$this->rtxCapable = $rtxCapable;
	}

	public static function fromLatest(ResourcePackInfoEntry $entry) : self
	{
		$uuid = $entry->getPackId()->toString();
		$version = $entry->getVersion();
		$sizeBytes = $entry->getSizeBytes();
		$encryptionKey = $entry->getEncryptionKey();
		$subPackName = $entry->getSubPackName();
		$contentId = $entry->getContentId();
		$hasScripts = $entry->hasScripts();
		$rtxCapable = $entry->isRtxCapable();
		return new self($uuid, $version, $sizeBytes, $encryptionKey, $subPackName, $contentId, $hasScripts, $rtxCapable);
	}

	public function getPackId() : string
	{
		return $this->packId;
	}

	public function getVersion() : string
	{
		return $this->version;
	}

	public function getSizeBytes() : int
	{
		return $this->sizeBytes;
	}

	public function getEncryptionKey() : string
	{
		return $this->encryptionKey;
	}

	public function getSubPackName() : string
	{
		return $this->subPackName;
	}

	public function getContentId() : string
	{
		return $this->contentId;
	}

	public function hasScripts() : bool
	{
		return $this->hasScripts;
	}

	public function isRtxCapable() : bool
	{
		return $this->rtxCapable;
	}

	public static function read(ByteBufferReader $in) : self
	{
		$uuid = CommonTypes::getString($in);
		$version = CommonTypes::getString($in);
		$sizeBytes = LE::readUnsignedLong($in);
		$encryptionKey = CommonTypes::getString($in);
		$subPackName = CommonTypes::getString($in);
		$contentId = CommonTypes::getString($in);
		$hasScripts = CommonTypes::getBool($in);
		$rtxCapable = CommonTypes::getBool($in);
		return new self($uuid, $version, $sizeBytes, $encryptionKey, $subPackName, $contentId, $hasScripts, $rtxCapable);
	}

	public function write(ByteBufferWriter $out) : void
	{
		CommonTypes::putString($out, $this->packId);
		CommonTypes::putString($out, $this->version);
		LE::writeUnsignedLong($out, $this->sizeBytes);
		CommonTypes::putString($out, $this->encryptionKey);
		CommonTypes::putString($out, $this->subPackName);
		CommonTypes::putString($out, $this->contentId);
		CommonTypes::putBool($out, $this->hasScripts);
		CommonTypes::putBool($out, $this->rtxCapable);
	}
}
