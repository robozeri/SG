<?php

namespace hungergames\statue;

use hungergames\main\HungerGamesPlugIn;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\Player;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\math\Vector2;
use pocketmine\utils\Config;


class StatueListener implements Listener {
	private $plugin;
	private $builder;
	public function __construct(HungerGamesPlugIn $plugin) {
		$this->plugin = $plugin;
		$this->builder = new StatueBuilder ( $this->plugin );
	}
	public function onPlayerJoin(PlayerJoinEvent $event) {
		$this->plugin->log ( "[HG] StatueListener: onPlayerJoin for player " . $event->getPlayer ()->getName () );
		$player = $event->getPlayer ();
		if (! isset ( $this->plugin->getStatueManager ()->npcsSpawns [$player->getName ()] )) {
			$this->plugin->getStatueManager->npcsSpawns [$player->getName ()] = $player->getName ();
			$builder = new StatueBuilder ( $this->plugin );
			$builder->displayStatues ( $player, $this->plugin->statueManager->npcs );
		}
	}
	
	/**
	 *
	 * @param PlayerRespawnEvent $event        	
	 *
	 *
	 */
	public function onPlayerRespawn(PlayerRespawnEvent $event) {
		$this->plugin->log ( "[HG] StatueListener: onPlayerRespawn for player " . $event->getPlayer ()->getName () );
		$player = $event->getPlayer ();
		if (! isset ( $this->plugin->getStatueManager ()->npcsSpawns [$player->getName ()] )) {
			$builder = new StatueBuilder ( $this->plugin );
			$builder->displayStatues ( $player, $this->plugin->statueManager->npcs );
			// $this->builder->spawnHallOfFrameWinners ();
			$this->plugin->getStatueManager->npcsSpawns [$player->getName ()] = $player->getName ();
		}
	}
	public function onPlayerMove(PlayerMoveEvent $event) {
		$player = $event->getPlayer ();
		if ($player instanceof Player) {	
			if ($player->getLevel ()->getName () === $this->plugin->vipLevelName) {				
				if (isset ( $this->plugin->statueManager->npcsSpawns [$player->getName ()] )) {
					return ;
				}
				foreach ( $this->plugin->statueManager->npcs as $xnpc ) {
					if ($xnpc instanceof StatueModel) {
						if ($player->getLevel ()->getName () === $xnpc->levelName) {
							$statuePos = $xnpc->position;
							$pp = new Vector2 ( round ( $player->x ), round ( $player->z ) );
							$npc = new Vector2 ( $statuePos->x, $statuePos->z );
							$dff = abs ( round ( $pp->distance ( $npc ) ) );
							if ($dff < 12 || $dff == 0) {
								$builder = new StatueBuilder ( $this->plugin );
								$builder->displayStatues ( $player, $this->plugin->statueManager->npcs );
								$this->plugin->statueManager->npcsSpawns [$player->getName ()] = $player->getName ();
							}
						}
					}
				}
			}
		}
	}
	
	/**
	 *
	 * @param PlayerQuitEvent $event        	
	 */
	public function onQuit(PlayerQuitEvent $event) {
		if ($event->getPlayer () instanceof Player) {
			$player = $event->getPlayer ();
			unset ( $this->plugin->getStatueManager ()->npcsSpawns [$event->getPlayer ()->getName ()] );
		}
	}

	public function onPlayerDeath(PlayerDeathEvent $event) {
		if ($event->getEntity () instanceof Player) {
			$player = $event->getEntity ();
			unset ( $this->plugin->getStatueManager ()->npcsSpawns [$player->getName ()] );
		}
	}
	public function onPlayerKicked(PlayerKickEvent $event) {
		if ($event->getPlayer () instanceof Player) {
			$player = $event->getPlayer ();
			unset ( $this->plugin->getStatueManager ()->npcsSpawns [$event->getPlayer ()->getName ()] );
		}
	}
	public function onPlayerInteract(PlayerInteractEvent $event) {
		$block = $event->getBlock ();
		if ($event->getPlayer () instanceof Player) {
			$player = $event->getPlayer ();
			$this->plugin->statueManager->handlePlayerTapOnStatueBlock ( $event->getPlayer (), $block );
		}
	}
}
