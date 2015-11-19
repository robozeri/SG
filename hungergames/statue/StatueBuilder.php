<?php

namespace hungergames\statue;

use hungergames\main\HungerGamesPlugIn;
use pocketmine\block\Block;
use pocketmine\event\player\PlayerAnimationEvent;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\level\particle\BubbleParticle;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\particle\EnchantParticle;
use pocketmine\level\particle\ExplodeParticle;
use pocketmine\level\particle\FlameParticle;
use pocketmine\level\particle\HeartParticle;
use pocketmine\level\particle\InkParticle;
use pocketmine\level\particle\ItemBreakParticle;
use pocketmine\level\particle\LavaDripParticle;
use pocketmine\level\particle\LavaParticle;
use pocketmine\level\particle\PortalParticle;
use pocketmine\level\particle\RedstoneParticle;
use pocketmine\level\particle\SmokeParticle;
use pocketmine\level\particle\SplashParticle;
use pocketmine\level\particle\SporeParticle;
use pocketmine\level\particle\TerrainParticle;
use pocketmine\level\particle\WaterDripParticle;
use pocketmine\level\particle\WaterParticle;
use pocketmine\level\Position;
use pocketmine\math\Vector3 as Vector3;
use pocketmine\network\Network;
use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\AddItemEntityPacket;
use pocketmine\network\protocol\AddPlayerPacket;
use pocketmine\network\protocol\AnimatePacket;
use pocketmine\network\protocol\MoveEntityPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\network\protocol\PlayerArmorEquipmentPacket;
use pocketmine\network\protocol\RemoveEntityPacket;
use pocketmine\network\protocol\RemovePlayerPacket;
use pocketmine\network\protocol\SetEntityMotionPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\Random;
use pocketmine\utils\TextFormat;


class StatueBuilder {
	private $pgin;
	private $peblock;
	public function __construct(HungerGamesPlugIn $pg) {
		$this->pgin = $pg;
	}
	public static function addParticles(Level $level, $name, Position $pos1, $count = 5) {
		$xd = ( float ) 280;
		$yd = ( float ) 260;
		$zd = ( float ) 280;
		$particle1 = self::getParticle ( $name, $pos1, $xd, $yd, $zd, 0 );
		$random = new Random ( ( int ) (\microtime ( \true ) * 1000) + \mt_rand () );
		for($i = 0; $i < $count; ++ $i) {
			$particle1->setComponents ( $pos1->x + $random->nextSignedFloat () * $xd, $pos1->y + $random->nextSignedFloat () * $yd, $pos1->z + $random->nextSignedFloat () * $zd );
			$level->addParticle ( $particle1 );
		}
	}
	public static function getParticle($name, Vector3 $pos, $xd, $yd, $zd, $data) {
		switch ($name) {
			case "explode" :
				return new ExplodeParticle ( $pos );
			case "bubble" :
				return new BubbleParticle ( $pos );
			case "splash" :
				return new SplashParticle ( $pos );
			case "wake" :
			case "water" :
				return new WaterParticle ( $pos );
			case "crit" :
				return new CriticalParticle ( $pos );
			case "smoke" :
				return new SmokeParticle ( $pos, $data !== \null ? $data : 0 );
			case "spell" :
				return new EnchantParticle ( $pos );
			case "dripwater" :
				return new WaterDripParticle ( $pos );
			case "driplava" :
				return new LavaDripParticle ( $pos );
			case "townaura" :
			case "spore" :
				return new SporeParticle ( $pos );
			case "portal" :
				return new PortalParticle ( $pos );
			case "flame" :
				return new FlameParticle ( $pos );
			case "lava" :
				return new LavaParticle ( $pos );
			case "reddust" :
				return new RedstoneParticle ( $pos, $data !== \null ? $data : 1 );
			case "snowballpoof" :
				return new ItemBreakParticle ( $pos, Item::get ( Item::SNOWBALL ) );
			case "itembreak" :
				if ($data !== \null and $data !== 0) {
					return new ItemBreakParticle ( $pos, $data );
				}
				break;
			case "terrain" :
				if ($data !== \null and $data !== 0) {
					return new TerrainParticle ( $pos, $data );
				}
				break;
			case "heart" :
				return new HeartParticle ( $pos, $data !== \null ? $data : 0 );
			case "ink" :
				return new InkParticle ( $pos, $data !== \null ? $data : 0 );
		}
		
		if (\substr ( $name, 0, 10 ) === "iconcrack_") {
			$d = \explode ( "_", $name );
			if (\count ( $d ) === 3) {
				return new ItemBreakParticle ( $pos, Item::get ( ( int ) $d [1], ( int ) $d [2] ) );
			}
		} elseif (\substr ( $name, 0, 11 ) === "blockcrack_") {
			$d = \explode ( "_", $name );
			if (\count ( $d ) === 2) {
				return new TerrainParticle ( $pos, Block::get ( $d [1] & 0xff, $d [1] >> 12 ) );
			}
		} elseif (\substr ( $name, 0, 10 ) === "blockdust_") {
			$d = \explode ( "_", $name );
			if (\count ( $d ) >= 4) {
				return new DustParticle ( $pos, $d [1] & 0xff, $d [2] & 0xff, $d [3] & 0xff, isset ( $d [4] ) ? $d [4] & 0xff : 255 );
			}
		}
		
		return \null;
	}
	
	/**
	 *
	 * @param Player $player        	
	 * @param array $statuelist        	
	 */
	public function displayStatues(Player $player, array $statuelist) {
		foreach ( $statuelist as $statue ) {
			if ($statue instanceof StatueModel) {
				if ($statue->type === StatueModel::STATUE_TYPE_NPC) {
					if ($player->getLevel ()->getName () === $statue->levelName) {
						if ($statue->category === StatueModel::STATUS_CATEGORY_KIT) {
							$statue->name = TextFormat::BOLD . TextFormat::WHITE . $statue->name;
							$statue->displayName = TextFormat::BOLD . TextFormat::WHITE . $statue->name . " | " . TextFormat::GOLD . "$" . $statue->cost . TextFormat::GRAY . " coins";
							if ($statue->name === "pvp_kit_free") {
								$statue->displayName = TextFormat::BOLD . TextFormat::GREEN . $statue->name . " | " . TextFormat::GOLD . "$" . $statue->cost . TextFormat::GRAY . " coins";
							}
							if ($statue->name === "pvp_kit_diamond") {
								$statue->displayName = TextFormat::BOLD . TextFormat::AQUA . $statue->name . " | " . TextFormat::GOLD . "$" . $statue->cost . TextFormat::GRAY . " coins";
							}
						} else {
							$statue->name = TextFormat::BOLD . TextFormat::WHITE . $statue->name;
							$statue->displayName = TextFormat::BOLD . TextFormat::WHITE . $statue->name;
						}
						$this->spawnStatue ( $statue, $player );
						$player->level->setBlock ( $statue->position, Item::get ( Item::GLASS )->getBlock (), false, true );
					}
				}
				if ($player->getLevel ()->getName () === $statue->levelName) {
					if ($statue->type == StatueModel::STATUE_TYPE_MOD) {
						$this->spawnMods ( $statue, $player );
					}
				}
			}
		}
	}
	public function removeMob($modId, $pos, Player $player) {
		$pk = new RemoveEntityPacket ();
		$pk->eid = $modId;
		$pk->x = $pos->x;
		$pk->y = $pos->y;
		$pk->z = $pos->z;
		$pk->clientID = 0;
		$player->dataPacket ( $pk );
		Server::broadcastPacket ( $player->getViewers (), $pk );
		$this->log ( " removed Mods at - " . $pos->x . " " . $pos->y . " " . $pos->z );
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
		$pk = new RemoveEntityPacket ();
		$pk->eid = $eid;
		$pk->x = $pos->x;
		$pk->y = $pos->y;
		$pk->z = $pos->z;
		$pk->clientID = 0;
		Server::broadcastPacket ( Server::getInstance ()->getOnlinePlayers (), $pk );
	}
	public function removeNPC($pos, Player $player) {
		$pk = new RemovePlayerPacket ();
		$pk->eid = 10001;
		$pk->x = $pos->x;
		$pk->y = $pos->y;
		$pk->z = $pos->z;
		$pk->clientID = 0;
		$player->dataPacket ( $pk );
		Server::broadcastPacket ( $player->getViewers (), $pk );
		$this->log ( " removed NPC at - " . $pos->x . " " . $pos->y . " " . $pos->z );
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
		$pk = new AddItemEntityPacket ();
		$pk->eid = $eid;
		$pk->x = $pos->x;
		$pk->y = $pos->y;
		$pk->z = $pos->z;
		$pk->yaw = 0;
		$pk->pitch = 0;
		$pk->unknown1 = 0;
		$pk->unknown2 = 0;
		$pk->item = $itemId;
		$pk->meta = 0;
		$pk->metadata = [ ];
		$pk->skin = null;
		Server::broadcastPacket ( Server::getInstance ()->getOnlinePlayers (), $pk );
	}
	public function spawnPlayer($eid, Player $p, $pos, $userName, $itemId, $slots, $mx = 0, $my = 290, $mz = 0) {
		$pk = new AddPlayerPacket ();
		$pk->clientID = rand ( 101, 9999 );
		
		$pk->username = $userName;
		$pk->eid = $eid;
		$pk->x = $pos->x + 0.5;
		$pk->y = $pos->y;
		$pk->z = $pos->z + 0.5;
		$pk->yaw = $p->yaw;
		$pk->pitch = $p->pitch;
		$pk->unknown1 = 0;
		$pk->unknown2 = 0;
		$pk->item = $itemId;
		$pk->meta = 0;
		$pk->metadata = [ ];
		$pk->skin = $p->getSkinData ();
		$pk->encode ();
		$p->directDataPacket ( $pk );
		Server::broadcastPacket ( $p->getViewers (), $pk );
		
		$p->addEntityMotion ( $eid, 0, 290, 0 );
		if ($slots != null) {
			$pk = new PlayerArmorEquipmentPacket ();
			$pk->eid = $eid;
			$pk->slots = $slots;
			$pk->encode ();
			$pk->isEncoded = true;
			$p->directDataPacket ( $pk );
			Server::broadcastPacket ( $p->getViewers (), $pk );
		}
	}
	
	/**
	 * Send NPC Players
	 *
	 * @param StatueModel $statue        	
	 * @param Player $player        	
	 * @internal param unknown $modId
	 * @internal param Player $p
	 */
	public function spawnStatue(StatueModel $statue, Player $player) {
		$slots = [ ];
		$slots [0] = $statue->armorHelmet;
		$slots [1] = $statue->armorChestplate;
		$slots [2] = $statue->armorLegging;
		$slots [3] = $statue->armorBoots;
		
		// $eid = rand ( 101, 9900 );
		$pk = new AddPlayerPacket ();
		$pk->clientID = rand ( 101, 9999 );
		$pk->username = $statue->displayName;
		$pk->eid = $statue->eid;
		$pk->x = $statue->position->x + 0.5;
		$pk->y = $statue->position->y;
		$pk->z = $statue->position->z + 0.5;
		$pk->yaw = 0;
		$pk->pitch = 0;
		$pk->unknown1 = 0;
		$pk->unknown2 = 0;
		$pk->item = $statue->itemOnHand;
		$pk->meta = 0;
		$pk->metadata = [ ];
		$pk->skin = $player->getSkinData ();
		$pk->encode ();
		$player->dataPacket ( $pk );
		Server::broadcastPacket ( $player->getViewers (), $pk );
		
		$player->addEntityMotion ( $statue->eid, 64, 290, 0 );
		
		if ($slots != null) {
			$pk = new PlayerArmorEquipmentPacket ();
			$pk->eid = $statue->eid;
			$pk->slots = $slots;
			$pk->encode ();
			$pk->isEncoded = true;
			$player->dataPacket ( $pk );
			Server::broadcastPacket ( $player->getViewers (), $pk );
		}
	}
	
	/**
	 * Send Mods
	 *
	 * @param StatueModel $statue        	
	 * @param Player $p        	
	 * @internal param unknown $modId
	 */
	public function spawnMods(StatueModel $statue, Player $p) {
		$pk = new AddEntityPacket ();
		$pk->eid = $statue->eid;
		$pk->type = $statue->networkId;
		$pk->x = $statue->position->x;
		$pk->y = $statue->position->y;
		$pk->z = $statue->position->z;
		
		$pk->yaw = 0;
		$pk->pitch = 30;
		$pk->metadata = array ();
		$p->dataPacket ( $pk );
		Server::broadcastPacket ( $p->getViewers (), $pk );
		
		$pk = new SetEntityMotionPacket ();
		$pk->entities = [ 
				[ 
						$statue->eid,
						0,
						290,
						0 
				] 
		];
		$p->dataPacket ( $pk );
		Server::broadcastPacket ( $p->getViewers (), $pk );
		$p->addEntityMotion ( $statue->eid, 64, 290, 0 );
	}
	public static function moveMod(StatueModel $statue) {
		$mx = array_rand ( array (
				0 => 0,
				5 => 5,
				10 => 10,
				16 => 16,
				32 => 32,
				64 => 64,
				128 => 128,
				192 => 192 
		) );
		$my = array_rand ( array (
				0 => 0,
				5 => 5,
				10 => 10,
				16 => 16,
				32 => 32,
				64 => 64,
				128 => 128,
				192 => 192 
		) );
		$mz = array_rand ( array (
				0 => 0,
				5 => 5,
				10 => 10,
				16 => 16,
				64 => 64,
				255 => 255,
				192 => 192 
		) );
		$pk = new MoveEntityPacket ();
		$pk->entities = [ 
				[ 
						$statue->eid,
						$mx,
						$my,
						$mz 
				] 
		];
		Server::getInstance ()->broadcastPacket ( Server::getInstance ()->getOnlinePlayers (), $pk );
	}
	public static function moveStatue(StatueModel $statue) {
		$pk = new MovePlayerPacket ();
		$pk->eid = $statue->eid;
		$pk->x = $statue->position->x + 0.5;
		$pk->y = $statue->position->y + 1.5;
		$pk->z = $statue->position->z + 0.5;
		$pk->yaw = array_rand ( array (
				0 => 0,
				5 => 5,
				10 => 10,
				16 => 16,
				32 => 32,
				64 => 64,
				128 => 128,
				192 => 192 
		) );
		$pk->bodyYaw = array_rand ( array (
				0 => 0,
				5 => 5,
				10 => 10,
				16 => 16,
				32 => 32,
				64 => 64,
				128 => 128,
				192 => 192 
		) );
		$pk->pitch = array_rand ( array (
				0 => 0,
				5 => 5,
				10 => 10,
				16 => 16,
				32 => 32,
				64 => 64,
				255 => 255,
				192 => 192 
		) );
		$pk->mode = 1;
		Server::getInstance ()->broadcastPacket ( Server::getInstance ()->getOnlinePlayers (), $pk );
	}
	public static function animateStatue(StatueModel $statue) {
		$pk = new AnimatePacket ();
		$pk->eid = $statue->eid;
		$pk->action = PlayerAnimationEvent::ARM_SWING;
		Server::broadcastPacket ( Server::getInstance ()->getOnlinePlayers (), $pk->setChannel ( Network::CHANNEL_WORLD_EVENTS ) );
	}
	public static function refreshNPCEquipments(Player $p, StatueModel $npc) {
		$slots = [ ];
		$slots [0] = $npc->armorHelmet;
		$slots [1] = $npc->armorChestplate;
		$slots [2] = $npc->armorLegging;
		$slots [3] = $npc->armorChestplate;
		$p->addEntityMotion ( 1, $p->motionX, $p->motionY, $p->motionZ );
	}
	public function getPlugIn() {
		return $this->pgin;
	}
	public function spawnHallOfFrameWinners() {
		// update podium
		$topWinners = $this->pgin->profileManager->retrieveTopPlayers ();
		
		$level = $this->getPlugIn ()->hubLevel;
		$winners = [ ];
		if (count ( $topWinners ) == 1) {
			$goldplayer = $topWinners [0] ["pname"];
			$this->getPlugIn ()->statueManager->npcsPodium ["gold"] = $goldplayer;
			
			$npos = $this->getGoldItemCasePos ();
			$eid = 10000;
			StatueBuilder::removeNPC2 ( $eid, $npos );
			self::spawnCaseItem ( $npos, $eid, Item::DIAMOND_SWORD );
			$npos->y = $npos->y - 0.25;
			self::spawnCaseItem ( $npos, $eid . "1", Item::DIAMOND_HELMET );
			self::spawnCaseItem ( $npos, $eid . "11", Item::FIRE );
		}
		
		if (count ( $topWinners ) == 2) {
			$goldplayer = $topWinners [0] ["pname"];
			$this->getPlugIn ()->statueManager->npcsPodium ["gold"] = $goldplayer;
			
			$silverplayer = $topWinners [1] ["pname"];
			$this->getPlugIn ()->statueManager->npcsPodium ["silver"] = $silverplayer;
			
			$npos = $this->getGoldItemCasePos ();
			$eid = 20000;
			StatueBuilder::removeNPC2 ( $eid, $npos );
			self::spawnCaseItem ( $npos, $eid, Item::DIAMOND_SWORD );
			$npos->x = $npos->x - 0.25;
			self::spawnCaseItem ( $npos, $eid . "1", Item::DIAMOND_HELMET );
			self::spawnCaseItem ( $npos, $eid . "11", Item::FIRE );
			
			$eid = mt_rand ( 3000, 30000 );
			$npos = $this->getSilverItemCasePos ();
			StatueBuilder::removeNPC2 ( $eid, $npos );
			self::spawnCaseItem ( $npos, $eid, Item::GOLD_SWORD );
			$npos->x = $npos->x - 0.25;
			self::spawnCaseItem ( $npos, $eid . "2", Item::GOLD_HELMET );
			$npos->x = $npos->x - 0.25;
			self::spawnCaseItem ( $npos, $eid . "11", Item::FIRE );
		}
		
		if (count ( $topWinners ) == 3) {
			$goldplayer = $topWinners [0] ["pname"];
			$this->getPlugIn ()->statueManager->npcsPodium ["gold"] = $goldplayer;
			
			$silverplayer = $topWinners [1] ["pname"];
			$this->getPlugIn ()->statueManager->npcsPodium ["silver"] = $silverplayer;
			
			$brownseplayer = $topWinners [2] ["pname"];
			$this->getPlugIn ()->statueManager->npcsPodium ["brownse"] = $brownseplayer;
			
			$eid = 10000;
			$npos = $this->getGoldItemCasePos ();
			StatueBuilder::removeNPC2 ( $eid, $npos );
			self::spawnCaseItem ( $npos, $eid, Item::DIAMOND_SWORD );
			$npos->x = $npos->x - 0.25;
			self::spawnCaseItem ( $npos, $eid . "1", Item::DIAMOND_HELMET );
			self::spawnCaseItem ( $npos, $eid . "11", Item::FIRE );
			
			$eid = 20000;
			$npos = $this->getSilverItemCasePos ();
			StatueBuilder::removeNPC2 ( $eid, $npos );
			self::spawnCaseItem ( $npos, $eid, Item::GOLD_SWORD );
			$npos->x = $npos->x - 0.25;
			self::spawnCaseItem ( $npos, $eid . "2", Item::GOLD_HELMET );
			$npos->x = $npos->x - 0.25;
			self::spawnCaseItem ( $npos, $eid . "11", Item::FIRE );
			
			$eid = 30000;
			$npos = $this->getBrownseItemCasePos ();
			StatueBuilder::removeNPC2 ( $eid, $npos );
			self::spawnCaseItem ( $npos, $eid, Item::BOW );
			$npos->x = $npos->x - 0.25;
			self::spawnCaseItem ( $npos, $eid . "2", Item::ARROW );
			$npos->x = $npos->x - 0.25;
			self::spawnCaseItem ( $npos, $eid . "11", Item::FIRE );
		}
	}
	private function getConfig() {
		return $this->pgin->getConfig ();
	}
	private function getGoldItemCasePos() {
		$x = $this->getConfig ()->get ( "hg_podium_gold_x" );
		$y = $this->getConfig ()->get ( "hg_podium_gold_y" );
		$z = $this->getConfig ()->get ( "hg_podium_gold_z" );
		return new Position ( $x, $y, $z, $this->pgin->hubLevel );
	}
	private function getGoldSignPos() {
		$x = $this->getConfig ()->get ( "hg_podium_gold_sign_x" );
		$y = $this->getConfig ()->get ( "hg_podium_gold_sign_y" );
		$z = $this->getConfig ()->get ( "hg_podium_gold_sign_z" );
		return new Position ( $x, $y, $z, $this->pgin->hubLevel );
	}
	private function getSilverItemCasePos() {
		$x = $this->getConfig ()->get ( "hg_podium_silver_x" );
		$y = $this->getConfig ()->get ( "hg_podium_silver_y" );
		$z = $this->getConfig ()->get ( "hg_podium_silver_z" );
		return new Position ( $x, $y, $z, $this->pgin->hubLevel );
	}
	private function getSilverSignPos() {
		$x = $this->getConfig ()->get ( "hg_podium_silver_sign_x" );
		$y = $this->getConfig ()->get ( "hg_podium_silver_sign_y" );
		$z = $this->getConfig ()->get ( "hg_podium_silver_sign_z" );
		return new Position ( $x, $y, $z, $this->pgin->hubLevel );
	}
	private function getBrownseItemCasePos() {
		$x = $this->getConfig ()->get ( "hg_podium_bronze_x" );
		$y = $this->getConfig ()->get ( "hg_podium_bronze_y" );
		$z = $this->getConfig ()->get ( "hg_podium_bronze_z" );
		return new Position ( $x, $y, $z, $this->pgin->hubLevel );
	}
	private function getBrownseSignPos() {
		$x = $this->getConfig ()->get ( "hg_podium_bronze_sign_x" );
		$y = $this->getConfig ()->get ( "hg_podium_bronze_sign_y" );
		$z = $this->getConfig ()->get ( "hg_podium_bronze_sign_z" );
		return new Position ( $x, $y, $z, $this->pgin->hubLevel );
	}
	public function updateHallOfFrameWinners() {
		if (empty ( $this->pgin->hubLevel )) {
			return;
		}
		$topWinners = $this->getPlugIn ()->profileManager->retrieveTopPlayers ();
		$level = $this->pgin->hubLevel;
		if (count ( $topWinners ) === 0 || empty ( $topWinners )) {
			if (! empty ( $this->getGoldSignPos () )) {
				$sign = $level->getTile ( $this->getGoldSignPos () );
				if (! is_null ( $sign )) {
					$sign->setText ( TextFormat::AQUA . "[Top #1]", TextFormat::DARK_RED . "Your Name", TextFormat::WHITE . "goes here", TextFormat::GOLD . "=MOCKING JAY=" );
				}
				unset ( $sign );
			}
			if (! empty ( $this->getSilverSignPos () )) {
				$sign = $level->getTile ( $this->getSilverSignPos () );
				if (! is_null ( $sign )) {
					$sign->setText ( TextFormat::AQUA . "[Top #2]", TextFormat::DARK_RED . "Your Name", TextFormat::WHITE . "goes here", TextFormat::GRAY . "conglatulations" );
				}
				unset ( $sign );
			}
			if (! empty ( $this->getBrownseSignPos () )) {
				$sign = $level->getTile ( $this->getBrownseSignPos () );
				if (! is_null ( $sign )) {
					$sign->setText ( TextFormat::AQUA . "[Top #3]", TextFormat::DARK_RED . "Your Name", TextFormat::WHITE . "goes here", TextFormat::GRAY . "conglatulations" );
				}
				unset ( $sign );
			}
			return;
		}
		
		$winners = [ ];
		if (count ( $topWinners ) == 1) {
			$goldplayer = $topWinners [0] ["pname"];
			$this->getPlugIn ()->npcsPodium ["gold"] = $goldplayer;
			
			$signPos1 = $this->getGoldSignPos ();
			$sign = $level->getTile ( $signPos1 );
			if (! is_null ( $sign )) {
				$sign->setText ( TextFormat::AQUA . "[Top #1]", TextFormat::DARK_RED . $goldplayer, TextFormat::WHITE . "Won: " . $topWinners [0] ["wins"], TextFormat::GOLD . "=MOCKING JAY=" );
			}
			return;
		}
		if (count ( $topWinners ) == 2) {
			$goldplayer = $topWinners [0] ["pname"];
			$this->getPlugIn ()->npcsPodium ["gold"] = $goldplayer;
			
			$silverplayer = $topWinners [1] ["pname"];
			$this->getPlugIn ()->npcsPodium ["silver"] = $silverplayer;
			
			$signPos1 = $this->getGoldSignPos ();
			$sign = $level->getTile ( $signPos1 );
			if (! is_null ( $sign )) {
				$sign->setText ( TextFormat::AQUA . "[Top #1]", TextFormat::DARK_RED . $goldplayer, TextFormat::WHITE . "Won: " . $topWinners [0] ["wins"], TextFormat::GOLD . "=MOCKING JAY=" );
			} 
			$signPos1 = $this->getSilverSignPos ();
			$sign = $level->getTile ( $signPos1 );
			if (! is_null ( $sign )) {
				$sign->setText ( TextFormat::GOLD . "[Top #2]", TextFormat::DARK_RED . $silverplayer, TextFormat::WHITE . "Won: " . $topWinners [1] ["wins"], TextFormat::GRAY . "conglatulations" );
			}
			return;
		}
		
		if (count ( $topWinners ) == 3) {
			$goldplayer = $topWinners [0] ["pname"];
			$this->getPlugIn ()->npcsPodium ["gold"] = $goldplayer;
			
			$silverplayer = $topWinners [1] ["pname"];
			$this->getPlugIn ()->npcsPodium ["silver"] = $silverplayer;
			
			$brownseplayer = $topWinners [2] ["pname"];
			$this->getPlugIn ()->npcsPodium ["silver"] = $brownseplayer;
			
			$signPos1 = $this->getGoldSignPos ();			
			$sign = $level->getTile ( $signPos1 );
			if (! is_null ( $sign )) {
				$sign->setText ( TextFormat::AQUA . "[Top #1]", TextFormat::DARK_RED . $goldplayer, TextFormat::WHITE . "Won: " . $topWinners [0] ["wins"], TextFormat::GOLD . "=MOCKING JAY=" );
			}			
			$signPos1 = $this->getSilverSignPos ();
			$sign = $level->getTile ( $signPos1 );
			if (! is_null ( $sign )) {
				$sign->setText ( TextFormat::GOLD . "[Top #2]", TextFormat::DARK_RED . $silverplayer, TextFormat::WHITE . "Won: " . $topWinners [1] ["wins"], TextFormat::GRAY . "conglatulations" );
			}			
			$signPos1 = $this->getBrownseSignPos ();
			$sign = $level->getTile ( $signPos1 );
			if (! is_null ( $sign )) {
				$sign->setText ( TextFormat::DARK_GREEN . "[Top #3]", TextFormat::WHITE . $brownseplayer, "Won: " . $topWinners [2] ["wins"], TextFormat::GRAY . "conglatulations" );
			}
			return;
		}
	}
	public static function spawnCaseItem($pos, $eid, $itemId) {
		$pk = new AddItemEntityPacket ();
		$pk->eid = $eid;
		$pk->x = $pos->x + 0.5;
		$pk->y = $pos->y + 1;
		$pk->z = $pos->z + 0.5;
		$pk->speedX = 0;
		$pk->speedY = 0;
		$pk->speedZ = 0;
		$pk->item = Item::get ( $itemId );
		
		Server::broadcastPacket ( Server::getInstance ()->getOnlinePlayers (), $pk );
		
		$px = $pos->x;
		if ($px < 0) {
			$px = $px - 0.5 - 0.15;
		} else {
			$px = $px + 0.5 + 0.15;
		}
		
		$pk = new MoveEntityPacket ();
		$pk->entities = [ 
				[ 
						$eid,
						$px,
						$pos->y + 1 + 0.25,
						$pos->z + 0.5,
						0,
						0,
						0 
				] 
		];
		$pk->encode ();
		Server::broadcastPacket ( Server::getInstance ()->getOnlinePlayers (), $pk );
	}
	public static function renderBlock(Player $lp, Position $pos, $blocktype) {
		$block = Item::get ( $blocktype );
		$direct = false;
		$update = true;
		$lp->getLevel ()->setBlock ( $pos, $block->getBlock (), $direct, $update );
	}
	private function log($msg) {
		$this->pgin->log ( $msg );
	}
}
