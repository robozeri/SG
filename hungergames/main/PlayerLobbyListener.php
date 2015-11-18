<?php

namespace hungergames\main;

use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\Player;
use pocketmine\Server;
use hungergames\level\GameLevelModel;
use pocketmine\level\Position;
use hungergames\arena\MapArenaModel;
use pocketmine\item\Item;
use pocketmine\event\player\PlayerGameModeChangeEvent;
use pocketmine\utils\TextFormat;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\math\Vector2 as Vector2;
use pocketmine\math\Vector3 as Vector3;
use hungergames\statue\StatueBuilder;
use hungergames\portal\MapPortal;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\network\protocol\ContainerOpenPacket;
use pocketmine\inventory\InventoryType;
use pocketmine\network\Network;
use hungergames\utils\PlayerHelper;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\player\PlayerEvent;
use pocketmine\entity\PrimedTNT;


class PlayerLobbyListener implements Listener {
	public $plugin;
	public function __construct(HungerGamesPlugIn $plugin) {
		$this->plugin = $plugin;
	}
	public function onPrimeTnT(ExplosionPrimeEvent $event) {
		if ($event->getEntity () instanceof Player) {
			$player = $event->getEntity ();
			if ($player->getLevel ()->getName () === $this->plugin->hubLevelName) {
				if (! $player->isOp ()) {
					$event->setCancelled ( true );
				}
			}
		}
	}
		
	public function onPlayerInteract(PlayerInteractEvent $event) {
		if ($event->getPlayer () instanceof Player) {
			$player = $event->getPlayer ();
			$block = $event->getBlock ();
			if ($this->plugin->blockhud) {
				$event->getPlayer ()->sendMessage ( "TOUCHED ".$block->getId()." [x=" . round($block->x) . " y=" . round($block->y) . " z=" . round($block->z ). "]" );
			}
		}
	}
	
	/**
	 *
	 * @param PlayerGameModeChangeEvent $event        	
	 */
	public function onPlayerGameModeChange(PlayerGameModeChangeEvent $event) {
		$this->showGameMode ( $event->getPlayer () );
	}
	private function showGameMode(Player $player) {
		$modeName = "";
		switch ($player->getGamemode ()) {
			case Player::ADVENTURE :
				$modeName = "Adventure";
				break;
			case Player::CREATIVE :
				$modeName = "Creative";
				break;
			case Player::SURVIVAL :
				$modeName = "Survival";
				break;
			case Player::SPECTATOR :
				$modeName = "Spectator";
				break;
		}
		$player->sendMessage ( TextFormat::DARK_GRAY . "[HG] You are in mode: " . TextFormat::DARK_GREEN . $modeName );
	}
	
	/**
	 * OnBlockBreak
	 *
	 * @param BlockBreakEvent $event        	
	 */
	public function onBlockBreak(BlockBreakEvent $event) {
		if (! $event->getPlayer ()->isOp ()) {
			$allow = $this->checkBlockBreakAndPlacementPermission ( $event->getPlayer (), $event->getBlock () );
			$this->plugin->log("PlayerLobbyListener->onBlockBreak allow: " . $allow );			
			if (! $allow) {
				$event->setCancelled ( true );
				//$this->plugin->log("[HG] PlayerLobbyListener: onBlockBreak Cancelled for player " . $event->getPlayer ()->getName () );
			}
			if ($allow) {
				if ($event->getBlock ()->getId () === Item::CHEST && !$event->getPlayer ()->isOp()) {
					$message = TextFormat::RED . "You are not allow break Chest";
					$event->getPlayer ()->sendPopup ( $message );
					$event->setCancelled ( true);
				} else {
					$this->plugin->log("[HG] PlayerLobbyListener: onBlockBreak ALLOW | " . $event->getPlayer ()->getGamemode () . "|" . $event->getPlayer ()->getName () . "|" . $event->getPlayer ()->getLevel ()->getName () );
					$event->setInstaBreak ( true );
					$event->setCancelled ( false );
				}
			}
		}
	}
	
	/**
	 * onBlockPlace
	 *
	 * @param BlockPlaceEvent $event        	
	 *
	 */
	public function onBlockPlace(BlockPlaceEvent $event) {
		if (! $event->getPlayer ()->isOp ()) {
			$allow = $this->checkBlockBreakAndPlacementPermission ( $event->getPlayer (), $event->getBlock () );
			$this->plugin->log("PlayerLobbyListener->onBlockPlace allow: " . $allow );
			if (! $allow) {
				$event->setCancelled ( true );
				$this->plugin->log("[HG] PlayerLobbyListener: onBlockPlace cancelled for player " . $event->getPlayer ()->getName () );
			}
			if ($allow) {
				$event->setCancelled ( false );
				$b = $event->getBlock ();
				if (! empty ( $event->getPlayer ()->getInventory () )) {
					$event->getPlayer ()->getLevel ()->setBlock ( new Position ( $b->x, $b->y, $b->z, $b->getLevel () ), $b );
					$this->plugin->log("[HG] PlayerLobbyListener: onBlockPlace ALLOW " .$event->getPlayer ()->isOp()." | ". $event->getPlayer ()->getGamemode () . "|" . $event->getPlayer ()->getName () . "|" . $event->getPlayer ()->getLevel ()->getName () );
					$items [] = $event->getPlayer ()->getInventory ()->getContents ();
					$i = 0;
					foreach ( $items as $index ) {
						foreach ( $index as $m ) {
							if ($m->getId () === $b->getId ()) {
								$i = $m->getCount ();
							}
						}
					}					
					if ($i > 0) {
						$i --;
						$event->getPlayer ()->getInventory ()->setItemInHand ( new Item ( Item::AIR ) );
						$event->getPlayer ()->getInventory ()->addItem ( new Item ( $b->getId (), 0, $i ) );
					}
				}

			}
		}
	}
	
	/**
	 *
	 * @param Player $player        	
	 * @return boolean
	 */
	private function checkBlockBreakAndPlacementPermission(Player $player, $block) {
		$player->getLevel ()->updateAllLight ( $player->getPosition () );
		$allow = false;
		// check llobby
		if ($player->getLevel ()->getName () === $this->plugin->hubLevelName || $player->getLevel ()->getName () === $this->plugin->vipLevelName) {
			if (! $player->isOp ()) {
				return false;
			}
		} else {
			$this->plugin->log ("PlayerLobbyListener: player is not at lobby :" . $player->getLevel ()->getName ());
			
			if ($player instanceof Player && ! $player->isOp () && ! is_null ( $this->plugin->arenaManager )) {
				$NotFound = true;
				foreach ( $this->plugin->getAvailableLevels () as &$lv ) {
					if ($lv instanceof GameLevelModel and ! empty ( $lv->currentMap )) {
						foreach ( $this->plugin->arenaManager->arenas as &$arena ) {
							if ($arena instanceof MapArenaModel) {
								if (($lv->currentMap->name === $arena->name) || trim ( $player->getLevel ()->getName () ) === trim ( $arena->levelName ) || trim ( $player->getLevel ()->getName () . "_TEMP" ) === trim ( $arena->levelName . "_TEMP" )) {
									$NotFound = false;
									if ($arena->allowBreakBlock || $arena->allowBlockPlace) {
										$this->plugin->log("PlayerLobbyListener: ARENA allow Break = true");
									}
									$allow = true;
									break;
								}
							}
						}
					}
					if ($NotFound) {
						$this->plugin->log("PlayerLobbyListener: arena not found :  ALLOW BREAK : TRUE");
						$allow = true;
					}
					if ($allow) {
						break;
					}
				}
			}
		}
		if ($player->isOp ()) {
			$allow = true;
		}
		return $allow;
	}
	
	/**
	 *
	 * @param PlayerMoveEvent $event        	
	 */
	public function onPlayerMove(PlayerMoveEvent $event) {
		$player = $event->getPlayer ();
	}
	
	/**
	 *
	 * @param EntityDamageEvent $event        	
	 */
	public function onEntityDamage(EntityDamageEvent $event) {
		if ($event instanceof EntityDamageByEntityEvent) {
			$entity = $event->getEntity ();
			$killer = $event->getDamager ();
			if ($entity instanceof Player && $killer instanceof Player) {
				if (($entity->getLevel ()->getName () === $this->plugin->hubLevelName) || ($entity->getLevel ()->getName () === $this->plugin->vipLevelName)) {
					$event->setCancelled ( true );
					$this->plugin->log ( "[HG] PlayerLobbyListener: onEntityDamage cancelled :".$entity->getName());
				}
			}
		}
	}
}
