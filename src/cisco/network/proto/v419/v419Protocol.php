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

namespace cisco\network\proto\v419;

use cisco\network\assemble\auth\JwtChain;
use cisco\network\assemble\command\CommandData;
use cisco\network\assemble\command\CommandEnum;
use cisco\network\assemble\command\CommandOverload;
use cisco\network\assemble\command\CommandParameter;
use cisco\network\etc\GlobalLoginPacket;
use cisco\network\etc\PreFormattedChunkSerializer;
use cisco\network\mcpe\MVRuntimeIdToStateId;
use cisco\network\NetworkSession;
use cisco\network\proto\TProtocol;
use cisco\network\proto\v419\craft\v419CraftingManagerFromDataHelper;
use cisco\network\proto\v419\packets\v419AddActorPacket;
use cisco\network\proto\v419\packets\v419AddItemActorPacket;
use cisco\network\proto\v419\packets\v419AddPlayerPacket;
use cisco\network\proto\v419\packets\v419AddVolumeEntityPacket;
use cisco\network\proto\v419\packets\v419AdventureSettingsPacket;
use cisco\network\proto\v419\packets\v419AnimateEntityPacket;
use cisco\network\proto\v419\packets\v419AnimatePacket;
use cisco\network\proto\v419\packets\v419AvailableCommandsPacket;
use cisco\network\proto\v419\packets\v419BossEventPacket;
use cisco\network\proto\v419\packets\v419CameraShakePacket;
use cisco\network\proto\v419\packets\v419ClientboundMapItemDataPacket;
use cisco\network\proto\v419\packets\v419ContainerClosePacket;
use cisco\network\proto\v419\packets\v419CorrectPlayerMovePredictionPacket;
use cisco\network\proto\v419\packets\v419DisconnectPacket;
use cisco\network\proto\v419\packets\v419EducationSettingsPacket;
use cisco\network\proto\v419\packets\v419EmotePacket;
use cisco\network\proto\v419\packets\v419HurtArmorPacket;
use cisco\network\proto\v419\packets\v419InteractPacket;
use cisco\network\proto\v419\packets\v419InventoryContentPacket;
use cisco\network\proto\v419\packets\v419InventorySlotPacket;
use cisco\network\proto\v419\packets\v419InventoryTransactionPacket;
use cisco\network\proto\v419\packets\v419ItemStackResponsePacket;
use cisco\network\proto\v419\packets\v419LevelChunkPacket;
use cisco\network\proto\v419\packets\v419LevelSoundEventPacket;
use cisco\network\proto\v419\packets\v419MobArmorEquipmentPacket;
use cisco\network\proto\v419\packets\v419MobEffectPacket;
use cisco\network\proto\v419\packets\v419MobEquipmentPacket;
use cisco\network\proto\v419\packets\v419ModalFormResponsePacket;
use cisco\network\proto\v419\packets\v419NetworkChunkPublisherUpdatePacket;
use cisco\network\proto\v419\packets\v419NetworkSettingsPacket;
use cisco\network\proto\v419\packets\v419PhotoTransferPacket;
use cisco\network\proto\v419\packets\v419PlayerActionPacket;
use cisco\network\proto\v419\packets\v419PlayerArmorDamagePacket;
use cisco\network\proto\v419\packets\v419PlayerListPacket;
use cisco\network\proto\v419\packets\v419RemoveVolumeEntityPacket;
use cisco\network\proto\v419\packets\v419RequestChunkRadiusPacket;
use cisco\network\proto\v419\packets\v419ResourcePacksInfoPacket;
use cisco\network\proto\v419\packets\v419ResourcePackStackPacket;
use cisco\network\proto\v419\packets\v419SetActorDataPacket;
use cisco\network\proto\v419\packets\v419SetActorMotionPacket;
use cisco\network\proto\v419\packets\v419SetTitlePacket;
use cisco\network\proto\v419\packets\v419SpawnParticleEffectPacket;
use cisco\network\proto\v419\packets\v419StartGamePacket;
use cisco\network\proto\v419\packets\v419TextPacket;
use cisco\network\proto\v419\packets\v419TransferPacket;
use cisco\network\proto\v419\packets\v419UpdateAttributesPacket;
use cisco\network\proto\v419\packets\v419UpdateBlockPacket;
use cisco\network\proto\v419\structure\v419InGamePacketHandler;
use cisco\network\proto\v419\structure\v419PacketPool;
use cisco\network\proto\v419\structure\v419ProtocolInfo;
use cisco\network\proto\v419\structure\v419StaticPacketCache;
use cisco\network\utils\RawPacketHelper;
use JsonException;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\LE;
use pocketmine\crafting\CraftingManager;
use pocketmine\lang\Language;
use pocketmine\lang\Translatable;
use pocketmine\network\mcpe\convert\ItemTranslator;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\handler\InGamePacketHandler;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\AddVolumeEntityPacket;
use pocketmine\network\mcpe\protocol\AnimateEntityPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\CameraShakePacket;
use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\CorrectPlayerMovePredictionPacket;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\EducationSettingsPacket;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\HurtArmorPacket;
use pocketmine\network\mcpe\protocol\InventoryContentPacket;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\ItemRegistryPacket;
use pocketmine\network\mcpe\protocol\ItemStackResponsePacket;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\NetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PhotoTransferPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\PlayerArmorDamagePacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\PlayerSkinPacket;
use pocketmine\network\mcpe\protocol\RemoveVolumeEntityPacket;
use pocketmine\network\mcpe\protocol\RequestChunkRadiusPacket;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\network\mcpe\protocol\SpawnParticleEffectPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\TransferPacket;
use pocketmine\network\mcpe\protocol\types\AbilitiesLayer;
use pocketmine\network\mcpe\protocol\types\ActorEvent;
use pocketmine\network\mcpe\protocol\types\LevelEvent;
use pocketmine\network\mcpe\protocol\types\LevelSoundEvent;
use pocketmine\network\mcpe\protocol\types\ParticleIds;
use pocketmine\network\mcpe\protocol\UpdateAbilitiesPacket;
use pocketmine\network\mcpe\protocol\UpdateAdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\AssumptionFailedError;
use pocketmine\utils\Utils;
use function count;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function strtolower;
use function ucfirst;
use const JSON_THROW_ON_ERROR;

final class v419Protocol extends TProtocol
{

	protected CraftingManager $craftingManager;
	protected v419StaticPacketCache $staticPacketCache;

	public function __construct()
	{
		parent::__construct(v419TypeConverter::getInstance()->getConverter(), new v419PacketPool(), new PreFormattedChunkSerializer(), new v419SkinHelper());
		$this->craftingManager = v419CraftingManagerFromDataHelper::make();
		$this->staticPacketCache = new v419StaticPacketCache($this);
	}

	public function getRaknetVersion() : int
	{
		return 10;
	}

	public function getProtocolId() : int
	{
		return v419ProtocolInfo::CURRENT_PROTOCOL;
	}

	public function injectExtraData(array &$extraData) : void
	{
		$extraData["IsEditorMode"] = false;
		$extraData["PlayFabId"] = ""; // ???
		$extraData["SkinGeometryDataEngineVersion"] = "";
		$extraData["TrustedSkin"] = true;
		$extraData["CompatibleWithClientSideChunkGen"] = false;
	}

	public function hasOldCompressionMethod() : bool
	{
		return true;
	}

	public function hasEncryption() : bool
	{
		return false;
	}

	public function incoming(ServerboundPacket $packet) : ?ServerboundPacket
	{
		return match (true) {
			$packet instanceof v419PlayerActionPacket => PlayerActionPacket::create($packet->actorRuntimeId, $packet->action, $packet->blockPosition, $packet->blockPosition, $packet->face),
			$packet instanceof v419DisconnectPacket => DisconnectPacket::create(0, $packet->message, null),
			$packet instanceof v419SetActorMotionPacket => SetActorMotionPacket::create($packet->actorRuntimeId, $packet->motion, 0),
			$packet instanceof v419RequestChunkRadiusPacket => RequestChunkRadiusPacket::create($packet->radius, $packet->radius),
			$packet instanceof v419ModalFormResponsePacket => $packet->formData === "null\n" ? ModalFormResponsePacket::cancel($packet->formId, ModalFormResponsePacket::CANCEL_REASON_CLOSED) : ModalFormResponsePacket::response($packet->formId, $packet->formData),
			$packet instanceof v419BossEventPacket => self::translate419BossEventPacketToLatest($packet),
			$packet instanceof v419InventoryTransactionPacket => InventoryTransactionPacket::create($packet->requestId, $packet->requestChangedSlots, $packet->trData),
			$packet instanceof v419AnimatePacket => AnimatePacket::create($packet->actorRuntimeId, $packet->action, $packet->data, null), // TODO: fill swingSource
			$packet instanceof v419InteractPacket => RawPacketHelper::translateInteractPacketToLatest($packet),
			default => $packet
		};
	}

	public function outcoming(ClientboundPacket $packet) : ?ClientboundPacket
	{
		if ($packet instanceof PlayerSkinPacket) {
			$this->adaptSkinData($packet->skin);
			return $packet;
		} elseif ($packet instanceof UpdateAbilitiesPacket) {
			foreach (Server::getInstance()->getWorldManager()->getWorlds() as $world) {
				$player = $world->getPlayers()[$packet->getData()->getTargetActorUniqueId()] ?? null;
				if ($player === null) {
					continue;
				}
				$npk = v419AdventureSettingsPacket::create(0, $packet->getData()->getCommandPermission(), -1, $packet->getData()->getPlayerPermission(), 0, $packet->getData()->getTargetActorUniqueId());
				if (isset($packet->getData()->getAbilityLayers()[0])) {
					$abilities = $packet->getData()->getAbilityLayers()[0]->getBoolAbilities();
					$npk->setFlag(v419AdventureSettingsPacket::WORLD_IMMUTABLE, $player->isSpectator());
					$npk->setFlag(v419AdventureSettingsPacket::NO_PVP, $player->isSpectator());
					$npk->setFlag(v419AdventureSettingsPacket::AUTO_JUMP, $player->hasAutoJump());
					$npk->setFlag(v419AdventureSettingsPacket::ALLOW_FLIGHT, $abilities[AbilitiesLayer::ABILITY_ALLOW_FLIGHT] ?? false);
					$npk->setFlag(v419AdventureSettingsPacket::NO_CLIP, $abilities[AbilitiesLayer::ABILITY_NO_CLIP] ?? false);
					$npk->setFlag(v419AdventureSettingsPacket::FLYING, $abilities[AbilitiesLayer::ABILITY_FLYING] ?? false);
				}
				return $npk;
			}
			throw new AssumptionFailedError("This line should not be reachable");
		} elseif ($packet instanceof LevelEventPacket) {
			if ($packet->eventId === LevelEvent::PARTICLE_DESTROY || $packet->eventId === (LevelEvent::ADD_PARTICLE_MASK | ParticleIds::TERRAIN)) {
				$packet->eventData = $this->getTypeConverter()->getMVBlockTranslator()->internalIdToNetworkId(MVRuntimeIdToStateId::getInstance()->getStateIdFromRuntimeId($packet->eventData));

			} elseif ($packet->eventId === LevelEvent::PARTICLE_PUNCH_BLOCK) {
				$packet->eventData = $this->getTypeConverter()->getMVBlockTranslator()->internalIdToNetworkId(MVRuntimeIdToStateId::getInstance()->getStateIdFromRuntimeId($packet->eventData & 0xFFFFFF));
			}
			return $packet;
		} elseif ($packet instanceof ActorEventPacket) {
			if ($packet->eventId === ActorEvent::EATING_ITEM) {
				$value = $packet->eventData;
				$netId = $value >> 16;
				$netData = $value & 0xffff;
				[$netId, $netData] = $this->getTypeConverter()->getMVItemTranslator()->toNetworkId(TypeConverter::getInstance()->getItemTranslator()->fromNetworkId($netId, $netData, ItemTranslator::NO_BLOCK_RUNTIME_ID));
				$packet->eventData = ($netId << 16) | $netData;
				return $packet;
			}
		} elseif ($packet instanceof PlayerListPacket) {
			foreach ($packet->entries as $entry) {
				if (!isset($entry->skinData)) {
					continue;
				}

				$this->adaptSkinData($entry->skinData);
			}

			return v419PlayerListPacket::fromLatest($packet);
		} elseif ($packet instanceof LevelSoundEventPacket) {
			if (($packet->sound === LevelSoundEvent::BREAK && $packet->extraData !== -1) || $packet->sound === LevelSoundEvent::PLACE || $packet->sound === LevelSoundEvent::HIT || $packet->sound === LevelSoundEvent::LAND || $packet->sound === LevelSoundEvent::ITEM_USE_ON) {
				$packet->extraData = $this->getTypeConverter()->getMVBlockTranslator()->internalIdToNetworkId(MVRuntimeIdToStateId::getInstance()->getStateIdFromRuntimeId($packet->extraData));
			}
			return v419LevelSoundEventPacket::fromLatest($packet);
		}

		return match (true) {
			$packet instanceof CraftingDataPacket => $this->staticPacketCache->getCraftingDataPacket(),
			$packet instanceof UpdateBlockPacket => v419UpdateBlockPacket::fromLatest($packet),
			$packet instanceof AddActorPacket => v419AddActorPacket::fromLatest($packet),
			$packet instanceof ResourcePacksInfoPacket => v419ResourcePacksInfoPacket::fromLatest($packet),
			$packet instanceof AddPlayerPacket => v419AddPlayerPacket::fromLatest($packet),
			$packet instanceof AddVolumeEntityPacket => v419AddVolumeEntityPacket::fromLatest($packet),
			$packet instanceof AnimateEntityPacket => v419AnimateEntityPacket::fromLatest($packet),
			$packet instanceof AvailableActorIdentifiersPacket => $this->staticPacketCache->getActorIdentifiersPacket(),
			$packet instanceof BiomeDefinitionListPacket => $this->staticPacketCache->getBiomeDefinitions(),
			$packet instanceof ContainerClosePacket => v419ContainerClosePacket::fromLatest($packet),
			$packet instanceof TextPacket => v419TextPacket::fromLatest($packet),
			$packet instanceof CameraShakePacket => v419CameraShakePacket::fromLatest($packet),
			$packet instanceof ClientboundMapItemDataPacket => v419ClientboundMapItemDataPacket::fromLatest($packet),
			$packet instanceof CreativeContentPacket => $this->staticPacketCache->getCreativeContent(),
			$packet instanceof DisconnectPacket => v419DisconnectPacket::fromLatest($packet),
			$packet instanceof EducationSettingsPacket => v419EducationSettingsPacket::fromLatest($packet),
			$packet instanceof EmotePacket => v419EmotePacket::fromLatest($packet),
			$packet instanceof HurtArmorPacket => v419HurtArmorPacket::fromLatest($packet),
			$packet instanceof UpdateAdventureSettingsPacket, $packet instanceof ItemRegistryPacket => null, // NOOP
			$packet instanceof InventoryContentPacket => v419InventoryContentPacket::fromLatest($packet),
			$packet instanceof InventorySlotPacket => v419InventorySlotPacket::fromLatest($packet),
			$packet instanceof InventoryTransactionPacket => v419InventoryTransactionPacket::fromLatest($packet),
			$packet instanceof ItemStackResponsePacket => v419ItemStackResponsePacket::fromLatest($packet),
			$packet instanceof LevelChunkPacket => v419LevelChunkPacket::fromLatest($packet),
			$packet instanceof MobArmorEquipmentPacket => v419MobArmorEquipmentPacket::fromLatest($packet),
			$packet instanceof MobEffectPacket => v419MobEffectPacket::fromLatest($packet),
			$packet instanceof NetworkChunkPublisherUpdatePacket => v419NetworkChunkPublisherUpdatePacket::fromLatest($packet),
			$packet instanceof NetworkSettingsPacket => v419NetworkSettingsPacket::fromLatest($packet),
			$packet instanceof PhotoTransferPacket => v419PhotoTransferPacket::fromLatest($packet),
			$packet instanceof TransferPacket => v419TransferPacket::fromLatest($packet),
			$packet instanceof RemoveVolumeEntityPacket => v419RemoveVolumeEntityPacket::fromLatest($packet),
			$packet instanceof ResourcePackStackPacket => v419ResourcePackStackPacket::fromLatest($packet),
			$packet instanceof SetActorDataPacket => v419SetActorDataPacket::fromLatest($packet),
			$packet instanceof SetActorMotionPacket => v419SetActorMotionPacket::fromLatest($packet),
			$packet instanceof SetTitlePacket => v419SetTitlePacket::fromLatest($packet),
			$packet instanceof SpawnParticleEffectPacket => v419SpawnParticleEffectPacket::fromLatest($packet),
			$packet instanceof StartGamePacket => v419StartGamePacket::fromLatest($packet),
			$packet instanceof UpdateAttributesPacket => v419UpdateAttributesPacket::fromLatest($packet),
			$packet instanceof PlayerArmorDamagePacket => v419PlayerArmorDamagePacket::fromLatest($packet),
			$packet instanceof CorrectPlayerMovePredictionPacket => v419CorrectPlayerMovePredictionPacket::fromLatest($packet),
			$packet instanceof BossEventPacket => v419BossEventPacket::fromLatest($packet),
			$packet instanceof AnimatePacket => v419AnimatePacket::fromLatest($packet),
			$packet instanceof MobEquipmentPacket => v419MobEquipmentPacket::fromLatest($packet),
			$packet instanceof AddItemActorPacket => v419AddItemActorPacket::fromLatest($packet),
			default => $packet // Unhandled between versions either it has no changes
		};
	}

	public function fetchPacketHandler(?PacketHandler $handler, NetworkSession $session) : ?PacketHandler
	{
		if (!$handler instanceof InGamePacketHandler) {
			return null;
		}

		return new v419InGamePacketHandler(
			Utils::assumeNotFalse($session->getPlayer() ?: false),
			$session,
			$session->getInvManager()
		);
	}

	public function getCraftingManager() : CraftingManager
	{
		return $this->craftingManager;
	}

	public function getStaticPacketCache() : v419StaticPacketCache
	{
		return $this->staticPacketCache;
	}

	public function decodeConnection(string $buffer, GlobalLoginPacket $loginPacket) : void
	{
		$reader = new ByteBufferReader($buffer);
		$chainDataJsonLen = LE::readUnsignedInt($reader);

		try {
			$chainDataJson = json_decode($reader->readByteArray($chainDataJsonLen), true, JSON_THROW_ON_ERROR);
		} catch (JsonException $exception) {
			throw PacketDecodeException::wrap($exception);
		}

		if (!is_array($chainDataJson) || count($chainDataJson) !== 1 || !isset($chainDataJson["chain"])) {
			throw new PacketDecodeException("Chain data must be a JSON object containing only the 'chain' element");
		}

		if (!is_array($chainDataJson["chain"])) {
			throw new PacketDecodeException("Chain data 'chain' element must be a list of strings");
		}

		$jwts = [];
		foreach ($chainDataJson["chain"] as $jwt) {
			if (!is_string($jwt)) {
				throw new PacketDecodeException("Chain data 'chain' must contain only strings");
			}
			$jwts[] = $jwt;
		}

		$wrapper = new JwtChain();
		$wrapper->chain = $jwts;
		$loginPacket->setChainDataJwt($wrapper);
		$clientDataJwtLength = LE::readUnsignedInt($reader);
		$loginPacket->clientDataJwt = $reader->readByteArray($clientDataJwtLength);
	}

	public function assembleCommands(Server $server, Player $player, Language $lang) : ?ClientboundPacket
	{
		$commandData = [];

		foreach ($server->getCommandMap()->getCommands() as $command) {
			$label = $command->getLabel();
			if (isset($commandData[$label]) || $label === "help" || !$command->testPermissionSilent($player)) {
				continue;
			}

			$lname = strtolower($label);
			$aliases = $command->getAliases();
			$aliasObj = null;
			if (count($aliases) > 0) {
				if (!in_array($lname, $aliases, true)) {
					$aliases[] = $lname;
				}
				$aliasObj = new CommandEnum(ucfirst($label) . "Aliases", $aliases);
			}

			$description = $command->getDescription();
			$commandData[$label] = new CommandData(
				$lname,
				$description instanceof Translatable ? $lang->translate($description) : $description,
				0,
				0,
				$aliasObj,
				[
					new CommandOverload(chaining: false, parameters: [CommandParameter::standard("args",  v419AvailableCommandsPacket::ARG_FLAG_VALID,v419AvailableCommandsPacket::ARG_TYPE_RAWTEXT, 0, true)])
				],
				chainedSubCommandData: []
			);
		}

		return v419AvailableCommandsPacket::create($commandData, [], [], []);
	}

	public function __toString() : string
	{
		return v419ProtocolInfo::MINECRAFT_VERSION;
	}

	/**
	 * Translates the boss event packet to latest
	 */
	private static function translate419BossEventPacketToLatest(v419BossEventPacket $packet) : BossEventPacket{
		$npk = new BossEventPacket();
		$npk->bossActorUniqueId = $packet->bossActorUniqueId;
		$npk->eventType = $packet->eventType;

		switch ($npk->eventType) {
			case BossEventPacket::TYPE_REGISTER_PLAYER:
			case BossEventPacket::TYPE_UNREGISTER_PLAYER:
			case BossEventPacket::TYPE_QUERY:
				$npk->playerActorUniqueId = $packet->playerActorUniqueId;
				break;
			/** @noinspection PhpMissingBreakStatementInspection */
			case BossEventPacket::TYPE_SHOW:
				$npk->title = $packet->title;
				$npk->filteredTitle = $packet->title;
				$npk->healthPercent = $packet->healthPercent;
			/** @noinspection PhpMissingBreakStatementInspection */
			case BossEventPacket::TYPE_PROPERTIES:
				$npk->darkenScreen = $packet->unknownShort === 1;
			case BossEventPacket::TYPE_TEXTURE:
				$npk->color = $packet->color;
				$npk->overlay = $packet->color;
				break;
			case BossEventPacket::TYPE_HEALTH_PERCENT:
				$npk->healthPercent = $packet->healthPercent;
				break;
			case BossEventPacket::TYPE_TITLE:
				$npk->title = $packet->title;
				$npk->filteredTitle = $packet->title;
				break;
			default:
				break;
		}

		return $npk;
	}
}
