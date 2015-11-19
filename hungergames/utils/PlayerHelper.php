<?php

namespace hungergames\utils;

use hungergames\main\HungerGameKit;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\inventory\InventoryType;
use pocketmine\item\Item;
use pocketmine\level\Position;
use pocketmine\level\sound\LaunchSound;
use pocketmine\nbt\tag\Compound;
use pocketmine\nbt\tag\Double;
use pocketmine\nbt\tag\Enum;
use pocketmine\nbt\tag\Float;
use pocketmine\network\Network;
use pocketmine\network\protocol\ContainerOpenPacket;
use pocketmine\network\protocol\ContainerSetContentPacket;
use pocketmine\Player;

class PlayerHelper {
	public static function openChest(Block $chest, Player $player) {
		if ($chest->getId () === Item::CHEST) {
			if ($player->gamemode === Player::CREATIVE) {
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
				$pk->encode ();
				$player->directDataPacket ( $pk->setChannel ( Network::CHANNEL_WORLD_EVENTS ) );
				if ($player->getInventory () != null) {
					$player->getInventory ()->sendContents ( $player );
				}
				$player->sendTip ( "chest open!" );
			}
		}
	}
	public static function fillUpChest(Block $chest, Player $player) {
		if ($chest->getId () == Item::CHEST || $chest->getId ()) {
			if ($player->gamemode == Player::CREATIVE) {
				$player->sendMessage ( "[HG] You are in creative Mode" );
				return;
			}
			if (! $player->isOp ()) {
				$pk = new ContainerSetContentPacket ();
				$pk->windowid = $player->getWindowId ( $player->getInventory () ); // ContainerSetContentPacket::SPECIAL_INVENTORY;
				for($i = 1; $i < 5; $i ++) {
					$pk->slots [] = HungerGameKit::randomItems ();
				}
				$player->dataPacket ( $pk->setChannel ( Network::CHANNEL_WORLD_EVENTS ) );
				if ($player->getInventory () != null) {
					$player->getInventory ()->sendContents ( $player );
				}
				// $player->sendMessage ( "[HG] openning chest" );
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
}
