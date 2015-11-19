<?php

namespace hungergames\arena;

use hungergames\main\HungerGamesPlugIn;
use hungergames\level\GamePlayer;
use hungergames\portal\MapPortal;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;

class MapArenaModel extends ArenaInfo {
	public $id;
	public $name;
	public $displayName;
	public $type;
	public $level;
	public $levelName;
	public $vote = 0;
	public $votedPlayers = [ ];
	public $location;
	public $items = [ ];
	public $playerscores = [ ];
	public $shooters = [ ];
	public $locked = false;
	public $unlockItem = false;
	public $unlockPassword;
	public $block;
	public $numberOfRandomItems = 2;
	public $sectionpos1;
	public $sectionpos2;
	public $gatePos1;
	public $gatePos2;
	public $arenaEnterPos;
	public $arenaExitPos;
	public $arenaDeathMatchPos;
	public $arenaWaitPos;
	public $enterLevelName;
	public $enterLevel;
	public $exitLevelName;
	public $exitLevel;
	public $invisibleTime = 20;
	public $status = false;
	public $signVote;
	public $signJoin;
	public $signStats;
	public $signExit;
	public $portalEnterPos1;
	public $portalEnterPos2;
	public $portalExitPos1;
	public $portalExitPos2;
	public $playerKits = [ ];
	public $chestLocations = [ ];
	public $spawnLocations = [ ];
	public $joinedPlayers = [ ];
	public $livePlayers = [ ];
	public $killedPlayers = [ ];
	public $leftPlayers = [ ];
	public $killsPerPlayers = [ ];
	public $hitsPerPlayers = [ ];
	public $autoRefill = true;
	public $minPlayers = 2;
	public $maxPlayers = 24;
	public $allowPvP = true;
	public $allowBreakBlock = true;
	public $allowBlockPlace = true;
	public $requiredAccessRank = 0;
	public $resetPeriod = 100;
	public $winnerCoins = 5;
	public $winnerRankUp = 1;
	public $playStartCountdown = 10;
	public $playInvisibleCountdown = 10;
	public $playFinishCountdown = 5;
	public $playStartTime;
	public $playFinishTime;
	public $huntingStartTime;
	public $huntingFinishTime;
	public $huntingDuration = 100;
	public $waitCountDown = 10;
	public $waitStartTime;
	public $waitFinishTime;
	public $deathMatchStartCountdown = 10;
	public $deathMatchFinishCountdown = 5;
	public $deathMatchDuration = 100;
	public $deathMatchEnter;
	public $deathMatchPos1;
	public $deathMatchPos2;
	public $deathMatchStartTime;
	public $deathMatchFinishTime;
	public $deatchMatchPlayers = [ ];
	public $deatchMatchWinner;
	public $published = false;
	public $openchests = [ ];
	
	// plugin
	public $plugin;
	public function __construct($name) {
		$this->name = $name;
	}
	public function resetVoteCounter() {
		$this->vote = 0;
	}
	public function save($datapath) {
		$datapath = $datapath . self::ARENA_DIRECTORY;
		if (! file_exists ( $datapath )) {
			mkdir ( $datapath );
		}
		$name = $this->name;
		$data = new Config ( $datapath . "$name.yml", Config::YAML );
		$data->set ( "name", $this->name );
		$data->set ( "levelName", $this->levelName );
		if (empty ( $this->displayName )) {
			$this->displayName = $this->name;
		}
		$data->set ( "displayName", $this->displayName );
		$data->set ( "invisibleTime", $this->invisibleTime );
		$data->set ( "status", $this->status );
		$data->set ( "waitCountDown", $this->waitCountDown );
		// locations
		if ($this->location != null) {
			$data->set ( "locationX", $this->location->x );
			$data->set ( "locationY", $this->location->y );
			$data->set ( "locationZ", $this->location->z );
		}
		// deathmatch spawn point
		if ($this->arenaDeathMatchPos != null) {
			$data->set ( "deathMatchPosX", $this->deathmatchpos->x );
			$data->set ( "deathMatchPosY", $this->deathmatchpos->y );
			$data->set ( "deathMatchPosZ", $this->deathmatchpos->z );
		}
		if ($this->arenaEnterPos != null) {
			$data->set ( "arenaEnterX", $this->arenaEnterPos->x );
			$data->set ( "arenaEnterY", $this->arenaEnterPos->y );
			$data->set ( "arenaEnterZ", $this->arenaEnterPos->z );
		}
		
		if ($this->arenaExitPos != null) {
			$data->set ( "arenaExitX", $this->arenaExitPos->x );
			$data->set ( "arenaExitY", $this->arenaExitPos->y );
			$data->set ( "arenaExitZ", $this->arenaExitPos->z );
		}
		
		if ($this->arenaDeathMatchPos != null) {
			$data->set ( "arenaDeathMatchX", $this->arenaDeathMatchPos->x );
			$data->set ( "arenaDeathMatchY", $this->arenaDeathMatchPos->y );
			$data->set ( "arenaDeathMatchZ", $this->arenaDeathMatchPos->z );
		}
		
		if ($this->arenaWaitPos != null) {
			$data->set ( "arenaWaitX", $this->arenaWaitPos->x );
			$data->set ( "arenaWaitY", $this->arenaWaitPos->y );
			$data->set ( "arenaWaitZ", $this->arenaWaitPos->z );
		}
		
		if ($this->signVote != null && $this->signVote != false) {
			$data->set ( "signVoteX", round ( $this->signVote->x ) );
			$data->set ( "signVoteY", round ( $this->signVote->y ) );
			$data->set ( "signVoteZ", round ( $this->signVote->z ) );
		}
		
		if ($this->signJoin != null && $this->signJoin != false) {
			$data->set ( "signJoinX", round ( $this->signJoin->x ) );
			$data->set ( "signJoinY", round ( $this->signJoin->y ) );
			$data->set ( "signJoinZ", round ( $this->signJoin->z ) );
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
		
		if ($this->portalExitPos1 != null) {
			$data->set ( "portalExit1X", $this->portalExitPos1->x );
			$data->set ( "portalExit1Y", $this->portalExitPos1->y );
			$data->set ( "portalExit1Z", $this->portalExitPos1->z );
		}
		if ($this->portalExitPos2 != null) {
			$data->set ( "portalExit2X", $this->portalExitPos2->x );
			$data->set ( "portalExit2Y", $this->portalExitPos2->y );
			$data->set ( "portalExit2Z", $this->portalExitPos2->z );
		}
		
		// timing
		$data->set ( "resetPeriod", $this->resetPeriod );
		$data->set ( "winnerCoins", $this->winnerCoins );
		$data->set ( "winnerRankUp", $this->winnerRankUp );
		$data->set ( "deathMatchDuration", $this->deathMatchDuration );
		$data->set ( "huntingDuration", $this->huntingDuration );
		// play
		$data->set ( "playStartCountdown", $this->playStartCountdown );
		$data->set ( "playFinishCountdown", $this->playFinishCountdown );
		$data->set ( "playStartTime", $this->playStartTime );
		$data->set ( "playFinishTime", $this->playFinishTime );
		// death match
		$data->set ( "deathMatchStartCountdown", $this->deathMatchStartCountdown );
		$data->set ( "deathMatchFinishCountdown", $this->deathMatchFinishCountdown );
		$data->set ( "deathMatchStartTime", $this->deathMatchStartTime );
		$data->set ( "deathMatchFinishTime", $this->deathMatchFinishTime );
		$data->set ( "deatchMatchPlayers", $this->deatchMatchPlayers );
		$data->set ( "deatchMatchWinner", $this->deatchMatchWinner );
		if ($this->deathMatchEnter != null) {
			$data->set ( "deathMatchEnterX", $this->deathMatchEnter->x );
			$data->set ( "deathMatchEnterY", $this->deathMatchEnter->y );
			$data->set ( "deathMatchEnterZ", $this->deathMatchEnter->z );
		}
		if ($this->deathMatchPos1 != null) {
			$data->set ( "deathMatchPos1X", $this->deathMatchPos1->x );
			$data->set ( "deathMatchPos1Y", $this->deathMatchPos1->y );
			$data->set ( "deathMatchPos1Z", $this->deathMatchPos1->z );
		}
		if ($this->deathMatchPos2 != null) {
			$data->set ( "deathMatchPos2X", $this->deathMatchPos2->x );
			$data->set ( "deathMatchPos2Y", $this->deathMatchPos2->y );
			$data->set ( "deathMatchPos2Z", $this->deathMatchPos2->z );
		}
		$data->set ( "minPlayers", $this->minPlayers );
		$data->set ( "maxPlayers", $this->maxPlayers );
		$data->set ( "requiredAccessRank", $this->requiredAccessRank );
		$data->set ( "autoRefill", $this->autoRefill );
		$data->set ( "items", $this->items );
		$data->set ( "locked", $this->locked );
		$data->set ( "unlockItem", $this->unlockItem );
		$data->set ( "unlockPassword", $this->unlockPassword );
		$data->set ( "numberOfRandomItems", $this->numberOfRandomItems );
		$data->set ( "allowPvP", $this->allowPvP );
		$data->set ( "allowBreakBlock", $this->allowBreakBlock );
		$data->set ( "allowBlockPlace", $this->allowBlockPlace );
		$data->set ( "requiredAccessRank", $this->requiredAccessRank );
		$data->set ( "block", $this->block );
		if ($this->sectionpos1 != null) {
			$data->set ( "sectionpos1X", $this->sectionpos1->x );
			$data->set ( "sectionpos1Y", $this->sectionpos1->y );
			$data->set ( "sectionpos1Z", $this->sectionpos1->z );
		}
		if ($this->sectionpos2 != null) {
			$data->set ( "sectionpos2X", $this->sectionpos2->x );
			$data->set ( "sectionpos2Y", $this->sectionpos2->y );
			$data->set ( "sectionpos2Z", $this->sectionpos2->z );
		}
		if ($this->gatePos1 != null) {
			$data->set ( "gatePos1X", $this->gatePos1->x );
			$data->set ( "gatePos1Y", $this->gatePos1->y );
			$data->set ( "gatePos1Z", $this->gatePos1->z );
		}
		if ($this->gatePos2 != null) {
			$data->set ( "gatePos2X", $this->gatePos2->x );
			$data->set ( "gatePos2Y", $this->gatePos2->y );
			$data->set ( "gatePos2X", $this->gatePos2->z );
		}
		if ($this->chestLocations != null && count ( $this->chestLocations ) > 0) {
			$data->set ( "chestLocations", self::convertPositionListToArray ( $this->chestLocations ) );
		}
		if ($this->spawnLocations != null && count ( $this->spawnLocations ) > 0) {
			$data->set ( "spawnLocations", self::convertPositionListToArray ( $this->spawnLocations ) );
		}
		if ($this->spawnLocations != null && count ( $this->spawnLocations ) === 0) {
			$data->set ( "spawnLocations", $this->spawnLocations );
		}
		
		if ($this->playerKits != null && count ( $this->playerKits ) > 0) {
			$data->set ( "playerKits", $this->playerKits );
		}
		if (! empty ( $this->enterLevelName )) {
			$data->set ( "enterLevelName", $this->enterLevelName );
		}
		if (! empty ( $this->exitLevelName )) {
			$data->set ( "exitLevelName", $this->exitLevelName );
		}
		$data->set ( "published", $this->published );
		$data->save ();
	}
	public static function convertPositionListToArray($positions) {
		$values = [ ];
		if ($positions != null) {
			foreach ( $positions as $pos ) {
				$values [] = array (
						round ( $pos->x ),
						round ( $pos->y ),
						round ( $pos->z ) 
				);
			}
		}
		return $values;
	}
	public static function load($path, $name) {
		$arena = null;
		if (! file_exists ( $path . self::ARENA_DIRECTORY . "$name.yml" )) {
			return null;
		}
		$data = new Config ( $path . self::ARENA_DIRECTORY . "$name.yml", Config::YAML );
		$data->getAll ();
		if ($data != null) {
			$arena = new MapArena ( $data->get ( "name" ), $data->get ( "levelName" ), null );
			$arena->id = $data->get ( "id" );
			$arena->name = $data->get ( "name" );
			$arena->type = $data->get ( "type" );
			$arena->levelName = $data->get ( "levelName" );
		}
	}
	public function isValid() {
		if ($this->name != null && $this->position != null) {
			return true;
		}
		return false;
	}
	public static function getData($path, $name) {
		if (! file_exists ( $path . self::DIRECTORY . "$name.yml" )) {
			return null;
		}
		$data = new Config ( $path . self::DIRECTORY . "$name.yml", Config::YAML );
		$data->getAll ();
		return $data;
	}
	public function portalEnter($pos, Player $player) {
		if ((min ( $this->portalEnterPos1->getX (), $this->portalEnterPos2->getX () ) <= $pos->getX ()) && (max ( $this->portalEnterPos1->getX (), $this->portalEnterPos2->getX () ) >= $pos->getX ()) && (min ( $this->portalEnterPos1->getY (), $this->portalEnterPos2->getY () ) <= $pos->getY ()) && (max ( $this->portalEnterPos1->getY (), $this->portalEnterPos2->getY () ) >= $pos->getY ()) && (min ( $this->portalEnterPos1->getZ (), $this->portalEnterPos2->getZ () ) <= $pos->getZ ()) && (max ( $this->portalEnterPos1->getZ (), $this->portalEnterPos2->getZ () ) >= $pos->getZ ())) {
			$player->onGround = true;
			MapPortal::teleportToMap ( $this->enterLevelName, $player );
			$player->teleport ( $this->arenaEnterPos );
			$player->onGround = false;
		} else {
			return false;
		}
	}
	public function portalExit($pos, Player $player) {
		if ((min ( $this->portalExitPos1->getX (), $this->portalExitPos2->getX () ) <= $pos->getX ()) && (max ( $this->portalExitPos1->getX (), $this->portalExitPos2->getX () ) >= $pos->getX ()) && (min ( $this->portalExitPos1->getY (), $this->portalExitPos2->getY () ) <= $pos->getY ()) && (max ( $this->portalExitPos1->getY (), $this->portalExitPos2->getY () ) >= $pos->getY ()) && (min ( $this->portalExitPos1->getZ (), $this->portalExitPos2->getZ () ) <= $pos->getZ ()) && (max ( $this->portalExitPos1->getZ (), $this->portalExitPos2->getZ () ) >= $pos->getZ ())) {
			$player->onGround = true;
			MapPortal::teleportToMap ( $this->exitLevelName, $player );
			$player->teleport ( $this->arenaExitPos );
			$player->onGround = false;
			$this->removePlayerFromArena ( $player );
			return true;
		} else {
			return false;
		}
	}
	public function removePlayerFromArena(Player $player) {
		if (isset ( $this->joinedPlayers [$player->getName ()] )) {
			unset ( $this->joinedPlayers [$player->getName ()] );
		}
		if (isset ( $this->livePlayers [$player->getName ()] )) {
			unset ( $this->livePlayers [$player->getName ()] );
		}
		
		$this->leftPlayers [$player->getName ()] = $player;
	}
	public function contains($pos) {
		$p1 = new Position ( $this->sectionpos1->x, $this->sectionpos1->y, $this->sectionpos1->z );
		$p2 = new Position ( $this->sectionpos2->x, $this->sectionpos2->y + 1, $this->sectionpos2->z );
		if ((min ( $p1->getX (), $p2->getX () ) <= $pos->getX ()) && (max ( $p1->getX (), $p2->getX () ) >= $pos->getX ()) && (min ( $p1->getY (), $p2->getY () ) <= $pos->getY ()) && (max ( $p1->getY (), $p2->getY () ) >= $pos->getY ()) && (min ( $p1->getZ (), $p2->getZ () ) <= $pos->getZ ()) && (max ( $p1->getZ (), $p2->getZ () ) >= $pos->getZ ())) {
			return true;
		} else {
			return false;
		}
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
	public function delete($path) {
		$xpath = $path . self::ARENA_DIRECTORY;
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
	public function setMine(&$output = null) {
		$send = false;
		$level = $this->sectionpos1->getLevel ();
		$bcnt = 1;
		$startX = min ( $this->sectionpos1->x, $this->sectionpos2->x );
		$endX = max ( $this->sectionpos1->x, $this->sectionpos2->x );
		$startY = min ( $this->sectionpos1->y, $this->sectionpos2->y );
		$endY = max ( $this->sectionpos1->y, $this->sectionpos2->y );
		$startZ = min ( $this->sectionpos1->z, $this->sectionpos2->z );
		$endZ = max ( $this->sectionpos1->z, $this->sectionpos2->z );
		$count = 0;
		for($x = $startX; $x <= $endX; ++ $x) {
			for($y = $startY; $y <= $endY; ++ $y) {
				for($z = $startZ; $z <= $endZ; ++ $z) {
					$direct = false;
					$update = false;
					if (rand ( 1, 8 ) == 1) {
						$block = Item::get ( Item::TNT )->getBlock ();
					} else {
						$block = Item::get ( Item::DIRT )->getBlock ();
						$key = $x . "." . $y . "." . $z;
						$this->getPlugin ()->notblocks [$key] = $block;
					}
					$level->setBlock ( new Position ( $x, $y, $z ), $block, $direct, $update );
					$count ++;
				}
			}
		}
		$output .= "$count block(s) have been updated.\n";
		return true;
	}
	public function setFloor(&$output = null) {
		$send = false;
		$level = $this->sectionpos1->getLevel ();
		$bcnt = 1;
		$startX = min ( $this->sectionpos1->x, $this->sectionpos2->x );
		$endX = max ( $this->sectionpos1->x, $this->sectionpos2->x );
		$startY = min ( $this->sectionpos1->y, $this->sectionpos2->y );
		$endY = max ( $this->sectionpos1->y, $this->sectionpos2->y );
		$startZ = min ( $this->sectionpos1->z, $this->sectionpos2->z );
		$endZ = max ( $this->sectionpos1->z, $this->sectionpos2->z );
		$count = 0;
		for($x = $startX; $x <= $endX; ++ $x) {
			for($y = $startY; $y <= $endY; ++ $y) {
				for($z = $startZ; $z <= $endZ; ++ $z) {
					$direct = false;
					$update = false;
					$level->setBlock ( new Position ( $x, $y, $z ), $this->block, $direct, $update );
					$count ++;
				}
			}
		}
		
		if ($send === false) {
			$forceSend = function ($X, $Y, $Z) {
				$this->changedCount [$X . ":" . $Y . ":" . $Z] = 4096;
			};
			$forceSend->bindTo ( $level, $level );
			for($X = $startX >> 4; $X <= ($endX >> 4); ++ $X) {
				for($Y = $startY >> 4; $Y <= ($endY >> 4); ++ $Y) {
					for($Z = $startZ >> 4; $Z <= ($endZ >> 4); ++ $Z) {
						$forceSend ( $X, $Y, $Z );
					}
				}
			}
		}
		$output .= "$count block(s) have been updated.\n";
		return true;
	}
	public function printArenaInfo() {
		$this->plugin->getLogger ()->info ( "--" );
		$this->plugin->getLogger ()->info ( "-Arena: " . $this->name . " status:" . $this->status );
		$this->plugin->getLogger ()->info ( "-Start time: " . $this->playStartTime . " finish time:" . $this->playFinishTime );
		$this->plugin->getLogger ()->info ( "-Arena pos1: " . $this->sectionpos1 . " pos2:" . $this->sectionpos2 );
		$this->plugin->getLogger ()->info ( "--\n" );
	}
	
	/**
	 *
	 * 1.Player joining Arena
	 *
	 * @param Player $player        	
	 * @return GamePlayer|null
	 */
	public function joiningArena(Player $player) {
		if (count ( $this->spawnLocations ) == 0) {
			$message = "[HG] Sorry, arena is full. please try later!";
			$player->sendMessage ( $message );
			return null;
		}
		$gamer = new GamePlayer ( $player->getName () );
		$gamer->player = $player;
		$gamer->arenaSpawnPos = array_shift ( $this->spawnLocations );
		$gamer->arenaDeathMatchPos = $this->arenaDeathMatchPos;
		$gamer->arenaName = $this->name;
		$gamer->levelName = $this->levelName;
		$gamer->invisibleTime = $this->invisibleTime;
		$this->joinedPlayers [$player->getName ()] = $gamer;
		return $gamer;
	}
	
	/**
	 * 2.
	 * Entering waiting room
	 *
	 * @param ArenaPlayer $gamer        	
	 */
	public function singlePlayerEnterWaitingRoom(ArenaPlayer $gamer) {
		$gamer->sendPlayerToWaitingRoom ();
		$message = "[HG] Please wait, preparing arena...";
		$gamer->notify ( $message );
	}
	
	/**
	 * 2.
	 * Entering waiting room
	 * prerequisite: arena have minimal players
	 */
	public function AllPlayersEnterWaitingRoom() {
		foreach ( $this->joinedPlayers as $gamer ) {
			$gamer->sendPlayerToWaitingRoom ();
			$message = "[HG] Please wait, preparing arena...";
			$gamer->notify ( $message );
			
			$this->livePlayers [] = $gamer;
		}
		$this->status = self::STATUS_WAITING;
	}
	
	/**
	 * 3.
	 * Entering Arena Spawn-point
	 * prerequisite: arena map loading complete
	 */
	public function enteringArenaSpawnPoint() {
		foreach ( $this->livePlayers as $gamer ) {
			$this->enterArena ( $gamer );
		}
		$this->status = self::STATUS_SPAWN;
	}
	
	/**
	 * 4.
	 * Releasing Invisible Players
	 *
	 * prerequisite: count-down complete
	 */
	public function releasingInvisiblePlayers() {
		foreach ( $this->livePlayers as $gamer ) {
			$gamer->releasePlayer ();
			foreach ( $this->livePlayers as $op ) {
				$gamer->hidePlayerFrom ( $op );
			}
		}
		$this->status = self::STATUS_INVISIBLE;
	}
	
	/**
	 * 4.
	 * Releasing Visible Players
	 */
	public function releasingVisiblePlayers() {
		foreach ( $this->livePlayers as $gamer ) {
			$gamer->releasePlayer ();
		}
		$this->status = self::STATUS_VISIBLE;
	}
	
	/**
	 * 5.
	 * Start Hunting
	 */
	public function startingHunting() {
		foreach ( $this->livePlayers as $gamer ) {
			$gamer->releasePlayer ();
			foreach ( $this->livePlayers as $op ) {
				$gamer->showPlayerTo ( $op );
			}
		}
		$this->status = self::STATUS_HUNTING;
	}
	
	/**
	 * 6.
	 * Starting Death-Match
	 */
	public function startingDeathMatch() {
		foreach ( $this->livePlayers as $gamer ) {
			$this->enterDeathMatch ( $gamer );
		}
		$this->status = self::STATUS_DEATH_MATCH_START;
	}
	
	/**
	 * 7.
	 * Finishing Death-Match
	 *
	 * @internal param ArenaPlayer $gamer
	 */
	public function finishingDeathMatch() {
		foreach ( $this->livePlayers as $gamer ) {
			$message = "[HG] death-match finished...";
			$gamer->notify ( $message );
		}
		$this->status = self::STATUS_DEATH_MATCH_FINISH;
		// game over
		$this->finishingGamePlay ();
	}
	
	/**
	 * 8.
	 * Finishing Game Play
	 */
	public function finishingGamePlay() {
		foreach ( $this->livePlayers as $gamer ) {
			$this->announceGameEnding ( $gamer );
			$this->exitArena ( $gamer );
		}
	}
	
	/**
	 * 9.
	 * Announce Game Over
	 *
	 * @param ArenaPlayer $gamer        	
	 */
	public function announceGameEnding(ArenaPlayer $gamer) {
		// check Winner
		$message = "[HG] game over...";
		$gamer->notify ( $message );
		$this->status = self::STATUS_GAME_OVER;
	}
	
	/**
	 * Existing Arena
	 *
	 * @param ArenaPlayer $gamer        	
	 */
	public function exitingArena(ArenaPlayer $gamer) {
		$gamer->leaveArena ();
		$message = "[HG] leaving arena...";
		$gamer->notify ( $message );
	}
	
	/**
	 * entering death match
	 *
	 * @param ArenaPlayer $gamer        	
	 */
	private function enterDeathMatch(ArenaPlayer $gamer) {
		$gamer->sendPlayerToDeathMatch ();
		$message = "[HG] let death match begin...";
		$gamer->notify ( $message );
	}
	
	/**
	 * entering arena
	 *
	 * @param ArenaPlayer|GamePlayer $gamer        	
	 */
	public function enterArena(GamePlayer $gamer) {
		$gamer->sendPlayerToArenaSpawnPoint ();
		$gamer->keepPlayerOnGround ();
	}
	public function isArenaFull() {
		if (count ( $this->joinedPlayers ) >= $this->maxPlayers) {
			return true;
		}
		return false;
	}
	public function hasMinPlayers() {
		if (count ( $this->joinedPlayers ) >= $this->minPlayers) {
			return true;
		}
		return false;
	}
	final public static function loadArenaByName($path, $name) {
		if (! file_exists ( $path )) {
			@mkdir ( $path, 0755, true );
		}
		$path = $path . self::ARENA_DIRECTORY;
		$data = new Config ( $path . $name . ".yml", Config::YAML );
		Server::getInstance ()->loadLevel ( $data->get ( "levelName" ) );
		$arenaLevel = Server::getInstance ()->getLevelByName ( $data->get ( "levelName" ) );
		
		$arena = new MapArenaModel ( $name );
		$arena->level = $arenaLevel;
		$arena->id = time ();
		$arena->name = $data->get ( "name" );
		$arena->displayName = $data->get ( "displayName" );
		$arena->datafile = $data->get ( "datafile" );
		$arena->levelName = $data->get ( "levelName" );
		$arena->type = $data->get ( "type" );
		$arena->location = new Position ( $data->get ( "locationX" ), $data->get ( "locationY" ), $data->get ( "locationZ" ) );
		$arena->items = $data->get ( "items" );
		$arena->locked = $data->get ( "locked" );
		$arena->unlockItem = $data->get ( "unlockItem" );
		$arena->unlockPassword = $data->get ( "unlockPassword" );
		$arena->numberOfRandomItems = $data->get ( "numberOfRandomItems" );
		$arena->invisibleTime = $data->get ( "invisibleTime" );
		$arena->waitCountDown = $data->get ( "waitCountDown" );
		$arena->published = $data->get ( "published" );
		$arena->status = MapArenaModel::STATUS_READY;
		
		if ($data->get ( "sectionpos1X" ) != null) {
			$arena->sectionpos1 = new Position ( $data->get ( "sectionpos1X" ), $data->get ( "sectionpos1Y" ), $data->get ( "sectionpos1Z" ), $arenaLevel );
		}
		if ($data->get ( "sectionpos2X" ) != null) {
			$arena->sectionpos2 = new Position ( $data->get ( "sectionpos2X" ), $data->get ( "sectionpos2Y" ), $data->get ( "sectionpos2Z" ), $arenaLevel );
		}
		
		if ($data->get ( "arenaDeathMatchX" ) != null) {
			$arena->arenaEnterPos = new Position ( $data->get ( "arenaDeathMatchX" ), $data->get ( "arenaDeathMatchY" ), $data->get ( "arenaDeathMatchZ" ), $arenaLevel );
		}
		if ($data->get ( "arenaWaitX" ) != null) {
			$arena->arenaWaitPos = new Position ( $data->get ( "arenaWaitX" ), $data->get ( "arenaWaitY" ), $data->get ( "arenaWaitZ" ), $arenaLevel );
		}
		
		if ($data->get ( "gatePos1X" ) != null) {
			$arena->gatePos1 = new Position ( $data->get ( "gatePos1X" ), $data->get ( "gatePos1Y" ), $data->get ( "gatePos1Z" ), $arenaLevel );
		}
		if ($data->get ( "gatePos2X" ) != null) {
			$arena->gatePos2 = new Position ( $data->get ( "gatePos2X" ), $data->get ( "gatePos2Y" ), $data->get ( "gatePos2Z" ), $arenaLevel );
		}
		if ($data->get ( "signExitX" ) != null) {
			$bx = $data->get ( "signExitX" );
			$by = $data->get ( "signExitY" );
			$bz = $data->get ( "signExitZ" );
			$arena->signExit = new Position ( $bx, $by, $bz, $arenaLevel );
		}
		$arena->chestLocations = self::convertArrayToPositions ( $data->get ( "chestLocations" ) );
		$arena->spawnLocations = self::convertArrayToPositions ( $data->get ( "spawnLocations" ) );
		$arena->playerKits = $data->get ( "playerKits" );
		$arena->minPlayers = $data->get ( "minPlayers" );
		$arena->maxPlayers = $data->get ( "maxPlayers" );
		$arena->allowPvP = $data->get ( "allowPvP", true );
		$arena->allowBreakBlock = $data->get ( "allowBreakBlock", true );
		$arena->allowBlockPlace = $data->get ( "allowBlockPlace" );
		$arena->requiredAccessRank = $data->get ( "requiredAccessRank" );
		$arena->winnerCoins = $data->get ( "winnerCoins" );
		$arena->winnerRankUp = $data->get ( "winnerRankUp" );
		$arena->block = $data->get ( "block" );
		$arena->resetPeriod = $data->get ( "resetPeriod" );
		$arena->autoRefill = $data->get ( "autoRefill" );
		$arena->playStartCountdown = $data->get ( "playStartCountdown" );
		$arena->playFinishCountdown = $data->get ( "playFinishCountdown" );
		$arena->playStartTime = $data->get ( "playStartTime" );
		$arena->playFinishTime = $data->get ( "playFinishTime" );
		$arena->deathMatchDuration = $data->get ( "deathMatchDuration", 100 );
		$arena->huntingDuration = $data->get ( "huntingDuration", 200 );
		$arena->deathMatchStartCountdown = $data->get ( "deathMatchStartCountdown" );
		$arena->deathMatchFinishCountdown = $data->get ( "deathMatchFinishCountdown" );
		$arena->deathMatchStartTime = $data->get ( "deathMatchStartTime" );
		$arena->deathMatchFinishTime = $data->get ( "deathMatchFinishTime" );
		$arena->deatchMatchPlayers = $data->get ( "deatchMatchPlayers" );
		$arena->deatchMatchWinner = $data->get ( "deatchMatchWinner" );
		
		if ($data->get ( "deathMatchEnterX" ) != null) {
			$arena->deathMatchEnter = new Position ( $data->get ( "deathMatchEnterX" ), $data->get ( "deathMatchEnterY" ), $data->get ( "deathMatchEnterZ" ), $arenaLevel );
		}
		
		if ($data->get ( "deathMatchPos1X" ) != null) {
			$arena->deathMatchPos1 = new Position ( $data->get ( "deathMatchPos1X" ), $data->get ( "deathMatchPos1Y" ), $data->get ( "deathMatchPos1Z" ), $arenaLevel );
		}
		if ($data->get ( "deathMatchPos2X" ) != null) {
			$arena->deathMatchPos2 = new Position ( $data->get ( "deathMatchPos2X" ), $data->get ( "deathMatchPos2Y" ), $data->get ( "deathMatchPos2Z" ), $arenaLevel );
		}
		
		if ($data->get ( "portalEnter1X" ) != null) {
			$arena->portalEnterPos1 = new Position ( $data->get ( "portalEnter1X" ), $data->get ( "portalEnter1Y" ), $data->get ( "portalEnter1Z" ), $arenaLevel );
		}
		
		if ($data->get ( "portalEnter2X" ) != null) {
			$arena->portalEnterPos2 = new Position ( $data->get ( "portalEnter2X" ), $data->get ( "portalEnter2Y" ), $data->get ( "portalEnter2Z" ), $arenaLevel );
		}
		if ($data->get ( "portalExit1X" ) != null) {
			$arena->portalExitPos1 = new Position ( $data->get ( "portalExit1X" ), $data->get ( "portalExit1Y" ), $data->get ( "portalExit1Z" ), $arenaLevel );
		}
		if ($data->get ( "portalExit2X" ) != null) {
			$arena->portalExitPos2 = new Position ( $data->get ( "portalExit2X" ), $data->get ( "portalExit2Y" ), $data->get ( "portalExit2Z" ), $arenaLevel );
		}
		if ($data->get ( "deathMatchPosX" ) != null) {
			$arena->deathmatchpos = new Position ( $data->get ( "deathMatchPosX" ), $data->get ( "deathMatchPosY" ), $data->get ( "deathMatchPosZ" ), $arenaLevel );
		}
		$arena->enterLevelName = $data->get ( "enterLevelName" );
		if (! empty ( $arena->enterLevelName )) {
			Server::getInstance ()->loadLevel ( $data->get ( "enterLevelName" ) );
			$enterLevel = Server::getInstance ()->getLevelByName ( $data->get ( "enterLevelName" ) );
			if ($data->get ( "arenaEnterX" ) != null) {
				$arena->arenaEnterPos = new Position ( $data->get ( "arenaEnterX" ), $data->get ( "arenaEnterY" ), $data->get ( "arenaEnterZ" ), $enterLevel );
			}
			$arena->enterLevel = $enterLevel;
		}		
		$arena->exitLevelName = $data->get ( "exitLevelName" );
		if (! empty ( $arena->exitLevelName )) {
			Server::getInstance ()->loadLevel ( $data->get ( "exitLevelName" ) );
			$exitLevel = Server::getInstance ()->getLevelByName ( $data->get ( "exitLevelName" ) );
			$arena->exitLevel = $exitLevel;
			if ($data->get ( "arenaExitX" ) != null) {
				$arena->arenaExitPos = new Position ( $data->get ( "arenaExitX" ), $data->get ( "arenaExitY" ), $data->get ( "arenaExitZ" ), $exitLevel );
			}
			if ($data->get ( "signVoteX" ) != null) {
				$arena->signVote = new Position ( $data->get ( "signVoteX" ), $data->get ( "signVoteY" ), $data->get ( "signVoteZ" ), $exitLevel );
			}			
			if ($data->get ( "signJoinX" ) != null) {
				$arena->signJoin = new Position ( $data->get ( "signJoinX" ), $data->get ( "signJoinY" ), $data->get ( "signJoinZ" ), $exitLevel );
			}
			if ($data->get ( "signStatsX" ) != null) {
				$arena->signStats = new Position ( $data->get ( "signStatsX" ), $data->get ( "signStatsY" ), $data->get ( "signStatsZ" ), $exitLevel );
			}
		}
		return $arena;
	}
	final public static function preloadArenas(HungerGamesPlugIn &$plugin) {
		$arenaList = [ ];
		$path = $plugin->getDataFolder () . self::ARENA_DIRECTORY;
		if (! file_exists ( $path )) {
			@mkdir ( $path, 0755, true );
		}
		$plugin->log ( "#loading arenas on " . $path );
		$handler = opendir ( $path );
		while ( ($filename = readdir ( $handler )) !== false ) {
			$plugin->getLogger ()->info ( $filename );
			if ($filename != "." && $filename != "..") {
				$data = new Config ( $path . $filename, Config::YAML );
				Server::getInstance ()->loadLevel ( $data->get ( "levelName" ) );
				$arenaLevel = Server::getInstance ()->getLevelByName ( $data->get ( "levelName" ) );
				$name = str_replace ( ".yml", "", $filename );
				$arena = self::loadArenaByName ( $plugin->getDataFolder (), $name );
				$arenaList [$name] = $arena;
			}
		}
		closedir ( $handler );
		return $arenaList;
	}
	public static function convertArrayToPositions($values) {
		$positions = [ ];
		if ($values != null) {
			foreach ( $values as $v ) {
				$positions [] = new Position ( $v [0], $v [1], $v [2] );
			}
		}
		return $positions;
	}
}     
