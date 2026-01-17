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

namespace cisco\network\global;

use cisco\network\mcpe\MVBlockTranslator;
use pmmp\encoding\ByteBufferWriter;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\SubChunk;

interface MVChunkSerializer
{

	public function getSubChunkCount(Chunk $chunk, int $dimensionId) : int;

	public function serializeFullChunk(Chunk $chunk, int $dimensionId, MVBlockTranslator $blockTranslator, ?string $tiles = null) : string;

	public function serializeSubChunk(SubChunk $subChunk, MVBlockTranslator $blockTranslator, ByteBufferWriter $writer, bool $persistentBlockStates) : void;

	public function serializeTiles(Chunk $chunk) : string;
}
