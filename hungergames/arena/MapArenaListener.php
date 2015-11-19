<?php

namespace hungergames\arena;

use hungergames\main\HungerGamesPlugIn;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\Player;
use pocketmine\event\block\SignChangeEvent;

class MapArenaListener implements Listener {
	public $plugin;
	public function __construct(HungerGamesPlugIn $plugin) {
		$this->plugin = $plugin;
	}
	public function onPlayerInteract(PlayerInteractEvent $event) {
		if ($event->getPlayer () instanceof Player) {
			$player = $event->getPlayer ();
			$block = $event->getBlock ();
			$this->plugin->arenaManager->handleTapOnArenaSigns ( $player, $block );
		}
	}
	
	/**
	 *
	 * @param BlockBreakEvent $event        	
	 */
	public function onBlockBreak(BlockBreakEvent $event) {
		if ($event->getPlayer () instanceof Player) {
			if ($this->plugin->arenaManager->setupModeAction === MapArenaManager::COMMAND_SETUP_WAND_ARENA_MAIN 
					|| $this->plugin->arenaManager->setupModeAction === MapArenaManager::COMMAND_SETUP_WAND_ARENA_MAIN_PLAYER_SPAWNS
					|| $this->plugin->arenaManager->setupModeAction === MapArenaManager::COMMAND_SETUP_WAND_ARENA_DEATH_MATCH 
					|| $this->plugin->arenaManager->setupModeAction === MapArenaManager::COMMAND_SETUP_WAND_ARENA_DEATH_MATCH_SPAWN) {
				$this->plugin->arenaManager->handleBlockBreakSelection ( $event->getPlayer (), $event->getBlock () );
			}
		}
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
					if ($line2 != null && $line2 === "arena") {
						if ($line3 != null && $line3 === "join") {
							$arenaName = $line4;
							$this->plugin->arenaManager->handleSetSignJoin($player, $arenaName, $block );
						}
					}
				}
				if ($line1 != null && $line1 === "hungergame") {
					if ($line2 != null && $line2 === "arena") {
						if ($line3 != null && $line3 === "vote") {
							$arenaName = $line4;
							$this->plugin->arenaManager->handleSetSignVote($player, $arenaName, $block );
						}
					}
				}
	
				if ($line1 != null && $line1 === "hungergame") {
					if ($line2 != null && $line2 === "arena") {
						if ($line3 != null && $line3 === "exit") {
							$arenaName = $line4;
							$this->plugin->arenaManager->handleSetSignExit($player, $arenaName, $block);
						}
					}
				}
				
				if ($line1 != null && $line1 === "hungergame") {
					if ($line2 != null && $line2 === "arena") {
						if ($line3 != null && $line3 === "stat") {
							$arenaName = $line4;
							$this->plugin->arenaManager->handleSetSignStat($player, $arenaName, $block);
						}
					}
				}
			}
		}
	}
	
}
