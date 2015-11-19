<?php

namespace hungergames\profile;

use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\level\Level;
use hungergames\main\HungerGamesPlugIn;

/**
 *
 * You're allowed to use for own usage only "as-is".
 * you're not allowed to republish or resell or for any commercial purpose.
 *
 * Thanks for your cooperate!
 *
 * Copyright (C) 2014 minecraftgenius76
 * YouTube Channel: http://www.youtube.com/user/minecraftgenius76
 *
 * @author minecraftgenius76
 *        
 */

/**
 * Player Story Providerr
 */
class PlayerStoryProvider {
	const DB_STORE_FILE = "HGv2_player_story.db";
	const DB_SQL_FILE_STORY = "sqlite3_player_story.sql";
	const DB_SQL_FILE_STORY_INDEX = "sqlite3_player_story_index.sql";
	private $plugin;
	public function __construct(HungerGamesPlugIn $pg) {
		$this->plugin = $pg;
	}
	public function getPlugIn() {
		return $this->plugin;
	}
	
	/**
	 * Initialize database
	 */
	public function initlize() {
		@mkdir ( $this->getPlugIn ()->getDataFolder () );
		if (! file_exists ( $this->getPlugIn ()->getDataFolder () . self::DB_STORE_FILE )) {
			$this->getPlugIn ()->database2 = new \SQLite3 ( $this->getPlugIn ()->getDataFolder () . self::DB_STORE_FILE, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE );
			$resource = $this->getPlugIn ()->getResource ( $this::DB_SQL_FILE_STORY );
			$this->getPlugIn ()->database2->exec ( stream_get_contents ( $resource ) );
			$this->plugin->info ( TextFormat::BLUE . "- [HG] Creating New Database - table" );
			$this->getPlugIn ()->database2->query ( "PRAGMA synchronous = OFF" );
			$this->getPlugIn ()->database2->query ( "PRAGMA count_changes = OFF" );
			$this->getPlugIn ()->database2->query ( "PRAGMA journal_mode = MEMORY" );
			$this->getPlugIn ()->database2->query ( "PRAGMA temp_store = MEMORY" );
		} else {
			$this->getPlugIn ()->database2 = new \SQLite3 ( $this->getPlugIn ()->getDataFolder () . self::DB_STORE_FILE, SQLITE3_OPEN_READWRITE );
			$this->plugin->info ( TextFormat::BLUE . "- [HG] use existing player Story database." );
		}		
	}
	
	/**
	 * retrieve top 5 players
	 *
	 * @param string $arena        	
	 * @param string $owner        	
	 * @return string|multitype:multitype:
	 */
	public function retrieveTopPlayerByLevel() {
		$records = [ ];
		try {
			$prepare = $this->plugin->database2->prepare ( "SELECT pname, wins, level from player_story group by pname order by wins desc LIMIT 5" );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$records [] = $data;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
		return $records;
	}
	
	/**
	 * retrieve top 5 players
	 *
	 * @param string $arena        	
	 * @param string $owner        	
	 * @return string|multitype:multitype:
	 */
	public function retrieveTopPlayerByMap() {
		$records = [ ];
		try {
			$prepare = $this->plugin->database2->prepare ( "SELECT pname, wins, map, level from player_story order by wins desc LIMIT 10" );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$records [] = $data;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
		return $records;
	}
	
	/**
	 * retrieve top 5 players
	 *
	 * @param string $arena        	
	 * @param string $owner        	
	 * @return string|multitype:multitype:
	 */
	public function retrieveTopLevelPlayers() {
		$records = [ ];
		try {			
			$start_time =microtime ( true );			
			$prepare = $this->plugin->database2->prepare ( "SELECT pname, level, map,wins, loss, points from player_story group by level order by wins desc LIMIT 10" );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$records [] = $data;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}		
		//echo "check-hasPlayerWonLevel took " . (microtime ( true ) - $start_time) . "/n";		
		return $records;
	}
	
	/**
	 * retrieve top map players
	 *
	 * @param string $arena        	
	 * @param string $owner        	
	 * @return string|multitype:multitype:
	 */
	public function retrieveTopMapPlayers() {
		$records = [ ];
		$start_time =microtime ( true );
		try {
			$prepare = $this->plugin->database2->prepare ( "SELECT pname, level, map,wins, loss, points from player_story group by map order by wins desc LIMIT 10" );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$records [] = $data;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
		//echo "check-retrieveTopMapPlayers took " . (microtime ( true ) - $start_time) . "/n";
		return $records;
	}
	public function retrievePlayerWinsByLevelMap($pname) {
		$records = [ ];
		try {
			$prepare = $this->plugin->database2->prepare ( "SELECT * from player_story WHERE pname = :pname group by level order by wins desc" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$records [] = $data;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
		return $records;
	}
	public function retrievePlayerWinsByPoints($pname) {
		$records = [ ];
		try {
			$prepare = $this->plugin->database2->prepare ( "SELECT * from player_story WHERE pname = :pname order by points desc" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$records [] = $data;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
		return $records;
	}
	
	/**
	 * retrieve arena by name
	 *
	 * @param unknown $arena        	
	 * @param unknown $owner        	
	 * @return string|multitype:multitype:
	 */
	public function isPlayerExist($pname) {
		$found = false;
		try {
			$prepare = $this->plugin->database2->prepare ( "SELECT 1 from player_story WHERE pname = :pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$found = true;
					break;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
		return $found;
	}
	
	/**
	 * player stats by level
	 *
	 * @param string $arena        	
	 * @param string $owner        	
	 * @return string|multitype:multitype:
	 */
	public function isPlayerLevelMapRecordExist($pname, $plevel, $pmap) {
		$found = false;
		try {
			$prepare = $this->plugin->database2->prepare ( "SELECT 1 from player_story WHERE pname = :pname and level=:level and map=:map" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$prepare->bindValue ( ":level", $plevel, SQLITE3_TEXT );
			$prepare->bindValue ( ":map", $pmap, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$found = true;
					break;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
		return $found;
	}
	public function hasPlayerWonLevel($pname, $plevel) {
		$start_time = microtime ( true );
		$won = false;
		try {
			$prepare = $this->plugin->database2->prepare ( "SELECT sum(wins) as \"wins\" from player_story WHERE pname = :pname and level=:plevel order by wins desc" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$prepare->bindValue ( ":plevel", $plevel, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$wins = $data ["wins"];
					if ($wins > 0) {
						$won = true;
					} else {
						$won = false;
					}
					break;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
		$this->plugin->log("check-hasPlayerWonLevel took ". (microtime(true)-$start_time));
		return $won;
	}
	
	/**
	 * Retrieve Player levels
	 *
	 * @param unknown $arena        	
	 * @return string|multitype:multitype:
	 */
	public function retrievePlayerLevels($pname) {
		$records = [ ];
		try {
			$prepare = $this->plugin->database2->prepare ( "SELECT level from player_story WHERE pname=:pname order by level asc" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$records [] = $data;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
		return $records;
	}
	
	/**
	 * Update existing level winnings
	 *
	 * @param string $pname        	
	 * @param string $plevel        	
	 * @param string $pmap        	
	 * @param string $ppoints        	
	 * @return string|NULL
	 */
	public function addPlayerLevelWinning($pname, $plevel, $pmap, $points) {
		try {
			$prepare = $this->plugin->database2->prepare ( "SELECT * from player_story WHERE pname=:pname and level=:plevel and map=:pmap" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$prepare->bindValue ( ":plevel", $plevel, SQLITE3_TEXT );
			$prepare->bindValue ( ":pmap", $pmap, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				$data = $result->fetchArray ( SQLITE3_ASSOC );
				$result->finalize ();
				if (isset ( $data ["pname"] )) {
					$newwins = $data ["wins"] + 1;
					$newpoints = $data ["points"] + $points;
					$prepare = $this->plugin->database2->prepare ( "UPDATE player_story SET wins=:wins, points=:points, note=:note WHERE pname=:pname and level=:plevel and map=:pmap" );
					$prepare->bindValue ( ":wins", $newwins, SQLITE3_INTEGER );
					$prepare->bindValue ( ":points", $newpoints, SQLITE3_INTEGER );
					$prepare->bindValue ( ":note", "win", SQLITE3_TEXT );
					$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
					$prepare->bindValue ( ":plevel", $plevel, SQLITE3_TEXT );
					$prepare->bindValue ( ":pmap", $pmap, SQLITE3_TEXT );
					$prepare->execute ();
					$this->plugin->log ( "PlayerStory: addPlayerLevelWinning |" . $pname . " | " . $plevel . " | " . $pmap . " | " . $points );
					return true;
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
		return false;
	}
	
	/**
	 *
	 * @param string $pname        	
	 * @param string $plevel        	
	 * @param string $pmap        	
	 * @return string
	 */
	public function addPlayerLevelLoss($pname, $plevel, $pmap) {
		try {
			$prepare = $this->plugin->database2->prepare ( "SELECT * from player_story WHERE pname=:pname and level=:plevel and map=:pmap" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$prepare->bindValue ( ":plevel", $plevel, SQLITE3_TEXT );
			$prepare->bindValue ( ":pmap", $pmap, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				$data = $result->fetchArray ( SQLITE3_ASSOC );
				$result->finalize ();
				if (isset ( $data ["pname"] )) {
					$newloss = $data ["loss"] + 1;
					$prepare = $this->plugin->database2->prepare ( "UPDATE player_story SET loss=:loss, note=:note  WHERE pname = :pname and level=:plevel and map=:pmap" );
					$prepare->bindValue ( ":loss", $newloss, SQLITE3_INTEGER );
					$prepare->bindValue ( ":note", "loss", SQLITE3_TEXT );
					$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
					$prepare->bindValue ( ":plevel", $plevel, SQLITE3_TEXT );
					$prepare->bindValue ( ":pmap", $pmap, SQLITE3_TEXT );
					$prepare->execute ();
					$this->plugin->log ( "PlayerStory: addPlayerLevelLoss: " . $pname . " | " . $plevel . " | " . $pmap );
					return true;
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
		return false;
	}
	
	/**
	 *
	 * @param string $pname        	
	 * @param string $newname        	
	 * @return string
	 */
	public function changePlayerName($pname, $newname) {
		try {
			$prepare = $this->plugin->database2->prepare ( "SELECT * from player_story WHERE pname=:pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				$data = $result->fetchArray ( SQLITE3_ASSOC );
				$result->finalize ();
				if (isset ( $data ["pname"] )) {
					$prepare = $this->plugin->database2->prepare ( "UPDATE player_story SET pname=:newname WHERE pname = :oldname" );
					$prepare->bindValue ( ":oldname", $pname, SQLITE3_TEXT );
					$prepare->bindValue ( ":newname", $newname, SQLITE3_TEXT );
					$prepare->execute ();
					$this->plugin->log ( "PlayerStory: changePlayerName| old=" . $pname . " | new=" . $newname );
					return true;
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
		return false;
	}
	public function upsetPlayerLevelWinning($pname, $plevel, $pmap, $ppoints) {
		try {
			if (! $this->isPlayerLevelMapRecordExist ( $pname, $plevel, $pmap )) {
				$this->upsetPlayerLevelStory ( $pname, $plevel, $pmap, 0, 0, 0, 0, 0, 0, microtime (), "new", "new" );
			}
			$rs = $this->addPlayerLevelWinning ( $pname, $plevel, $pmap, $ppoints );
			return true;
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
		return false;
	}
	public function upsetPlayerLevelLoss($pname, $plevel, $pmap) {
		try {
			if (! $this->isPlayerLevelMapRecordExist ( $pname, $plevel, $pmap )) {
				$this->upsetPlayerLevelStory ( $pname, $plevel, $pmap, 0, 0, 0, 0, 0, 0, microtime (), "new", "new" );
			}
			$rs = $this->addPlayerLevelLoss ( $pname, $plevel, $pmap );
			return true;
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
		return false;
	}
	
	/**
	 * retrieve all arena names
	 *
	 * @return string|multitype:Ambigous <>
	 */
	public function retrieveAllPlayers() {
		$records = [ ];
		try {
			$prepare = $this->plugin->database2->prepare ( "SELECT * FROM player_story order by pname" );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$records [] = $data;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
		return $records;
	}
	public function upsetPlayerLevelStory($pname, $level, $map, $type, $rank, $rating, $wins, $loss, $points, $date, $status, $note) {
		try {
			$created_date = date ( 'Y-m-d H:i:s' );
			$prepare = $this->plugin->database2->prepare ( "SELECT * from player_story WHERE pname=:pname and level=:level and map=:map" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$prepare->bindValue ( ":level", $pname, SQLITE3_TEXT );
			$prepare->bindValue ( ":map", $map, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				$data = $result->fetchArray ( SQLITE3_ASSOC );
				$result->finalize ();
				if (isset ( $data ["pname"] )) {
					try {
						$prepare = $this->plugin->database2->prepare ( "UPDATE player_story type=:type, rank=:rank, rating=:rating,wins=:wins, loss=:loss, points=:points, date=:date, status=:status, note=:note WHERE pname=:pname and level=:level and map=:map" );
						$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
						$prepare->bindValue ( ":level", $level, SQLITE3_TEXT );
						$prepare->bindValue ( ":map", $map, SQLITE3_TEXT );
						$prepare->bindValue ( ":type", $type, SQLITE3_TEXT );
						$prepare->bindValue ( ":rank", $rank, SQLITE3_INTEGER );
						$prepare->bindValue ( ":rating", $rating, SQLITE3_INTEGER );
						$prepare->bindValue ( ":wins", $wins, SQLITE3_INTEGER );
						$prepare->bindValue ( ":loss", $loss, SQLITE3_INTEGER );
						$prepare->bindValue ( ":points", $points, SQLITE3_INTEGER );
						$prepare->bindValue ( ":date", $created_date, SQLITE3_TEXT );
						$prepare->bindValue ( ":status", $status, SQLITE3_TEXT );
						$prepare->bindValue ( ":note", $note, SQLITE3_TEXT );
						$prepare->execute ();
					} catch ( \Exception $e ) {
						$this->plugin->printError ( $e );
						return false;
					}
					return true;
				} else {
					try {
						$prepare = $this->plugin->database2->prepare ( "INSERT INTO player_story (pname,level, map,type,rank,rating,wins,loss,points,date,status,note) VALUES (:pname,:level, :map,:type,:rank,:rating, :wins,:loss,:points,:date,:status,:note)" );
						$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
						$prepare->bindValue ( ":level", $level, SQLITE3_TEXT );
						$prepare->bindValue ( ":map", $map, SQLITE3_TEXT );
						$prepare->bindValue ( ":type", $type, SQLITE3_TEXT );
						$prepare->bindValue ( ":rank", $rank, SQLITE3_INTEGER );
						$prepare->bindValue ( ":rating", $rating, SQLITE3_INTEGER );
						$prepare->bindValue ( ":wins", $wins, SQLITE3_INTEGER );
						$prepare->bindValue ( ":loss", $loss, SQLITE3_INTEGER );
						$prepare->bindValue ( ":points", $points, SQLITE3_INTEGER );
						$prepare->bindValue ( ":date", $created_date, SQLITE3_TEXT );
						$prepare->bindValue ( ":status", $status, SQLITE3_TEXT );
						$prepare->bindValue ( ":note", $note, SQLITE3_TEXT );
						$prepare->execute ();
					} catch ( \Exception $e ) {
						$this->plugin->printError ( $e );
						return false;
					}
					return "player profile created!";
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
		return false;
	}
	public function removePlayerProfile($pname, $level, $map) {
		try {
			$prepare = $this->plugin->database2->prepare ( "DELETE FROM player_story WHERE pname = :pname and level=:level and map=:map" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$prepare->bindValue ( ":level", $pname, SQLITE3_TEXT );
			$prepare->bindValue ( ":map", map, SQLITE3_TEXT );
			$prepare->execute ();
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
		return "profile deleted!";
	}
	public function testing() {
// 		for($i = 0; $i < 50000; $i ++) {
			
// 			$pname = "xake" . $i;
// 			$plevel = "1";
// 			$pmap = "1_Survial_Swamp";
// 			$ppoints = "10";
// 			$start = microtime ( true );
// 			$data = $this->upsetPlayerLevelWinning ( $pname, $plevel, $pmap, $ppoints );
// 			echo "[DB] Add Level Winning " . $i . " took " . (microtime ( true ) - $start) . "\n";
// 			// var_dump ( $data );
// 			$data = $this->hasPlayerWonLevel($pname, $plevel);
// 		}
		
		// echo "[DB] Has Player Won Level\n";
		// $data = $this->hasPlayerWonLevel($pname, $plevel);
		// var_dump ( $data );
		
		// $pname = "loda1";
		// $plevel = "1";
		// $pmap = "1_Survial_Swamp";
		// $ppoints = "10";
		// echo "[DB] Add Loss Winning\n";
		// $data = $this->upsetPlayerLevelLoss($pname, $plevel, $pmap, $ppoints );
		// var_dump ( $data );
		
		// echo "[DB] Has Player Won Level\n";
		// $data = $this->hasPlayerWonLevel($pname, $plevel);
		// var_dump ( $data );
		
		// echo "[DB] Add Level Winning\n";
		// $data = $this->upsetPlayerLevelWinning ( $pname, $plevel, $pmap, $ppoints );
		// var_dump ( $data );
		
		// echo "[DB] Has Player Won Level\n";
		// $data = $this->hasPlayerWonLevel($pname, $plevel);
		// var_dump ( $data );
		
		// echo "[DB] retrieve player wins by level\n";
		// $data = $this->retrievePlayerLevels( $pname );
		// var_export ( $data );
		
		// $pname = "ben1";
		// $plevel = "1";
		// $pmap = "1_Survial_Swamp";
		// $ppoints = "10";
		// echo "[DB] Add Level Winning\n";
		// //$data = $this->upsetPlayerLevelWinning ( $pname, $plevel, $pmap, $ppoints );
		// var_dump ( $data );
		
		// $pname = "demo1";
		// $plevel = "1";
		// $pmap = "1_Survial_Swamp";
		// $ppoints = "10";
		// echo "[DB] Add Level Winning\n";
		// $data = $this->upsetPlayerLevelWinning ( $pname, $plevel, $pmap, $ppoints );
		// var_dump ( $data );
		
		// echo "[DB] Add Level Losses\n";
		// $data=$this->upsetPlayerLevelLoss($pname, $plevel, $pmap);
		// var_dump($data);
		// $pname = "demo1";
		$pname = "denny";
		$plevel = "1";
		$pmap = "1_Survival_Breeze";
		$ppoints = "5";
		
		// echo "[DB] top wins by level\n";
		$data = $this->retrieveTopLevelPlayers ();
		var_export ( $data );
		
		// echo "[DB] top wins by map\n";
		$data = $this->retrieveTopPlayerByMap ();
		var_export ( $data );
		
		echo "[DB] Has Player Won Level\n";
		$data = $this->hasPlayerWonLevel ( $pname, $plevel );
		
		// echo "[DB] Add Level Winning\n";
		// $data = $this->upsetPlayerLevelWinning ( $pname, $plevel, $pmap, $ppoints );
		// $data = $this->upsetPlayerLevelWinning ( $pname, $plevel, $pmap, $ppoints );
		// $data = $this->upsetPlayerLevelWinning ( $pname, $plevel, $pmap, $ppoints );
		// var_dump ( $data );
		
		// var_dump ( $data );
		
		// $pname = "demo1";
		// $plevel = "1";
		// $pmap = "1_Survival_Land";
		// $ppoints = "5";
		// echo "[DB] Add Level Winning\n";
		// $data = $this->upsetPlayerLevelWinning ( $pname, $plevel, $pmap, $ppoints );
		// var_dump ( $data );
		
		// // echo "[DB] Add Level Losses\n";
		// // $data=$this->upsetPlayerLevelLoss($pname, $plevel, $pmap);
		// // var_dump($data);
		
		// $pname = "demo1";
		// $plevel = "2";
		// $pmap = "2_Catching Fire";
		// $ppoints = "5";
		// echo "[DB] Add Level Winning\n";
		// $data = $this->upsetPlayerLevelWinning ( $pname, $plevel, $pmap, $ppoints );
		// var_dump ( $data );
		
		// $pname = "demo1";
		// $plevel = "3";
		// $pmap = "3_MockingJay_Highway";
		// $ppoints = "5";
		// echo "[DB] Add Level Winning\n";
		// $data = $this->upsetPlayerLevelWinning ( $pname, $plevel, $pmap, $ppoints );
		// var_dump ( $data );
		
		// $pname = "demo1";
		// $plevel = "3";
		// $pmap = "3_MockingJay_Airport";
		// $ppoints = "5";
		// // echo "[DB] Add Level Winning\n";
		// // $data = $this->upsetPlayerLevelWinning ( $pname, $plevel, $pmap, $ppoints );
		// // var_dump ( $data );
		// echo "[DB] Add Level Losses\n";
		// $data=$this->upsetPlayerLevelLoss($pname, $plevel, $pmap);
		// var_dump($data);
		
		// $pname = "demo1";
		// $plevel = "4";
		// $pmap = "4_VIP_Barcos";
		// $ppoints = "5";
		// echo "[DB] Add Level Winning\n";
		// $data = $this->upsetPlayerLevelWinning ( $pname, $plevel, $pmap, $ppoints );
		// var_dump ( $data );
		
		// $pname = "demo1";
		// $plevel = "4";
		// $pmap = "4_VIP_Ultimate";
		// $ppoints = "5";
		// echo "[DB] Add Level Winning\n";
		// $data = $this->upsetPlayerLevelWinning ( $pname, $plevel, $pmap, $ppoints );
		// var_dump ( $data );
		
		// echo "[DB] retrieve top 5 players by level \n";
		// $data = $this->retrieveTopPlayerByLevel();
		// var_export($data);
		
		// echo "[DB] retrieve player wins by level\n";
		// $data = $this->retrievePlayerWinsByLevelMap ( $pname );
		// var_export ( $data );
		
		// echo "[DB] retrieve player points \n";
		// $data = $this->retrievePlayerWinsByPoints($pname);
		// var_export($data);
		
		// echo "[DB] retrieve all players \n";
		// $data = $this->retrieveAllPlayers();
		// var_export($data);
	}
}
