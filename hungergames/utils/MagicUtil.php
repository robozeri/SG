<?php

namespace hungergames\utils;

use game\tntrun\itemcase\ItemCaseBuilder;
use pocketmine\block\Block;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\item\Item;
use pocketmine\level\Explosion;
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
use pocketmine\level\sound\LaunchSound;
use pocketmine\math\Vector3 as Vector3;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Float;
use pocketmine\network\protocol\AddMobPacket;
use pocketmine\network\protocol\RotateHeadPacket;
use pocketmine\Player;
use pocketmine\utils\Random;
use pocketmine\level\particle\FloatingTextParticle;


class MagicUtil {
	
	public static function addFloatingText(Level $level, $text, $title, Position $pos1)
	{
		$particle = new FloatingTextParticle ($pos1, $text, $title);
		$level->addParticle($particle);
		return $particle;
	}
	
	public static function addRanbowDust(Level $level, Position $pos1, $count = 3)
	{
		$pos = new Position ($pos1->x, $pos1->y + 2, $pos1->z, $level);
		for ($i = 0; $i <$count; $i++) {
			$pos = new Position ($pos1->x + rand(-2, 3), $pos1->y + rand(0, 5), $pos1->z + rand(-2, 3), $level);
			$level->addParticle(new DustParticle ($pos, rand(1, 255), rand(1, 255), rand(1, 255)));
			$level->addParticle(new DustParticle ($pos, rand(1, 255), rand(1, 255), rand(1, 255)));
			$level->addParticle(new DustParticle ($pos, rand(1, 255), rand(1, 255), rand(1, 255)));
			$level->addParticle(new DustParticle ($pos, rand(1, 255), rand(1, 255), rand(1, 255)));
			$level->addParticle(new DustParticle ($pos, rand(1, 255), rand(1, 255), rand(1, 255)));
		}
	}
	
	public static function addPlayerFireworks(Player $player, Position $pos1, $count = 5)
	{
		$pos = new Position ($player->getPosition()->x, $player->getPosition()->y + 3, $player->getPosition()->z, $player->getLevel());
		$pos2 = new Position ($player->getPosition()->x, $player->getPosition()->y + 1, $player->getPosition()->z, $player->getLevel());
		$exp = new Explosion ($pos, 1);
		$exp->explodeB();
		MagicUtil::addParticles($player->getLevel(), "explode", $pos2);
		for ($i = 0; $i < 5; $i++) {
			$pos = new Position ($pos1->x + rand(-2, 3), $pos1->y + rand(0, 5), $pos1->z + rand(-2, 3), $player->getLevel());
			$player->getLevel()->addParticle(new DustParticle ($pos, rand(1, 255), rand(1, 255), rand(1, 255)));
			$player->getLevel()->addParticle(new DustParticle ($pos, rand(1, 255), rand(1, 255), rand(1, 255)));
			$player->getLevel()->addParticle(new DustParticle ($pos, rand(1, 255), rand(1, 255), rand(1, 255)));
			$player->getLevel()->addParticle(new DustParticle ($pos, rand(1, 255), rand(1, 255), rand(1, 255)));
			$player->getLevel()->addParticle(new DustParticle ($pos, rand(1, 255), rand(1, 255), rand(1, 255)));
		}
		MagicUtil::addParticles($player->getLevel(), "driplava", $pos1);
	}
	
	public static function addLevelFireworks(Level $level, Position $pos1, $count = 3)
	{
		if (!is_null($level)) {
			$pos = new Position ($pos1->x, $pos1->y + 3, $pos1->z, $level);
			$pos2 = new Position ($pos1->x, $pos1->y + 1, $pos1->z, $level);
			// $exp = new Explosion ( $pos, 1 );
			// $exp->explodeB ();
			MagicUtil::addParticles($level, "explode", $pos2, 100);
			// for($i = 0; $i < $count; $i ++) {
			// $pos = new Position ( $pos1->x + rand ( - 1, 2 ), $pos1->y + rand ( 0, 3 ), $pos1->z + rand ( - 1, 2 ), $level );
			// $level->addParticle ( new DustParticle ( $pos, rand ( 1, 255 ), rand ( 1, 255 ), rand ( 1, 255 ) ) );
			// $level->addParticle ( new DustParticle ( $pos, rand ( 1, 255 ), rand ( 1, 255 ), rand ( 1, 255 ) ) );
			// $level->addParticle ( new DustParticle ( $pos, rand ( 1, 255 ), rand ( 1, 255 ), rand ( 1, 255 ) ) );
			// $level->addParticle ( new DustParticle ( $pos, rand ( 1, 255 ), rand ( 1, 255 ), rand ( 1, 255 ) ) );
			// $level->addParticle ( new DustParticle ( $pos, rand ( 1, 255 ), rand ( 1, 255 ), rand ( 1, 255 ) ) );
			// $level->addParticle ( new DustParticle ( $pos, rand ( 1, 255 ), rand ( 1, 255 ), rand ( 1, 255 ) ) );
			// }
			MagicUtil::addParticles($level, "lava", $pos1, 100);
		}
	}
	
	public static function makeExplosion(Level $level, Position $pos) {
		$explosion = new Explosion ( $pos, 1 );
		$explosion->explodeB();
		self::addParticles ( $level, "driplava", $pos, 80 );		
	}
	
	public static function openChest(Block $chest, Player $player) {
		if ($chest->getId () == Item::CHEST) {
			if ($player->gamemode == Player::CREATIVE) {
				$player->sendMessage ( "[HG] You are in creative Mode" );
				return;
			}
			if (! $player->isOp ()) {
				$pk = new ContainerOpenPacket ();
				$pk->windowid = $player->getWindowId ( $player->getInventory () );
				$pk->type = InventoryType::CHEST;
				$pk->slots = $player->getInventory ()->getSize ();
				$pk->x = $chest->x;
				$pk->y = $chest->y;
				$pk->z = $chest->z;
				$player->dataPacket ( $pk->setChannel ( Network::CHANNEL_WORLD_EVENTS ) );
				$player->getInventory ()->sendContents ( $player );
			}
		}
	}
	public static function blockPlace(Block $block, Position $pos, Player $player) {
		if (! $player->isOp ()) {
			$player->getLevel ()->setBlock ( $pos, $block, true, true );
		}
	}
	public static function blockBreak(Block $block, Position $pos, Player $player) {
		if (! $player->isOp ()) {
			$player->getLevel ()->setBlock ( $pos, (new Item ( Item::AIR ))->getBlock (), true, true );
		}
	}
	
	/**
	 *
	 * @param Player $player        	
	 * @param Position $fromPos        	
	 */
	public static function shootArrow(Player $player, Position $fromPos) {
		$nbt = new Compound ( "", [ 
				"Pos" => new Enum ( "Pos", [ 
						new Double ( "", $fromPos->x ),
						new Double ( "", $fromPos->y + $player->getEyeHeight () ),
						new Double ( "", $fromPos->z ) 
				] ),
				"Motion" => new Enum ( "Motion", [ 
						new Double ( "", - \sin ( $player->yaw / 180 * M_PI ) *\cos ( $player->pitch / 180 * M_PI ) ),
						new Double ( "", - \sin ( $player->pitch / 180 * M_PI ) ),
						new Double ( "",\cos ( $player->yaw / 180 * M_PI ) *\cos ( $player->pitch / 180 * M_PI ) ) 
				] ),
				"Rotation" => new Enum ( "Rotation", [ 
						new Float ( "", $player->yaw ),
						new Float ( "", $player->pitch ) 
				] ) 
		] );
		
		$diff = 3;
		$p = $diff / 20;
		$f =\min ( (($p * 2) + $p * 2) / 3, 1 ) * 2;
		$ev = new EntityShootBowEvent ( $player, Item::get ( Item::BOW ), Entity::createEntity ( "Arrow", $player->chunk, $nbt, $player, $f == 2 ?\true :\false ), $f );
		
		$ev->getProjectile ()->spawnToAll ();
		$player->level->addSound ( new LaunchSound ( $player ), $player->getViewers () );
	}

    /**
     * /effect <player> <effect> [seconds] [amplifier] [hideParticles]
     *
     * @param Player $player
     * @param        $effectType
     * @param int    $duration
     * @param int    $amplification
     * @param bool   $hiderParticles
     * @return true
     */
	public static function addEffect(Player $player, $effectType, $duration = 300, $amplification = 0, $hiderParticles = false) {
		$effect = Effect::getEffectByName ( $effectType );
		if ($effect === null) {
			$effect = Effect::getEffect ( ( int ) $effectType );
		}
		// $duration = 300;
		$amplification = 0;
		if ($hiderParticles) {
			$effect->setVisible ( \false );
		}
		$effect->setDuration ( $duration )->setAmplifier ( $amplification );
		$player->addEffect ( $effect );
		return $effect;
	}
	public static function addParticles(Level $level, $name, Position $pos1, $count = 5) {
		
		// $pos = new Vector3((float) $args[1], (float) $args[2], (float) $args[3]);
		$xd = ( float ) 380;
		$yd = ( float ) 360;
		$zd = ( float ) 380;
		// $count = 8;
		// $data = isset($args[8]) ? (int) $args[8] : \null;
		// $pos = new Vector3((float) $args[1], (float) $args[2], (float) $args[3]);
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
	public static function generateRandomEffects() {
		$effectId = 19;
		switch (rand ( 1, 18 )) {
			case 1 :
				$effectId = Effect::POISON;
				break;
			case 2 :
				$effectId = Effect::CONFUSION;
				break;
			case 3 :
				$effectId = Effect::DAMAGE_RESISTANCE;
				break;
			case 4 :
				$effectId = Effect::FATIGUE;
				break;
			case 5 :
				$effectId = Effect::FIRE_RESISTANCE;
				break;
			case 6 :
				$effectId = Effect::HEALTH_BOOST;
				break;
			case 7 :
				$effectId = Effect::INVISIBILITY;
				break;
			case 8 :
				$effectId = Effect::JUMP;
				break;
			case 9 :
				$effectId = Effect::NAUSEA;
				break;
			case 10 :
				$effectId = Effect::SLOWNESS;
				break;
			case 11 :
				$effectId = Effect::SPEED;
				break;
			case 12 :
				$effectId = Effect::STRENGTH;
				break;
			case 13 :
				$effectId = Effect::SWIFTNESS;
				break;
			case 14 :
				$effectId = Effect::WEAKNESS;
				break;
			case 15 :
				$effectId = Effect::WITHER;
				break;
			case 16 :
				$effectId = Effect::DAMAGE_RESISTANCE;
				break;
			case 17 :
				$effectId = Effect::POISON;
				break;
			case 18 :
				$effectId = Effect::POISON;
				break;
			default :
				$effectId = Effect::POISON;
				break;
		}
		
		return $effectId;
	}
	
	/**
	 *
	 * find matching particles for an given effect
	 *
	 * @param unknown $effectId        	
	 * @return Ambigous <NULL, string>
	 */
	public static function matchEffectParticles($effectId) {
		$particle = null;
		switch ($effectId) {
			case Effect::POISON :
				$particle = "explode";
				break;
			case Effect::CONFUSION :
				$particle = "bubble";
				break;
			case Effect::DAMAGE_RESISTANCE :
				$particle = "portal";
				break;
			case Effect::FATIGUE :
				$particle = "bubble";
				break;
			case Effect::FIRE_RESISTANCE :
				$particle = "flame";
				break;
			case Effect::HEALTH_BOOST :
				$particle = "heart";
				break;
			case Effect::JUMP :
				$particle = Effect::JUMP;
				break;
			case Effect::NAUSEA :
				$particle = "explode";
				break;
			case Effect::SLOWNESS :
				$particle = "bubble";
				break;
			case Effect::SPEED :
				$particle = "reddust";
				break;
			case Effect::STRENGTH :
				$particle = "reddust";
				break;
			case Effect::SWIFTNESS :
				$particle = "reddust";
				break;
			case Effect::WEAKNESS :
				$particle = "explode";
				break;
			case Effect::WITHER :
				$particle = "portal";
				break;
			default :
		}
		
		return $particle;
	}
}
