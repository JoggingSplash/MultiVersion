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

namespace cisco\network\proto\latest;

use cisco\Loader;
use cisco\network\global\MVSkinHelper;
use pocketmine\network\mcpe\protocol\types\skin\SkinData;
use function str_contains;

class LatestSkinHelper implements MVSkinHelper
{

	public function adaptSkinData(SkinData &$skin) : void
	{
		$skin = new SkinData(
			$skin->getSkinId(),
			"",
			$skin->getResourcePatch(),
			$skin->getSkinImage(),
			$skin->getAnimations(),
			$skin->getCapeImage(),
			$skin->getGeometryData() !== "" && str_contains($skin->getGeometryData(), "format_version") ? $skin->getGeometryData() : Loader::getDefaultSkinFormat(),
			$skin->getGeometryDataEngineVersion(),
			$skin->getAnimationData(),
			$skin->getCapeId(),
			$skin->getFullSkinId(),
			$skin->getArmSize(),
			$skin->getSkinColor(),
			$skin->getPersonaPieces(),
			$skin->getPieceTintColors(),
			$skin->isVerified(),
			$skin->isPremium(),
			$skin->isPersona(),
			$skin->isPersonaCapeOnClassic(),
			$skin->isPrimaryUser(),
			$skin->isOverride(),
		);
	}
}
