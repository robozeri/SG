<?php

namespace hungergames\level;

use hungergames\main\HungerGamesPlugIn;
use hungergames\utils\MagicUtil;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\entity\Arrow;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityDespawnEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\Server;
use pocketmine\item\Item;
use hungergames\utils\PlayerHelper;
use hungergames\main\HungerGameKit;
use pocketmine\event\inventory\InventoryOpenEvent;
use hungergames\arena\MapArenaModel;
use hungergames\portal\MapPortalManager;
use hungergames\portal\MapPortal;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\utils\TextFormat;


class GameLevelListener implements Listener {
	public $plugin;
	public function __construct(HungerGamesPlugIn $plugin) {
		$this->plugin = $plugin;
	}
	
	/**
	 *
	 * @param PlayerQuitEvent $event        	
	 */
	public function onQuit(PlayerQuitEvent $event) {
		$this->plugin->log ( "GameLevelListener: " . $event->getEventName ()." player: ".$event->getPlayer()->getName());
		//should only notify team $quitMessage
		//$event->setQuitMessage("[HG]".$event->getPlayer()->getName()." left the game");
		if ($event->getPlayer () instanceof Player) {
			$player = $event->getPlayer ();
			$this->plugin->gameLevelManager->handlePlayerLeaveTheGame ( $player );
		}
	}
	
	/**
	 *
	 * @param BlockBreakEvent $event        	
	 */
	public function onBlockBreak(BlockBreakEvent $event) {
		if ($event->getPlayer () instanceof Player) {
			if ($this->plugin->gameLevelManager->setupModeAction === GameLevelManager::COMMAND_SETUP_WAND_GAME_LEVEL || $this->plugin->gameLevelManager->setupModeAction === GameLevelManager::COMMAND_SETUP_WAND_GAME_LEVEL_GATE) {
				$this->plugin->gameLevelManager->handleBlockBreakSelection ( $event->getPlayer (), $event->getBlock () );
			}
		}
	}
	public function onPlayerInteract(PlayerInteractEvent $event) {
		$player = $event->getPlayer ();
		$block = $event->getBlock ();
		if ($player instanceof Player) {
			$this->handleLobbyFallOfFameExitSign ( $player, $block );
			$this->plugin->gameLevelManager->handleTapOnGameLevelSigns ( $player, $block );
			$found = false;
			if ($block->getId () === Item::CHEST) {
				foreach ( $this->plugin->getAvailableLevels () as &$lv ) {
					if ($lv instanceof GameLevelModel and ! empty ( $lv->currentMap ) || $player->isOp ()) {
						if (! empty ( $lv->currentMap ) and ($lv->currentMap instanceof MapArenaModel)) {
							if (isset ( $lv->joinedPlayers [$player->getName ()] ) || isset ( $lv->currentMap->livePlayers [$player->getName ()] )) {
								PlayerHelper::openChest ( $block, $player );
								$this->plugin->getServer ()->getPluginManager ()->callEvent ( new InventoryOpenEvent ( $player->getInventory (), $player ) );
								$player->addWindow ( $player->getInventory (), true );
								$key = $block->x . "." . $block->y . "." . $block->z;
								if (! isset ( $lv->openchests [$lv->type] [$key] )) {
									if ($lv->type === GameLevelModel::LEVEL_THREE || $lv->type === GameLevelModel::LEVEL_VIP) {
										HungerGameKit::fillRandomChestItemIncludingTnT ( $player, $block );
									} else {
										HungerGameKit::fillRandomChestItems ( $player, $block );
									}
									$lv->openchests [$lv->type] [$key] = $block;
									$this->plugin->log ( " Refill touched chest at " . $block->getLevel ()->getName () . " |" . $lv->currentMap->levelName . " |" . $player->getLevel ()->getName () );
								}
								$found = true;
								break;
							}
						}
						if ($found) {
							break;
						}
					}
				}
			}
		}
	}
	public function onPlayerChat(PlayerChatEvent $event) {
		$playerInGame = false;
		$InGamePlayers = [ ];
		$msg = $event->getMessage ();
		$player = $event->getPlayer ();
		//$this->plugin->log ( " handlePlayerInGameChat - | " . $player->getName () . "| msg: " . $msg );
		if (! empty ( $player ) && ! empty ( $msg )) {
			foreach ( $this->plugin->gameLevelManager->levels as &$lv ) {
				if ($lv instanceof GameLevelModel) {
					if (isset ( $lv->joinedPlayers [$player->getName ()] )) {
						$message = TextFormat::GRAY . "[HG-" . TextFormat::RED . $lv->type . TextFormat::GRAY . "]" . TextFormat::YELLOW . " > " . TextFormat::WHITE . $msg;
						$event->setMessage ( $message );
						$event->setRecipients ( $lv->joinedPlayers );
						$playerInGame = true;
						$this->plugin->log ( " handlePlayerInGameChat - in-game-message " . $msg . " send to " . count ( $lv->joinedPlayers ) );
					}
					if (count ( $lv->joinedPlayers ) > 0) {
						$InGamePlayers = array_merge ( $lv->joinedPlayers, $InGamePlayers );
						$this->plugin->log ( " handlePlayerInGameChat - merged " . $lv->name . " players " . count ( $lv->joinedPlayers ) );
					}
				}
			}
			if (! $playerInGame) {
				$filteredplayers = array_diff ( $this->plugin->getServer ()->getOnlinePlayers (), $InGamePlayers );
				$this->plugin->log ( " handlePlayerInGameChat - not-in-game-message " . $msg . " send to " . count ( $filteredplayers ) );
				$event->setRecipients ( $filteredplayers );
			}
		}
	}
	private function handleLobbyFallOfFameExitSign(Player $player, $block) {
		$x = $this->plugin->getConfig ()->get ( "hg_lobby_hof_sign_exit_x" );
		$y = $this->plugin->getConfig ()->get ( "hg_lobby_hof_sign_exit_y" );
		$z = $this->plugin->getConfig ()->get ( "hg_lobby_hof_sign_exit_z" );
		if (round ( $x ) === round ( $block->x ) && round ( $y ) === round ( $block->y ) && round ( $z ) === round ( $block->z )) {
			$player->teleport ( $this->plugin->hubSpawnPos );
		}
	}
	public function onPlayerDeath(PlayerDeathEvent $event) {
		$this->plugin->log ( "GameLevelListener: onPlayerDeath " . $event->getEventName () );
		if ($event->getEntity () instanceof Player) {
			if ($this->plugin->gameLevelManager->handlePlayerDeath ( $event->getEntity () )) {
				$event->setDeathMessage ( "" );
			}
		}
	}
	public function onPlayerKicked(PlayerKickEvent $event) {
		$this->plugin->log ( "GameLevelListener: " . $event->getEventName () );
		if ($event->getPlayer () instanceof Player) {
			$player = $event->getPlayer ();
			$this->plugin->gameLevelManager->handlePlayerLeaveTheGame ( $player );
		}
	}
	public function onPlayerMove(PlayerMoveEvent $event) {
		$player = $event->getPlayer ();
		$x = round ( $event->getFrom ()->x );
		$y = round ( $event->getFrom ()->y );
		$z = round ( $event->getFrom ()->z );
		
		foreach ( $this->plugin->getAvailableLevels () as &$lv ) {
			if ($lv instanceof GameLevelModel) {
				if ($lv->status === GameLevelModel::STATUS_AVAILABLE) {
					if ($player->getLevel ()->getName () != $lv->levelName) {
						continue;
					}
					if ($lv->portalEnter ( $this->plugin, $lv, $player )) {
						return;
						break;
					}
				} elseif ($lv->currentStep === GameLevelModel::STEP_WAITING) {
					if (strtolower ( $player->level->getName () ) === strtolower ( $lv->currentMap->levelName )) {
						$player->onGround = true;
						$event->setCancelled ( true );
					} else {
						$player->onGround = false;
					}
					break;
				}
			}
		}
	}
	
	/**
	 *
	 * Handle Level 3 - Exploding Arrows
	 *
	 * @param ProjectileHitEvent $event        	
	 */
	public function onProjectTileHit(ProjectileHitEvent $event) {
		if ($event->getEntity () instanceof Arrow) {
			foreach ( $this->plugin->getAvailableLevels () as &$lv ) {
				try {
					if ($lv instanceof GameLevelModel) {
						if ($lv->type === GameLevelModel::LEVEL_THREE || $lv->type === GameLevelModel::LEVEL_VIP || $lv->type === GameLevelModel::LEVEL_FOUR) {
							if (empty ( $lv->currentMap )) {
								continue;
							}
							$pos = new Position ( $event->getEntity ()->getPosition ()->x, $event->getEntity ()->getPosition ()->y, $event->getEntity ()->getPosition ()->z, $event->getEntity ()->getLevel () );
							MagicUtil::makeExplosion ( $event->getEntity ()->getLevel (), $pos );
							MagicUtil::addParticles ( $event->getEntity ()->getLevel (), "explode", $event->getEntity ()->getPosition (), 20 );
							MagicUtil::addParticles ( $event->getEntity ()->getLevel (), "flame", $event->getEntity ()->getPosition (), 20 );
							break;
						}
					}
				} catch ( \Exception $e ) {
					$this->plugin->printError ( $e );
				}
			}
		}
	}
	
	/**
	 * Give default permissions to players
	 *
	 * @param Player $player        	
	 */
	private function grantPlayerDefaultPermissions(Player $player) {
		$player->addAttachment ( $this->plugin, "plugin.hungergames", TRUE );
	}
	
	/**
	 * Sign change
	 *
	 * @param SignChangeEvent $event        	
	 */
	public function onSignChange(SignChangeEvent $event) {
		if (strtolower ( $event->getPlayer ()->getLevel ()->getName () ) === strtolower ( $this->plugin->hubLevelName )) {
			$player = $event->getPlayer ();
			$block = $event->getBlock ();
			$line1 = $event->getLine ( 0 );
			$line2 = $event->getLine ( 1 );
			$line3 = $event->getLine ( 2 );
			$line4 = $event->getLine ( 3 );
			
			if (! $event->getPlayer ()->isOp ()) {
				$event->getPlayer ()->sendMessage ( "[HG] You are not authorized to use this command." );
				$event->setCancelled ( true );
			} else {
				if ($line1 != null && $line1 === "hungergame") {
					if ($line2 != null && $line2 === "level") {
						if ($line3 != null && $line3 === "join") {
							$arenaName = $line4;
							$this->plugin->gameLevelManager->handleSetSignJoin ( $player, $arenaName, $block );
						}
					}
				}
				if ($line1 != null && $line1 === "hungergame") {
					if ($line2 != null && $line2 === "level") {
						if ($line3 != null && $line3 === "join2") {
							$arenaName = $line4;
							$this->plugin->gameLevelManager->handleSetSignJoin2 ( $player, $arenaName, $block );
						}
					}
				}
				if ($line1 != null && $line1 === "hungergame") {
					if ($line2 != null && $line2 === "level") {
						if ($line3 != null && $line3 === "exit") {
							$arenaName = $line4;
							$this->plugin->gameLevelManager->handleSetSignJoin2 ( $player, $arenaName, $block );
						}
					}
				}
				if ($line1 != null && $line1 === "hungergame") {
					if ($line2 != null && $line2 === "level") {
						if ($line3 != null && $line3 === "stat") {
							$arenaName = $line4;
							$this->plugin->gameLevelManager->handleSetSignJoin2 ( $player, $arenaName, $block );
						}
					}
				}
			}
		}
	}
}
