<?php

namespace hungergames\statue;

use hungergames\main\HungerGamesPlugIn;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\math\Vector2 as Vector2;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use hungergames\utils\MagicUtil;
use pocketmine\utils\TextFormat;


class StatueManager {
	const STATUE_DIR = 'statue_data/';
	public $plugin;
	public $npcs = [ ];
	public $npcsPositions = [ ];
	public $npcsPodium = [ ];
	public $npcsSpawns = [ ];
	public $npcsIds = [ ];
	public function __construct(HungerGamesPlugIn $plugin) {
		$this->plugin = $plugin;
	}
	public function loadStatues() {
		$path = $this->plugin->getDataFolder () . self::STATUE_DIR;
		if (! file_exists ( $path )) {
			@mkdir ( $path, 0755, true );
			foreach ( $this->plugin->getResources () as $resource ) {
				if (! $resource->isDir ()) {
					$fp = $resource->getPathname ();
					if (strpos ( $fp, "statue_data" ) !== false) {
						$this->plugin->info ( TextFormat::AQUA . " *** setup default [statue]: " . $resource->getFilename () );
						copy ( $resource->getPathname (), $path . $resource->getFilename () );
					}
				}
			}
		}
		$handler = opendir ( $path );
		$i = 1;
		while ( ($filename = readdir ( $handler )) !== false ) {
			if ($filename != "." && $filename != "..") {
				$data = new Config ( $path . $filename, Config::YAML );
				$this->plugin->getLogger ()->info ( "load statue file:" . $path . $filename );
				Server::getInstance ()->loadLevel ( $data->get ( "levelName" ) );
				$pLevel = Server::getInstance ()->getLevelByName ( $data->get ( "levelName" ) );
				
				$name = str_replace ( ".yml", "", $filename );
				$eid = $data->get ( "eid" );
				$networkId = $data->get ( "networkId" );
				$type = $data->get ( "type" );
				$levelname = $data->get ( "levelName" );
				$pos = new Position ( $data->get ( "positionX" ), $data->get ( "positionY" ), $data->get ( "positionZ" ), $pLevel );
				$itemOnHand = $data->get ( "itemOnHand" );
				$armorHelmet = $data->get ( "armorHelmet" );
				$armorChestplate = $data->get ( "armorChestplate" );
				$armorLegging = $data->get ( "armorLegging" );
				$armorBoots = $data->get ( "armorBoots" );
				$kitName = $data->get ( "kitName" );
				$kitBlock = $data->get ( "kitBlock" );
				$cost = $data->get ( "cost" );
				$category = $data->get ( "category", "mod" );
				
				$statue = new StatueModel ( $name, $type, $networkId, $pos, $levelname, $itemOnHand, $armorHelmet, $armorChestplate, $armorLegging, $armorBoots );
				$statue->cost = $cost;
				$statue->kitBlock = $kitBlock;
				$statue->kitName = $kitName;
				$statue->particles = $data->get ( "particles" );
				$statue->eid = $eid;
				$statue->category = $category;
				
				if ($data->get ( "destinationPosX" ) != null) {
					$statue->destinationPos = new Position ( $data->get ( "destinationPosX" ), $data->get ( "destinationPosY" ), $data->get ( "destinationPosZ" ) );
				}
				$statue->destinationLevelName = $data->get ( "destinationLevelName" );
				$statue->message = $data->get ( "message" );
				
				$this->npcs [$name] = $statue;
				$posKey = round ( $pos->x ) . "." . round ( $pos->y ) . "." . round ( $pos->z );
				$this->npcsPositions [$posKey] = $statue;
				
				$statue->eid = StatueModel::generateEID () + $i + mt_rand ( 1, 100 );
				$i ++;
				if (isset ( $this->npcsIds [$statue->eid] )) {
					$statue->eid = StatueModel::generateEID ();
				}
				$this->npcsIds [$statue->eid] = $statue;
				
				// preset block
				$block = Item::get ( $statue->kitBlock );
				if ($block === null) {
					$block = Item::get ( Item::GLASS );
				}
			}
		}
		closedir ( $handler );
		$this->plugin->getLogger ()->info ( "total loaded statue count:" . count ( $this->npcs ) );
	}
	public function handlePlayerTapOnStatueBlock(Player $player, $block) {
		$posKey = round ( $block->x ) . "." . round ( $block->y ) . "." . round ( $block->z );
		if (isset ( $this->npcsPositions [$posKey] )) {
			$statue = $this->npcsPositions [$posKey];
			if ($statue instanceof StatueModel) {
				if ($statue->category === StatueModel::STATUS_CATEGORY_KIT) {
					$this->processKitPurchaseTranssaction ( $player, $statue );
				} else {
					MagicUtil::addParticles ( $player->getLevel (), "portal", $player->getPosition (), 100 );
					$player->sendPopup ( TextFormat::BOLD . TextFormat::GOLD . $statue->message );
					$player->teleport ( $statue->destinationPos );
					$player->sendMessage ( TextFormat::GRAY . "[HG]" . $statue->message );
				}
			}
		}
	}
	
	/**
	 *
	 * @param Player $player        	
	 * @param StatueModel $statue        	
	 */
	public function processKitPurchaseTranssaction(Player $player, StatueModel $statue) {
		$data = $this->plugin->profileManager->retrievePlayerByName ( $player->getName () );
		if ($data == null || count ( $data ) == 0) {
			$this->plugin->profileManager->addPlayer ( $player->getName () );
			$data = $this->plugin->profileManager->retrievePlayerByName ( $player->getName () );
		}
		if ($this->plugin->profileManager->hasPlayerAlreadyPurchasedKit ( $player->getName (), $statue->kitName )) {
			$this->plugin->gamekitManager->putOnGameKit ( $player, $statue->kitName );
			$player->sendMessage ( TextFormat::GREEN . "[HG] You already purchased [" . $statue->kitName . "]" );
			$player->sendTip ( TextFormat::BOLD . "[HG] loaded " . TextFormat::YELLOW . $statue->message );
		} elseif ($data != null && $data [0] ["balance"] >= $statue->cost) {
			$player->sendMessage ( TextFormat::AQUA . "[HG] Thanks for your purchased [" . TextFormat::GOLD . $statue->kitName . "]" );
			$this->plugin->profileManager->withdraw ( $player->getName (), $statue->cost );
			$this->plugin->profileManager->upsetPlayerKitPurchased ( $player->getName (), $statue->kitName );
			$this->plugin->gamekitManager->putOnGameKit ( $player, $statue->kitName );
			$player->sendTip ( TextFormat::BOLD . "[HG] loaded " . TextFormat::YELLOW . $statue->message );
		} elseif ($data != null && $data [0] ["balance"] < $statue->cost) {
			$player->sendMessage ( TextFormat::YELLOW . "[HG] Sorry, not enought coins. [item : " . $statue->cost . " coins]" );
		}
	}
	public function handlePlayerJoinTap(Player $player, $block) {
		$posKey = round ( $block->x ) . "." . round ( $block->y ) . "." . round ( $block->z );
		if (isset ( $this->npcsPositions [$posKey] )) {
			$npc = $this->npcsPositions [$posKey];
			
			$data = $this->plugin->profileprovider->retrievePlayerByName ( $player->getName () );
			if ($npc->kitName == "vip_kit") {
				$player->sendMessage ( "[HG] VIP only!" );
			} else {
				if ($data == null || count ( $data ) == 0) {
					$this->plugin->profileprovider->addPlayer ( $player->getName () );
					$data = $this->plugin->profileprovider->retrievePlayerByName ( $player->getName () );
				}
				$player->setNameTag ( $player->getName () );
			}
		}
	}
	
	/**
	 * handle player presence
	 *
	 * @param Player $player        	
	 * @param Position $from        	
	 */
	public function npcHandlePlayerPresence(Player $player, Position $from) {
		foreach ( $this->npcs as $xnpc ) {
			$statuePos = $xnpc->position;
			$pp = new Vector2 ( round ( $player->x ), round ( $player->z ) );
			$npc = new Vector2 ( $statuePos->x, $statuePos->z );
			$dff = abs ( round ( $pp->distance ( $npc ) ) );
			if ($dff < 4 || $dff == 0) {
				$x = round ( $from->x );
				$y = round ( $from->y );
				$z = round ( $from->z );
				if (round ( $player->x ) != $x || round ( $player->y ) != $y || round ( $player->z ) != $z) {
					$posKey = round ( $statuePos->x ) . "." . round ( $statuePos->y ) . "." . round ( $statuePos->z );
					if (isset ( $this->npcsPositions [$posKey] )) {
						$npc = $this->npcsPositions [$posKey];
						$block = Item::get ( Item::GLASS );
						$direct = false;
						$update = true;
						$player->level->setBlock ( $statuePos, $block->getBlock (), $direct, $update );
						StatueBuilder::refreshNPCEquipments ( $player, $npc );
					}
				}
			}
		}
	}
	public function listStatues(Player $sender) {
		$path = $this->plugin->getDataFolder () . self::STATUE_DIR;
		if (! file_exists ( $path )) {
			@mkdir ( $this->plugin->getDataFolder (), 0755, true );
			@mkdir ( $path );
			return;
		}
		$output = "list of statues:\n";
		$handler = opendir ( $path );
		$i = 1;
		while ( ($filename = readdir ( $handler )) !== false ) {
			if ($filename != "." && $filename != "..") {
				$data = new Config ( $path . $filename, Config::YAML );
				$name = str_replace ( ".yml", "", $filename );
				$type = $data->get ( "type" );
				$levelname = $data->get ( "levelName" );
				$pos = new Position ( $data->get ( "positionX" ), $data->get ( "positionY" ), $data->get ( "positionZ" ) );
				$networkId = $data->get ( "networkId" );
				$itemOnHand = $data->get ( "itemOnHand" );
				$armorHelmet = $data->get ( "armorHelmet" );
				$armorChestplate = $data->get ( "armorChestplate" );
				$armorLegging = $data->get ( "armorLegging" );
				$armorBoots = $data->get ( "armorBoots" );
				$output .= $i . ". " . $name . " | (" . $type . ") at " . $pos->x . " " . $pos->y . " " . $pos->z . "\n";
				$i ++;
			}
		}
		$sender->sendMessage ( $output );
		closedir ( $handler );
	}
	public function findStatueByName(Player $sender, $statueName) {
		$path = $this->plugin->getDataFolder () . self::STATUE_DIR;
		if (! file_exists ( $path )) {
			@mkdir ( $this->plugin->getDataFolder (), 0755, true );
			@mkdir ( $path );
		}
		$handler = opendir ( $path );
		while ( ($filename = readdir ( $handler )) !== false ) {
			if ($filename != "." && $filename != "..") {
				$data = new Config ( $path . $filename, Config::YAML );
				$name = str_replace ( ".yml", "", $filename );
				$levelName = $data->get ( "levelName" );
				$networkId = $data->get ( "networkId" );
				$type = $data->get ( "type" );
				Server::getInstance ()->loadLevel ( $data->get ( "levelName" ) );
				$pLevel = Server::getInstance ()->getLevelByName ( $data->get ( "levelName" ) );
				$pos = new Position ( $data->get ( "positionX" ), $data->get ( "positionY" ), $data->get ( "positionZ" ), $pLevel );
				if ($name == $statueName) {
					$this->log ( " found statue " . $name . " | " . $pos );
					return new StatueModel ( $name, $type, $networkId, $pos, levelName );
				}
			}
		}
		closedir ( $handler );
		return null;
	}
	public function saveStatue(StatueModel $statue) {
		$success = false;
		try {
			$this->npcs [$statue->name] = $statue;
			$statue->save ( $this->plugin->getDataFolder () . self::STATUE_DIR );
			$success = true;
		} catch ( \Exception $e ) {
			$this->log ( "add statue error " . $e->__toString () );
		}
		$this->log ( "Added statue " . $statue->name . " | " . $statue->position );
		return $success;
	}
	public function deleteStatue($name) {
		$found = false;
		$statue = null;
		if (isset ( $this->npcs [$name] )) {
			$statue = $this->npcs [$name];
			unset ( $this->npcs [$name] );
			$found = true;
		}
		try {
			if ($found) {
				$statue->delete ( $this->plugin->getDataFolder () . self::STATUE_DIR );
				$this->log ( "deleted statue -" . $name );
				return true;
			}
		} catch ( \Exception $e ) {
			$this->log ( "delete statue error " . $e->__toString () );
		}
		return false;
	}
	public static function createNPC2($eid, $npos, $name, $itemNo = null, $armorHelmet = null, $armorChestplate = null, $armorLegging = null, $armorBoots = null) {
		$x = $npos->x;
		$y = $npos->y;
		$z = $npos->z;
		$ps = new Position ( $x, $y, $z );
		$npcpos = $npos == null ? $ps : $npos;
		$npcName = $name == null ? "NPC" : $name;
		$slots = [ ];
		$slots [0] = $armorHelmet;
		$slots [1] = $armorChestplate;
		$slots [2] = $armorLegging;
		$slots [3] = $armorBoots;
		self::spawnPlayer2 ( $eid, $npcpos, $npcName, $itemNo, $slots );
	}
	public static function removeNPC2($eid, $pos) {
		$pk = new RemovePlayerPacket ();
		$pk->eid = $eid;
		$pk->x = $pos->x;
		$pk->y = $pos->y;
		$pk->z = $pos->z;
		$pk->clientID = 0;
		Server::broadcastPacket ( Server::getInstance ()->getOnlinePlayers (), $pk );
	}
	
	/**
	 * Send NPC Players
	 *
	 * @param
	 *        	$eid
	 * @param
	 *        	$pos
	 * @param
	 *        	$userName
	 * @param
	 *        	$itemId
	 * @param
	 *        	$slots
	 * @internal param unknown $modId
	 * @internal param Player $p
	 */
	public static function spawnPlayer2($eid, $pos, $userName, $itemId, $slots) {
		$pk = new AddPlayerPacket ();
		$pk->clientID = 0;
		$pk->username = $userName;
		$pk->eid = $eid;
		$pk->x = $pos->x;
		$pk->y = $pos->y;
		$pk->z = $pos->z;
		$pk->yaw = 0;
		$pk->pitch = 0;
		$pk->item = $itemId;
		$pk->meta = 0;
		$pk->metadata = [ ];
		Server::broadcastPacket ( Server::getInstance ()->getOnlinePlayers (), $pk );
		
		if ($slots != null) {
			$pk = new PlayerArmorEquipmentPacket ();
			$pk->eid = $eid;
			$pk->slots = $slots;
			$pk->encode ();
			$pk->isEncoded = true;
			Server::broadcastPacket ( Server::getInstance ()->getOnlinePlayers (), $pk );
		}
	}
	public function testRecord() {
		$name = "liberty";
		$type = "npc";
		$levelname = "world";
		$pos = new Position ( 128, 128, 128 );
		$networkId = 0;
		$itemOnHand = 1;
		$armorHelmet = null;
		$armorChestplate = null;
		$armorLegging = null;
		$armorBoots = null;
		$statue = new StatueModel ( $name, $type, $networkId, $pos, $levelname, $itemOnHand, $armorHelmet, $armorChestplate, $armorLegging, $armorBoots );
		$statue->save ( $this->plugin->getDataFolder () . self::STATUE_DIR );
	}
}
