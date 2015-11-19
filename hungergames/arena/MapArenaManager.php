<?php

namespace hungergames\arena;

use hungergames\main\HungerGamesPlugIn;
use hungergames\portal\MapPortal;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\level\sound\ClickSound;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use hungergames\utils\MagicUtil;
use pocketmine\utils\TextFormat;
use hungergames\level\GameLevelModel;
use pocketmine\level\Level;


class MapArenaManager extends ArenaInfo {
	public $plugin;
	public $arenas = [ ];
	public $arenasBackup = [ ];
	public $setupModeAction = "";
	public $setupModeData = "";
	const COMMAND_SETUP_NEW_ARENA = "setup_new_arena";
	const COMMAND_SETUP_WAND_ARENA_MAIN = "setup_wand_arena_main";
	const COMMAND_SETUP_WAND_ARENA_MAIN_PLAYER_SPAWNS = "setup_wand_arena_main_player_spawn";
	const COMMAND_SETUP_WAND_ARENA_DEATH_MATCH = "setup_wand_arena_death_match";
	const COMMAND_SETUP_WAND_ARENA_DEATH_MATCH_SPAWN = "setup_wand_arena_death_match_spawn";
	
	/**
	 *
	 * @param HungerGamesPlugIn $plugin        	
	 */
	public function __construct(HungerGamesPlugIn $plugin) {
		$this->plugin = $plugin;
	}
	public function resetArena($arenaName) {
		unset ( $this->arenas [$arenaName] );
		$this->arenas [$arenaName] = clone ($this->arenasBackup [$arenaName]);
	}
	public function handlePlayerLeaveArena(Player $player) {
		foreach ( $this->plugin->getAvailableArenas () as &$arena ) {
			$arena->removePlayerFromArena ( $player );
		}
	}
	public static function listsArenas(Player $sender, $path) {
		$xpath = $path . MapArena::ARENA_DIRECTORY;
		if (! file_exists ( $xpath )) {
			@mkdir ( $xpath, 0755, true );
			return null;
		}
		$output = "List of Arenas:\n";
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
	public function setPosition1(&$session, Position $position, &$output) {
		$output = "";
		$session ["selection"] [0] = array (
				round ( $position->x ),
				round ( $position->y ),
				round ( $position->z ),
				$position->level 
		);
		$count = $this->countBlocks ( $session ["selection"] );
		if ($count === false) {
			$count = "";
		} else {
			$count = " ($count)";
		}
		$output .= "Position #1 set to (" . $session ["selection"] [0] [0] . ", " . $session ["selection"] [0] [1] . ", " . $session ["selection"] [0] [2] . ")$count.\n";
		return true;
	}
	public function setPosition2(&$session, Position $position, &$output) {
		$output = "";
		$session ["selection"] [1] = array (
				round ( $position->x ),
				round ( $position->y ),
				round ( $position->z ),
				$position->level 
		);
		$count = $this->countBlocks ( $session ["selection"] );
		if ($count === false) {
			$count = "";
		} else {
			$count = " ($count)";
		}
		$output .= "Position #2 set to (" . $session ["selection"] [1] [0] . ", " . $session ["selection"] [1] [1] . ", " . $session ["selection"] [1] [2] . ")$count.\n";
		return true;
	}
	private function countBlocks($selection, &$startX = null, &$startY = null, &$startZ = null) {
		if (! is_array ( $selection ) or $selection [0] === false or $selection [1] === false or $selection [0] [3] !== $selection [1] [3]) {
			return false;
		}
		$startX = min ( $selection [0] [0], $selection [1] [0] );
		$endX = max ( $selection [0] [0], $selection [1] [0] );
		$startY = min ( $selection [0] [1], $selection [1] [1] );
		$endY = max ( $selection [0] [1], $selection [1] [1] );
		$startZ = min ( $selection [0] [2], $selection [1] [2] );
		$endZ = max ( $selection [0] [2], $selection [1] [2] );
		return ($endX - $startX + 1) * ($endY - $startY + 1) * ($endZ - $startZ + 1);
	}
	public static function lengthSq($x, $y, $z) {
		return ($x * $x) + ($y * $y) + ($z * $z);
	}
	
	/**
	 *
	 * @param Player $sender        	
	 * @param array $args        	
	 */
	public function createArena(Player $sender, array $args) {
		if (! $sender->isOp ()) {
			$sender->sendMessage ( "[HG] You are not authorized to use this command." );
			return;
		}
		if (count ( $args ) != 2) {
			$sender->sendMessage ( "[HG] Usage:/hg newarena [name]" );
			return;
		}
		// $sender->sendMessage ( TextFormat::GRAY."[HG] Creating new Arena" );
		$name = $args [1];
		if (isset ( $this->plugin->arenaManager->arenas [$name] )) {
			$sender->sendMessage ( TextFormat::RED . "[HG] Arena name ALREADY Exist!" );
			return;
		}
		$position = $sender->getPosition ();
		$leveName = $sender->level->getName ();
		$newArena = new MapArenaModel ( $name );
		$newArena->location = $sender->getPosition ();
		$newArena->levelName = $sender->getLevel ()->getName ();
		$newArena->save ( $this->plugin->getDataFolder () );
		$this->plugin->arenaManager->arenas [$name] = $newArena;
		$sender->sendMessage ( TextFormat::GREEN . "[HG] Success! New arena [" . TextFormat::GOLD . $name . TextFormat::GREEN . "] record created!" );
		$sender->sendMessage ( TextFormat::GREEN . "[HG] Arena file save to [" . $this->plugin->getDataFolder () . MapArenaModel::ARENA_DIRECTORY . "/" . $name . ".yml" );
		$sender->sendMessage ( TextFormat::GRAY . "[HG] your are ready for setup steps" );
		// clear selection
		$this->handleDeSelCommand ( $sender );
		return;
	}
	public function handlePos1Command($sender, $args) {
		$output = "";
		if (! ($sender instanceof Player)) {
			$output .= "Please run this command in-game.\n";
			$sender->sendMessage ( $output );
			return;
		}
		$session = &$this->session ( $sender );
		$this->setPosition1 ( $session, new Position ( $sender->x - 0.5, $sender->y, $sender->z - 0.5, $sender->getLevel () ), $output );
		$sender->sendMessage ( $output );
	}
	public function handlePos2Command($sender, $args) {
		$output = "";
		if (! ($sender instanceof Player)) {
			$output .= "Please run this command in-game.\n";
			$sender->sendMessage ( $output );
			return;
		}
		$session = &$this->session ( $sender );
		$this->setPosition2 ( $session, new Position ( $sender->x - 0.5, $sender->y, $sender->z - 0.5, $sender->getLevel () ), $output );
		$sender->sendMessage ( $output );
	}
	public function handleArenaWandCommand(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg arenawand [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		if (! isset ( $this->plugin->arenaManager->arenas [$args [1]] )) {
			$output = "[HG] Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$session = &$this->session ( $player );
		$this->handleDeSelCommand ( $player );
		$session ["wand-usage"] = true;
		$player->sendMessage ( "[HG] Wand selected" );
		if ($player->getInventory ()->getItemInHand ()->getId () != 292) {
			$player->getInventory ()->setItemInHand ( new Item ( 292 ) );
		}
		$arenaName = $args [1];
		$this->plugin->arenaManager->setupModeAction = self::COMMAND_SETUP_WAND_ARENA_MAIN;
		$this->plugin->arenaManager->setupModeData = $arenaName;
		$player->sendMessage ( TextFormat::GRAY . "[HG] Selected " . TextFormat::YELLOW . "[Arena] " . TextFormat::GRAY . "Wand" );
		$player->sendMessage ( TextFormat::WHITE . "[HG] Break a block to set Pos#1, then highest diagonal block for Pos#2" );
	}
	public function handleArenaDeathMatchWandCommand(Player $player, $args) {
		$session = &$this->session ( $player );
		$this->handleDeSelCommand ( $player );
		$session ["wand-usage"] = true;
		$player->sendMessage ( "[HG] Wand selected" );
		if ($player->getInventory ()->getItemInHand ()->getId () != 292) {
			$player->getInventory ()->setItemInHand ( new Item ( 292 ) );
		}
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg matchwand [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		if (! isset ( $this->plugin->arenaManager->arenas [$args [1]] )) {
			$output = "[HG] Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$arenaName = $args [1];
		$this->plugin->arenaManager->setupModeAction = self::COMMAND_SETUP_WAND_ARENA_DEATH_MATCH;
		$this->plugin->arenaManager->setupModeData = $arenaName;
		$player->sendMessage ( TextFormat::GRAY . "[HG] Selected " . TextFormat::YELLOW . "[Death Match] " . TextFormat::GRAY . "Wand" );
		$player->sendMessage ( TextFormat::WHITE . "[HG] Break a lowerst block to set Pos#1, then highest diagonal block for Pos#2" );
	}
	
	/**
	 *
	 * @param Player $player        	
	 * @param unknown $args        	
	 */
	public function handleArenaWandPlayerSpawnPointCommand(Player $player, $args) {
		$session = &$this->session ( $player );
		$this->handleDeSelCommand ( $player );
		$session ["wand-usage"] = true;
		$player->sendMessage ( "Wand selected" );
		if ($player->getInventory ()->getItemInHand ()->getId () != 292) {
			$player->getInventory ()->setItemInHand ( new Item ( 292 ) );
		}
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg playerspawnwand [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		if (! isset ( $this->plugin->arenaManager->arenas [$args [1]] )) {
			$output = "[HG] Arena Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$arenaName = $args [1];
		$this->plugin->arenaManager->setupModeAction = self::COMMAND_SETUP_WAND_ARENA_MAIN_PLAYER_SPAWNS;
		$this->plugin->arenaManager->setupModeData = $arenaName;
		$player->sendMessage ( TextFormat::GRAY . "[HG] Selected " . TextFormat::YELLOW . "[Player Spawn Point] " . TextFormat::GRAY . "Wand" );
		$player->sendMessage ( TextFormat::WHITE . "[HG] Break a block to set player spawn point." );
	}
	
	/**
	 *
	 * @param Player $player        	
	 * @param unknown $args        	
	 */
	public function handleSetArenaPlayerSpawnCommand(Player $player, $args) {		
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg setarenaspawn [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		$arenaName = $args [1];
		if (! isset ( $this->plugin->arenaManager->arenas [$arenaName] )) {
			$output = "[HG] Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$editArena = $this->plugin->arenaManager->arenas [$arenaName];
		$x = round ( $player->x );
		$y = round ( $player->y );
		$z = round ( $player->z );
		$key = $x . $y . $z;
		if ($editArena instanceof MapArenaModel) {
			$editArena->spawnLocations [$key] = $player->getPosition ();
			$this->plugin->arenaManager->arenas [$arenaName] = $editArena;
			$editArena->save ( $this->plugin->getDataFolder () );
		}
		$player->sendMessage ( TextFormat::GRAY . "[HG] Set player spawnpoint using current location " . TextFormat::YELLOW . "[" . $x . " " . $y . " " . $z . " ]" );
	}
	
	/**
	 *
	 * @param Player $player        	
	 * @param unknown $args        	
	 */
	public function ListArenaPlayerSpawnCommand(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg listspawns [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		$arenaName = $args [1];
		if (! isset ( $this->plugin->arenaManager->arenas [$arenaName] )) {
			$output = "[HG] Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$editArena = $this->plugin->arenaManager->arenas [$arenaName];
		$x = round ( $player->x );
		$y = round ( $player->y );
		$z = round ( $player->z );
		$key = $x . $y . $z;
		if ($editArena instanceof MapArenaModel) {
			if (empty ( $editArena->spawnLocations )) {
				$player->sendMessage ( "[HG] No spawn location found!" );
				return;
			} else {
				$out = TextFormat::BOLD . "[HG] " . $arenaName . " player spawn points [" . count ( $editArena->spawnLocations ) . "]:\n";
				foreach ( $editArena->spawnLocations as $key => $value ) {
					if ($value instanceof Position) {
						$out .= " [" . $value->x . "," . $value->y . "," . $value->z . "]\n";
					}
				}
				$player->sendMessage ( $out . "\n" );
			}
		}
	}
	
	/**
	 *
	 * @param Player $player        	
	 * @param unknown $args        	
	 */
	public function clearArenaPlayerSpawnPointsCommand(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg clearspawn [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		$arenaName = $args [1];
		if (! isset ( $this->plugin->arenaManager->arenas [$arenaName] )) {
			$output = "[HG] Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$editArena = $this->plugin->arenaManager->arenas [$arenaName];
		if ($editArena instanceof MapArenaModel) {
			$count = count ( $editArena->spawnLocations );
			$editArena->spawnLocations = [ ];
			$this->plugin->arenaManager->arenas [$arenaName] = $editArena;
			$editArena->save ( $this->plugin->getDataFolder () );			
			$player->sendMessage ( TextFormat::GREEN . "[HG] Success! \nRemoved [" . $count . "] spawns from arena [" . $arenaName . "]" );
		}
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
	public function handleSetArenaEntranceCommand(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg setarenaenter [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		$arenaName = $args [1];
		if (! isset ( $this->plugin->arenaManager->arenas [$arenaName] )) {
			$output = "[HG] Arena Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$arena = $this->plugin->arenaManager->arenas [$arenaName];
		if ($arena instanceof MapArenaModel) {
			$arena->enterLevelName = $player->getLevel ()->getName ();
			$arena->arenaEnterPos = $player->getPosition ();
			$this->plugin->arenaManager->arenas [$arenaName] = $arena;
			$arena->save ( $this->plugin->getDataFolder () );
		}
		$player->sendMessage ( TextFormat::GREEN . "[HG] Arena [Entrance] set to position :" . TextFormat::GOLD . round ( $arena->arenaEnterPos->x ) . " " . round ( $arena->arenaEnterPos->y ) . " " . round ( $arena->arenaEnterPos->z ) );
	}
	public function handleSetArenaExitCommand(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg setarenaexit [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		$arenaName = $args [1];
		if (! isset ( $this->plugin->arenaManager->arenas [$arenaName] )) {
			$output = "[HG] Arena Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$arena = $this->plugin->arenaManager->arenas [$arenaName];
		if ($arena instanceof MapArenaModel) {
			$arena->exitLevelName = $player->getLevel ()->getName ();
			$arena->arenaExitPos = $player->getPosition ();
			$this->plugin->arenaManager->arenas [$arenaName] = $arena;
			$arena->save ( $this->plugin->getDataFolder () );
		}
		$player->sendMessage ( TextFormat::GREEN . "[HG] Arena [Exit] set to position :" . TextFormat::GOLD . round ( $arena->arenaExitPos->x ) . " " . round ( $arena->arenaExitPos->y ) . " " . round ( $arena->arenaExitPos->z ) );
	}
	public function handleSetArenaPosition1Command(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg setarenapos1 [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		$arenaName = $args [1];
		if (! isset ( $this->plugin->arenaManager->arenas [$arenaName] )) {
			$output = "[HG] Arena Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$arena = $this->plugin->arenaManager->arenas [$arenaName];
		if ($arena instanceof MapArenaModel) {
			$arena->sectionpos1 = $player->getPosition ();
			$this->plugin->arenaManager->arenas [$arenaName] = $arena;
			$arena->save ( $this->plugin->getDataFolder () );
		}
		$player->sendMessage ( TextFormat::GRAY . "[HG] Arena " . TextFormat::GOLD . "[Position #1] " . TextFormat::GRAY . "set to position :" . TextFormat::GOLD . round ( $arena->sectionpos1->x ) . " " . round ( $arena->sectionpos1->y ) . " " . round ( $arena->sectionpos1->z ) );
	}
	public function handleSetArenaPosition2Command(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg setarenapos2 [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		$arenaName = $args [1];
		if (! isset ( $this->plugin->arenaManager->arenas [$arenaName] )) {
			$output = "[HG] Arena Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$arena = $this->plugin->arenaManager->arenas [$arenaName];
		if ($arena instanceof MapArenaModel) {
			$arena->sectionpos2 = $player->getPosition ();
			$this->plugin->arenaManager->arenas [$arenaName] = $arena;
			$arena->save ( $this->plugin->getDataFolder () );
		}
		$player->sendMessage ( TextFormat::GRAY . "[HG] Arena " . TextFormat::GOLD . "[Position #2] " . TextFormat::GRAY . "set to position :" . TextFormat::GOLD . round ( $arena->sectionpos2->x ) . " " . round ( $arena->sectionpos2->y ) . " " . round ( $arena->sectionpos2->z ) );
	}
	public function handleSetArenaWallCommand(Player $player, $args) {
		$output = "";
		if (count ( $args ) != 3) {
			$output = "[HG] Usage: /hg setarenawall [arena name] [blockid].\n";
			$player->sendMessage ( $output );
			return;
		}
		$arenaName = $args [1];
		$blockid = $args [2];
		if (! isset ( $this->plugin->arenaManager->arenas [$arenaName] )) {
			$output = "[HG] Arena Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$block = Item::get ( $blockid )->getBlock ();
		if ($block === null) {
			$output = "[HG] Invalid block id.\n";
			$player->sendMessage ( $output );
			return;
		}
		$arena = $this->plugin->arenaManager->arenas [$arenaName];
		$this->plugin->arenaManager->setWall ($player->getLevel(), $arena->sectionpos1, $arena->sectionpos2, $block, $output );
		$player->sendMessage ( TextFormat::GREEN . "[HG] Arena wall setted [" . $output . "]" );
	}
	public function handleSetArenaDeathMatchPosition1Command(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg setmatchpos1 [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		$arenaName = $args [1];
		if (! isset ( $this->plugin->arenaManager->arenas [$arenaName] )) {
			$output = "[HG] Arena Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$arena = $this->plugin->arenaManager->arenas [$arenaName];
		if ($arena instanceof MapArenaModel) {
			$arena->deathMatchPos1 = $player->getPosition ();
			$this->plugin->arenaManager->arenas [$arenaName] = $arena;
			$arena->save ( $this->plugin->getDataFolder () );
		}
		$player->sendMessage ( TextFormat::GRAY . "[HG] Arena Death-Match" . TextFormat::GOLD . "[Position #1] " . TextFormat::GRAY . "set to position :" . TextFormat::GOLD . round ( $arena->deathMatchPos1->x ) . " " . round ( $arena->deathMatchPos1->y ) . " " . round ( $arena->deathMatchPos1->z ) );
	}
	public function handleSetArenaDeathMatchPosition2Command(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg setmatchpos2 [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		$arenaName = $args [1];
		if (! isset ( $this->plugin->arenaManager->arenas [$arenaName] )) {
			$output = "[HG] Arena Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$arena = $this->plugin->arenaManager->arenas [$arenaName];
		if ($arena instanceof MapArenaModel) {
			$arena->deathMatchPos2 = $player->getPosition ();
			$this->plugin->arenaManager->arenas [$arenaName] = $arena;
			$arena->save ( $this->plugin->getDataFolder () );
		}
		$player->sendMessage ( TextFormat::GRAY . "[HG] Arena Death-Match" . TextFormat::GOLD . "[Position #2] " . TextFormat::GRAY . "set to position :" . TextFormat::GOLD . round ( $arena->deathMatchPos2->x ) . " " . round ( $arena->deathMatchPos2->y ) . " " . round ( $arena->deathMatchPos2->z ) );
	}
	public function handleSetArenaDeathMatchEntranceCommand(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg setmatchenter [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		$arenaName = $args [1];
		if (! isset ( $this->plugin->arenaManager->arenas [$arenaName] )) {
			$output = "[HG] Arena Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$arena = $this->plugin->arenaManager->arenas [$arenaName];
		if ($arena instanceof MapArenaModel) {
			$arena->deathMatchEnter = $player->getPosition ();
			$this->plugin->arenaManager->arenas [$arenaName] = $arena;
			$arena->save ( $this->plugin->getDataFolder () );
		}
		$player->sendMessage ( TextFormat::GRAY . "[HG] Success! Arena Death-Match Entrance set [" . TextFormat::GOLD . round ( $arena->deathMatchEnter->x ) . " " . round ( $arena->deathMatchEnter->y ) . " " . round ( $arena->deathMatchEnter->z ) . "]" );
	}
	public function handleSetDeathMatchWallsCommand(Player $player, $args) {
		$output = "";
		if (count ( $args ) != 3) {
			$output = "[HG] Usage: /hg setmatchwall [arena name] [blockid].\n";
			$player->sendMessage ( $output );
			return;
		}
		$arenaName = $args [1];
		$blockid = $args [2];
		if (! isset ( $this->plugin->arenaManager->arenas [$arenaName] )) {
			$output = "[HG] Arena Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$block = Item::get ( $blockid )->getBlock ();
		if ($block === null) {
			$output = "[HG] Invalid block id.\n";
			$player->sendMessage ( $output );
			return;
		}
		$arena = $this->plugin->arenaManager->arenas [$arenaName];
		if ($arena instanceof MapArenaModel) {
			$this->plugin->arenaManager->setWall ( $player->getLevel(), $arena->deathMatchPos1, $arena->deathMatchPos2, $block, $output );
		}
		$player->sendMessage ( TextFormat::GREEN . "[HG] Success! Arena wall built [" . $output . "]" );
	}
	public function handleActivateArenaCommand(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = TextFormat::YELLOW . "[HG] Usage: /hg publish [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		$arenaName = $args [1];
		if (! isset ( $this->plugin->arenaManager->arenas [$arenaName] )) {
			$output = TextFormat::YELLOW . "[HG] Arena Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$arena = $this->plugin->arenaManager->arenas [$arenaName];
		if ($arena instanceof MapArenaModel) {
			$arena->published = true;
			$this->plugin->arenaManager->arenas [$arenaName] = $arena;
			$arena->save ( $this->plugin->getDataFolder () );
		}
		$player->sendMessage ( TextFormat::GREEN . "[HG] Success! Published arena [" . $arenaName . "]." );
	}
	public function handleDeActivateArenaCommand(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = TextFormat::YELLOW . "[HG] Usage: /hg unpublish [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		$arenaName = $args [1];
		if (! isset ( $this->plugin->arenaManager->arenas [$arenaName] )) {
			$output = TextFormat::YELLOW . "[HG] Arena Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$arena = $this->plugin->arenaManager->arenas [$arenaName];
		if ($arena instanceof MapArenaModel) {
			$arena->published = false;
			$this->plugin->arenaManager->arenas [$arenaName] = $arena;
			$arena->save ( $this->plugin->getDataFolder () );
		}
		$player->sendMessage ( TextFormat::GREEN . "[HG] Success! Unpublished arena [" . $arenaName . "] " );
	}
	public function handleGameLevelPortalWandCommand(Player $player, $args) {
		$session = &$this->session ( $player );
		$this->handleDeSelCommand ( $player );
		$session ["wand-usage"] = true;
		$player->sendMessage ( "Wand selected" );
		if ($player->getInventory ()->getItemInHand ()->getId () != 292) {
			$player->getInventory ()->setItemInHand ( new Item ( 292 ) );
		}
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg portalwand [game level name].\n";
			$player->sendMessage ( $output );
			return;
		}
        $levelName = $args[1];
		if (! isset ( $this->plugin->gameLevelManager->levels [$levelName] )) {
			$output = "[HG] Game Level Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$this->plugin->arenaManager->setupModeAction = self::COMMAND_SETUP_WAND_GAME_LEVEL;
		$this->plugin->arenaManager->setupModeData = $levelName;
		$player->sendMessage ( TextFormat::GRAY . "[HG] Selected " . TextFormat::YELLOW . "[Game Level Portal] " . TextFormat::GRAY . "Wand" );
		$player->sendMessage ( TextFormat::WHITE . "[HG] Break a lowerst block to set Pos#1, then highest diagonal block for Pos#2" );
	}
	public function handleSetGamePortalPosition1Command(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg setportalpos1 [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		$levelName = $args [1];
		if (! isset ( $this->plugin->gameLevelManager->levels [$levelName] )) {
			$output = "[HG] Level Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}

		$gamelevel = $this->plugin->gameLevelManager->levels [$levelName];
		if ($gamelevel instanceof GameLevelModel) {
			$gamelevel->portalEnterPos1 = $player->getPosition ();
			$this->plugin->gameLevelManager->levels [$levelName] = $gamelevel;
			$gamelevel->save ( $this->plugin->getDataFolder () );
		}
		$player->sendMessage ( TextFormat::GRAY . "[HG] Game Level Portal" . TextFormat::GOLD . "[Position #1] " . TextFormat::GRAY . "set to position :" . TextFormat::GOLD . round ( $gamelevel->portalEnterPos1->x ) . " " . round ( $gamelevel->portalEnterPos1->y ) . " " . round ( $gamelevel->portalEnterPos1->z ) );
	}
	public function handleSetGamePortalPosition2Command(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg setportalpos2 [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		$levelName = $args [1];
		if (! isset ( $this->plugin->gameLevelManager->levels [$levelName] )) {
			$output = "[HG] Level Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$gamelevel = $this->plugin->gameLevelManager->levels [$levelName];
		if ($gamelevel instanceof GameLevelModel) {
			$gamelevel->portalEnterPos2 = $player->getPosition ();
			$this->plugin->gameLevelManager->levels [$levelName] = $gamelevel;
			$gamelevel->save ( $this->plugin->getDataFolder () );
		}
		$player->sendMessage ( TextFormat::GRAY . "[HG] Game Level Portal" . TextFormat::GOLD . "[Position #2] " . TextFormat::GRAY . "set to :" . TextFormat::GOLD . round ( $gamelevel->portalEnterPos2->x ) . " " . round ( $gamelevel->portalEnterPos2->y ) . " " . round ( $gamelevel->portalEnterPos2->z ) );
	}
	public function handleGameLevelPortalGateWandCommand(Player $player, $args) {
		$session = &$this->session ( $player );
		$this->handleDeSelCommand ( $player );
		$session ["wand-usage"] = true;
		$player->sendMessage ( "Wand selected" );
		if ($player->getInventory ()->getItemInHand ()->getId () != 292) {
			$player->getInventory ()->setItemInHand ( new Item ( 292 ) );
		}
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg levelgatewand [game level name].\n";
			$player->sendMessage ( $output );
			return;
		}
        $levelName = $args[1];
		if (! isset ( $this->plugin->gameLevelManager->levels [$levelName] )) {
			$output = "[HG] Game Level Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$gamelevel = $args [1];
		$this->plugin->arenaManager->setupModeAction = self::COMMAND_SETUP_WAND_GAME_LEVEL_GATE;
		$this->plugin->arenaManager->setupModeData = $gamelevel;
		$player->sendMessage ( TextFormat::GRAY . "[HG] Selected " . TextFormat::YELLOW . "[Game Level Portal] " . TextFormat::GRAY . "Wand" );
		$player->sendMessage ( TextFormat::WHITE . "[HG] Break a lowerst block to set Pos#1, then highest diagonal block for Pos#2" );
	}
	public function handleSetGamePortalGate1Command(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg setportalgatepos1 [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		$levelName = $args [1];
		if (! isset ( $this->plugin->gameLevelManager->levels [$levelName] )) {
			$output = "[HG] Level Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$gamelevel = $this->plugin->gameLevelManager->levels [$levelName];
		if ($gamelevel instanceof GameLevelModel) {
			$gamelevel->gatePos1 = $player->getPosition ();
			$this->plugin->gameLevelManager->levels [$levelName] = $gamelevel;
			$gamelevel->save ( $this->plugin->getDataFolder () );
		}
		$player->sendMessage ( TextFormat::GRAY . "[HG] Game Level Portal " . TextFormat::GOLD . "[Gate Position #1] " . TextFormat::GRAY . "set to :" . TextFormat::GOLD . round ( $gamelevel->gatePos1->x ) . " " . round ( $gamelevel->gatePos1->y ) . " " . round ( $gamelevel->gatePos1->z ) );
	}
	public function handleSetGamePortalGate2Command(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg setportalgatepos2 [arena name].\n";
			$player->sendMessage ( $output );
			return;
		}
		$levelName = $args [1];
		if (! isset ( $this->plugin->gameLevelManager->levels [$levelName] )) {
			$output = "[HG] Level Name not found!.\n";
			$player->sendMessage ( $output );
			return;
		}
		$gamelevel = $this->plugin->gameLevelManager->levels [$levelName];
		if ($gamelevel instanceof GameLevelModel) {
			$gamelevel->gatePos2 = $player->getPosition ();
			$this->plugin->gameLevelManager->levels [$levelName] = $gamelevel;
			$gamelevel->save ( $this->plugin->getDataFolder () );
		}
		$player->sendMessage ( TextFormat::GRAY . "[HG] Game Level Portal " . TextFormat::GOLD . "[Gate Position #2] " . TextFormat::GRAY . "set to :" . TextFormat::GOLD . round ( $gamelevel->gatePos2->x ) . " " . round ( $gamelevel->gatePos2->y ) . " " . round ( $gamelevel->gatePos2->z ) );
	}
	public function handleSetGameLobbyCommand(Player $player, $args) {
		if (count ( $args ) != 2) {
			$output = "[HG] Usage: /hg setgamelobby\n";
			$player->sendMessage ( $output );
			return;
		}
		// update the server setting in
		$this->plugin->getConfig ()->set ( "hg_lobby_levelname", $player->level->getName () );
		$this->plugin->getConfig ()->set ( "hg_lobby_spawn_x", round ( $player->x ) );
		$this->plugin->getConfig ()->set ( "hg_lobby_spawn_y", round ( $player->y ) );
		$this->plugin->getConfig ()->set ( "hg_lobby_spawn_z", round ( $player->z ) );
		$this->plugin->getConfig ()->save ();
		$player->sendMessage ( "[HG] [Game lobby] set to position :" . round ( $player->x ) . " " . round ($player->y ) . " " . round ( $player->z ) );
	}
	public function handleSetServerLobbyCommand(Player $player, $args) {
		if (count ( $args ) != 1) {
			$output = "[HG] Usage: /hg setserverlobby\n";
			$player->sendMessage ( $output );
			return;
		}
		// update the server setting in
		$this->plugin->getConfig ()->set ( "server_lobby_levelname", $player->level->getName () );
		$this->plugin->getConfig ()->set ( "server_lobby_x", $player->x );
		$this->plugin->getConfig ()->set ( "server_lobby_y", $player->y );
		$this->plugin->getConfig ()->set ( "server_lobby_z", $player->z );
		$this->plugin->getConfig ()->save ();
		$player->sendMessage ( "[HG] Server [Server lobby] set to [" . round ( $player->x ) . " " . round ( $player->y ) . " " . round ( $player->z ) . "]" );
	}
	public function handleSetSignJoin(Player $player, $arenaName, $block) {
		if (! $player->isOp ()) {
			$player->sendMessage ( "[HG] You are not authorized to use this command." );
			return;
		}
		if (! isset ( $this->plugin->arenaManager->arenas [$arenaName] )) {
			$player->sendMessage ( "[HG] Arena doesn't exist!" );
			return;
		}
		$arena = $this->plugin->arenaManager->arenas [$arenaName];
		$arena->signJoin = new Position ( $block->x, $block->y, $block->z );
		$this->plugin->arenaManager->arenas [$arenaName] = $arena;
		$arena->save ( $this->plugin->getDataFolder () );
		$player->sendMessage ( "[HG] Arena [Join Sign] set [" . TextFormat::GOLD . round ( $arena->signJoin->x ) . " " . round ( $arena->signJoin->y ) . " " . round ( $arena->signJoin->z ) . "]" );
	}
	public function handleSetSignExit(Player $player, $arenaName, $block) {
		if (! $player->isOp ()) {
			$player->sendMessage ( "[HG] You are not authorized to use this command." );
			return;
		}
		if (! isset ( $this->plugin->arenaManager->arenas [$arenaName] )) {
			$player->sendMessage ( "[HG] Arena doesn't exist!" );
			return;
		}
		$arena = $this->plugin->arenaManager->arenas [$arenaName];
		$arena->signExit = new Position ( $block->x, $block->y, $block->z );
		$this->plugin->arenaManager->arenas [$arenaName] = $arena;
		$arena->save ( $this->plugin->getDataFolder () );
		$player->sendMessage ( "[HG] Arena [Join Sign] set [" . TextFormat::GOLD . round ( $arena->signExit->x ) . " " . round ( $arena->signExit->y ) . " " . round ( $arena->signExit->z ) . "]" );
	}
	public function handleSetSignVote(Player $player, $arenaName, $block) {
		if (! $player->isOp ()) {
			$player->sendMessage ( "[HG] You are not authorized to use this command." );
			return;
		}
		if (! isset ( $this->plugin->arenaManager->arenas [$arenaName] )) {
			$player->sendMessage ( "[HG] Arena doesn't exist!" );
			return;
		}
		$arena = $this->plugin->arenaManager->arenas [$arenaName];
		$arena->signVote = new Position ( $block->x, $block->y, $block->z );
		$this->plugin->arenaManager->arenas [$arenaName] = $arena;
		$arena->save ( $this->plugin->getDataFolder () );
		$player->sendMessage ( "[HG] Arena [Vote Sign] set [" . TextFormat::GOLD . round ( $arena->signVote->x ) . " " . round ( $arena->signVote->y ) . " " . round ( $arena->signVote->z ) . "]" );
	}
	public function handleSetSignStat(Player $player, $arenaName, $block) {
		if (! $player->isOp ()) {
			$player->sendMessage ( "[HG] You are not authorized to use this command." );
			return;
		}
		if (! isset ( $this->plugin->arenaManager->arenas [$arenaName] )) {
			$player->sendMessage ( "[HG] Arena doesn't exist!" );
			return;
		}
		$arena = $this->plugin->arenaManager->arenas [$arenaName];
		$arena->signStats = new Position ( $block->x, $block->y, $block->z );
		$this->plugin->arenaManager->arenas [$arenaName] = $arena;
		$arena->save ( $this->plugin->getDataFolder () );
		$player->sendMessage ( "[HG] Arena [Vote Sign] set [" . TextFormat::GOLD . round ( $arena->signStats->x ) . " " . round ( $arena->signStats->y ) . " " . round ( $arena->signStats->z ) . "]" );
	}
	
	/**
	 *
	 * @param Position $p1        	
	 * @param Position $p2        	
	 * @param unknown $block        	
	 * @param string $output        	
	 * @return boolean
	 */
	public function setGate(Position $p1, Position $p2, $block, &$output = null) {
		$send = false;
		$level = $p1->getLevel ();
		$bcnt = 1;
		$startX = min ( $p1->x, $p2->x );
		$endX = max ( $p1->x, $p2->x );
		$startY = min ( $p1->y, $p2->y );
		$endY = max ( $p1->y, $p2->y );
		$startZ = min ( $p1->z, $p2->z );
		$endZ = max ( $p1->z, $p2->z );
		$count = 0;
		for($x = $startX; $x <= $endX; ++ $x) {
			for($y = $startY; $y <= $endY; ++ $y) {
				for($z = $startZ; $z <= $endZ; ++ $z) {
					$level->setBlock ( new Position ( $x, $y, $z ), $block, false, true );
					$count ++;
				}
			}
		}
		$output .= "$count block(s) have been updated.\n";
		return true;
	}
	
	/**
	 *
	 * @param Position $p1        	
	 * @param Position $p2        	
	 * @param unknown $block        	
	 * @param string $output        	
	 * @return boolean
	 */
	public function setWall(Level $level, Position $p1, Position $p2, $block, &$output = null) {
		$send = false;
		//$level = $p1->getLevel ();
		$bcnt = 1;
		$startX = min ( $p1->x, $p2->x );
		$endX = max ( $p1->x, $p2->x );
		$startY = min ( $p1->y, $p2->y );
		$endY = max ( $p1->y, $p2->y );
		$startZ = min ( $p1->z, $p2->z );
		$endZ = max ( $p1->z, $p2->z );
		$count = 0;
		for($x = $startX; $x <= $endX; ++ $x) {
			for($y = $startY; $y <= $endY; ++ $y) {
				for($z = $startZ; $z <= $endZ; ++ $z) {
					if ($x == $startX || $x == $endX || $z == $startZ || $z == $endZ) {
						$level->setBlock ( new Position ( $x, $y, $z ), $block, false, true );
						$count ++;
					}
				}
			}
		}
		$output .= "$count block(s) have been updated.\n";
		return true;
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
			if ($this->plugin->arenaManager->setupModeAction === MapArenaManager::COMMAND_SETUP_WAND_ARENA_MAIN || $this->plugin->arenaManager->setupModeAction === MapArenaManager::COMMAND_SETUP_WAND_ARENA_DEATH_MATCH) {
				$session = &$this->plugin->arenaManager->session ( $player );
				if ($session != null && $session ["wand-usage"] === true) {
					if (! isset ( $session ["wand-pos1"] ) || $session ["wand-pos1"] === null) {
						$session ["wand-pos1"] = $b;
						// $this->plugin->arenaManager->setPosition1 ( $session, new Position ( $b->x - 0.5, $b->y, $b->z - 0.5, $player->getLevel () ), $output );
						// setup
						if (! isset ( $this->plugin->arenaManager->arenas [$this->plugin->arenaManager->setupModeData] ) || $this->plugin->arenaManager->setupModeData === "") {
							$player->sendMessage ( "[HG] Arena not found! " );
							return;
						}
						$arena = $this->plugin->arenaManager->arenas [$this->plugin->arenaManager->setupModeData];
						if ($this->plugin->arenaManager->setupModeAction === MapArenaManager::COMMAND_SETUP_WAND_ARENA_MAIN) {
							$arena->sectionpos1 = new Position ( round ( $b->x ), round ( $b->y ), round ( $b->z ) );
						}
						if ($this->plugin->arenaManager->setupModeAction === MapArenaManager::COMMAND_SETUP_WAND_ARENA_DEATH_MATCH) {
							$arena->deathMatchPos1 = new Position ( round ( $b->x ), round ( $b->y ), round ( $b->z ) );
						}
						$arena->save ( $this->plugin->getDataFolder () );
						$this->plugin->arenaManager->arenas [$this->plugin->arenaManager->setupModeData] = $arena;
						$player->sendMessage ( TextFormat::WHITE . "[HG] " . $this->plugin->arenaManager->setupModeData . " position#1 set " . " [" . TextFormat::GOLD . round ( $b->x ) . " " . round ( $b->y ) . " " . round ( $b->z ) . TextFormat::WHITE . "]" );
						return;
					}
					if (! isset ( $session ["wand-pos2"] ) || $session ["wand-pos2"] === null) {
						$session ["wand-pos2"] = $b;
						// $this->plugin->arenaManager->setPosition2 ( $session, new Position ( $b->x - 0.5, $b->y, $b->z - 0.5, $player->getLevel () ), $output );
						// setup
						$arena = $this->plugin->arenaManager->arenas [$this->plugin->arenaManager->setupModeData];
						if ($this->plugin->arenaManager->setupModeAction === MapArenaManager::COMMAND_SETUP_WAND_ARENA_MAIN) {
							$arena->sectionpos2 = new Position ( round ( $b->x ), round ( $b->y ), round ( $b->z ) );
						}
						if ($this->plugin->arenaManager->setupModeAction === MapArenaManager::COMMAND_SETUP_WAND_ARENA_DEATH_MATCH) {
							$arena->deathMatchPos2 = new Position ( round ( $b->x ), round ( $b->y ), round ( $b->z ) );
						}
						$arena->save ( $this->plugin->getDataFolder () );
						$this->plugin->arenaManager->arenas [$this->plugin->arenaManager->setupModeData] = $arena;
						$player->sendMessage ( TextFormat::WHITE . "[HG] " . $this->plugin->arenaManager->setupModeData . " position#2 set " . " [" . TextFormat::GOLD . round ( $b->x ) . " " . round ( $b->y ) . " " . round ( $b->z ) . TextFormat::WHITE . "]" );
						$this->plugin->setupModeAction = "";
						$this->plugin->setupModeData = "";
						return;
					}
				}
			}
		}
		
		if ($player instanceof Player) {
			if ($this->plugin->arenaManager->setupModeAction === MapArenaManager::COMMAND_SETUP_WAND_ARENA_MAIN_PLAYER_SPAWNS) {
				$session = &$this->plugin->arenaManager->session ( $player );
				if ($session != null && $session ["wand-usage"] === true) {
					if (! isset ( $session ["spawn-pos"] ) || $session ["spawn-pos"] === null) {
						$session ["spawn-pos"] = $b;
						if (! isset ( $this->plugin->arenaManager->arenas [$this->plugin->arenaManager->setupModeData] ) || $this->plugin->arenaManager->setupModeData === "") {
							$player->sendMessage ( "[HG] Arena not found! " );
							return;
						}
						$arena = $this->plugin->arenaManager->arenas [$this->plugin->arenaManager->setupModeData];
						$x = round ( $b->x );
						$y = round ( $b->y );
						$z = round ( $b->z );
						$key = $x . $y . $z;
						if ($arena instanceof MapArenaModel) {
							$arena->spawnLocations [$key] = new Position ( $b->x, $b->y, $b->z, $b->getLevel () );
							$this->plugin->arenaManager->arenas [$this->plugin->arenaManager->setupModeData] = $arena;
							$arena->save ( $this->plugin->getDataFolder () );
						}
						$this->plugin->arenaManager->arenas [$this->plugin->arenaManager->setupModeData] = $arena;
						$player->sendMessage ( "[HG] Arena Spawn Position set to " . " [" . TextFormat::LIGHT_PURPLE . round ( $b->x ) . " " . round ( $b->y ) . " " . round ( $b->z ) . TextFormat::WHITE . "]" );
						unset ( $session ["spawn-pos"] );
						return;
					}
				}
			}
		}
	}
	
	/**
	 * Recursively delete a directory
	 *
	 * @param string $dir
	 *        	Directory name
	 * @param boolean $deleteRootToo
	 *        	Delete specified top-level directory as well
	 */
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
		$path = $this->plugin->getDataFolder () . self::ARENA_DIRECTORY;
		if (! file_exists ( $path )) {
			@mkdir ( $path, 0755, true );
			foreach ($this->plugin->getResources() as $resource) {
				if (!$resource->isDir()) {
					$fp = $resource->getPathname();
					if (strpos($fp, "arena_data")!= false) {
						$this->plugin->info(TextFormat::AQUA." *** setup default [ARENA Data]: ".$resource->getFilename());
						copy($resource->getPathname(), $path.$resource->getFilename());
					}
				}
			}
		}	
		$this->arenas = MapArenaModel::preloadArenas ( $this->plugin );
		$this->arenasBackup = $this->arenas;
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
	
	/**
	 *
	 * @param Level $level        	
	 * @param unknown $base        	
	 * @param unknown $competition        	
	 */
	public function addWorld(Level $level, $base, $competition) {
		$fileutil = new FileUtil ();
		$source = $level->getServer ()->getDataPath () . "worlds/" . $base . "/";
		$dest = $level->getServer ()->getDataPath () . "worlds/" . $competition . "/";
		$this->deleteWorld ( $level, $competition );
		if ($fileutil->xcopy ( $source, $dest )) {
			$this->log ( "New Hunger Game Competition World [" . $competition . "] created!" );
			try {
				$level->getServer ()->loadLevel ( $competition );
			} catch ( \Exception $e ) {
				$this->log ( "level loading error: " . $e->getMessage () );
			}
			$this->log ( "loaded world: " . $competition );
		} else {
			$this->log ( "problem creating new map. please contact administrator." );
		}
	}
	
	/**
	 *
	 * @param Level $level        	
	 * @param unknown $worldname        	
	 */
	public function deleteWorld(Level $level, $worldname) {
		Server::getInstance ()->unloadLevel ( $level, true );
		$levelpath = $level->getServer ()->getDataPath () . "worlds/" . $worldname . "/";
		$fileutil = new FileUtil ();
		$fileutil->unlinkRecursive ( $levelpath, true );
	}
	private function log($msg) {
		$this->plugin->log ( $msg );
	}
	
	/**
	 *
	 * Handle Tap On Arena Signs
	 *
	 * @param Player $player        	
	 * @param unknown $b        	
	 * @return boolean
	 */
	public function handleTapOnArenaSigns(Player $player, $b) {
		foreach ( $this->arenas as &$arena ) {
			if ($arena instanceof MapArenaModel) {
				// tap on vote sign
				if (! empty ( $arena->signVote )) {
					$blockPosKey = round ( $b->x ) . "." . round ( $b->y ) . "." . round ( $b->z );
					$casePosKey = round ( $arena->signVote->x ) . "." . round ( $arena->signVote->y ) . "." . round ( $arena->signVote->z );
					if ($blockPosKey === $casePosKey && $blockPosKey != "0.0.0") {
						if (! $arena->published) {
							$player->sendMessage ( "[HG] This map is not available yet! please try another one." );
							break;
						}
						$players [] = $player;
						$player->getLevel ()->addSound ( new ClickSound ( $player->getPosition () ), $players );
						MagicUtil::addParticles ( $player->getLevel (), "reddust", $arena->signVote, 20 );		
						
						if ( ($arena->vote===0)  && isset ( $arena->votedPlayers [$player->getName ()])) {
							unset($arena->votedPlayers [$player->getName ()]);
						}						
						if (! isset ( $arena->votedPlayers [$player->getName ()])) {
							$arena->vote ++;
							$arena->votedPlayers [$player->getName ()] = $player;
						} else {
							$player->sendMessage ( TextFormat::YELLOW."[HG] Already voted! [" . $arena->displayName."]");
						}
						$player->sendMessage ( TextFormat::GRAY."[HG] " . $arena->displayName . " map votes [" . TextFormat::YELLOW. $arena->vote . TextFormat::GRAY."]" );
						break;
					}
				}
				if (! empty ( $arena->signExit ) && ! empty ( $arena->arenaExitPos )) {
					$blockPosKey = round ( $b->x ) . "." . round ( $b->y ) . "." . round ( $b->z );
					$casePosKey = round ( $arena->signExit->x ) . "." . round ( $arena->signExit->y ) . "." . round ( $arena->signExit->z );					
					if ($blockPosKey === $casePosKey && $blockPosKey != "0.0.0") {
						$players [] = $player;
						$player->level->addSound ( new ClickSound ( $player->getPosition () ), $players );
						MagicUtil::addParticles ( $player->getLevel (), "portal", $player->getPosition (), 100 );
						unset ( $arena->joinedPlayers [$player->getName ()] );
						unset ( $arena->livePlayers [$player->getName ()] );
						$player->teleport ( $arena->arenaExitPos );
						break;
					}
				}
				if (! empty ( $arena->signJoin ) && ! empty ( $arena->arenaEnterPos )) {
					$blockPosKey = round ( $b->x ) . "." . round ( $b->y ) . "." . round ( $b->z );
					$casePosKey = round ( $arena->signJoin->x ) . "." . round ( $arena->signJoin->y ) . "." . round ( $arena->signJoin->z );
					if ($blockPosKey === $casePosKey && $blockPosKey != "0.0.0") {
						$players [] = $player;
						$player->level->addSound ( new ClickSound ( $player->getPosition () ), $players );
						MagicUtil::addParticles ( $player->getLevel (), "portal", $player->getPosition (), 100 );
						MapPortal::teleportToMap ( $arena->levelName, $player );
						$player->teleport ( $arena->arenaEnterPos );
						break;
					}
				}
			}
		}
	}
}
