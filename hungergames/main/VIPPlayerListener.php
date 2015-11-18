<?php

namespace hungergames\main;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\math\Vector2 as Vector2;
use pocketmine\math\Vector3 as Vector3;
use pocketmine\Player;
use pocketmine\Server;
use hungergames\portal\MapPortal;
use pocketmine\utils\TextFormat;
use hungergames\statue\StatueBuilder;
use hungergames\level\GameLevelModel;
use pocketmine\level\Position;
use hungergames\arena\MapArenaModel;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\item\Item;
use pocketmine\network\protocol\ContainerOpenPacket;
use pocketmine\inventory\InventoryType;
use pocketmine\network\Network;
use hungergames\utils\PlayerHelper;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\player\PlayerEvent;
use pocketmine\event\level\LevelEvent;
use pocketmine\event\level\SpawnChangeEvent;
use pocketmine\level\particle\PortalParticle;


class VIPPlayerListener implements Listener {
	public $plugin;
	private $vips = [ ];
	public function __construct(HungerGamesPlugIn $plugin) {
		$this->plugin = $plugin;
	}
	public function onPlayerInteract(PlayerInteractEvent $event) {
		$player = $event->getPlayer ();
		$block = $event->getBlock ();
		if ($player instanceof Player) {
			if (round ( $this->plugin->vipSignPos->x ) === round ( $block->x ) and round ( $this->plugin->vipSignPos->y ) === round ( $block->y ) and round ( $this->plugin->vipSignPos->z ) === round ( $block->z )) {
				if ($this->plugin->vipenforceaccess) {
					$vip = $this->checkInVIP ( $event->getPlayer () );
					if (! $vip) {
						$message = TextFormat::YELLOW . "[HG] Require " . TextFormat::RED . "VIP+ " . TextFormat::YELLOW . "membership.";
						$event->getPlayer ()->sendMessage ( $message );
						return;
					}
				}
				MapPortal::teleportingToLobby ( $player, $this->plugin->vipLevelName, $this->plugin->vipSpawnPos );
				$this->plugin->log ( "[HG] teleporting to VIP lodge " . $this->plugin->vipSpawnPos->x . " " . $this->plugin->vipSpawnPos->y . " " . $this->plugin->vipSpawnPos->z );
				$player->sendTip ( TextFormat::BOLD . TextFormat::WHITE . "Welcome to " . TextFormat::RED . "[V.I.P.+ " . TextFormat::GOLD . "Lodge]" );
				return;
			}
			if (round ( $this->plugin->vipExitSignPos->x ) === round ( $block->x ) and round ( $this->plugin->vipExitSignPos->y ) === round ( $block->y ) and round ( $this->plugin->vipExitSignPos->z ) === round ( $block->z )) {
				MapPortal::teleportingToLobby ( $player, $this->plugin->hubLevelName, $this->plugin->hubSpawnPos );
				$this->plugin->log ( "[HG] teleporting to HG lobby " . $this->plugin->hubSpawnPos->x . " " . $this->plugin->hubSpawnPos->y . " " . $this->plugin->hubSpawnPos->z );
				$player->sendTip ( TextFormat::BOLD . TextFormat::WHITE . "Welcome to " . TextFormat::RED . "HG " . TextFormat::GOLD . "Lobby" );
				return;
			}
		}
	}
	
	/**
	 *
	 * @param PlayerJoinEvent $event        	
	 */
	public function onPlayerJoin(PlayerJoinEvent $event) {
		if ($this->plugin->vipenforceaccess) {
			if ($event->getPlayer () instanceof Player) {
				if ($event->getPlayer ()->getLevel ()->getName () === $this->plugin->vipLevelName) {
					$vip = $this->checkInVIP ( $event->getPlayer () );
					if (! $vip) {
						$event->getPlayer ()->kick ( TextFormat::YELLOW . "[HG] Require VIP+ membership!" );
						return;
					}
				}
			}
			$this->plugin->log ( "[HG] VIPPlayerListener->onPlayerJoin: " . $event->getPlayer ()->getName () );
		}
	}
	
	/**
	 *
	 * @param PlayerRespawnEvent $event        	
	 */
	public function onPlayerRespawn(PlayerRespawnEvent $event) {
		if ($this->plugin->vipenforceaccess) {
			if ($event->getPlayer () instanceof Player) {
				if ($event->getPlayer ()->getLevel ()->getName () === $this->plugin->vipLevelName) {
					$vip = $this->checkInVIP ( $event->getPlayer () );
					if (! $vip) {
						$event->getPlayer ()->kick ( TextFormat::YELLOW . "[HG] Require VIP+ membership!" );
						return;
					}
				}
			}
			$this->plugin->log ( "[HG] VIPPlayerListener->onPlayerRespawn: " . $event->getPlayer ()->getName () );
		}
	}
	
	private function checkInVIP(Player $player) {
		//if ($player->getLevel ()->getName () === $this->plugin->vipLevelName) {
			if (isset ( $this->vips [$player->getName ()] )) {
				$this->plugin->log ( "[HG] VIPPlayerListener->VIPCheck-IN: " . $player->getName () );
				return true;
			} else {
				$vip = $this->plugin->profileManager->isPlayerVIP ( $player->getName () );
				if (! $vip) {
					return false;
				}
				if (! $player->isOp ()) {
					if (! $player->isSurvival ()) {
						$player->setGamemode ( Player::SURVIVAL );
					}
				}
				$this->vips [$player->getName ()] = $player->getName ();
				$this->plugin->log ( "[HG] VIPPlayerListener->VIPCheck-IN: " . $player->getName () );
				return true;
			}
		//}
		return false;
	}
	
	private function checkOutVIP(Player $player) {
		if ($player->getLevel ()->getName () === $this->plugin->vipLevelName) {
			if (isset ( $this->vips [$player->getName ()] )) {
				unset ( $this->vips [$player->getName ()] );
			}
		}
	}
	
	/**
	 *
	 * @param PlayerQuitEvent $event        	
	 */
	public function onQuit(PlayerQuitEvent $event) {
		if ($this->plugin->vipenforceaccess) {
			if ($event->getPlayer () instanceof Player) {
				$this->checkOutVIP ( $event->getPlayer () );
				$this->plugin->log ( "[HG] VIPPlayerListener->onQuit: " . $event->getPlayer ()->getName () );
			}
		}
	}
	public function onPlayerDeath(PlayerDeathEvent $event) {
		if ($this->plugin->vipenforceaccess) {
			if ($event->getEntity () instanceof Player) {
				$this->checkOutVIP ( $event->getEntity () );
				$this->plugin->log ( "[HG] VIPPlayerListener->onPlayerDeath: " . $event->getEntity ()->getName () );
			}
		}
	}
	public function onPlayerKicked(PlayerKickEvent $event) {
		if ($this->plugin->vipenforceaccess) {
			if ($event->getPlayer () instanceof Player) {
				$this->checkOutVIP ( $event->getPlayer () );
				$this->plugin->log ( "[HG] VIPPlayerListener->onPlayerKicked: " . $event->getPlayer ()->getName () );
			}
		}
	}
}
