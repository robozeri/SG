<?php

namespace hungergames\score;

use hungergames\main\HungerGamesPlugIn;
use hungergames\level\GameLevelModel;
use pocketmine\entity\Arrow;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\ProjectileHitEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;


class ScoreListener implements Listener {
	public $plugin;
	public function __construct(HungerGamesPlugIn $plugin) {
		$this->plugin = $plugin;
	}
	public function onProjectileLaunch(ProjectileLaunchEvent $event) {
		try {
			$player = $event->getEntity ()->shootingEntity;
			if (($player instanceof Player) and ($event->getEntity () instanceof Arrow)) {
				if (($event->getEntity () instanceof Arrow)) {
					foreach ( $this->plugin->gameLevelManager->levels as &$lv ) {
						if ($lv instanceof GameLevelModel) {
							if (count ( $lv->joinedPlayers ) === 0) {
								continue;
							}
							if (isset ( $lv->currentMap )) {
								if (isset ( $lv->currentMap->playerscores [$player->getName ()] )) {
									$lv->currentMap->shooters [$event->getEntity ()->getId ()] = $player->getName ();
									$scores = $lv->currentMap->playerscores [$player->getName ()];
									$scores ["shots"] = $scores ["shots"] + 1;
									$lv->currentMap->playerscores [$player->getName ()] = $scores;
									break;
								}
							}
						}
					}
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
	}
	
	/**
	 *
	 * @param ProjectileHitEvent $event        	
	 */
	public function onProjectTileHit(ProjectileHitEvent $event) {
		try {
			foreach ( $this->plugin->gameLevelManager->levels as &$lv ) {
				if ($lv instanceof GameLevelModel) {
					if (count ( $lv->joinedPlayers ) === 0) {
						continue;
					}
					// skip on-level related
					// if ($event->getEntity ()->getLevel ()->getName () != $lv->levelName) {
					// continue;
					// }
					if (isset ( $lv->currentMap )) {
						if (isset ( $lv->currentMap->shooters [$event->getEntity ()->getId ()] )) {
							$shooterName = $lv->currentMap->shooters [$event->getEntity ()->getId ()];
							unset ( $lv->currentMap->shooters [$event->getEntity ()->getId ()] );
							$scores = $lv->currentMap->playerscores [$shooterName];
							$scores ["hits"] = $scores ["hits"] + 1;
							$lv->currentMap->playerscores [$shooterName] = $scores;
							$lv->currentMap->killedPlayers [$shooterName] = $shooterName;
							break;
						}
					}
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
	}
	
	/**
	 *
	 * @param EntityDeathEvent $event        	
	 */
	public function onPlayerDeath(PlayerDeathEvent $event) {
		$this->plugin->log ( "[HG] ScoreListener onPlayerDeath: " . $event->getEventName () );
		try {
			if ($event->getEntity () instanceof Player) {
				$player = $event->getEntity ();
				if ($player->getLastDamageCause () === EntityDamageEvent::CAUSE_ENTITY_ATTACK || $player->getLastDamageCause () === EntityDamageEvent::CAUSE_PROJECTILE) {
					foreach ( $this->plugin->getAvailableLevels () as &$lv ) {
						if (count ( $lv->joinedPlayers ) == 0) {
							continue;
						}
						if ($lv instanceof GameLevelModel) {
							if (isset ( $lv->currentMap )) {
								if (isset ( $lv->currentMap->killedPlayers [$player->getName ()] )) {
									$killerName = $lv->currentMap->killedPlayers [$player->getName ()];
									if (isset ( $lv->currentMap->playerscores [$killerName] )) {
										$scores = $lv->currentMap->playerscores [$killerName];
										$scores ["kills"] = $scores ["kills"] + 1;
										$lv->currentMap->playerscores [$killerName] = $scores;
									}
									break;
								}
							}
						}
					}
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
	}
	
	/**
	 *
	 * @param EntityDamageEvent $event        	
	 */
	public function onPlayerHurt(EntityDamageEvent $event) {
		try {
			if ($event instanceof EntityDamageByEntityEvent) {
				$entity = $event->getEntity ();
				$killer = $event->getDamager ();
				if ($killer instanceof Player and $entity instanceof Player) {
					foreach ( $this->plugin->gameLevelManager->levels as &$lv ) {
						if ($lv instanceof GameLevelModel) {
							if (count ( $lv->joinedPlayers ) === 0) {
								continue;
							}
							// skip on-level related
							// if ($entity->getLevel ()->getName () != $lv->levelName) {
							// continue;
							// }
							if (isset ( $lv->currentMap )) {
								if (isset ( $lv->currentMap->playerscores [$killer->getName ()] )) {
									$scores = $lv->currentMap->playerscores [$killer->getName ()];
									$scores ["hits"] = $scores ["hits"] + 1;
									if ($entity->getHealth () === 0) {
										$scores ["kills"] = $scores ["kills"] + 1;
									}
									$lv->currentMap->playerscores [$killer->getName ()] = $scores;
								}
								$lv->currentMap->killedPlayers [$entity->getName ()] = $killer->getName ();
							}
							break;
						}
					}
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
	}
	
	/**
	 *
	 * @param PlayerQuitEvent $event        	
	 */
	public function onQuit(PlayerQuitEvent $event) {
		if ($event->getPlayer () instanceof Player) {
			$player = $event->getPlayer ();
		}
	}
	public function onPlayerKicked(PlayerKickEvent $event) {
		if ($event->getPlayer () instanceof Player) {
			$player = $event->getPlayer ();
		}
	}
}
