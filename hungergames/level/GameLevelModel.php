<?php

namespace hungergames\level;

use hungergames\main\HungerGamesPlugIn;
use hungergames\utils\MagicUtil;
use pocketmine\entity\Effect;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\sound\DoorSound;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\level\sound\LaunchSound;
use hungergames\arena\MapArenaModel;


class GameLevelModel {
	private $plugin;
	const DIR_LEVEL_DATA = 'level_data/';
	const TYPE_LEVEL_ONE = "HG level #1";
	const TYPE_LEVEL_TWO = "HG level #2";
	const TYPE_LEVEL_THREE = "HG level #3";
	const LEVEL_ONE = 1;
	const LEVEL_TWO = 2;
	const LEVEL_THREE = 3;
	const LEVEL_FOUR = 4;
	const LEVEL_VIP = 4;
	const STEP_JOINING = "join now";
	const STEP_MAP_SELECTION = "map selection";
	const STEP_WAITING = "waiting";
	const STEP_INVISIBLE = "invisible";
	const STEP_HUNTING = "hunting";
	const STEP_DEATH_MATCH = "death-match";
	const STEP_ANNOUNCE_WINNING = "announce-winning";
	const STEP_GAME_OVER = "game-over";
	const STATUS_AVAILABLE = "available";
	const STATUS_MAP_SELECTION = "map selection";
	const STATUS_RUNNING = "running";
	const STATUS_RESETTING = "resetting";
	const STATUS_EXITING = "exiting";
	
	// variables
	public $name;
	public $displayName;
	public $level;
	public $levelName;
	public $gamelevel = 0;
	public $location;
	public $status = self::STATUS_AVAILABLE;
	public $type;

	public $enterpos;
	public $enterLevelName;
	public $portalEnterPos1;
	public $portalEnterPos2;

	public $mapselectpos;
	public $mapselectLevelName;

	public $exitpos;
	public $exitLevelName;
	public $players = 0;
	public $waitCountDown = 20;
	public $winnerCoins = 5;
	public $minPlayers = 1;
	public $maxPlayers = 24;
	public $joinedPlayers = [ ];
	public $playersWithEffects = [ ];

	public $gatePos1;
	public $gatePos2;
	public $particles;

	public $signJoin;
	public $signJoin2;
	public $signStats;
	public $signExit;
	public $signVote;

	public $maps = [ ];
	public $mapVotes = [ ];
	public $currentMap = null;
	public $currentStep = self::STEP_JOINING;
	public $joinDownCounter = 15;
	public $joinDownCounterReset = 15;
	public $startTime;
	public $finishTime;
	public $mapSelectionWaitTime = 30;
	public $forceResetDoor = true;
	public $forceSignUpdate = true;
	public $chestsresetcounter = 0;
	public $openchests = [ ];
	public $newtask = null;
	/**
	 * constructor
	 *
	 * @param
	 *        	$name
	 * @internal param HungerGamesPlugIn $plugin
	 */
	public function __construct($name) {
		$this->name = $name;
	}
	public function save($path) {
		$path = $path . self::DIR_LEVEL_DATA;
		if (! file_exists ( $path )) {
			@mkdir ( $path, 0755, true );
		}
		$name = $this->name;
		$data = new Config ( $path . "$name.yml", Config::YAML );
		if (empty ( $this->levelName )) {
			$this->plugin->getLogger ()->error ( "[GameLevel] missing name - level Name is NULL -" . $this->name );
			return false;
		}
		if (empty ( $this->name )) {
			$this->plugin->getLogger ()->error ( "[GameLevel] missing name -  Name is NULL -" . $this->name );
			return false;
		}
		$data->set ( "id", time () );
		$data->set ( "name", $this->name );
		$data->set ( "type", $this->type == null ? self::TYPE_MAP_PORTAL : $this->type );
		if (empty ( $this->displayName )) {
			$this->displayName = $this->name;
		}
		$data->set ( "displayName", $this->displayName );
		$data->set ( "levelName", $this->levelName );
		$data->set ( "waitCountDown", $this->waitCountDown );
		$data->set ( "minPlayers", $this->minPlayers );
		$data->set ( "maxPlayers", $this->maxPlayers );
		$data->set ( "type", $this->type );
		$data->set ( "joinDownCounter", $this->joinDownCounter );
		$data->set ( "particles", $this->particles );
		$data->set ( "winnerCoins", $this->winnerCoins );
		// locations
		if ($this->location != null) {
			$data->set ( "locationX", $this->location->x );
			$data->set ( "locationY", $this->location->y );
			$data->set ( "locationZ", $this->location->z );
		}
		
		if ($this->gatePos1 != null) {
			$data->set ( "gatePos1X", $this->gatePos1->x );
			$data->set ( "gatePos1Y", $this->gatePos1->y );
			$data->set ( "gatePos1Z", $this->gatePos1->z );
		}
		if ($this->gatePos2 != null) {
			$data->set ( "gatePos2X", $this->gatePos2->x );
			$data->set ( "gatePos2Y", $this->gatePos2->y );
			$data->set ( "gatePos2Z", $this->gatePos2->z );
		}
		
		if ($this->signJoin != null && $this->signJoin != false) {
			$data->set ( "signJoinX", round ( $this->signJoin->x ) );
			$data->set ( "signJoinY", round ( $this->signJoin->y ) );
			$data->set ( "signJoinZ", round ( $this->signJoin->z ) );
		}
		
		if ($this->signJoin2 != null && $this->signJoin2 != false) {
			$data->set ( "signJoin2X", round ( $this->signJoin2->x ) );
			$data->set ( "signJoin2Y", round ( $this->signJoin2->y ) );
			$data->set ( "signJoin2Z", round ( $this->signJoin2->z ) );
		}
		
		if ($this->signStats != null && $this->signStats != false) {
			$data->set ( "signStatsX", round ( $this->signStats->x ) );
			$data->set ( "signStatsY", round ( $this->signStats->y ) );
			$data->set ( "signStatsZ", round ( $this->signStats->z ) );
		}
		if ($this->signExit != null && $this->signExit != false) {
			$data->set ( "signExitX", round ( $this->signExit->x ) );
			$data->set ( "signExitY", round ( $this->signExit->y ) );
			$data->set ( "signExitZ", round ( $this->signExit->z ) );
		}
		
		if (! empty ( $this->enterLevelName )) {
			$data->set ( "enterLevelName", $this->enterLevelName );
			if ($this->enterpos != null) {
				$data->set ( "entranceX", $this->enterpos->x );
				$data->set ( "entranceY", $this->enterpos->y );
				$data->set ( "entranceZ", $this->enterpos->z );
			}
		}
		if (! empty ( $this->exitLevelName )) {
			$data->set ( "exitLevelName", $this->exitLevelName );
			if ($this->exitpos != null) {
				$data->set ( "exitX", $this->exitpos->x );
				$data->set ( "exitY", $this->exitpos->y );
				$data->set ( "exitZ", $this->exitpos->z );
			}
		}
		
		if (! empty ( $this->mapselectLevelName )) {
			$data->set ( "mapselectLevelName", $this->mapselectLevelName );
			if ($this->mapselectpos != null) {
				$data->set ( "mapselectX", $this->mapselectpos->x );
				$data->set ( "mapselectY", $this->mapselectpos->y );
				$data->set ( "mapselectZ", $this->mapselectpos->z );
			}
		}
		
		if ($this->portalEnterPos1 != null) {
			$data->set ( "portalEnter1X", $this->portalEnterPos1->x );
			$data->set ( "portalEnter1Y", $this->portalEnterPos1->y );
			$data->set ( "portalEnter1Z", $this->portalEnterPos1->z );
		}
		
		if ($this->portalEnterPos2 != null) {
			$data->set ( "portalEnter2X", $this->portalEnterPos2->x );
			$data->set ( "portalEnter2Y", $this->portalEnterPos2->y );
			$data->set ( "portalEnter2Z", $this->portalEnterPos2->z );
		}
		
		$data->set ( "mapSelectionWaitTime", $this->mapSelectionWaitTime );
		$data->set ( "maps", $this->maps );
		$data->save ();
		return true;
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
	public static function convertPositionListToArray($positions) {
		$values = [ ];
		if ($positions != null) {
			foreach ( $positions as $pos ) {
				$values [] = array (
						$pos->x,
						$pos->y,
						$pos->z 
				);
			}
		}
		return values;
	}
	public function delete($path) {
		$xpath = $path . self::TYPE_MAP_PORTAL;
		$name = $this->name;
		@unlink ( $path . "$name.yml" );
		
		$this->unlinkRecursive ( $path . "$name.yml", false );
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
	public function randomMap() {
		return $this->maps != null && count ( $this->maps ) > 0 ? array_rand ( $this->maps ) : null;
	}
	public function teleportJoinedPlayersToMapSelectionRoom() {
		foreach ( $this->joinedPlayers as $player ) {
			$player->teleport ( $this->mapselectpos );
		}
	}
	public static function preloadLevels(HungerGamesPlugIn &$plugin) {
		$levelList = [ ];
		$path = $plugin->getDataFolder () . self::DIR_LEVEL_DATA;
		if (! file_exists ( $path )) {
			@mkdir ( $path, 0755, true );
		}
		$plugin->getLogger ()->info ( "#loading game level on " . $path );
		$handler = opendir ( $path );
		while ( ($filename = readdir ( $handler )) !== false ) {
			$plugin->getLogger ()->info ( $filename );
			if ($filename != "." && $filename != "..") {
				$data = new Config ( $path . $filename, Config::YAML );
				Server::getInstance ()->loadLevel ( $data->get ( "levelName" ) );
				$pLevel = Server::getInstance ()->getLevelByName ( $data->get ( "levelName" ) );
				$name = str_replace ( ".yml", "", $filename );
				$arena = new GameLevelModel ( $plugin, $name );
				$arena->id = time ();
				$arena->level = $pLevel;
				$arena->name = $data->get ( "name" );
				$arena->displayName = $data->get ( "displayName" );
				$arena->datafile = $data->get ( "datafile" );
				$arena->levelName = $data->get ( "levelName" );
				$arena->type = $data->get ( "type" );
				$arena->location = new Position ( $data->get ( "locationX" ), $data->get ( "locationY" ), $data->get ( "locationZ" ) );
				$arena->waitCountDown = $data->get ( "waitCountDown",20);
				$arena->joinDownCounter = $data->get ( "joinDownCounter", 18 );
				$arena->joinDownCounterReset = $data->get ( "joinDownCounter", 18 );
				$arena->status = self::STATUS_AVAILABLE;
				$arena->mapSelectionWaitTime = $data->get ( "mapSelectionWaitTime" );
				
				if ($data->get ( "entranceX" ) != null) {
					$arena->enterpos = new Position ( $data->get ( "entranceX" ), $data->get ( "entranceY" ), $data->get ( "entranceZ" ), $pLevel );
				}
				
				if ($data->get ( "exitX" ) != null) {
					$arena->exitPos = new Position ( $data->get ( "exitX" ), $data->get ( "exitY" ), $data->get ( "exitZ" ) );
				}
				
				if ($data->get ( "gatePos1X" ) != null) {
					$arena->gatePos1 = new Position ( $data->get ( "gatePos1X" ), $data->get ( "gatePos1Y" ), $data->get ( "gatePos1Z" ), $pLevel );
				}
				if ($data->get ( "gatePos2X" ) != null) {
					$arena->gatePos2 = new Position ( $data->get ( "gatePos2X" ), $data->get ( "gatePos2Y" ), $data->get ( "gatePos2Z" ), $pLevel );
				}
				
				if ($data->get ( "portalEnter1X" ) != null) {
					$arena->portalEnterPos1 = new Position ( $data->get ( "portalEnter1X" ), $data->get ( "portalEnter1Y" ), $data->get ( "portalEnter1Z" ), $pLevel );
				}
				
				if ($data->get ( "portalEnter2X" ) != null) {
					$arena->portalEnterPos2 = new Position ( $data->get ( "portalEnter2X" ), $data->get ( "portalEnter2Y" ), $data->get ( "portalEnter2Z" ), $pLevel );
				}
				
				$bx = $data->get ( "mapselectX" );
				$by = $data->get ( "mapselectY" );
				$bz = $data->get ( "mapselectZ" );
				$arena->mapselectpos = new Position ( $bx, $by, $bz, $pLevel );
				
				$bx = $data->get ( "signJoinX" );
				$by = $data->get ( "signJoinY" );
				$bz = $data->get ( "signJoinZ" );
				$arena->signJoin = new Position ( $bx, $by, $bz, $pLevel );
				
				$bx2 = $data->get ( "signJoin2X" );
				$by2 = $data->get ( "signJoin2Y" );
				$bz2 = $data->get ( "signJoin2Z" );
				$arena->signJoin2 = new Position ( $bx2, $by2, $bz2, $pLevel );
				
				$bx = $data->get ( "signStatsX" );
				$by = $data->get ( "signStatsY" );
				$bz = $data->get ( "signStatsZ" );
				$arena->signStats = new Position ( $bx, $by, $bz, $pLevel );
				
				$bx = $data->get ( "signExitX" );
				$by = $data->get ( "signExitY" );
				$bz = $data->get ( "signExitZ" );
				$arena->signExit = new Position ( $bx, $by, $bz );
				$arena->mapselectLevelName = $data->get ( "mapselectLevelName" );
				
				$arena->maps = $data->get ( "maps" );
				$arena->minPlayers = $data->get ( "minPlayers" );
				$arena->maxPlayers = $data->get ( "maxPlayers" );
				$arena->particles = $data->get ( "particles" );
				$arena->winnerCoins = $data->get ( "winnerCoins", 5 );
				
				$levelList [$name] = $arena;
			}
		}
		closedir ( $handler );
		return $levelList;
	}
	public function insideLevelEntracePortal($pos) {
		$p1 = new Position ( $this->portalEnterPos1->x, $this->portalEnterPos1->y, $this->portalEnterPos1->z );
		$p2 = new Position ( $this->portalEnterPos2->x, $this->portalEnterPos2->y + 1, $this->portalEnterPos2->z );
		if ((min ( $p1->getX (), $p2->getX () ) <= $pos->getX ()) && (max ( $p1->getX (), $p2->getX () ) >= $pos->getX ()) && (min ( $p1->getY (), $p2->getY () ) <= $pos->getY ()) && (max ( $p1->getY (), $p2->getY () ) >= $pos->getY ()) && (min ( $p1->getZ (), $p2->getZ () ) <= $pos->getZ ()) && (max ( $p1->getZ (), $p2->getZ () ) >= $pos->getZ ())) {
			return true;
		} else {
			return false;
		}
	}
	/**
	 *
	 * @param GameLevelModel $lv        	
	 * @param Player $player        	
	 * @return boolean
	 */
	public function portalEnter(HungerGamesPlugIn $plugin, GameLevelModel $lv, Player $player) {
		if ($player->getLevel ()->getName () != $lv->levelName) {
			return true;
		}
		if ($lv->insideLevelEntracePortal ( $player->getPosition () )) {
			if (! isset ( $this->joinedPlayers [$player->getName ()] )) {
				if ($plugin->storyenforceaccess && $lv->type != 1) {
					$w = $plugin->storyManager->hasPlayerWonLevel ( $player->getName (), ($lv->type - 1) );
					if (! $w) {
						$player->sendMessage ( TextFormat::RED . "[HG Story Mode Enabled]" );
						$player->sendMessage ( TextFormat::YELLOW . "Required ".TextFormat::GOLD."WIN ".TextFormat::YELLOW ."previous level before play this level!" );
						$player->getLevel ()->addSound ( new LaunchSound ( $player->getPosition () ), array (
								$player 
						) );
						if ($player->isOp ()) {
							$player->sendMessage ( TextFormat::AQUA . "[Admin BY-PASS Story Mode Checking]" );
						} else {
							$player->teleport ( $lv->enterpos );
							return;
						}
					}
				}
				
				$this->joinedPlayers [$player->getName ()] = $player;
				$message= TextFormat::GRAY . "[HG] [" . TextFormat::AQUA . $player->getName () . TextFormat::GRAY . "] joined " . $lv->displayName . " [" . TextFormat::GREEN . count ( $lv->joinedPlayers ) . TextFormat::GRAY . " |" . TextFormat::WHITE . "minimal " . TextFormat::YELLOW . $lv->minPlayers . TextFormat::GRAY . "]";
				$player->getServer()->broadcastMessage($message , $this->joinedPlayers);
				
				$lv->level->addSound ( new DoorSound ( $player->getPosition () ), $lv->joinedPlayers );
				$effect = MagicUtil::addEffect ( $player, Effect::STRENGTH );
				if ($effect != null) {
					$this->playersWithEffects [$player->getName ()] = $effect;
				}
				if (! $player->isOp ()) {
					if (! $player->isSurvival ()) {
						$player->setGamemode ( Player::SURVIVAL );
					}
				}
				$player->sendMessage(TextFormat::YELLOW. "[HG] Please wait here");				
				$player->sendMessage(TextFormat::GRAY. "[HG] The game auto start with minimal players");
			}
		} else {
			if (isset ( $this->joinedPlayers [$player->getName ()] )) {
				$message =TextFormat::GRAY."[ " . $player->getName () . " ] left " . $lv->displayName ;
				$player->getServer()->broadcastMessage($message , $this->joinedPlayers);
								
				$lv->level->addSound ( new DoorSound ( $player ), $lv->joinedPlayers );
				unset ( $this->joinedPlayers [$player->getName ()] );
				if (count ( $this->joinedPlayers ) < $lv->minPlayers) {
					$lv->joinDownCounter = $lv->joinDownCounterReset;
				}				
				foreach ($plugin->arenaManager->arenas as &$arena) {
					if ($arena instanceof MapArenaModel) {
						if (isset ( $arena->votedPlayers [$player->getName ()] )) {
							unset ( $arena->votedPlayers [$player->getName ()] );
							if ($arena->vote >= 1) {
								$arena->vote--;
							}
							break;
						}
					}
				}				
				if (isset ( $this->playersWithEffects [$player->getName ()] )) {
					$effect = $this->playersWithEffects [$player->getName ()];
					if ($effect != null) {
						$player->removeEffect ( $effect->getId () );
						unset ( $this->playersWithEffects [$player->getName ()] );
					}
				}
			}
		}
	}
	
	/**
	 *
	 * @param unknown $action        	
	 * @param string $output        	
	 * @return boolean
	 */
	public function setPortalGate($action, &$output = null) {
		$start_time = microtime(true);
		$send = false;
		$level = $this->gatePos1->getLevel ();
		$bcnt = 1;
		$startX = min ( $this->gatePos1->x, $this->gatePos2->x );
		$endX = max ( $this->gatePos1->x, $this->gatePos2->x );
		$startY = min ( $this->gatePos1->y, $this->gatePos2->y );
		$endY = max ( $this->gatePos1->y, $this->gatePos2->y );
		$startZ = min ( $this->gatePos1->z, $this->gatePos2->z );
		$endZ = max ( $this->gatePos1->z, $this->gatePos2->z );
		$count = 0;
		for($x = $startX; $x <= $endX; ++ $x) {
			for($y = $startY; $y <= $endY; ++ $y) {
				for($z = $startZ; $z <= $endZ; ++ $z) {
					$block = Item::get ( Item::AIR );
					if ($action != null && $action === "close") {
						$block = Item::get ( Item::IRON_BAR );
					}
					$level->setBlock ( new Position ( $x, $y, $z, $level ), $block->getBlock (), false, true );
					$count ++;
				}
			}
		}
		$output .= "$count block(s) have been updated took ".(microtime(true)-$start_time)." ms";
		return true;
	}
	/**
	 *
	 * @param Level $level        	
	 * @param Position $pos1        	
	 * @param Position $pos2        	
	 * @param string $output        	
	 * @return number
	 */
	public function countNotEmptyBlocks(Level $level, Position $pos1, Position $pos2, &$output = null) {
		$count = 0;
		$startX = min ( $pos1->x, $pos2->x );
		$endX = max ( $pos1->x, $pos2->x );
		$startY = min ( $pos1->y + 6, $pos2->y );
		$endY = max ( $pos1->y, $pos2->y );
		$startZ = min ( $pos1->z, $pos2->z );
		$endZ = max ( $pos1->z, $pos2->z );
		
		for($x = $startX; $x <= $endX; ++ $x) {
			for($y = $startY; $y <= $endY; ++ $y) {
				for($z = $startZ; $z <= $endZ; ++ $z) {
					$blockid = $level->getBlockIdAt ( $x, $y, $z );
					if ($blockid != 0) {
						$count ++;
					}
				}
			}
		}
		$output .= "$count block(s) have been updated.\n";
		return $count;
	}
	public static function createSampleLevel(HungerGamesPlugIn $plugin) {
		$portal = new GameLevel ( "sample_portal" );
		$portal->levelName = "portal";
		$portal->displayName = "map portal";
		$portal->type = self::TYPE_LEVEL_ONE;
		$portal->enterpos = new Position ( "128", "12", "123" );
		$portal->enterLevelName = "HG_Island";
		$portal->exitpos = new Position ( "128", "12", "123" );
		$portal->exitLevelName = "MinigamesHub";
		
		$portal->location = new Position ( "128", "12", "123" );
		$portal->maps = array (
				"catching_fire",
				"hg_tornament" 
		);
		
		$portal->save ( $plugin->getDataFolder () );
	}
}
