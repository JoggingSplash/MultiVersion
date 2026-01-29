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

namespace cisco\network\proto\v486;

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
use cisco\network\proto\v486\packets\v486AddActorPacket;
use cisco\network\proto\v486\packets\v486AddPlayerPacket;
use cisco\network\proto\v486\packets\v486AddVolumeEntityPacket;
use cisco\network\proto\v486\packets\v486AdventureSettingsPacket;
use cisco\network\proto\v486\packets\v486AnimatePacket;
use cisco\network\proto\v486\packets\v486AvailableCommandsPacket;
use cisco\network\proto\v486\packets\v486ClientboundMapItemDataPacket;
use cisco\network\proto\v486\packets\v486ContainerClosePacket;
use cisco\network\proto\v486\packets\v486DisconnectPacket;
use cisco\network\proto\v486\packets\v486EmotePacket;
use cisco\network\proto\v486\packets\v486GameRulesChangedPacket;
use cisco\network\proto\v486\packets\v486InteractPacket;
use cisco\network\proto\v486\packets\v486InventoryContentPacket;
use cisco\network\proto\v486\packets\v486InventorySlotPacket;
use cisco\network\proto\v486\packets\v486InventoryTransactionPacket;
use cisco\network\proto\v486\packets\v486ItemStackResponsePacket;
use cisco\network\proto\v486\packets\v486LevelChunkPacket;
use cisco\network\proto\v486\packets\v486LevelSoundEventPacket;
use cisco\network\proto\v486\packets\v486MobArmorEquipmentPacket;
use cisco\network\proto\v486\packets\v486MobEffectPacket;
use cisco\network\proto\v486\packets\v486ModalFormResponsePacket;
use cisco\network\proto\v486\packets\v486NetworkChunkPublisherUpdatePacket;
use cisco\network\proto\v486\packets\v486NetworkSettingsPacket;
use cisco\network\proto\v486\packets\v486PlayerActionPacket;
use cisco\network\proto\v486\packets\v486PlayerListPacket;
use cisco\network\proto\v486\packets\v486RemoveVolumeEntityPacket;
use cisco\network\proto\v486\packets\v486RequestChunkRadiusPacket;
use cisco\network\proto\v486\packets\v486ResourcePacksInfoPacket;
use cisco\network\proto\v486\packets\v486ResourcePackStackPacket;
use cisco\network\proto\v486\packets\v486SetActorDataPacket;
use cisco\network\proto\v486\packets\v486SetActorMotionPacket;
use cisco\network\proto\v486\packets\v486SetTitlePacket;
use cisco\network\proto\v486\packets\v486SpawnParticleEffectPacket;
use cisco\network\proto\v486\packets\v486StartGamePacket;
use cisco\network\proto\v486\packets\v486TextPacket;
use cisco\network\proto\v486\packets\v486TransferPacket;
use cisco\network\proto\v486\packets\v486UpdateAttributesPacket;
use cisco\network\proto\v486\structure\v486InGamePacketHandler;
use cisco\network\proto\v486\structure\v486PacketPool;
use cisco\network\proto\v486\structure\v486ProtocolInfo;
use cisco\network\proto\v486\structure\v486StaticPacketCache;
use cisco\network\utils\RawPacketHelper;
use JsonMapper;
use JsonMapper_Exception;
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
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\AddVolumeEntityPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\AvailableActorIdentifiersPacket;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\BiomeDefinitionListPacket;
use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\CodeBuilderPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\network\mcpe\protocol\CreativeContentPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
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
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\NetworkChunkPublisherUpdatePacket;
use pocketmine\network\mcpe\protocol\NetworkSettingsPacket;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
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
use function is_object;
use function json_decode;
use function json_last_error_msg;
use function strtolower;
use function ucfirst;

class v486Protocol extends TProtocol
{

	private v486StaticPacketCache $staticPacketCache;

	public function __construct()
	{
		parent::__construct(v486TypeConverter::getInstance()->getConverter(), new v486PacketPool(), new PreFormattedChunkSerializer(), new v486SkinHelper());
		$this->staticPacketCache = new v486StaticPacketCache($this);
	}

	public function getRaknetVersion() : int
	{
		return 10;
	}

	public function hasEncryption() : bool
	{
		return true;
	}

	public function getProtocolId() : int
	{
		return v486ProtocolInfo::CURRENT_PROTOCOL;
	}

	public function hasOldCompressionMethod() : bool
	{
		return true;
	}

	public function hasDebug() : bool {
		return true;
	}

	public function fetchPacketHandler(?PacketHandler $handler, NetworkSession $session) : ?PacketHandler
	{
		if (!$handler instanceof InGamePacketHandler) {
			return null;
		}
		return new v486InGamePacketHandler(
			Utils::assumeNotFalse($session->getPlayer() ?: false),
			$session,
			$session->getInvManager()
		);
	}

	public function incoming(ServerboundPacket $packet) : ?ServerboundPacket
	{
		return match (true) {
			$packet instanceof v486DisconnectPacket => DisconnectPacket::create(0, $packet->message, null),
			$packet instanceof v486ModalFormResponsePacket => $packet->formData !== "null\n" ? ModalFormResponsePacket::response($packet->formId, $packet->formData) : ModalFormResponsePacket::cancel($packet->formId, ModalFormResponsePacket::CANCEL_REASON_CLOSED),
			$packet instanceof v486PlayerActionPacket => PlayerActionPacket::create($packet->actorRuntimeId, $packet->action, $packet->blockPosition, $packet->blockPosition, $packet->face),
			$packet instanceof v486RequestChunkRadiusPacket => RequestChunkRadiusPacket::create($packet->radius, $packet->radius),
			$packet instanceof v486SetActorMotionPacket => SetActorMotionPacket::create($packet->actorRuntimeId, $packet->motion, 0),
			$packet instanceof v486ContainerClosePacket => ContainerClosePacket::create($packet->windowId, 0, $packet->server),
			$packet instanceof v486AnimatePacket => AnimatePacket::create($packet->action, $packet->action, 0.0, null),
			$packet instanceof v486InteractPacket => RawPacketHelper::translateInteractPacketToLatest($packet),
			$packet instanceof v486LevelSoundEventPacket => LevelSoundEventPacket::create($packet->sound, $packet->position, $packet->extraData, $packet->entityType, $packet->isBabyMob, $packet->disableRelativeVolume, -1),
			$packet instanceof v486InventoryTransactionPacket => InventoryTransactionPacket::create($packet->requestId, $packet->requestChangedSlots, $packet->trData),
			default => $packet
		};
	}

	public function outcoming(ClientboundPacket $packet) : ?ClientboundPacket
	{
		if ($packet instanceof ActorEventPacket) {
			if ($packet->eventId === ActorEvent::EATING_ITEM) {
				$value = $packet->eventData;
				$netId = $value >> 16;
				$netData = $value & 0xffff;
				[$netId, $netData] = $this->getTypeConverter()->getMVItemTranslator()->toNetworkId(TypeConverter::getInstance()->getItemTranslator()->fromNetworkId($netId, $netData, ItemTranslator::NO_BLOCK_RUNTIME_ID));
				$packet->eventData = ($netId << 16) | $netData;
				return $packet;
			}
		} elseif ($packet instanceof LevelEventPacket) {
			if ($packet->eventId === LevelEvent::PARTICLE_DESTROY || $packet->eventId === (LevelEvent::ADD_PARTICLE_MASK | ParticleIds::TERRAIN)) {
				$packet->eventData = $this->getTypeConverter()->getMVBlockTranslator()->internalIdToNetworkId(MVRuntimeIdToStateId::getInstance()->getStateIdFromRuntimeId($packet->eventData));

			} elseif ($packet->eventId === LevelEvent::PARTICLE_PUNCH_BLOCK) {
				$packet->eventData = $this->getTypeConverter()->getMVBlockTranslator()->internalIdToNetworkId(MVRuntimeIdToStateId::getInstance()->getStateIdFromRuntimeId($packet->eventData & 0xFFFFFF));
			}
			return $packet;
		} elseif ($packet instanceof LevelSoundEventPacket) {
			if (($packet->sound === LevelSoundEvent::BREAK && $packet->extraData !== -1) || $packet->sound === LevelSoundEvent::PLACE || $packet->sound === LevelSoundEvent::HIT || $packet->sound === LevelSoundEvent::LAND || $packet->sound === LevelSoundEvent::ITEM_USE_ON) {
				$packet->extraData = $this->getTypeConverter()->getMVBlockTranslator()->internalIdToNetworkId(MVRuntimeIdToStateId::getInstance()->getStateIdFromRuntimeId($packet->extraData));
			}
			return $packet;
		} elseif ($packet instanceof UpdateAbilitiesPacket) {
			foreach (Server::getInstance()->getWorldManager()->getWorlds() as $world) {
				$player = $world->getPlayers()[$packet->getData()->getTargetActorUniqueId()] ?? null;
				if ($player === null) continue;
				$npk = v486AdventureSettingsPacket::create(0, $packet->getData()->getCommandPermission()->value, -1, $packet->getData()->getPlayerPermission(), 0, $packet->getData()->getTargetActorUniqueId());
				if (isset($packet->getData()->getAbilityLayers()[0])) {
					$abilities = $packet->getData()->getAbilityLayers()[0]->getBoolAbilities();
					$npk->setFlag(v486AdventureSettingsPacket::WORLD_IMMUTABLE, $player->isSpectator());
					$npk->setFlag(v486AdventureSettingsPacket::NO_PVP, $player->isSpectator());
					$npk->setFlag(v486AdventureSettingsPacket::AUTO_JUMP, $player->hasAutoJump());
					$npk->setFlag(v486AdventureSettingsPacket::ALLOW_FLIGHT, $abilities[AbilitiesLayer::ABILITY_ALLOW_FLIGHT] ?? false);
					$npk->setFlag(v486AdventureSettingsPacket::NO_CLIP, $abilities[AbilitiesLayer::ABILITY_NO_CLIP] ?? false);
					$npk->setFlag(v486AdventureSettingsPacket::FLYING, $abilities[AbilitiesLayer::ABILITY_FLYING] ?? false);
				}
				return $npk;
			}
			throw new AssumptionFailedError("This line should be unreachable");
		} elseif ($packet instanceof UpdateBlockPacket) {
			$packet->blockRuntimeId = $this->getTypeConverter()->getMVBlockTranslator()->internalIdToNetworkId(MVRuntimeIdToStateId::getInstance()->getStateIdFromRuntimeId($packet->blockRuntimeId));
			return $packet;
		} elseif ($packet instanceof PlayerListPacket) {
			foreach ($packet->entries as $entry) {
				if (!isset($entry->skinData)) {
					continue;
				}
				$this->adaptSkinData($entry->skinData);
			}
			return v486PlayerListPacket::fromLatest($packet);
		} elseif ($packet instanceof PlayerSkinPacket) {
			$this->adaptSkinData($packet->skin);
			return $packet;
		}

		return match (true) {
			$packet instanceof SetTitlePacket => v486SetTitlePacket::fromLatest($packet),
			$packet instanceof MobArmorEquipmentPacket => v486MobArmorEquipmentPacket::fromLatest($packet),
			$packet instanceof AddActorPacket => v486AddActorPacket::fromLatest($packet),
			$packet instanceof AddPlayerPacket => v486AddPlayerPacket::fromLatest($packet),
			$packet instanceof AddVolumeEntityPacket => v486AddVolumeEntityPacket::fromLatest($packet),
			$packet instanceof AvailableActorIdentifiersPacket => $this->staticPacketCache->getActorIdentifiersPacket(),
			$packet instanceof AvailableCommandsPacket, $packet instanceof CraftingDataPacket, $packet instanceof CodeBuilderPacket, $packet instanceof UpdateAdventureSettingsPacket, $packet instanceof ItemRegistryPacket => null,
			$packet instanceof BiomeDefinitionListPacket => $this->staticPacketCache->getBiomeDefinitionList(),
			$packet instanceof ClientboundMapItemDataPacket => v486ClientboundMapItemDataPacket::fromLatest($packet),
			$packet instanceof CreativeContentPacket => $this->staticPacketCache->getCreativeContentPacket(),
			$packet instanceof DisconnectPacket => v486DisconnectPacket::fromLatest($packet),
			$packet instanceof EmotePacket => v486EmotePacket::fromLatest($packet),
			$packet instanceof InventoryContentPacket => v486InventoryContentPacket::fromLatest($packet),
			$packet instanceof InventorySlotPacket => v486InventorySlotPacket::fromLatest($packet),
			$packet instanceof ItemStackResponsePacket => v486ItemStackResponsePacket::fromLatest($packet),
			$packet instanceof LevelChunkPacket => v486LevelChunkPacket::fromLatest($packet),
			$packet instanceof ContainerClosePacket => v486ContainerClosePacket::fromLatest($packet),
			$packet instanceof MobEffectPacket => v486MobEffectPacket::fromLatest($packet),
			$packet instanceof NetworkChunkPublisherUpdatePacket => v486NetworkChunkPublisherUpdatePacket::fromLatest($packet),
			$packet instanceof NetworkSettingsPacket => v486NetworkSettingsPacket::fromLatest($packet),
			$packet instanceof RemoveVolumeEntityPacket => v486RemoveVolumeEntityPacket::fromLatest($packet),
			$packet instanceof ResourcePacksInfoPacket => v486ResourcePacksInfoPacket::fromLatest($packet),
			$packet instanceof ResourcePackStackPacket => v486ResourcePackStackPacket::fromLatest($packet),
			$packet instanceof SetActorDataPacket => v486SetActorDataPacket::fromLatest($packet),
			$packet instanceof TextPacket => v486TextPacket::fromLatest($packet),
			$packet instanceof SetActorMotionPacket => v486SetActorMotionPacket::fromLatest($packet),
			$packet instanceof SpawnParticleEffectPacket => v486SpawnParticleEffectPacket::fromLatest($packet),
			$packet instanceof StartGamePacket => v486StartGamePacket::fromLatest($packet),
			$packet instanceof UpdateAttributesPacket => v486UpdateAttributesPacket::fromLatest($packet),
			$packet instanceof TransferPacket => v486TransferPacket::fromLatest($packet),
			$packet instanceof LevelSoundEventPacket => v486LevelSoundEventPacket::fromLatest($packet),
			$packet instanceof InventoryTransactionPacket => v486InventoryTransactionPacket::fromLatest($packet),
			$packet instanceof GameRulesChangedPacket => v486GameRulesChangedPacket::fromLatest($packet),
			default => $packet
		};
	}

	public function injectExtraData(array &$extraData) : void
	{
		$extraData['MaxViewDistance'] = 7;
		$extraData["IsEditorMode"] = false;
		$extraData["TrustedSkin"] = true;
		$extraData["CompatibleWithClientSideChunkGen"] = false;
	}

	public function decodeConnection(string $buffer, GlobalLoginPacket $loginPacket) : void
	{
		$in = new ByteBufferReader($buffer);
		$chainDataLen = LE::readUnsignedInt($in);
		$chainDataJson = json_decode($in->readByteArray($chainDataLen));
		if (!is_object($chainDataJson)) {
			throw new PacketDecodeException("Failed decoding chain data JSON: " . json_last_error_msg());
		}
		$mapper = new JsonMapper();
		$mapper->bExceptionOnMissingData = true;
		$mapper->bExceptionOnUndefinedProperty = true;
		try {
			$chainData = $mapper->map($chainDataJson, new JwtChain());
		} catch (JsonMapper_Exception $e) {
			throw PacketDecodeException::wrap($e);
		}
		$loginPacket->setChainDataJwt($chainData);
		$clientDataLen = LE::readUnsignedInt($in);
		$loginPacket->clientDataJwt = $in->readByteArray($clientDataLen);
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
					new CommandOverload(chaining: false, parameters: [CommandParameter::standard("args", v486AvailableCommandsPacket::ARG_FLAG_VALID, v486AvailableCommandsPacket::ARG_TYPE_RAWTEXT, 0, true)])
				],
				chainedSubCommandData: []
			);
		}
		return null; // v486AvailableCommandsPacket::create($commandData, [], [], []);
	}

	public function __toString() : string
	{
		return v486ProtocolInfo::MINECRAFT_VERSION;
	}

	public function getCraftingManager() : CraftingManager
	{
		return Server::getInstance()->getCraftingManager();
	}

}
