<?php

namespace hungergames\portal;

use hungergames\main\HungerGamesPlugIn;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\level\Level;
use pocketmine\math\Vector3;


class MapPortal {
	private $plugin;
	const DIR_PORTAL_DATA = 'portal_data/';
	const TYPE_MAP_PORTAL = "type_map_portal";
	// variables
	public $name;
	public $displayName;
	public $type;
	public $levelName;
	public $level;
	// entrance portal
	public $portalEnterPos1;
	public $portalEnterPos2;
	public $enterpos;
	public $enterLevelName;
	
	// exit portal
	public $portalExitPos1;
	public $portalExitPos2;
	public $exitpos;
	public $exitLevelName;
	// map list
	public $maps = [ ];
	public $selectedMap = null;
	// safe spawn location
	public $location;
	
	/**
	 * constructor
	 *
	 * @param HungerGamesPlugIn $plugin        	
	 * @param unknown $name        	
	 */
	public function __construct(HungerGamesPlugIn $plugin, $name) {
		$this->plugin = $plugin;
		$this->name = $name;
	}
	
	/**
	 * portal enter
	 *
	 * @param unknown $portallevelName        	
	 * @param Player $player        	
	 * @return boolean
	 */
	public function portalEnter($portallevelName, Player $player) {
		$pos = $player->getPosition ();
		if ((min ( $this->portalEnterPos1->getX (), $this->portalEnterPos2->getX () ) <= $pos->getX ()) && (max ( $this->portalEnterPos1->getX (), $this->portalEnterPos2->getX () ) >= $pos->getX ()) && (min ( $this->portalEnterPos1->getY (), $this->portalEnterPos2->getY () ) <= $pos->getY ()) && (max ( $this->portalEnterPos1->getY (), $this->portalEnterPos2->getY () ) >= $pos->getY ()) && (min ( $this->portalEnterPos1->getZ (), $this->portalEnterPos2->getZ () ) <= $pos->getZ ()) && (max ( $this->portalEnterPos1->getZ (), $this->portalEnterPos2->getZ () ) >= $pos->getZ ())) {
			$player->onGround = true;
			if ($portallevelName === $player->getLevel ()->getName ()) {
				$player->teleport ( $this->enterpos );
			} else {
				self::teleportToMap ( $portallevelName, $player );
				$player->teleport ( $this->enterpos );
			}
			$player->onGround = false;
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * portal exit
	 *
	 * @param unknown $portallevelName        	
	 * @param Player $player        	
	 * @return boolean
	 */
	public function portalExit($portallevelName, Player $player) {
		$pos = $player->getPosition ();
		if ((min ( $this->portalExitPos1->getX (), $this->portalExitPos2->getX () ) <= $pos->getX ()) && (max ( $this->portalExitPos1->getX (), $this->portalExitPos2->getX () ) >= $pos->getX ()) && (min ( $this->portalExitPos1->getY (), $this->portalExitPos2->getY () ) <= $pos->getY ()) && (max ( $this->portalExitPos1->getY (), $this->portalExitPos2->getY () ) >= $pos->getY ()) && (min ( $this->portalExitPos1->getZ (), $this->portalExitPos2->getZ () ) <= $pos->getZ ()) && (max ( $this->portalExitPos1->getZ (), $this->portalExitPos2->getZ () ) >= $pos->getZ ())) {
			$player->onGround = true;
			if ($portallevelName === $player->getLevel ()->getName ()) {
				$player->teleport ( $this->exitpos );
			} else {
				self::teleportToMap ( $portallevelName, $player );
				$player->teleport ( $this->exitpos );
			}
			$player->onGround = false;
			return true;
		} else {
			return false;
		}
	}

    /**
     * activate portal
     *
     * @param Player $player
     * @internal param unknown $pos
     */
	public function activatePortal(Player $player) {
		$done = false;
		if ($this->portalEnter ( $this->enterLevelName, $player )) {
			return;
		} elseif ($this->portalExit ( $this->exitLevelName, $player )) {
			return;
		}
	}
	public function save($path) {
		$path = $path . self::DIR_PORTAL_DATA;
		if (! file_exists ( $path )) {
			@mkdir ( $path, 0755, true );
		}
		$name = $this->name;
		$data = new Config ( $path . "$name.yml", Config::YAML );
		if ($this->levelName == null) {
			$this->plugin->getLogger ()->error ( "[Map Portal] missing name - level Name is NULL -" . $this->name );
			return false;
		}
		if ($this->name == null) {
			$this->plugin->getLogger ()->error ( "[Map Portal] missing name -  Name is NULL -" . $this->name );
			return false;
		}		
		// locations
		if ($this->location != null) {
			$data->set ( "locationX", $this->location->x );
			$data->set ( "locationY", $this->location->y );
			$data->set ( "locationZ", $this->location->z );
		}		
		$data->set ( "id", time () );
		$data->set ( "name", $this->name );
		$data->set ( "type", $this->type == null ? self::TYPE_MAP_PORTAL : $this->type );
		$data->set ( "displayName", $this->displayName );
		$data->set ( "levelName", $this->levelName );
		
		$data->set ( "enterLevelName", $this->enterLevelName );
		$data->set ( "exitLevelName", $this->exitLevelName );
		
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
		if ($this->enterpos != null) {
			$data->set ( "entranceX", $this->enterpos->x );
			$data->set ( "entranceY", $this->enterpos->y );
			$data->set ( "entranceZ", $this->enterpos->z );
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
		
		if ($this->exitpos != null) {
			$data->set ( "exitX", $this->exitpos->x );
			$data->set ( "exitY", $this->exitpos->y );
			$data->set ( "exitZ", $this->exitpos->z );
		}		
		$data->save ();
		return true;
	}
	public static function getData($path, $name) {
		if (! file_exists ( $path . self::DIR_PORTAL_DATA . "$name.yml" )) {
			return null;
		}
		$data = new Config ( $path . self::DIR_PORTAL_DATA . "$name.yml", Config::YAML );
		$data->getAll ();
		return $data;
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
	
	final public static function teleportingToLobby(Player $player, $levelname, Position $pos) {
		if (! $player->getServer ()->isLevelLoaded ( $levelname )) {
			$ret = $player->getServer ()->loadLevel ( $levelname );
			if (! $ret) {
				$player->sendMessage ( "[HG]Error, unable load World: " . $levelname );
				return;
			}
		}
		if (! $player->getServer ()->isLevelGenerated ( $levelname )) {
			$player->sendMessage ( "[HG] world generation is not ready! try later." );
			return;
		}
		$level = $player->getServer ()->getLevelByName ( $levelname );
		if (is_null($level)) {
			$player->sendMessage ( "[HG] Error, unable access world: " . $levelname );
			return;
		}
		$player->teleport ($level->getSafeSpawn());
		self::safeTeleporting($player, new Position($pos->x,$pos->y,$pos->z,$level));
	}
	
	final public static function teleportingToHallOfFrame(HungerGamesPlugIn $plugin, Player $player, $levelname) {
		if (! $player->getServer ()->isLevelLoaded ( $levelname )) {
			$ret = $player->getServer ()->loadLevel ( $levelname );
			if (! $ret) {
				$player->sendMessage ( "[HG]Error, unable load World: " . $levelname );
				return;
			}
		}
		if (! $player->getServer ()->isLevelGenerated ( $levelname )) {
			$player->sendMessage ( "[HG] world generation is not ready! try later." );
			return;
		}
		$level = $player->getServer ()->getLevelByName ( $levelname );
		if (is_null($level)) {
			$player->sendMessage ( "[HG] Error, unable access world: " . $levelname );
			return;
		}
		$player->teleport ($level->getSafeSpawn());
		self::safeTeleporting($player, new Position($plugin->hubHallOfFramePos->x,$plugin->hubHallOfFramePos->y,$plugin->hubHallOfFramePos->z,$level));
	}
	
	final public static function teleportToMap($levelname, Player $player) {
		if (! $player->getServer ()->isLevelLoaded ( $levelname )) {
			$ret = $player->getServer ()->loadLevel ( $levelname );
			if (! $ret) {
				$player->sendMessage ( "[HG]Error, unable load World: " . $levelname );
				return;
			}
		}
		if (! $player->getServer ()->isLevelGenerated ( $levelname )) {
			$player->sendMessage ( "[HG] world generation is not ready! try later." );
			return;
		}
		$level = $player->getServer ()->getLevelByName ( $levelname );
		if (is_null($level)) {
			$player->sendMessage ( "[HG] Error, unable access world: " . $levelname );
			return;
		}
		$player->getLevel ()->updateAllLight ( $player->getPosition () );
		$player->getLevel ()->updateAround ( $player->getPosition () );
		$player->teleport ($level->getSafeSpawn());
	}
	
	final public static function safeTeleporting(Player $player, $destinationPos) {
		if (is_null($destinationPos) || is_null($player)) {
			throw new \Exception ("[HG] safeTelepoerting parameter can not be nu", "200", null);			
		}
		//$player->getLevel()->getChunkAt($destinationPos->x, $destinationPos->z);
		//$player->getLevel()->requestChunk($destinationPos->x, $destinationPos->z, $player);
		$player->getLevel()->updateAllLight($destinationPos);
		$player->getLevel()->updateAround($destinationPos);
		$player->teleport($destinationPos);		
	}
	
	public static function createSamplePortal(HungerGamesPlugIn $plugin) {
		$portal = new MapPortal ( $plugin, "sample_portal" );
		$portal->levelName = "portal";
		$portal->displayName = "map portal";
		$portal->type = self::TYPE_MAP_PORTAL;
		$portal->portalEnterPos1 = new Position ( "128", "12", "123" );
		$portal->portalEnterPos2 = new Position ( "128", "12", "123" );
		$portal->enterpos = new Position ( "128", "12", "123" );
		$portal->enterLevelName = "HG_Island";		
		$portal->portalExitPos1 = new Position ( "128", "12", "123" );
		$portal->portalExitPos2 = new Position ( "128", "12", "123" );
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
