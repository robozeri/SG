<?php

namespace mhungergames\story;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\event\Listener;
use pocketmine\math\Vector3 as Vector3;


class GameStoryProvider {
	const DB_STORE_FILE = "HGv2_game_story.db";
	const DB_SQL_FILE_PROFILE = "sqlite3_player_story.sql";
	private $plugin;
	public function __construct(HungerGamesPlugIn $pg) {
		$this->plugin = $pg;
	}
	public function getPlugIn() {
		return $this->plugin;
	}
	public function initlize() {
		// create plugin folder
		@mkdir ( $this->getPlugIn ()->getDataFolder () );
		if (! file_exists ( $this->getPlugIn ()->getDataFolder () . self::DB_STORE_FILE )) {
			// open in file
			$this->getPlugIn ()->gamestory_db = new \SQLite3 ( $this->getPlugIn ()->getDataFolder () . self::DB_STORE_FILE, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE );
			// create profile table
			$resource = $this->getPlugIn ()->getResource ( $this::DB_SQL_FILE_PROFILE );
			$this->getPlugIn ()->database->exec ( stream_get_contents ( $resource ) );
			$this->plugin->log ( TextFormat::BLUE . "- [BW] Creating New Game Story Database." );
		} else {
			$this->getPlugIn ()->database = new \SQLite3 ( $this->getPlugIn ()->getDataFolder () . self::DB_STORE_FILE, SQLITE3_OPEN_READWRITE );
			$this->plugin->log ( TextFormat::BLUE . "- [BW] loaded use existing Game Story Database." );
		}
	}
	public function retrieveTop10WinsByMap($pname) {
		$records = [ ];
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_story order by map, wins desc LIMIT 10" );
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
	public function retrieveTop10WinsByPoints($pname) {
		$records = [ ];
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_story where pname=:pname order by points desc LIMIT 10" );
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
	public function retrievePlayerStory($pname) {
		$records = [ ];
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_story WHERE pname = :pname order by level" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$records [] = $data;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			//$this->plugin->log ( $e->getMessage () . "\n" . $e->getTraceAsString () );
			$this->plugin->printError($e);
		}
		return $records;
	}
	public function isPlayerExist($pname) {
		$found = false;
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT 1 from player_story WHERE pname = :pname" );
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
			//$this->plugin->log ( $e->getMessage () . "\n" . $e->getTraceAsString () );
			$this->plugin->printError($e);
		}
		return $found;
	}
	public function getPlayerLevel($pname, $plevel) {
		$records = [ ];
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_story WHERE pname = :pname and level=:level" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$prepare->bindValue ( ":level", $plevel, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$records [] = $data;
					break;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
			//$this->plugin->log ( $e->getMessage () . "\n" . $e->getTraceAsString () );
		}
		return $found;
	}
	
	/**
	 * Retrieve Player Stats
	 *
	 * @param unknown $arena        	
	 * @return string|multitype:multitype:
	 */
	public function retrievePlayerStats($pname) {
		$records = [ ];
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT wins,loss from player_story WHERE pname=:pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					// $wins = $data ['wins'];
					// $loss = $data ['loss'];
					$records [] = $data;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			//$this->plugin->log ( $e->getMessage () . "\n" . $e->getTraceAsString () );
			$this->plugin->printError($e);
		}
		return $records;
	}
	public function upsetPlayerStory($pname, $plevel, $pmap, $balance, $wins, $loss) {
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_story WHERE pname = :pname and level = :plevel and map = :pmap" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$prepare->bindValue ( ":plevel", $pname, SQLITE3_TEXT );
			$prepare->bindValue ( ":pmap", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				$data = $result->fetchArray ( SQLITE3_ASSOC );
				$result->finalize ();
				if (isset ( $data ["pname"] ) and isset ( $data ["level"] ) and isset ( $data ["map"] )) {
					try {
						$prepare = $this->plugin->database->prepare ( "UPDATE player_story SET balance=:balance, wins=:wins, loss=:loss WHERE pname = :pname" );
						$prepare->bindValue ( ":balance", $balance, SQLITE3_INTEGER );
						$prepare->bindValue ( ":wins", $wins, SQLITE3_INTEGER );
						$prepare->bindValue ( ":loss", $loss, SQLITE3_INTEGER );
						$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
						$prepare->execute ();
					} catch ( \Exception $e ) {
						LogUtil::printLog ( $this->getPlugIn (), $e );
						return "data error: " . $e->getMessage ();
					}
					return "player story updated!";
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->log ( $e->getMessage () . "\n" . $e->getTraceAsString () );
		}
		return null;
	}
	
	/**
	 * 
	 * @param string $pname
	 * @param string $plevel
	 * @return boolean
	 */
	public function hasPlayerCompletePreviousLevel($pname, $plevel) {
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_story WHERE pname = :pname and level = :plevel" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$prepare->bindValue ( ":plevel", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$found = true;
					break;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->log ( $e->getMessage () . "\n" . $e->getTraceAsString () );
		}
		return false;
	}
	
	// public function addPlayerWinning($pname, $level, $map, $amount) {
	// try {
	// $prepare = $this->plugin->database->prepare ( "SELECT * from player_story WHERE pname = :pname" );
	// $prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
	// $result = $prepare->execute ();
	// if ($result instanceof \SQLite3Result) {
	// $data = $result->fetchArray ( SQLITE3_ASSOC );
	// $result->finalize ();
	// if (isset ( $data ["pname"] )) {
	
	// $newwins = $data ["wins"] + 1;
	// $newbalance = $data ["balance"] + $amount;
	// $prepare = $this->plugin->database->prepare ( "UPDATE player_story SET balance=:balance, wins=:wins WHERE pname = :pname" );
	// $prepare->bindValue ( ":balance", $newbalance, SQLITE3_INTEGER );
	// $prepare->bindValue ( ":wins", $newwins, SQLITE3_INTEGER );
	// $prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
	// $prepare->execute ();
	
	// return "player profile updated!";
	// }
	// }
	// } catch ( \Exception $e ) {
	// $this->plugin->log ( $e->getMessage () . "\n" . $e->getTraceAsString () );
	// }
	// return null;
	// }
	public function upsetPlayerWinning($pname, $amount) {
		try {
			if (! $this->isPlayerExist ( $pname )) {
				$this->upsetPlayer ( $pname, $pname, 0, 0, 0, 0, "false", 0, 0, 0, "new" );
			}
			$rs = $this->addPlayerWinning ( $pname, $amount );
			return $rs;
		} catch ( \Exception $e ) {
			$this->plugin->log ( $e->getMessage () . "\n" . $e->getTraceAsString () );
		}
		return null;
	}
	public function upsetPlayerLoss($pname) {
		try {
			if (! $this->isPlayerExist ( $pname )) {
				$this->upsetPlayer ( $pname, $pname, 0, 0, 0, 0, "false", 0, 0, 0, "new" );
			}
			$rs = $this->addPlayerLoss ( $pname );
			return $rs;
		} catch ( \Exception $e ) {
			$this->plugin->log ( $e->getMessage () . "\n" . $e->getTraceAsString () );
		}
		return null;
	}
	public function addPlayer($pname) {
		$ok = false;
		try {
			if (! $this->isPlayerExist ( $pname )) {
				$this->upsetPlayer ( $pname, $pname, 0, 0, 0, 0, "false", 0, 0, 0, "new" );
			}
			$ok = true;
		} catch ( \Exception $e ) {
			$this->plugin->log ( $e->getMessage () . "\n" . $e->getTraceAsString () );
		}
		return $ok;
	}
	public function addPlayerLoss($pname) {
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_story WHERE pname = :pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				$data = $result->fetchArray ( SQLITE3_ASSOC );
				$result->finalize ();
				if (isset ( $data ["pname"] )) {
					try {
						$loss = $data ["loss"] + 1;
						$prepare = $this->plugin->database->prepare ( "UPDATE player_story SET loss=:loss WHERE pname = :pname" );
						$prepare->bindValue ( ":loss", $loss, SQLITE3_INTEGER );
						$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
						$prepare->execute ();
					} catch ( \Exception $e ) {
						LogUtil::printLog ( $this->getPlugIn (), $e );
						return "data error: " . $e->getMessage ();
					}
					return "player profile updated!";
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->log ( $e->getMessage () . "\n" . $e->getTraceAsString () );
		}
		return null;
	}

	
	/**
	 * retrieve all arena names
	 *
	 * @return string|multitype:Ambigous <>
	 */
	public function retrieveAllPlayers() {
		$records = [ ];
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * FROM player_story" );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$records [] = $data;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->log ( $e->getMessage () . "\n" . $e->getTraceAsString () );
		}
		return $records;
	}
	
	/*
	 * CREATE TABLE player_story ( pname TEXT PRIMARY KEY, level INTEGER, map TEXT, type INTEGER, rank INTEGER, rating INTEGER, wins INTEGER, loss INTEGER, points INTEGER, status TEXT, note TEXT, date TEXT );
	 */
	public function upsetPlayerStory($pname, $password, $balance, $rank, $wins, $loss, $vip, $home_x, $home_y, $home_z, $status) {
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_story WHERE pname = :pname and level=:plevel and map=:pmap" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$prepare->bindValue ( ":plevel", $plevel, SQLITE3_TEXT );
			$prepare->bindValue ( ":pmap", $pmap, SQLITE3_TEXT );
			$result = $prepare->execute ();
			
			if ($result instanceof \SQLite3Result) {
				$data = $result->fetchArray ( SQLITE3_ASSOC );
				$result->finalize ();
				if (isset ( $data ["pname"] )) {
					try {
						$prepare = $this->plugin->database->prepare ( "UPDATE player_story SET level=:level, map=:map, type=:type, rank=:rank, rating=:rating, wins = :wins, loss=:loss, points=:points, status=:status, note=:note, date=:date  WHERE pname = :pname and level=:plevel and map=:pmap" );
						$prepare->bindValue ( ":level", $level, SQLITE3_TEXT );
						$prepare->bindValue ( ":map", $map, SQLITE3_INTEGER );
						$prepare->bindValue ( ":type", $type, SQLITE3_INTEGER );
						$prepare->bindValue ( ":rank", $rank, SQLITE3_INTEGER );
						$prepare->bindValue ( ":rating", $rating, SQLITE3_INTEGER );
						$prepare->bindValue ( ":wins", $wins, SQLITE3_TEXT );
						$prepare->bindValue ( ":loss", $loss, SQLITE3_INTEGER );
						$prepare->bindValue ( ":status", $status, SQLITE3_INTEGER );
						$prepare->bindValue ( ":note", $note, SQLITE3_INTEGER );
						$prepare->bindValue ( ":date", $date, SQLITE3_TEXT );
						$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
						$prepare->execute ();
					} catch ( \Exception $e ) {
						LogUtil::printLog ( $this->getPlugIn (), $e );
						return "data error: " . $e->getMessage ();
					}
					return "player profile updated!";
				} else {
					try {
						$prepare = $this->plugin->database->prepare ( "INSERT INTO player_story (pname,level, map,type,rank,rating,wins,loss,status, note,date) VALUES (:pname,:level, :map,:type,:rank,:rating,:wins,:loss,:status,:note,:date)" );
						$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
						$prepare->bindValue ( ":level", $password, SQLITE3_TEXT );
						$prepare->bindValue ( ":map", $balance, SQLITE3_INTEGER );
						$prepare->bindValue ( ":type", $rank, SQLITE3_INTEGER );
						$prepare->bindValue ( ":rank", $wins, SQLITE3_INTEGER );
						$prepare->bindValue ( ":rating", $loss, SQLITE3_INTEGER );
						$prepare->bindValue ( ":wins", $vip, SQLITE3_TEXT );
						$prepare->bindValue ( ":loss", $home_x, SQLITE3_INTEGER );
						$prepare->bindValue ( ":status", $home_y, SQLITE3_INTEGER );
						$prepare->bindValue ( ":note", $home_z, SQLITE3_INTEGER );
						$prepare->bindValue ( ":date", $status, SQLITE3_TEXT );
						$prepare->execute ();
					} catch ( \Exception $e ) {
						LogUtil::printLog ( $this->getPlugIn (), $e );
						return "data error: " . $e->getMessage ();
					}
					return "player profile created!";
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->log ( $e->getMessage () . "\n" . $e->getTraceAsString () );
		}
		return null;
	}
	public function removePlayerStory($pname) {
		try {
			$prepare = $this->plugin->database->prepare ( "DELETE FROM player_story WHERE pname = :pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$prepare->execute ();
		} catch ( \Exception $e ) {
			$this->plugin->log ( $e->getMessage () . "\n" . $e->getTraceAsString () );
		}
		return "player story deleted!";
	}
}
