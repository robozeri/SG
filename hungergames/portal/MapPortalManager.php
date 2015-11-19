<?php

namespace hungergames\portal;

use hungergames\main\HungerGamesPlugIn;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;


class MapPortalManager {
	private $plugin;
	
	public $portals = [];
	
	public function __construct(HungerGamesPlugIn &$plugin) {
		$this->plugin = $plugin;
	}
	public function preloadPortals() {
		$path = $this->plugin->getDataFolder () . MapPortal::DIR_PORTAL_DATA;
		if (! file_exists ( $path )) {
			@mkdir ( $path, 0755, true );
		}		
		$this->plugin->log("#loading portal " . $path );
		$handler = opendir ( $path );
		while ( ($filename = readdir ( $handler )) !== false ) {
			$this->plugin->getLogger ()->info ( $filename );
			if ($filename != "." && $filename != "..") {
				$data = new Config ( $path . $filename, Config::YAML );
				$data->getAll ();
				Server::getInstance ()->loadLevel ( $data->get ( "levelName" ) );
				$pLevel = Server::getInstance ()->getLevelByName ( $data->get ( "levelName" ) );
				$name = str_replace ( ".yml", "", $filename );
				$name = $data->get ( "name" );
				$levelName = $data->get ( "levelName" );
				
				$portal = new MapPortal ( $this->plugin, $name );
				$portal->levelName = $levelName;
				$portal->displayName = $data->get ( "displayName" );
				$portal->type = $data->get ( "type" );
				
				if ($data->get ( "portalEnter1X" ) != null) {
					$portal->portalEnterPos1 = new Position ( $data->get ( "portalEnter1X" ), $data->get ( "portalEnter1Y" ), $data->get ( "portalEnter1Z" ), $pLevel );
				}
				
				if ($data->get ( "portalEnter2X" ) != null) {
					$portal->portalEnterPos2 = new Position ( $data->get ( "portalEnter2X" ), $data->get ( "portalEnter2Y" ), $data->get ( "portalEnter2Z" ), $pLevel );
				}
				
				if ($data->get ( "entranceX" ) != null) {
					$portal->enterpos = new Position ( $data->get ( "entranceX" ), $data->get ( "entranceY" ), $data->get ( "entranceZ" ) );
				}
				
				if ($data->get ( "portalExit1X" ) != null) {
					$portal->portalExitPos1 = new Position ( $data->get ( "portalExit1X" ), $data->get ( "portalExit1Y" ), $data->get ( "portalExit1Z" ), $pLevel );
				}
				if ($data->get ( "portalExit2X" ) != null) {
					$portal->portalExitPos2 = new Position ( $data->get ( "portalExit2X" ), $data->get ( "portalExit2Y" ), $data->get ( "portalExit2Z" ), $pLevel );
				}
				if ($data->get ( "exitX" ) != null) {
					$portal->exitpos = new Position ( $data->get ( "exitX" ), $data->get ( "exitY" ), $data->get ( "exitZ" ) );
				}				
				if ($data->get ( "locationX" ) != null) {
					$portal->location = new Position ( $data->get ( "locationX" ), $data->get ( "locationY" ), $data->get ( "locationZ" ), $pLevel );
				}				
				$portal->enterLevelName = $data->get ( "enterLevelName" );
				$portal->exitLevelName = $data->get ( "exitLevelName" );				
				$portal->maps = $data->get ( "maps" );				
				$this->portals [$name] = $portal;
			}
		}
		closedir ( $handler );
	}
	public function addPortal(MapPortal $p) {
		$this->plugin->portals [$p->name] = $p;
		$p->save ( $this->plugin->getDataFolder () . MapPortal::DIR_PORTAL_DATA );
		$this->plugin->log( "save portal " . $p->name . " | " . $p->destination );
	}
	public function deletePortal($name) {
		if (! isset ( $this->plugin->portals [$name] ))
			return false;
		$this->plugin->portals [$name]->delete ( $this->plugin->getDataFolder () . MapPortal::DIR_PORTAL_DATA );
		unset ( $this->plugin->portals [$name] );
		return true;
	}
	public static function isLevelLoaded(Player $player, $levelhome) {
		$level = null;
		if (! $player->getServer ()->isLevelGenerated ( $levelhome )) {
			$player->sendMessage ( "unable to find world name [" . $levelhome . "]" );
			return null;
		}		
		if (! $player->getServer ()->isLevelLoaded ( $levelhome )) {
			$player->getServer ()->loadLevel ( $levelhome );
		}		
		if ($player->getServer ()->isLevelLoaded ( $levelhome )) {
			$player->sendMessage ( "level loaded -" . $levelhome );
			$level = $player->getServer ()->getLevelByName ( $levelhome );
			if ($level == null) {
				$player->sendMessage ( "level not found: " . $levelhome );
				return null;
			}
		}
		return $level;
	}
	public static function getLevel($server, $levelhome) {
		$level = null;
		if (! $server->isLevelGenerated ( $levelhome )) {
			return;
		}		
		if (! $server->isLevelLoaded ( $levelhome )) {
			$server->loadLevel ( $levelhome );
		}		
		if ($server->isLevelLoaded ( $levelhome )) {
			$level = $server->getLevelByName ( $levelhome );
			if ($level == null) {
				return null;
			}
		}
		return $level;
	}
}
