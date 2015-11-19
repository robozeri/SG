<?php

namespace hungergames\profile;


use pocketmine\utils\TextFormat;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\level\Level;
use hungergames\main\HungerGamesPlugIn;


class PlayerProfileProvider {
	const DB_STORE_FILE = "HGv2_player_profile.db";
	const DB_SQL_FILE_PROFILE = "sqlite3_player_profile.sql";
	const DB_SQL_FILE_PROFILE_INDEX = "sqlite3_player_profile_index.sql";
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
			$this->getPlugIn ()->database = new \SQLite3 ( $this->getPlugIn ()->getDataFolder () . self::DB_STORE_FILE, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE );
			$resource = $this->getPlugIn ()->getResource ( $this::DB_SQL_FILE_PROFILE );
			$this->getPlugIn ()->database->exec ( stream_get_contents ( $resource ) );			
			$this->getPlugIn ()->database->query ("PRAGMA synchronous = OFF" );
			$this->getPlugIn ()->database->query ("PRAGMA count_changes = OFF" );
			$this->getPlugIn ()->database->query ("PRAGMA journal_mode = MEMORY" );
			$this->getPlugIn ()->database->query ("PRAGMA temp_store = MEMORY" );			
			$this->plugin->info ( TextFormat::BLUE . "- [HG] Creating New Database -table" );	
		} else {
			$this->getPlugIn ()->database = new \SQLite3 ( $this->getPlugIn ()->getDataFolder () . self::DB_STORE_FILE, SQLITE3_OPEN_READWRITE );
			$this->plugin->info ( TextFormat::BLUE . "- [HG] Use existing player database." );
		}
	}
	
	/**
	 * retrieve top 3 players
	 *
	 * @param unknown $arena
	 * @param unknown $owner
	 * @return string|multitype:multitype:
	 */
	public function retrieveTopPlayers() {
		$records = [ ];
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT pname, wins from player_profile order by wins desc LIMIT 3");
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$records [] = $data;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e) ;
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
	public function retrieveVIPs() {
		$records = [ ];
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_profile WHERE vip = 'true'" );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$records [] = $data;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e) ;
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
	public function retrievePlayerByName($pname) {
		$records = [ ];
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_profile WHERE pname = :pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$records [] = $data;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
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
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_profile WHERE pname = :pname" );
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
			$this->plugin->printError($e);
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
			$prepare = $this->plugin->database->prepare ( "SELECT wins,loss from player_profile WHERE pname=:pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$records [] = $data;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return $records;
	}
	
	/**
	 * Player kit already exist
	 *
	 * @param unknown $arena
	 * @param unknown $owner
	 * @return string|multitype:multitype:
	 */
	public function hasPlayerAlreadyPurchasedKit($pname, $kitname) {
		$found = false;
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT kit from player_profile WHERE pname = :pname and status=:kitname " );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$prepare->bindValue ( ":kitname", $kitname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$found = true;
					break;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return $found;
	}
	
	public function upsetPlayerKitPurchased($pname, $kitname) {
		try {
			if (!$this->isPlayerExist($pname)) {
				$this->upsetPlayer($pname, $pname, 0, 0, 0, 0, "false", 0, 0, 0, "free_kit");
			}
			$rs = $this->updatePlayerKit($pname, $kitname);
			return $rs;
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return null;
	}
	
	public function updatePlayerKit($pname, $kit) {
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_profile WHERE pname = :pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				$data = $result->fetchArray ( SQLITE3_ASSOC );
				$result->finalize ();
				$this->plugin->log("new kit: ". $data ["status"]);
				if (isset ( $data ["pname"] )) {
					try {
						$prepare = $this->plugin->database->prepare ( "UPDATE player_profile SET status=:kitname WHERE pname = :pname" );
						$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
						$prepare->bindValue ( ":kitname", $kit, SQLITE3_TEXT );
						$prepare->execute ();
					} catch ( \Exception $e ) {
						$this->plugin->printError($e);
					}
					return "kit updated success!";
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return null;
	}
	
	/**
	 * Retrieve Player Stats
	 *
	 * @param unknown $arena        	
	 * @return string|multitype:multitype:
	 */
	public function retrievePlayerBalance($pname) {
		$records = [ ];
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT balance from player_profile WHERE pname=:pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$records [] = $data;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return $records;
	}
	
	/**
	 * Retrieve VIP Player
	 *
	 * @param unknown $arena        	
	 * @return string|multitype:multitype:
	 */
	public function retrievePlayerVIP($pname) {
		$records = [ ];
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_profile WHERE pname=:pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$records [] = $data;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return $records;
	}
	public function upsetPlayerStats($pname, $balance, $wins, $loss) {
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_profile WHERE pname = :pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				$data = $result->fetchArray ( SQLITE3_ASSOC );
				$result->finalize ();
				if (isset ( $data ["pname"] )) {
					try {
						$prepare = $this->plugin->database->prepare ( "UPDATE player_profile SET balance=:balance, wins=:wins, loss=:loss WHERE pname = :pname" );
						$prepare->bindValue ( ":balance", $balance, SQLITE3_INTEGER );
						$prepare->bindValue ( ":wins", $wins, SQLITE3_INTEGER );
						$prepare->bindValue ( ":loss", $loss, SQLITE3_INTEGER );
						$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
						$prepare->execute ();
					} catch ( \Exception $e ) {
						$this->plugin->printError($e);
						return "data error: " . $e->getMessage ();
					}
					return "player profile updated!";
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return null;
	}
	
	public function changePlayerName($pname, $newname) {
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_profile WHERE pname=:pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				$data = $result->fetchArray ( SQLITE3_ASSOC );
				$result->finalize ();
				if (isset ( $data ["pname"] )) {
					$prepare = $this->plugin->database->prepare ( "UPDATE player_profile SET pname=:newname WHERE pname = :oldname" );
					$prepare->bindValue ( ":oldname", $pname, SQLITE3_TEXT );
					$prepare->bindValue ( ":newname", $newname, SQLITE3_TEXT );
					$prepare->execute();			
					$this->plugin->log ("PlayerProfile: changePlayerName old=".$pname." | new=".$newname);
					return true;
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return false;
	}
	
	
	public function addPlayerWinning($pname, $amount) {
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_profile WHERE pname = :pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				$data = $result->fetchArray ( SQLITE3_ASSOC );
				$result->finalize ();
				if (isset ( $data ["pname"] )) {					
					$newwins = $data ["wins"] + 1;
					$newbalance = $data ["balance"] + $amount;
					$prepare = $this->plugin->database->prepare ( "UPDATE player_profile SET balance=:balance, wins=:wins WHERE pname = :pname" );
					$prepare->bindValue ( ":balance", $newbalance, SQLITE3_INTEGER );
					$prepare->bindValue ( ":wins", $newwins, SQLITE3_INTEGER );
					$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
					$prepare->execute ();					
					$this->plugin->log("PlayerProfile: addPlayerWinning |".$pname." | ".$amount);					
					return "player profile updated!";
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return null;
	}

	public function upsetPlayerWinning($pname, $amount) {
		try {
			if (! $this->isPlayerExist ( $pname )) {
				$this->upsetPlayer ( $pname, $pname, 0, 0, 0, 0, "false", 0, 0, 0, "new" );
			}
			if (empty($amount)) {
				$amount = 5;
			}
			$rs = $this->addPlayerWinning ( $pname, $amount );
			return $rs;
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
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
			$this->plugin->printError($e);
		}
		return null;
	}
	public function addVIP($pname) {
		$ok = false;
		try {
			if (! $this->isPlayerExist ( $pname )) {
				$this->upsetPlayer ( $pname, $pname, 0, 0, 0, 0, "false", 0, 0, 0, "new" );
			}
			$rs = $this->updateVIP ( $pname, "true" );
			$ok = true;
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return $ok;
	}
	
	public function upsetVIP($pname, $status) {
		$ok = false;
		try {
			if (! $this->isPlayerExist ( $pname )) {
				$this->upsetPlayer ( $pname, $pname, 0, 0, 0, 0, "false", 0, 0, 0, "new" );
			}
			$rs = $this->updateVIP ( $pname, $status );
			$ok = true;
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
			return "db error: " . $e->getMessage ();
		}
		return $ok;
	}
	
	
	public function addPlayer($pname) {
		$ok = false;
		try {
			if (! $this->isPlayerExist ( $pname )) {
				$this->upsetPlayer ( $pname, $pname, 0, 0, 0, 0, "false", 0, 0, 0, "new" );
			}
			$ok = true;
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return $ok;
	}
	public function isPlayerVIP($pname) {
		try {
			if (! $this->isPlayerExist ( $pname )) {
				$this->upsetPlayer ( $pname, $pname, 0, 0, 0, 0, "false", 0, 0, 0, "new" );
				return false;
			}
			$data = $this->retrievePlayerVIP ( $pname );			
			if ($data [0] ["vip"] === "true") {
				return true;
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return false;
	}
	
	public function addPlayerLoss($pname) {
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_profile WHERE pname = :pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				$data = $result->fetchArray ( SQLITE3_ASSOC );
				$result->finalize ();
				if (isset ( $data ["pname"] )) {
					try {
						$loss = $data ["loss"] + 1;
						$prepare = $this->plugin->database->prepare ( "UPDATE player_profile SET loss=:loss WHERE pname = :pname" );
						$prepare->bindValue ( ":loss", $loss, SQLITE3_INTEGER );
						$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
						$prepare->execute ();						
						$this->plugin->log ("PlayerProfile: addPlayerLoss: ".$pname);						
					} catch ( \Exception $e ) {
						$this->plugin->printError($e);
						return "data error: " . $e->getMessage ();
					}
					return "player profile updated!";
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return null;
	}
	public function deposit($pname, $amount) {
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_profile WHERE pname = :pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				$data = $result->fetchArray ( SQLITE3_ASSOC );
				$result->finalize ();				
				$this->plugin->log("deposit: old balance :" . $data ["balance"] );
				if (isset ( $data ["pname"] )) {
					try {
						$newBalance = $data ["balance"] + $amount;
						$this->plugin->log ( "deposit: new balance :" . $newBalance );												
						$prepare = $this->plugin->database->prepare ( "UPDATE player_profile SET balance=:balance WHERE pname = :pname" );
						$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
						$prepare->bindValue ( ":balance", $newBalance, SQLITE3_INTEGER );
						$prepare->execute ();
					} catch ( \Exception $e ) {
						$this->plugin->printError($e);
						return "data error: " . $e->getMessage ();
					}
					return "deposit success!";
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return null;
	}
	public function withdraw($pname, $amount) {
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_profile WHERE pname = :pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				$data = $result->fetchArray ( SQLITE3_ASSOC );
				$result->finalize ();
				if (isset ( $data ["pname"] )) {
					$this->plugin->log("withdraw: old balance :" . $data ["balance"] );					
					if ($data ["balance"] < $amount) {
						return "Insufficient fund!";
					}
					try {
						$newBalance = $data ["balance"] - $amount;
						$this->plugin->log("withdraw: new balance :" . $newBalance );						
						$prepare = $this->plugin->database->prepare ( "UPDATE player_profile SET balance=:balance WHERE pname = :pname" );
						$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
						$prepare->bindValue ( ":balance", $newBalance, SQLITE3_INTEGER );
						$prepare->execute ();
					} catch ( \Exception $e ) {
						$this->plugin->printError($e);
						return "data error: " . $e->getMessage ();
					}
					return "withdraw success!";
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return null;
	}
	public function updateVIP($pname, $vip) {
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_profile WHERE pname=:pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				$data = $result->fetchArray ( SQLITE3_ASSOC );
				$result->finalize ();
				if (isset ( $data ["pname"] )) {
					try {
						$prepare = $this->plugin->database->prepare ( "UPDATE player_profile SET vip=:vip WHERE pname = :pname" );
						$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
						$prepare->bindValue ( ":vip", $vip, SQLITE3_TEXT );
						$prepare->execute ();
					} catch ( \Exception $e ) {
						$this->plugin->printError($e);
						return "data error: " . $e->getMessage ();
					}
					return "player profile updated!";
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return null;
	}
	public function setBalance($pname, $amount) {
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_profile WHERE pname=:pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				$data = $result->fetchArray ( SQLITE3_ASSOC );
				$result->finalize ();
				if (isset ( $data ["pname"] )) {
					try {
						$prepare = $this->plugin->database->prepare ( "UPDATE player_profile SET balance=:balance WHERE pname = :pname" );
						$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
						$prepare->bindValue ( ":balance", $amount, SQLITE3_TEXT );
						$prepare->execute ();
					} catch ( \Exception $e ) {
						$this->plugin->printError($e);
						return "data error: " . $e->getMessage ();
					}
					return "player balance updated!";
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return null;
	}
	public function updatePassword($pname, $newPassword) {
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_profile WHERE pname=:pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				$data = $result->fetchArray ( SQLITE3_ASSOC );
				$result->finalize ();
				if (isset ( $data ["pname"] )) {
					try {
						$prepare = $this->plugin->database->prepare ( "UPDATE player_profile SET password=:password WHERE pname = :pname" );
						$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
						$prepare->bindValue ( ":password", $newPassword, SQLITE3_TEXT );
						$prepare->execute ();
					} catch ( \Exception $e ) {
						$this->plugin->printError($e);
						return "data error: " . $e->getMessage ();
					}
					return "player profile updated!";
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return null;
	}
	
	/**
	 * Admin reset password
	 *
	 * @param unknown $pname        	
	 * @return string|NULL
	 */
	public function resetPassword($pname) {
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_profile WHERE pname=:pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				$data = $result->fetchArray ( SQLITE3_ASSOC );
				$result->finalize ();
				if (isset ( $data ["pname"] )) {
					try {
						$prepare = $this->plugin->database->prepare ( "UPDATE player_profile SET password=:password WHERE pname = :pname" );
						$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
						$prepare->bindValue ( ":password", $pname, SQLITE3_TEXT );
						$prepare->execute ();
					} catch ( \Exception $e ) {
						$this->plugin->printError($e);
						return "data error: " . $e->getMessage ();
					}
					return "player profile updated!";
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
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
			$prepare = $this->plugin->database->prepare ( "SELECT * FROM player_profile" );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				while ( $data = $result->fetchArray ( SQLITE3_ASSOC ) ) {
					$records [] = $data;
				}
				$result->finalize ();
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return $records;
	}
	public function upsetPlayer($pname, $password, $balance, $rank, $wins, $loss, $vip, $home_x, $home_y, $home_z, $status) {
		try {
			$prepare = $this->plugin->database->prepare ( "SELECT * from player_profile WHERE pname = :pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$result = $prepare->execute ();
			if ($result instanceof \SQLite3Result) {
				$data = $result->fetchArray ( SQLITE3_ASSOC );
				$result->finalize ();
				if (isset ( $data ["pname"] )) {
					try {
						$prepare = $this->plugin->database->prepare ( "UPDATE player_profile SET password=:password, balance=:balance, rank=:rank, wins=:wins, loss=:loss, vip=:vip, home_x = :home_x, home_y=:home_y, home_z=:home_z, status=:status WHERE pname = :pname" );
						$prepare->bindValue ( ":password", $password, SQLITE3_TEXT );
						$prepare->bindValue ( ":balance", $balance, SQLITE3_INTEGER );
						$prepare->bindValue ( ":rank", $rank, SQLITE3_INTEGER );
						$prepare->bindValue ( ":wins", $wins, SQLITE3_INTEGER );
						$prepare->bindValue ( ":loss", $loss, SQLITE3_INTEGER );
						$prepare->bindValue ( ":vip", $vip, SQLITE3_TEXT );
						$prepare->bindValue ( ":home_x", $home_x, SQLITE3_INTEGER );
						$prepare->bindValue ( ":home_y", $home_y, SQLITE3_INTEGER );
						$prepare->bindValue ( ":home_z", $home_z, SQLITE3_INTEGER );
						$prepare->bindValue ( ":status", $status, SQLITE3_TEXT );
						$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
						$prepare->execute ();
					} catch ( \Exception $e ) {
						$this->plugin->printError($e);
						return "data error: " . $e->getMessage ();
					}
					return "player profile updated!";
				} else {
					try {
						$prepare = $this->plugin->database->prepare ( "INSERT INTO player_profile (pname,password, balance,rank,wins,loss,vip,home_x,home_y, home_z,status) VALUES (:pname,:password, :balance,:rank,:wins,:loss,:vip,:home_x,:home_y,:home_z,:status)" );
						$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
						$prepare->bindValue ( ":password", $password, SQLITE3_TEXT );
						$prepare->bindValue ( ":balance", $balance, SQLITE3_INTEGER );
						$prepare->bindValue ( ":rank", $rank, SQLITE3_INTEGER );
						$prepare->bindValue ( ":wins", $wins, SQLITE3_INTEGER );
						$prepare->bindValue ( ":loss", $loss, SQLITE3_INTEGER );
						$prepare->bindValue ( ":vip", $vip, SQLITE3_TEXT );
						$prepare->bindValue ( ":home_x", $home_x, SQLITE3_INTEGER );
						$prepare->bindValue ( ":home_y", $home_y, SQLITE3_INTEGER );
						$prepare->bindValue ( ":home_z", $home_z, SQLITE3_INTEGER );
						$prepare->bindValue ( ":status", $status, SQLITE3_TEXT );
						$prepare->execute ();
					} catch ( \Exception $e ) {
						$this->plugin->printError($e);
						return "data error: " . $e->getMessage ();
					}
					return "player profile created!";
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return null;
	}
	public function removePlayerProfile($pname) {
		try {
			$prepare = $this->plugin->database->prepare ( "DELETE FROM player_profile WHERE pname = :pname" );
			$prepare->bindValue ( ":pname", $pname, SQLITE3_TEXT );
			$prepare->execute ();
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
		return "profile deleted!";
	}
	
}
