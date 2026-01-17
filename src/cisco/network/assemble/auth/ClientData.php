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

namespace cisco\network\assemble\auth;

use pocketmine\network\mcpe\protocol\types\login\clientdata\ClientDataAnimationFrame;

/**
 * @deprecated
 */
final class ClientData
{
	/**
	 * @var ClientDataAnimationFrame[]
	 * @required
	 */
	public array $AnimatedImageData;

	/** @required */
	public string $ArmSize;

	/** @required */
	public string $CapeData;

	/** @required */
	public string $CapeId;

	/** @required */
	public int $CapeImageHeight;

	/** @required */
	public int $CapeImageWidth;

	/** @required */
	public bool $CapeOnClassicSkin;

	/** @required */
	public int $ClientRandomId;

	/** @required */
	public bool $CompatibleWithClientSideChunkGen;

	/** @required */
	public int $CurrentInputMode;

	/** @required */
	public int $DefaultInputMode;

	/** @required */
	public string $DeviceId;

	/** @required */
	public string $DeviceModel;

	/** @required */
	public int $DeviceOS;

	/** @required */
	public string $GameVersion;

	/** @required */
	public int $GraphicsMode;

	/** @required */
	public int $GuiScale;

	/** @required */
	public bool $IsEditorMode;

	/** @required */
	public string $LanguageCode;

	/** @required */
	public int $MaxViewDistance;

	/** @required */
	public int $MemoryTier;

	public bool $OverrideSkin;

	/**
	 * @var ClientDataPersonaSkinPiece[]
	 * @required
	 */
	public array $PersonaPieces;

	/** @required */
	public bool $PersonaSkin;

	/**
	 * @var ClientDataPersonaPieceTintColor[]
	 * @required
	 */
	public array $PieceTintColors;

	/** @required */
	public string $PlatformOfflineId;

	/** @required */
	public string $PlatformOnlineId;

	/** @required */
	public int $PlatformType;

	public string $PlatformUserId = ""; //xbox-only, apparently

	/** @required */
	public string $PlayFabId;

	/** @required */
	public bool $PremiumSkin = false;

	/** @required */
	public string $SelfSignedId;

	/** @required */
	public string $ServerAddress;

	/** @required */
	public string $SkinAnimationData;

	/** @required */
	public string $SkinColor;

	/** @required */
	public string $SkinData;

	/** @required */
	public string $SkinGeometryData;

	/** @required */
	public string $SkinGeometryDataEngineVersion;

	/** @required */
	public string $SkinId;

	/** @required */
	public int $SkinImageHeight;

	/** @required */
	public int $SkinImageWidth;

	/** @required */
	public string $SkinResourcePatch;

	/** @required */
	public string $ThirdPartyName;

	/** @required */
	public bool $TrustedSkin;

	/** @required */
	public int $UIProfile;
}
