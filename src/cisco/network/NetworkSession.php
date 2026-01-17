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

namespace cisco\network;

use cisco\network\etc\MVBatch;
use cisco\network\etc\MVChunkCache;
use cisco\network\global\MVLoginPacketHandler;
use cisco\network\proto\TProtocol;
use cisco\network\utils\ReflectionUtils;
use Closure;
use InvalidArgumentException;
use pmmp\encoding\ByteBufferReader;
use pmmp\encoding\ByteBufferWriter;
use pmmp\encoding\VarInt;
use pocketmine\event\server\DataPacketDecodeEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\lang\KnownTranslationFactory;
use pocketmine\lang\Translatable;
use pocketmine\network\FilterNoisyPacketException;
use pocketmine\network\mcpe\compression\CompressBatchPromise;
use pocketmine\network\mcpe\compression\Compressor;
use pocketmine\network\mcpe\compression\DecompressionException;
use pocketmine\network\mcpe\convert\TypeConverter;
use pocketmine\network\mcpe\encryption\DecryptionException;
use pocketmine\network\mcpe\encryption\EncryptionContext;
use pocketmine\network\mcpe\EntityEventBroadcaster;
use pocketmine\network\mcpe\handler\PacketHandler;
use pocketmine\network\mcpe\NetworkSession as BaseNetworkSession;
use pocketmine\network\mcpe\PacketBroadcaster;
use pocketmine\network\mcpe\PacketSender;
use pocketmine\network\mcpe\protocol\ClientboundPacket;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\Packet;
use pocketmine\network\mcpe\protocol\PacketDecodeException;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\serializer\PacketBatch;
use pocketmine\network\mcpe\protocol\ServerboundPacket;
use pocketmine\network\mcpe\protocol\types\CompressionAlgorithm;
use pocketmine\network\NetworkSessionManager;
use pocketmine\network\PacketHandlingException;
use pocketmine\player\PlayerInfo;
use pocketmine\player\UsedChunkStatus;
use pocketmine\Server;
use pocketmine\timings\Timings;
use pocketmine\utils\BinaryDataException;
use pocketmine\utils\ObjectSet;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Utils;
use pocketmine\world\World;
use raklib\generic\DisconnectReason;
use ReflectionException;
use function base64_encode;
use function bin2hex;
use function count;
use function get_class;
use function implode;
use function is_string;
use function ord;
use function random_bytes;
use function str_split;
use function strlen;
use function substr;
use function time;

class NetworkSession extends BaseNetworkSession {

	protected TProtocol $protocol;

	private bool $enableCompression = true;

	private bool $isFirstPacket = true;

	public function __construct(Server $server, NetworkSessionManager $manager, PacketPool $packetPool, PacketSender $sender, PacketBroadcaster $broadcaster, EntityEventBroadcaster $entityEventBroadcaster, Compressor $compressor, TypeConverter $typeConverter, string $ip, int $port)
	{
		parent::__construct($server, $manager, $packetPool, $sender, $broadcaster, $entityEventBroadcaster, $compressor, $typeConverter, $ip, $port);
		$this->setHandler(new MVLoginPacketHandler(
			$server,
			$this,
			function (PlayerInfo $info) : void {
				ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "info", $info);
				$this->getLogger()->info(Server::getInstance()->getLanguage()->translate(KnownTranslationFactory::pocketmine_network_session_playerName(TextFormat::AQUA . $info->getUsername() . TextFormat::RESET)));
				$this->getLogger()->setPrefix("NetworkSession: " . $this->getDisplayName());
				ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "manager")->markLoginReceived($this);
			},
			function (bool $authenticated, bool $authRequired, Translatable|string|null $error, ?string $clientPubKey) : void {
				ReflectionUtils::invoke(BaseNetworkSession::class, $this, "setAuthenticationStatus", $authenticated, $authRequired, $error, $clientPubKey);
			},
			$this->onSessionStartSuccess(...)
		));
	}

	/**
	 * @throws ReflectionException
	 */
	public function setHandler(?PacketHandler $handler) : void
	{
		if (ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "connected")) { //TODO: this is fine since we can't handle anything from a disconnected session, but it might produce surprises in some cases
			$newHandler = $handler;
			if (isset($this->protocol)) {
				$newHandler = $this->protocol->fetchPacketHandler($handler, $this) ?? $handler;
			}

			ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "handler", $newHandler);
			$newHandler?->setUp();
		}
	}

	/**
	 * @throws ReflectionException
	 */
	public function handleEncoded(string $payload) : void
	{
		if (!ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "connected")) {
			return;
		}

		Timings::$playerNetworkReceive->startTiming();
		try {

			/**
			 * @var EncryptionContext $cipher
			 */
			$cipher = ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "cipher");
			if ($cipher !== null) {
				Timings::$playerNetworkReceiveDecrypt->startTiming();
				try {
					$payload = $cipher->decrypt($payload);
				} catch (DecryptionException $e) {
					$this->getLogger()->debug("Encrypted packet: " . base64_encode($payload));
					throw PacketHandlingException::wrap($e, "Packet decryption error");
				} finally {
					Timings::$playerNetworkReceiveDecrypt->stopTiming();
				}
			}

			if (strlen($payload) < 1) {
				throw new PacketHandlingException("No bytes in payload");
			}

			if (!$this->enableCompression) {
				$decompressed = $payload;
			} else {
				Timings::$playerNetworkReceiveDecompress->startTiming();
				if (!$this->isFirstPacket && !$this->protocol->hasOldCompressionMethod()) {
					$compressionType = ord($payload[0]);
					$compressed = substr($payload, 1);
					if ($compressionType === CompressionAlgorithm::NONE) {
						$decompressed = $compressed;
					} elseif ($compressionType === $this->getCompressor()->getNetworkId()) {
						try {
							$decompressed = $this->getCompressor()->decompress($compressed);
						} catch (DecompressionException $e) {
							$this->getLogger()->debug("Failed to decompress packet: " . base64_encode($compressed));
							throw PacketHandlingException::wrap($e, "Compressed packet batch decode error");
						} finally {
							Timings::$playerNetworkReceiveDecompress->stopTiming();
						}
					} else {
						throw new PacketHandlingException("Packet compressed with unexpected compression type $compressionType");
					}
				} else {
					try {
						$decompressed = $this->getCompressor()->decompress($payload);
					} catch (DecompressionException $e) {
						if (!$this->isFirstPacket) {
							$this->getLogger()->debug("Failed to decompress packet: " . base64_encode($payload));
							throw PacketHandlingException::wrap($e, "Compressed packet batch decode error");
						} else {
							$this->getLogger()->debug("Failed to decompress packet: " . base64_encode($payload));

							$this->enableCompression = false;
							$this->setHandler(new MVLoginPacketHandler(
								Server::getInstance(),
								$this,
								function (PlayerInfo $info) : void {
									ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "info", $info);
									$this->getLogger()->info(Server::getInstance()->getLanguage()->translate(KnownTranslationFactory::pocketmine_network_session_playerName(TextFormat::AQUA . $info->getUsername() . TextFormat::RESET)));
									$this->getLogger()->setPrefix("NetworkSession: " . $this->getDisplayName());
									ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "manager")->markLoginReceived($this);
								},
								function (bool $authenticated, bool $authRequired, Translatable|string|null $error, ?string $clientPubKey) : void {
									ReflectionUtils::invoke(BaseNetworkSession::class, $this, "setAuthenticationStatus", $authenticated, $authRequired, $error, $clientPubKey);
								},
								$this->onSessionStartSuccess(...)
							));

							$decompressed = $payload;
						}
					} finally {
						Timings::$playerNetworkReceiveDecompress->stopTiming();
					}
				}
			}

			$count = 0;
			try {
				$stream = new ByteBufferReader($decompressed);
				foreach (PacketBatch::decodeRaw($stream) as $buffer) {
					if(++$count >= 300){
						throw new PacketHandlingException("Reached hard limit of " . 300 . " per batch packet");
					}
					/**
					 * @var ?Packet $packet
					 */
					$packet = ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "packetPool")->getPacket($buffer);
					if ($packet === null) {
						$this->getLogger()->debug("Unknown packet: " . base64_encode($buffer));
						throw new PacketHandlingException("Unknown packet received: PID: " . (VarInt::unpackUnsignedInt($buffer) & DataPacket::PID_MASK));
					}

					try {
						$this->handleDataPacket($packet, $buffer);
					} catch (PacketHandlingException $e) {
						$this->getLogger()->debug($packet->getName() . ": " . base64_encode($buffer));
						throw PacketHandlingException::wrap($e, "Error processing " . $packet->getName());
					}

					if(!$this->isConnected()){
						//handling this packet may have caused a disconnection
						$this->getLogger()->debug("Aborting batch processing due to server-side disconnection");
						break;
					}
				}
			} catch (PacketDecodeException|BinaryDataException $e) {
				$this->getLogger()->logException($e);
				throw PacketHandlingException::wrap($e, "Packet batch decode error");
			} finally {
				$this->isFirstPacket = false;
			}
		} finally {
			Timings::$playerNetworkReceive->stopTiming();
		}
	}

	public function handleDataPacket(Packet $packet, string $buffer) : void
	{
		if (!isset($this->protocol)) {
			parent::handleDataPacket($packet, $buffer);
			return;
		}

		if (!$packet instanceof ServerboundPacket) {
			throw new PacketDecodeException("Unexpected non-serverbound packet");
		}

		$timings = Timings::getReceiveDataPacketTimings($packet);
		$timings->startTiming();

		try {
			$decodeTimings = Timings::getDecodeDataPacketTimings($packet);
			$decodeTimings->startTiming();
			try {
				if (DataPacketDecodeEvent::hasHandlers()) {
					$ev = new DataPacketDecodeEvent($this, $packet->pid(), $buffer);
					$ev->call();
					if ($ev->isCancelled()) {
						return;
					}
				}

				$stream = new ByteBufferReader($buffer);
				try {
					$packet->decode($stream);
				} catch (PacketDecodeException $e) {
					throw PacketHandlingException::wrap($e);
				}

				if ($stream->getUnreadLength() > 0) {
					$remains = substr($stream->getData(), $stream->getOffset());
					$this->getLogger()->debug("Still " . $stream->getUnreadLength() . " bytes unread in " . $packet->getName() . ": " . bin2hex($remains));
				}
			} finally {
				$decodeTimings->stopTiming();
			}

			$proto = $this->protocol;
			$pk = $proto->incoming(clone $packet);

			if ($pk === null) {
				$proto->getLogger()->debug("Ignoring {$packet->getName()} from {$this->getIp()}:{$this->getPort()}");
				return;
			}

			if (DataPacketReceiveEvent::hasHandlers()) {
				$ev = new DataPacketReceiveEvent($this, $pk);
				$ev->call();
				if ($ev->isCancelled()) {
					return;
				}
			}
			$handlerTimings = Timings::getHandleDataPacketTimings($packet);
			$handlerTimings->startTiming();
			try {
				if ($this->getHandler() === null || !$pk->handle($this->getHandler())) {
					$proto->getLogger()->debug("Unhandled " . $pk->getName() . ": " . base64_encode($stream->getData()));
				}
			} catch (FilterNoisyPacketException $exception) {
				// NOOP
			} finally {
				$handlerTimings->stopTiming();
			}
		} finally {
			$timings->stopTiming();
		}
	}

	public function syncAvailableCommands() : void {
		Utils::assumeNotFalse(isset($this->protocol), "Protocol should be already provided");
		$player = $this->getPlayer();
		$packet = $this->protocol->assembleCommands(
			$player->getServer(),
			$player,
			$player->getLanguage()
		);

		if ($packet !== null) {
			$this->addToSendBuffer(self::encodePacketTimed(new ByteBufferWriter(), $packet));
			return;
		}

		parent::syncAvailableCommands();
	}

	/**
	 * I dont need to override this but the @{deprecated} tag makes me mad
	 */
	public function addToSendBuffer(string $buffer) : void {
		parent::addToSendBuffer($buffer); // TODO: Change the autogenerated stub
	}

	/**
	 * @throws ReflectionException
	 */
	public function tick() : void
	{
		if (!ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "connected")) {
			ReflectionUtils::invoke(BaseNetworkSession::class, $this, "dispose");
			return;
		}

		if (ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "info") === null) {
			if (time() >= ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "connectTime") + 10) {
				$this->disconnectWithError(KnownTranslationFactory::pocketmine_disconnect_error_loginTimeout());
			}

			return;
		}

		$player = $this->getPlayer();
		if ($player !== null) {
			$player->doChunkRequests();

			$dirtyAttributes = $player->getAttributeMap()->needSend();
			$this->getEntityEventBroadcaster()->syncAttributes([$this], $player, $dirtyAttributes);
			foreach ($dirtyAttributes as $attribute) {
				//TODO: we might need to send these to other players in the future
				//if that happens, this will need to become more complex than a flag on the attribute itself
				$attribute->markSynchronized();
			}
		}
		Timings::$playerNetworkSendInventorySync->startTiming();
		try {
			$this->getInvManager()?->flushPendingUpdates();
		} finally {
			Timings::$playerNetworkSendInventorySync->stopTiming();
		}

		$this->flushGamePacketQueue();
	}

	public function disconnectWithError(Translatable|string $reason, Translatable|string|null $disconnectScreenMessage = null) : void
	{
		$errorId = implode("-", str_split(bin2hex(random_bytes(6)), 4));

		$this->disconnect(
			reason: KnownTranslationFactory::pocketmine_disconnect_error($reason, $errorId)->prefix(TextFormat::RED),
			disconnectScreenMessage: KnownTranslationFactory::pocketmine_disconnect_error($disconnectScreenMessage ?? $reason, $errorId),
		);
	}

	/**
	 * Disconnects the session, destroying the associated player (if it exists).
	 *
	 * @param Translatable|string      $reason                  Shown in the server log - this should be a short one-line message
	 * @param Translatable|string|null $disconnectScreenMessage Shown on the player's disconnection screen (null will use the reason)
	 */
	public function disconnect(Translatable|string $reason, Translatable|string|null $disconnectScreenMessage = null, bool $notify = true) : void
	{
		$this->tryDisconnect(function () use ($reason, $disconnectScreenMessage, $notify) : void {
			if ($notify) {
				$this->sendDisconnectPacket($disconnectScreenMessage ?? $reason);
			}
			$this->getPlayer()?->onPostDisconnect($reason, null);
		}, $reason);
	}

	/**
	 * @phpstan-param Closure() : void $func
	 */
	public function tryDisconnect(Closure $func, Translatable|string $reason) : void
	{
		if (ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "connected") && !ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "disconnectGuard")) {
			ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "disconnectGuard", true);
			$func();
			ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "disconnectGuard", false);
			$this->flushGamePacketQueue();
			ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "sender")->close();
			/**
			 * @var ObjectSet $disposeHooks
			 */
			$disposeHooks = ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "disposeHooks");
			foreach ($disposeHooks as $callback) {
				$callback();
			}
			$disposeHooks->clear();
			$this->setHandler(null);
			ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "connected", false);

			$this->getLogger()->info(Server::getInstance()->getLanguage()->translate(KnownTranslationFactory::pocketmine_network_session_close($reason)));
		}
	}

	private function sendDisconnectPacket(Translatable|string $message) : void
	{
		if ($message instanceof Translatable) {
			$message = Server::getInstance()->getLanguage()->translate($message);
		}
		$this->sendDataPacket(DisconnectPacket::create(DisconnectReason::CLIENT_DISCONNECT, $message, ""));
	}

	/**
	 * @throws ReflectionException
	 */
	public function sendDataPacket(ClientboundPacket $packet, bool $immediate = false) : bool
	{
		if (!isset($this->protocol)) {
			return parent::sendDataPacket($packet, $immediate);
		}

		if (!ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "connected")) {
			return false;
		}

		if (!ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "loggedIn") && !$packet->canBeSentBeforeLogin()) {
			throw new InvalidArgumentException("Attempted to send " . get_class($packet) . " to " . $this->getDisplayName() . " too early");
		}

		$timings = Timings::getSendDataPacketTimings($packet);
		$timings->startTiming();
		try {
			$packets = [$packet];
			if (DataPacketSendEvent::hasHandlers()) {
				$ev = new DataPacketSendEvent([$this], $packets);
				$ev->call();
				if ($ev->isCancelled()) {
					return false;
				}

				$packets = $ev->getPackets();
			}

			$writer = new ByteBufferWriter();

			$proto = $this->protocol;

			foreach ($packets as $packet) {
				// Memory reuse
				$writer->clear();

				$pk = $proto->outcoming(clone $packet);

				if ($pk === null) {
					$proto->getLogger()->debug("Ignoring {$packet->getName()} from {$this->getIp()}:{$this->getPort()}");
					continue;
				}

				$this->addToSendBuffer(self::encodePacketTimed($writer, $pk));
			}

			if ($immediate) {
				$this->flushGamePacketQueue();
			}

			return true;
		} finally {
			$timings->stopTiming();
		}
	}

	/**
	 * Instructs the networksession to start using the chunk at the given coordinates. This may occur asynchronously.
	 *
	 * @param Closure $onCompletion To be called when chunk sending has completed.
	 *
	 * @phpstan-param Closure() : void $onCompletion
	 */
	public function startUsingChunk(int $chunkX, int $chunkZ, Closure $onCompletion) : void
	{
		$world = $this->getPlayer()->getLocation()->getWorld();

		$promiseOrString = MVChunkCache::getInstance($world, $this->getCompressor(), $this->protocol)->request($chunkX, $chunkZ);
		if (is_string($promiseOrString)) {
			$this->sendChunkPacket($promiseOrString, $onCompletion, $world);
			return;
		}
		$promiseOrString->onResolve(function (CompressBatchPromise $promise) use ($world, $onCompletion, $chunkX, $chunkZ) : void {
			if (!$this->isConnected()) {
				return;
			}

			$currentWorld = $this->getPlayer()->getLocation()->getWorld();
			if ($world !== $currentWorld || ($status = $this->getPlayer()->getUsedChunkStatus($chunkX, $chunkZ)) === null) {
				$this->getLogger()->debug("Tried to send no-longer-active chunk $chunkX $chunkZ in world " . $world->getFolderName());
				return;
			}

			if ($status !== UsedChunkStatus::REQUESTED_SENDING) {
				//TODO: make this an error
				//this could be triggered due to the shitty way that chunk resends are handled
				//right now - not because of the spammy re-requesting, but because the chunk status reverts
				//to NEEDED if they want to be resent.
				return;
			}

			$this->sendChunkPacket($promise->getResult(), $onCompletion, $world);
		});
	}

	/**
	 * @phpstan-param Closure() : void $onCompletion
	 */
	private function sendChunkPacket(string $chunkPacket, Closure $onCompletion, World $world) : void
	{
		$world->timings->syncChunkSend->startTiming();
		try {
			$this->queueCompressed($chunkPacket);
			$onCompletion();
		} finally {
			$world->timings->syncChunkSend->stopTiming();
		}
	}

	public function queueCompressed(CompressBatchPromise|string $payload, bool $immediate = false) : void
	{
		Timings::$playerNetworkSend->startTiming();
		try {
			$this->flushGamePacketQueue();
			ReflectionUtils::invoke(BaseNetworkSession::class, $this, "queueCompressedNoGamePacketFlush", $payload, $immediate);
		} finally {
			Timings::$playerNetworkSend->stopTiming();
		}
	}

	public function onClientDisconnect(Translatable|string $reason) : void
	{
		$this->tryDisconnect(function () use ($reason) : void {
			$this->getPlayer()?->onPostDisconnect($reason, null);
		}, $reason);
	}

	public function onPlayerDestroyed(Translatable|string $reason, Translatable|string $disconnectScreenMessage) : void
	{
		$this->tryDisconnect(function () use ($disconnectScreenMessage) : void {
			ReflectionUtils::invoke(BaseNetworkSession::class, $this, "sendDisconnectPacket", $disconnectScreenMessage);
		}, $reason);
	}

	/**
	 * @throws ReflectionException
	 */
	private function onSessionStartSuccess() : void
	{
		$this->getLogger()->debug("Session start handshake completed, awaiting login packet");
		$this->flushGamePacketQueue();
		$this->enableCompression = true;
		$this->setHandler(new MVLoginPacketHandler(
			Server::getInstance(),
			$this,
			function (PlayerInfo $info) : void {
				ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "info", $info);
				$this->getLogger()->info(Server::getInstance()->getLanguage()->translate(KnownTranslationFactory::pocketmine_network_session_playerName(TextFormat::AQUA . $info->getUsername() . TextFormat::RESET)));
				$this->getLogger()->setPrefix("NetworkSession: " . $this->getDisplayName());
				ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "manager")->markLoginReceived($this);
			},
			function (bool $authenticated, bool $authRequired, Translatable|string|null $error, ?string $clientPubKey) : void {
				ReflectionUtils::invoke(BaseNetworkSession::class, $this, "setAuthenticationStatus", $authenticated, $authRequired, $error, $clientPubKey);
			},
			$this->onSessionStartSuccess(...)
		));
	}

	private function flushGamePacketQueue() : void
	{
		$sendBuffer = ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "sendBuffer");
		if (count($sendBuffer) > 0) {
			Timings::$playerNetworkSend->startTiming();
			try {
				$syncMode = null;
				if (ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "forceAsyncCompression")) {
					$syncMode = false;
				}

				$stream = new ByteBufferWriter();
				PacketBatch::encodeRaw($stream, $sendBuffer);

				$batch = $this->enableCompression ? MVBatch::prepareBatch($stream->getData(), $this->getProtocol(), $this->getCompressor(), $syncMode, Timings::$playerNetworkSendCompressSessionBuffer) : $stream->getData();

				ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "sendBuffer", []);
				$ackPromises = ReflectionUtils::getProperty(BaseNetworkSession::class, $this, "sendBufferAckPromises");
				ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "sendBufferAckPromises", []);

				//these packets were already potentially buffered for up to 50ms - make sure the transport layer doesn't
				//delay them any longer
				ReflectionUtils::invoke(BaseNetworkSession::class, $this, "queueCompressedNoGamePacketFlush", $batch, true, $ackPromises);
			} finally {
				Timings::$playerNetworkSend->stopTiming();
			}
		}
	}

	public function getProtocol() : TProtocol
	{
		return $this->protocol;
	}

	/**
	 * @throws ReflectionException
	 */
	public function setProtocol(TProtocol $protocol) : void
	{
		$this->protocol = $protocol;
		EncryptionContext::$ENABLED = $protocol->hasEncryption();
		ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "packetPool", $protocol->getPacketPool());
		ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "broadcaster", $protocol->getBroadcaster());
		ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "entityEventBroadcaster", $protocol->getEntityEventBroadcaster());
		ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "compressor", $protocol->getCompressor());
		ReflectionUtils::setProperty(BaseNetworkSession::class, $this, "typeConverter", $protocol->getTypeConverter());
	}
}
