<?php

namespace hungergames\level;

use hungergames\arena\MapArenaModel;
use hungergames\main\HungerGamesPlugIn;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\level\sound\LaunchSound;
use hungergames\main\HungerGameKit;
use pocketmine\item\Item;
use hungergames\task\HungerGamesRecordLossTask;
use hungergames\task\HungerGamesPortalResetTask;


class GameLevelManager {
	const COMMAND_SETUP_WAND_GAME_LEVEL = "setup_wand_game_level";
	const COMMAND_SETUP_WAND_GAME_LEVEL_GATE = "setup_wand_game_level_gate";
	const ACTION_GATE_OPEN = "gate_open";
	const ACTION_GATE_CLOSE = "gate_close";
	public $plugin;
	public $levelsBackup = [ ];
	public $levels = [ ];
	public $setupModeAction = "";
	public $setupModeData = "";
	public function __construct(HungerGamesPlugIn $plugin) {
		$this->plugin = $plugin;
	}
	public function resetLevel($levelName) {
		unset ( $this->levels [$levelName] );
		$this->levels [$levelName] = clone ($this->levelsBackup [$levelName]);
		$this->plugin->log ( "* ResetLevel: " . $levelName . "* " );
	}
	public static function listLevels(Player $sender, $path) {
		$xpath = $path . GameLevelModel::DIR_LEVEL_DATA;
		if (! file_exists ( $xpath )) {
			@mkdir ( $xpath, 0755, true );
			return null;
		}
		$output = "List of Levels:\n";
		$handler = opendir ( $xpath );
		$i = 1;
		while ( ($filename = readdir ( $handler )) !== false ) {
			if ($filename != "." && $filename != "..") {
				$data = new Config ( $xpath . $filename, Config::YAML );
				$name = str_replace ( ".yml", "", $filename );
				$id = $data->get ( "id" );
				$levelname = $data->get ( "levelName" );
				$pos = new Position ( $data->get ( "positionX" ), $data->get ( "positionY" ), $data->get ( "positionZ" ) );
				$output .= $i . ". " . $name . " " . $pos->x . " " . $pos->y . " " . $pos->z . "\n";
				$i ++;
			}
		}
		closedir ( $handler );
		return $output;
	}
	public function &session(Player $sender) {
		if (! isset ( $this->plugin->sessions [$sender->getName ()] )) {
			$this->plugin->sessions [$sender->getName ()] = array (
					"selection" => array (
							false,
							false 
					),
					"arena-name" => false,
					"arena-type" => false,
					"action" => false,
					"wand-usage" => false,
					"edit-mode" => false 
			);
		}
		return $this->plugin->sessions [$sender->getName ()];
	}
	public function handleSetSignJoin(Player $player, $arenaName, $block) {
		if (! $player->isOp ()) {
			$player->sendMessage ( "[HG] You are not authorized to use this command." );
			return;
		}
		if (! isset ( $this->plugin->gameLevelManager->levels [$arenaName] )) {
			$player->sendMessage ( "[HG] Level doesn't exist!" );
			return;
		}
		$arena = $this->plugin->gameLevelManager->levels [$arenaName];
		$arena->signJoin = new Position ( $block->x, $block->y, $block->z );
		$this->plugin->gameLevelManager->levels [$arenaName] = $arena;
		$arena->save ( $this->plugin->getDataFolder () );
		$player->sendMessage ( "[HG] Level [Join Sign] set [" . TextFormat::GOLD . round ( $arena->signJoin->x ) . " " . round ( $arena->signJoin->y ) . " " . round ( $arena->signJoin->z ) . "]" );
	}
	public function handleSetSignJoin2(Player $player, $arenaName, $block) {
		if (! $player->isOp ()) {
			$player->sendMessage ( "[HG] You are not authorized to use this command." );
			return;
		}
		if (! isset ( $this->plugin->gameLevelManager->levels [$arenaName] )) {
			$player->sendMessage ( "[HG] Level doesn't exist!" );
			return;
		}
		$arena = $this->plugin->gameLevelManager->levels [$arenaName];
		$arena->signJoin2 = new Position ( $block->x, $block->y, $block->z );
		$this->plugin->gameLevelManager->levels [$arenaName] = $arena;
		$arena->save ( $this->plugin->getDataFolder () );
		$player->sendMessage ( "[HG] Level [Join2 Sign] set [" . TextFormat::GOLD . round ( $arena->signJoin2->x ) . " " . round ( $arena->signJoin2->y ) . " " . round ( $arena->signJoin2->z ) . "]" );
	}
	public function handleSetSignExit(Player $player, $arenaName, $block) {
		if (! $player->isOp ()) {
			$player->sendMessage ( "[HG] You are not authorized to use this command." );
			return;
		}
		if (! isset ( $this->plugin->gameLevelManager->levels [$arenaName] )) {
			$player->sendMessage ( "[HG] Level doesn't exist!" );
			return;
		}
		$arena = $this->plugin->gameLevelManager->levels [$arenaName];
		$arena->signExit = new Position ( $block->x, $block->y, $block->z );
		$this->plugin->gameLevelManager->levels [$arenaName] = $arena;
		$arena->save ( $this->plugin->getDataFolder () );
		$player->sendMessage ( "[HG] Level [Exit Sign] set [" . TextFormat::GOLD . round ( $arena->signExit->x ) . " " . round ( $arena->signExit->y ) . " " . round ( $arena->signExit->z ) . "]" );
	}
	public function handleSetSignStat(Player $player, $arenaName, $block) {
		if (! $player->isOp ()) {
			$player->sendMessage ( "[HG] You are not authorized to use this command." );
			return;
		}
		if (! isset ( $this->plugin->gameLevelManager->levels [$arenaName] )) {
			$player->sendMessage ( "[HG] Level doesn't exist!" );
			return;
		}
		$arena = $this->plugin->gameLevelManager->levels [$arenaName];
		$arena->signStats = new Position ( $block->x, $block->y, $block->z );
		$this->plugin->gameLevelManager->levels [$arenaName] = $arena;
		$arena->save ( $this->plugin->getDataFolder () );
		$player->sendMessage ( "[HG] Level [Exit Sign] set [" . TextFormat::GOLD . round ( $arena->signStats->x ) . " " . round ( $arena->signStats->y ) . " " . round ( $arena->signStats->z ) . "]" );
	}
	
	/**
	 *
	 * @param Player $player        	
	 * @param
	 *        	$b
	 * @internal param BlockBreakEvent $event
	 */
	public function handleBlockBreakSelection(Player $player, $b) {
		$output = "";
		if ($player instanceof Player) {
			if ($this->plugin->gameLevelManager->setupModeAction === GameLevelManager::COMMAND_SETUP_WAND_GAME_LEVEL || $this->plugin->gameLevelManager->setupModeAction === GameLevelManager::COMMAND_SETUP_WAND_GAME_LEVEL_GATE) {
				$session = &$this->plugin->gameLevelManager->session ( $player );
				if ($session != null && $session ["wand-usage"] === true) {
					if (! isset ( $session ["wand-pos1"] ) || $session ["wand-pos1"] === null) {
						$session ["wand-pos1"] = $b;
						if (! isset ( $this->plugin->gameLevelManager->levels [$this->plugin->gameLevelManager->setupModeData] ) || $this->plugin->gameLevelManager->setupModeData === "") {
							$player->sendMessage ( "[HG] " . $this->plugin->gameLevelManager->setupModeData . " level not found! " );
							return;
						}
						$arena = $this->plugin->gameLevelManager->levels [$this->plugin->gameLevelManager->setupModeData];
						if ($this->plugin->gameLevelManager->setupModeAction === GameLevelManager::COMMAND_SETUP_WAND_GAME_LEVEL) {
							$arena->portalEnterPos1 = new Position ( round ( $b->x ), round ( $b->y ), round ( $b->z ) );
						}
						if ($this->plugin->gameLevelManager->setupModeAction === GameLevelManager::COMMAND_SETUP_WAND_GAME_LEVEL_GATE) {
							$arena->gatePos1 = new Position ( round ( $b->x ), round ( $b->y ), round ( $b->z ) );
						}
						$arena->save ( $this->plugin->getDataFolder () );
						$this->plugin->gameLevelManager->levels [$this->plugin->gameLevelManager->setupModeData] = $arena;
						$player->sendMessage ( TextFormat::WHITE . "[HG] " . $this->plugin->gameLevelManager->setupModeData . " position#1 set " . " [" . TextFormat::GOLD . round ( $b->x ) . " " . round ( $b->y ) . " " . round ( $b->z ) . TextFormat::WHITE . "]" );
						return;
					}
					if (! isset ( $session ["wand-pos2"] ) || $session ["wand-pos2"] === null) {
						$session ["wand-pos2"] = $b;
						$arena = $this->plugin->gameLevelManager->levels [$this->plugin->gameLevelManager->setupModeData];
						if ($this->plugin->gameLevelManager->setupModeAction === GameLevelManager::COMMAND_SETUP_WAND_GAME_LEVEL) {
							$arena->portalEnterPos2 = new Position ( round ( $b->x ), round ( $b->y ), round ( $b->z ) );
						}
						if ($this->plugin->gameLevelManager->setupModeAction === GameLevelManager::COMMAND_SETUP_WAND_GAME_LEVEL_GATE) {
							$arena->gatePos2 = new Position ( round ( $b->x ), round ( $b->y ), round ( $b->z ) );
						}
						$arena->save ( $this->plugin->getDataFolder () );
						$player->sendMessage ( TextFormat::WHITE . "[HG] " . $this->plugin->gameLevelManager->setupModeData . " position#2 set " . " [" . TextFormat::GOLD . round ( $b->x ) . " " . round ( $b->y ) . " " . round ( $b->z ) . TextFormat::WHITE . "]" );
						$this->plugin->setupModeAction = "";
						$this->plugin->setupModeData = "";
						return;
					}
				}
			}
		}
	}
	public function handleSetLevelEntranceCommand(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg setlevelenter [level name].\n";
			$player->sendMessage ( $output );
			return;
		}
		$arenaName = $args [1];
		if (! isset ( $this->plugin->gameLevelManager->levels [$arenaName] )) {
			$output = "[HG] Level Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$arena = $this->plugin->gameLevelManager->levels [$arenaName];
		if ($arena instanceof GameLevelModel) {
			$arena->enterLevelName = $player->getLevel ()->getName ();
			$arena->enterpos = $player->getPosition ();
			$this->plugin->gameLevelManager->levels [$arenaName] = $arena;
			$arena->save ( $this->plugin->getDataFolder () );
		}
		$player->sendMessage ( TextFormat::GREEN . "[HG] Success! Level [Entrance Position] set :" . TextFormat::GOLD . round ( $arena->enterpos->x ) . " " . round ( $arena->enterpos->y ) . " " . round ( $arena->enterpos->z ) );
	}
	public function handleSetLevelExitCommand(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg setlevelexit [level name].\n";
			$player->sendMessage ( $output );
			return;
		}
		$arenaName = $args [1];
		if (! isset ( $this->plugin->gameLevelManager->levels [$arenaName] )) {
			$output = "[HG] Level Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$arena = $this->plugin->gameLevelManager->levels [$arenaName];
		if ($arena instanceof GameLevelModel) {
			$arena->exitLevelName = $player->getLevel ()->getName ();
			$arena->exitpos = $player->getPosition ();
			$this->plugin->gameLevelManager->levels [$arenaName] = $arena;
			$arena->save ( $this->plugin->getDataFolder () );
		}
		$player->sendMessage ( TextFormat::GREEN . "[HG] Success! Level [Exit Position] set :" . TextFormat::GOLD . round ( $arena->exitpos->x ) . " " . round ( $arena->exitpos->y ) . " " . round ( $arena->exitpos->z ) );
	}
	public function handleLevelWandCommand(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg levelwand [level name].\n";
			$player->sendMessage ( $output );
			return;
		}
		if (! isset ( $this->plugin->gameLevelManager->levels [$args [1]] )) {
			$output = "[HG] Level Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$session = &$this->session ( $player );
		$this->handleDeSelCommand ( $player );
		$session ["wand-usage"] = true;
		if (! empty ( $player->getInventory () ) && $player->getInventory ()->getItemInHand ()->getId () != 292) {
			$player->getInventory ()->setItemInHand ( new Item ( 292 ) );
		}
		$arenaName = $args [1];
		$this->plugin->gameLevelManager->setupModeAction = self::COMMAND_SETUP_WAND_GAME_LEVEL;
		$this->plugin->gameLevelManager->setupModeData = $arenaName;
		$player->sendMessage ( TextFormat::GRAY . "[HG] Selected " . TextFormat::YELLOW . "[Level] " . TextFormat::GRAY . " Wand" );
		$player->sendMessage ( TextFormat::WHITE . "[HG] Break a block to set Pos#1, then highest diagonal block for Pos#2" );
	}
	public function handleLevelGateWandCommand(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg levelgatewand [level name].\n";
			$player->sendMessage ( $output );
			return;
		}
		if (! isset ( $this->plugin->gameLevelManager->levels [$args [1]] )) {
			$output = "[HG] Level Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$session = &$this->session ( $player );
		$this->handleDeSelCommand ( $player );
		$session ["wand-usage"] = true;
		// $player->sendMessage ( "[HG] Portal Wand selected" );
		if (! empty ( $player->getInventory () ) && $player->getInventory ()->getItemInHand ()->getId () != 292) {
			$player->getInventory ()->setItemInHand ( new Item ( 292 ) );
		}
		$arenaName = $args [1];
		$this->plugin->gameLevelManager->setupModeAction = self::COMMAND_SETUP_WAND_GAME_LEVEL_GATE;
		$this->plugin->gameLevelManager->setupModeData = $arenaName;
		$player->sendMessage ( TextFormat::GRAY . "[HG] Selected " . TextFormat::YELLOW . "[Level Gate] " . TextFormat::GRAY . " Wand" );
		$player->sendMessage ( TextFormat::WHITE . "[HG] Break a block to set Pos#1, then highest diagonal block for Pos#2" );
	}
	public function handleDeSelCommand($sender) {
		$session = &$this->session ( $sender );
		$session ["selection"] = array (
				false,
				false 
		);
		unset ( $session ["wand-pos1"] );
		unset ( $session ["wand-pos2"] );
		unset ( $session ["arena-name"] );
		unset ( $session ["action"] );
		// also clear these two
		$this->plugin->setupModeAction = "";
		$this->plugin->setupModeData = "";
		$output = "Selection cleared.\n";
		$sender->sendMessage ( $output );
	}
	public function createLevel(Player $sender, array $args) {
		if (! $sender->isOp ()) {
			$sender->sendMessage ( "[HG] You are not authorized to use this command." );
			return;
		}
		if (count ( $args ) != 2) {
			$sender->sendMessage ( "[HG] Usage:/bh newarena [name]" );
			return;
		}
		$sender->sendMessage ( "[HG] Creating new Arena" );
		$defenceName = $args [1];
		if (isset ( $this->plugin->playArenas [$defenceName] )) {
			$sender->sendMessage ( "[HG] Warning! arena ALREADY Exist!. please use another name!" );
			return;
		}
		
		$name = $args [1];
		$position = $sender->getPosition ();
		$leveName = $sender->getLevel ()->getName ();
		$newArena = new GameLevelModel ( $this->plugin, $name );
		$newArena->save ( $this->plugin->getDataFolder () );
		$this->plugin->arenas [$name] = $newArena;
		$sender->sendMessage ( "[HG] New Arena Saved!" );
		return;
	}
	public function unlinkRecursive($dir, $deleteRootToo) {
		if (! $dh = @opendir ( $dir )) {
			return;
		}
		while ( false !== ($obj = readdir ( $dh )) ) {
			if ($obj == '.' || $obj == '..') {
				continue;
			}
			
			if (! @unlink ( $dir . '/' . $obj )) {
				$this->unlinkRecursive ( $dir . '/' . $obj, true );
			}
		}
		
		closedir ( $dh );
		
		if ($deleteRootToo) {
			@rmdir ( $dir );
		}
		
		return;
	}
	public function preloadArenas() {
		$path = $this->plugin->getDataFolder () . GameLevelModel::DIR_LEVEL_DATA;
		if (! file_exists ( $path )) {
			@mkdir ( $path, 0755, true );
			foreach ( $this->plugin->getResources () as $resource ) {
				if (! $resource->isDir ()) {
					$fp = $resource->getPathname ();
					if (strpos ( $fp, "level_data" ) != false) {
						$this->plugin->info ( TextFormat::AQUA . " *** setup default [LEVEL Data]: " . $resource->getFilename () );
						copy ( $resource->getPathname (), $path . $resource->getFilename () );
					}
				}
			}
		}
		
		$this->levels = GameLevelModel::preloadLevels ( $this->plugin );
		$this->levelsBackup = $this->levels;
	}
	public static function convertArrayToPositions($values) {
		$positions = [ ];
		if ($values != null && count ( $values ) == 3) {
			foreach ( $values as $v ) {
				$positions [] = new Position ( $v [0], $v [1], $v [2] );
			}
		}
		return $positions;
	}
	public function handleAdditionOfLevelArenaCommand(Player $player, $args) {
		if (count ( $args ) != 3) {
			$output = TextFormat::YELLOW . "[HG] Usage: /hg addlevelarena [level name] [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		$levelName = $args [1];
		$arenaName = $args [2];
		if (! isset ( $this->plugin->arenaManager->arenas [$arenaName] )) {
			$output = TextFormat::YELLOW . "[HG] Failed! Arena Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$arena = $this->plugin->arenaManager->arenas [$arenaName];
		if (! isset ( $this->plugin->gameLevelManager->levels [$levelName] )) {
			$output = TextFormat::YELLOW . "[HG] Failed! Level Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$updlevel = $this->plugin->gameLevelManager->levels [$levelName];
		if ($updlevel instanceof GameLevelModel) {
			foreach ( $updlevel->maps as $m ) {
				if ($m === $arenaName) {
					$player->sendMessage ( TextFormat::YELLOW . "[HG] Arena [$arenaName] Already Exist in level [$levelName]" );
					break;
				}
			}
			$updlevel->maps [] = $arenaName;
			$this->plugin->gameLevelManager->levels [$levelName] = $updlevel;
			$updlevel->save ( $this->plugin->getDataFolder () );
		}
		$player->sendMessage ( TextFormat::GREEN . "[HG] Success! Added [" . TextFormat::GOLD . $arenaName . TextFormat::GREEN . "] arena to level: [" . $levelName . "]" );
	}
	public function handleRemovalOfLevelArenaCommand(Player $player, $args) {
		if (count ( $args ) != 3) {
			$output = TextFormat::YELLOW . "[HG] Usage: /hg dellevelarena [level name] [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		$levelName = $args [1];
		$arenaName = $args [2];
		if (! isset ( $this->plugin->arenaManager->arenas [$arenaName] )) {
			$output = TextFormat::YELLOW . "[HG] Failed! Arena Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$arena = $this->plugin->arenaManager->arenas [$arenaName];
		if (! isset ( $this->plugin->gameLevelManager->levels [$levelName] )) {
			$output = TextFormat::YELLOW . "[HG] Failed! Level Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$updlevel = $this->plugin->gameLevelManager->levels [$levelName];
		if ($updlevel instanceof GameLevelModel) {
			// check if map name exist
			$found = false;
			$newList = $updlevel->maps;
			$updlevel->maps = [ ];
			foreach ( $newList as $m ) {
				if ($m === $arenaName) {
					$found = true;
				} else {
					$updlevel->maps [] = $m;
				}
			}
			if ($found) {
				$this->plugin->gameLevelManager->levels [$levelName] = $updlevel;
				$updlevel->save ( $this->plugin->getDataFolder () );
				$player->sendMessage ( TextFormat::GREEN . "[HG] Success! Removed [" . TextFormat::GOLD . $arenaName . TextFormat::GREEN . "] arena to level: [" . $levelName . "]" );
			} else {
				$output = TextFormat::YELLOW . "[HG] Failed! Arena Name not found in level.\n";
				$player->sendMessage ( $output );
			}
		}
	}
	
	/**
	 * Create Map Session when minimal players joined35;;;
	 *
	 * @param unknown $arenaName        	
	 */
	public function createMapSession($arenaName) {
		$arena = $this->plugin->playArenas [$arenaName];
		if (! isset ( $this->plugin->arenaSessions [$arenaName] )) {
			$competition = $arenaName . "_live";
			Server::getInstance ()->loadLevel ( $arenaName );
			$level = $this->plugin->getServer ()->getLevelByName ( $arenaName );
			$this->addWorld ( $level, $arenaName, $competition );
			$this->plugin->arenaSessions [$competition] = $arena;
		}
	}
	
	/**
	 * Delete Map Session
	 *
	 * @param unknown $arenaName        	
	 */
	public function deleteMapSession($arenaName) {
		if (isset ( $this->plugin->arenaSessions [$arenaName] )) {
			unset ( $this->plugin->arenaSessions [$arenaName] );
			$competition = $arenaName . "_live";
			Server::getInstance ()->loadLevel ( $arenaName );
			$level = $this->plugin->getServer ()->getLevelByName ( $arenaName );
			$this->deleteWorld ( $level, $competition );
		}
	}
	public function addWorld(Level $level, $base, $competition) {
		$fileutil = new FileUtil ();
		$source = $level->getServer ()->getDataPath () . "worlds/" . $base . "/";
		$dest = $level->getServer ()->getDataPath () . "worlds/" . $competition . "/";
		$this->deleteWorld ( $level, $competition );
		if ($fileutil->xcopy ( $source, $dest )) {
			$this->plugin->log ( "New Hunger Game Competition World [" . $competition . "] created!" );
			try {
				$level->getServer ()->loadLevel ( $competition );
			} catch ( \Exception $e ) {
				$this->plugin->printError ( $e );
			}
			$this->plugin->log ( "loaded world: " . $competition );
		} else {
			$this->plugin->log ( "problem creating new map. please contact administrator." );
		}
	}
	public function deleteWorld(Level $level, $worldname) {
		Server::getInstance ()->unloadLevel ( $level, true );
		$levelpath = $level->getServer ()->getDataPath () . "worlds/" . $worldname . "/";
		$fileutil = new FileUtil ();
		$fileutil->unlinkRecursive ( $levelpath, true );
		$this->plugin->log ( "Competition world [" . $worldname . "] deleted forever!" );
	}
	public function teleportWorld(Player $p, $levelname) {
		if (! $p->getServer ()->isLevelLoaded ( $levelname )) {
			$ret = $p->getServer ()->loadLevel ( $levelname );
			if (! $ret) {
				$p->sendMessage ( "Error, unable load World: " . $levelname . ". please contact server administrator." );
				return;
			}
		}
		if (! $p->getServer ()->isLevelGenerated ( $levelname )) {
			$p->sendMessage ( "new world generation is not ready! try later." );
			return;
		}
		$level = $p->getServer ()->getLevelByName ( $levelname );
		if ($level == null) {
			$p->sendMessage ( "Error, unable access world: " . $levelname . ". please contact server administrator." );
			return;
		}
		$p->sendMessage ( "Teleporting to [" . $levelname . "]" );
		$p->teleport ( $level->getSafeSpawn () );
	}
	private function log($msg) {
		$this->plugin->getLogger ()->debug ( $msg );
	}
	/**
	 *
	 * @param Player $player        	
	 */
	public function handlePlayerDeath(Player $player) {
		$isInGamePlayer = false;
		$this->plugin->log ( "handle player death - | " . $player->getName () . "| alive: " . $player->isAlive () );
		if (! empty ( $player )) {
			foreach ( $this->plugin->gameLevelManager->levels as &$lv ) {
				if ($lv instanceof GameLevelModel) {
					if (isset ( $lv->joinedPlayers [$player->getName ()] )) {
						$message = "[HG] regret " . TextFormat::AQUA . "[" . $player->getName () . "] was " . TextFormat::RED . "killed.";
						$player->getServer ()->broadcastMessage ( $message, $lv->joinedPlayers );
						$isInGamePlayer = true;
						unset ( $lv->joinedPlayers [$player->getName ()] );
						if (! empty ( $lv->currentMap )) {
							if (isset ( $lv->currentMap->livePlayers [$player->getName ()] )) {
								unset ( $lv->currentMap->livePlayers [$player->getName ()] );
								$recordloss = new HungerGamesRecordLossTask ( $this->plugin, $lv, $player->getName () );
								$this->plugin->getServer ()->getScheduler ()->scheduleDelayedTask ( $recordloss, mt_rand ( 1, 5 ) );
							}
							if (isset ( $lv->currentMap->joinedPlayers [$player->getName ()] )) {
								unset ( $lv->currentMap->joinedPlayers [$player->getName ()] );
							}
							if (isset ( $lv->currentMap->votedPlayers [$player->getName ()] )) {
								unset ( $lv->currentMap->votedPlayers [$player->getName ()] );
							}
						}
						
						$this->plugin->log ( "[HG]gamelevelmanager handlePlayerDeath: " . $player->getName () . " |  remains: " . count ( $lv->joinedPlayers ) );
						if (count ( $lv->joinedPlayers ) === 0 || count ( $lv->joinedPlayers ) === 1) {
							if ($lv->currentStep === GameLevelModel::STEP_HUNTING || $lv->currentStep === GameLevelModel::STEP_DEATH_MATCH) {
								$this->plugin->openGate ( $lv );
								$lv->status = GameLevelModel::STATUS_AVAILABLE;
								$lv->currentStep = GameLevelModel::STEP_GAME_OVER;
								$this->plugin->log ( "[HG]gamelevelmanager handlePlayerDeath: gameover :" . $lv->currentStep );
							}
						}
						break;
					}
				}
			}
			if ($player->isAlive ()) {
				$player->kill ();
			}
		}
		
		return $isInGamePlayer;
	}
	
	/**
	 *
	 * @param Player $player        	
	 */
	public function handlePlayerInGameChat(Player $player, $msg) {
		$playerInGame = false;
		$InGamePlayers = [ ];
		$this->plugin->log ( " handlePlayerInGameChat - | " . $player->getName () . "| msg: " . $msg );
		if (! empty ( $player ) && ! empty ( $msg )) {
			foreach ( $this->plugin->gameLevelManager->levels as &$lv ) {
				if ($lv instanceof GameLevelModel) {
					if (isset ( $lv->joinedPlayers [$player->getName ()] )) {
						$msg = TextFormat::GRAY . "[HG-" . TextFormat::RED . $lv->type . TextFormat::GRAY . "]" . TextFormat::YELLOW . $player->getName () . ">" . TextFormat::WHITE . $msg;
						$player->getServer ()->broadcastMessage ( $msg, $lv->joinedPlayers );
						$playerInGame = true;
						$this->plugin->log ( " handlePlayerInGameChat - in-game-message " . $msg . " send to " . count ( $lv->joinedPlayers ) );
					}
					if (count ( $lv->joinedPlayers ) > 0) {
						$InGamePlayers = array_merge ( $lv->joinedPlayers, $InGamePlayers );
					}
				}
			}
			if (! $playerInGame) {
				$broadcastplayers = array_diff ( $this->plugin->getServer ()->getOnlinePlayers (), $InGamePlayers );
				$player->getServer ()->broadcastMessage ( $msg, $broadcastplayers );
				$this->plugin->log ( " handlePlayerInGameChat - not-in-game-message " . $msg . " send to " . count ( $broadcastplayers ) );
			}
		}
	}
	public function handlePlayerLeaveTheGame(Player $player) {
		if ($player != null) {
			foreach ( $this->plugin->gameLevelManager->levels as &$lv ) {
				if ($lv instanceof GameLevelModel) {
					if (count ( $lv->joinedPlayers ) > 0) {
						if (isset ( $lv->joinedPlayers [$player->getName ()] )) {
							unset ( $lv->joinedPlayers [$player->getName ()] );
							$this->plugin->log ( "[HG]gamelevelmanager handlePlayerLeaveTheGame: " . $player->getName () . " |  remains: " . count ( $lv->joinedPlayers ) );
							if (count ( $lv->joinedPlayers ) === 0 || count ( $lv->joinedPlayers ) === 1) {
								if ($lv->currentStep === GameLevelModel::STATUS_MAP_SELECTION || $lv->currentStep === GameLevelModel::STEP_WAITING) {
									$this->plugin->openGate ( $lv );
									$this->plugin->log ( "[HG] No player left in portal - reset portal state | " . $lv->type . " | " . $lv->name );
									$lv->joinDownCounter = $lv->joinDownCounterReset;
									$lv->status = GameLevelModel::STATUS_AVAILABLE;
									$lv->currentStep = GameLevelModel::STEP_JOINING;
								}
							}
							// @FIXE ME remove votes
							foreach ( $this->plugin->arenaManager->arenas as &$arena ) {
								if ($arena instanceof MapArenaModel) {
									if (isset ( $arena->votedPlayers [$player->getName ()] )) {
										unset ( $arena->votedPlayers [$player->getName ()] );
										if ($arena->vote >= 1) {
											$arena->vote --;
										}
										break;
									}
								}
							}
						}
						if (! empty ( $lv->currentMap )) {
							if (isset ( $lv->currentMap->livePlayers [$player->getName ()] )) {
								unset ( $lv->currentMap->livePlayers [$player->getName ()] );
								$recordloss = new HungerGamesRecordLossTask ( $this->plugin, $lv, $player->getName () );
								$this->plugin->getServer ()->getScheduler ()->scheduleDelayedTask ( $recordloss, 30 );
							}
							if (isset ( $lv->currentMap->joinedPlayers [$player->getName ()] )) {
								unset ( $lv->currentMap->joinedPlayers [$player->getName ()] );
							}
							if (isset ( $lv->currentMap->votedPlayers [$player->getName ()] )) {
								unset ( $lv->currentMap->votedPlayers [$player->getName ()] );
							}
							break;
						}
					}
				}
			}
		}
		// @FIXME
		if (! empty ( $player->getInventory () && $player->isAlive () )) {
			HungerGameKit::clearAllInventories ( $player );
		}
	}
	public function removePlayerFromLevel(GameLevelModel &$lv, Player $player) {
		if (isset ( $lv->joinedPlayers [$player->getName ()] )) {
			unset ( $lv->joinedPlayers [$player->getName ()] );
		}
	}
	public function removePlayerFromArena(MapArenaModel &$arena, Player $player) {
		if (isset ( $arena->joinedPlayers [$player->getName ()] )) {
			unset ( $arena->joinedPlayers [$player->getName ()] );
		}
		if (isset ( $arena->livePlayers [$player->getName ()] )) {
			unset ( $arena->livePlayers [$player->getName ()] );
		}
		if (isset ( $arena->votedPlayers [$player->getName ()] )) {
			unset ( $arena->votedPlayers [$player->getName ()] );
		}
		
		if ($player != null) {
			if ($player->getLevel ()->getName () === $arena->levelName) {
				if ($player->getInventory () != null) {
					$player->getInventory ()->clearAll ();
				}
			}
		}
	}
	public function handleTapOnGameLevelSigns(Player $player, $b) {
		$success = true;
		foreach ( $this->plugin->getAvailableLevels () as &$lv ) {
			if ($lv instanceof GameLevelModel) {
				if (! empty ( $lv->signStats )) {
					$blockPosKey = round ( $b->x ) . "." . round ( $b->y ) . "." . round ( $b->z );
					$casePosKey = round ( $lv->signStats->x ) . "." . round ( $lv->signStats->y ) . "." . round ( $lv->signStats->z );
					if ($blockPosKey === $casePosKey && $blockPosKey != "0.0.0") {
						if ($lv->status != GameLevelModel::STATUS_AVAILABLE) {
							$player->sendMessage ( "[HG] The Hunger level #1 status [" . $lv->status . "]. Please join later." );
							return;
						}
						if (count ( $lv->joinedPlayers ) >= $lv->maxPlayers) {
							$player->sendMessage ( "[HG] The Hunger level is full. Please join later." );
							return;
						}
						$player->sendMessage ( TextFormat::WHITE . "[HG] " . $lv->name . " is available!" );
						break;
					}
				}
				if (! empty ( $lv->signJoin )) {
					$blockPosKey = round ( $b->x ) . "." . round ( $b->y ) . "." . round ( $b->z );
					$casePosKey = round ( $lv->signJoin->x ) . "." . round ( $lv->signJoin->y ) . "." . round ( $lv->signJoin->z );
					if ($blockPosKey === $casePosKey && $blockPosKey != "0.0.0") {
						if ($lv->status != GameLevelModel::STATUS_AVAILABLE) {
							$player->sendMessage ( "[HG] The Hunger level #1 status [" . $lv->status . "]. Please join later." );
							return;
						}
						if (count ( $lv->joinedPlayers ) >= $lv->maxPlayers) {
							$player->sendMessage ( "[HG] The Hunger level is full. Please join later." );
							return;
						}
						$player->sendMessage ( TextFormat::WHITE . "[HG] " . $lv->name . " is available!" );
						break;
					}
				}
				
				if (! empty ( $lv->signJoin2 )) {
					$blockPosKey = round ( $b->x ) . "." . round ( $b->y ) . "." . round ( $b->z );
					$casePosKey = round ( $lv->signJoin2->x ) . "." . round ( $lv->signJoin2->y ) . "." . round ( $lv->signJoin2->z );
					if ($blockPosKey === $casePosKey && $blockPosKey != "0.0.0") {
						$players [] = $player;
						$player->getLevel ()->addSound ( new LaunchSound ( $player->getPosition () ), $players );
						if (is_null ( $lv->enterpos )) {
							$this->plugin->log ( "[Error] Missing Game level entrance confguration" );
						} else {
							$player->teleport ( $lv->enterpos );
						}
					}
				}
				if (! empty ( $lv->signExit ) && ! empty ( $lv->exitPos )) {
					$blockPosKey = round ( $b->x ) . "." . round ( $b->y ) . "." . round ( $b->z );
					$casePosKey = round ( $lv->signExit->x ) . "." . round ( $lv->signExit->y ) . "." . round ( $lv->signExit->z );
					if ($blockPosKey === $casePosKey && $blockPosKey != "0.0.0") {
						$player->teleport ( $lv->exitPos );
						$this->handlePlayerLeaveTheGame ( $player );
						break;
					}
				}
			}
		}
		return $success;
	}
}
