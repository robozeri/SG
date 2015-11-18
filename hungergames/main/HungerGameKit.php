<?php

namespace hungergames\main;

use pocketmine\block\Block;
use pocketmine\inventory\ChestInventory;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3 as Vector3;
use pocketmine\Player;
use pocketmine\tile\Tile;
use pocketmine\tile\Chest;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;


class HungerGameKit {
	const DIR_KITS = "kits_data/";
	// kit types
	const KIT_DIAMOND_ARMOR = "diamond_kit";
	const KIT_GOLD_ARMOR = "gold_kit";
	const KIT_IRON_ARMOR = "iron_kit";
	const KIT_LEATHER_ARMOR = "leather_kit";
	const KIT_CHAIN_ARMOR = "chain_kit";
	const KIT_FREE_NO_ARMOR = "free_kit";
	const KIT_UNKNOWN = "Unknown";
	private $kits = [ ];
	protected $plugin;
	public function __construct(HungerGamesPlugIn $plugin) {
		$this->plugin = $plugin;
	}
	
	/**
	 * KITS INVENTORY MANAGEMENT APIs
	 */
	public function initlize() {
		$path = $this->plugin->getDataFolder () . self::DIR_KITS;
		if (! file_exists ( $path )) {
			@mkdir ( $path, 0755, true );
			foreach ( $this->plugin->getResources () as $resource ) {
				if (! $resource->isDir ()) {
					$fp = $resource->getPathname ();
					if (strpos ( $fp, "kits_data" ) != false) {
						$this->plugin->info ( TextFormat::AQUA . " *** setup default [KITS Data]: " . $resource->getFilename () );
						copy ( $resource->getPathname (), $path . $resource->getFilename () );
					}
				}
			}
		}
		
		$this->kits = array (
				self::KIT_GOLD_ARMOR => self::KIT_GOLD_ARMOR,
				self::KIT_IRON_ARMOR => self::KIT_IRON_ARMOR,
				self::KIT_DIAMOND_ARMOR => self::KIT_DIAMOND_ARMOR,
				self::KIT_LEATHER_ARMOR => self::KIT_LEATHER_ARMOR,
				self::KIT_CHAIN_ARMOR => self::KIT_CHAIN_ARMOR 
		);
	}
	
	/**
	 * wear game kits
	 *
	 * @param Player $p        	
	 * @param unknown $kitType        	
	 */
	public function putOnGameKit(Player $p, $kitType) {
		switch ($kitType) {
			case self::KIT_GOLD_ARMOR :
				$this->loadKit ( self::KIT_GOLD_ARMOR, $p );
				break;
			case self::KIT_IRON_ARMOR :
				$this->loadKit ( self::KIT_IRON_ARMOR, $p );
				break;
			case self::KIT_DIAMOND_ARMOR :
				$this->loadKit ( self::KIT_DIAMOND_ARMOR, $p );
				break;
			case self::KIT_LEATHER_ARMOR :
				$this->loadKit ( self::KIT_LEATHER_ARMOR, $p );
				break;
			case self::KIT_CHAIN_ARMOR :
				$this->loadKit ( self::KIT_CHAIN_ARMOR, $p );
				break;
			case self::KIT_FREE_NO_ARMOR :
				$this->loadKit ( self::KIT_FREE_NO_ARMOR, $p );
				break;
			default :
				$this->loadKit ( self::KIT_UNKNOWN, $p );
		}
	}
	
	/**
	 * Load Game Kit
	 *
	 * @param unknown $teamkitName        	
	 * @param Player $p        	
	 */
	public function loadKit($teamkitName, Player $p) {
		$teamKit = $this->getKit ( $this->plugin->getDataFolder (), $teamkitName )->getAll ();
		if (! empty ( $p ) and ! empty ( $p->getInventory () )) {
			$p->getInventory ()->clearAll ();
			if ($teamKit ["armors"] ["helmet"] [0] != null) {
				$p->getInventory ()->setHelmet ( new Item ( $teamKit ["armors"] ["helmet"] [0], $teamKit ["armors"] ["helmet"] [1], $teamKit ["armors"] ["helmet"] [2] ) );
			}
			if ($teamKit ["armors"] ["chestplate"] [0] != null) {
				$p->getInventory ()->setChestplate ( new Item ( $teamKit ["armors"] ["chestplate"] [0], $teamKit ["armors"] ["chestplate"] [1], $teamKit ["armors"] ["chestplate"] [2] ) );
			}
			if ($teamKit ["armors"] ["leggings"] [0] != null) {
				$p->getInventory ()->setLeggings ( new Item ( $teamKit ["armors"] ["leggings"] [0], $teamKit ["armors"] ["leggings"] [1], $teamKit ["armors"] ["leggings"] [2] ) );
			}
			if ($teamKit ["armors"] ["boots"] [0] != null) {
				$p->getInventory ()->setBoots ( new Item ( $teamKit ["armors"] ["boots"] [0], $teamKit ["armors"] ["boots"] [1], $teamKit ["armors"] ["boots"] [2] ) );
			}
			$p->getInventory ()->sendArmorContents ( $p );
			$weapons = $teamKit ["weapons"];
			foreach ( $weapons as $w ) {
				$item = new Item ( $w [0], $w [1], $w [2] );
				$p->getInventory ()->addItem ( $item );
			}
			$foods = $teamKit ["foods"];
			foreach ( $foods as $w ) {
				$item = new Item ( $w [0], $w [1], $w [2] );
				$p->getInventory ()->addItem ( $item );
			}
			$p->getInventory ()->setHeldItemIndex ( 0 );
			$p->getInventory ()->sendArmorContents ( $p->getInventory ()->getViewers () );
		}
	}
	
	/**
	 * Get Game Kit By Name
	 *
	 * @param unknown $kitName        	
	 * @return \pocketmine\utils\Config
	 */
	public static function getKit($pluginDataFolder, $kitName) {
		if (! (file_exists ( $pluginDataFolder . self::DIR_KITS . strtolower ( $kitName ) . ".yml" ))) {
			@mkdir ( $pluginDataFolder . self::DIR_KITS, 0777, true );
			if ($kitName == self::KIT_GOLD_ARMOR) {
				return new Config ( $pluginDataFolder . self::DIR_KITS . strtolower ( self::KIT_GOLD_ARMOR ) . ".yml", Config::YAML, array (
						"kitName" => self::KIT_GOLD_ARMOR,
						"isDefault" => false,
						"price" => 80,
						"quantity" => 1,
						"health" => 20,
						"itemOnHand" => 283,
						"armors" => array (
								"helmet" => array (
										Item::GOLD_HELMET,
										"0",
										"1" 
								),
								"chestplate" => array (
										Item::GOLD_CHESTPLATE,
										"0",
										"1" 
								),
								"leggings" => array (
										Item::GOLD_LEGGINGS,
										"0",
										"1" 
								),
								"boots" => array (
										Item::GOLD_BOOTS,
										"0",
										"1" 
								) 
						),
						"weapons" => array (
								Item::GOLD_SHOVEL => array (
										Item::GOLD_SHOVEL,
										"0",
										"1" 
								),
								item::SNOWBALL => array (
										item::SNOWBALL,
										"0",
										"64" 
								) 
						),
						"foods" => array (
								Item::APPLE => array (
										Item::APPLE,
										"0",
										"2" 
								),
								Item::CARROT => array (
										Item::CARROT,
										"0",
										"2" 
								) 
						) 
				) );
			} elseif ($kitName == self::KIT_IRON_ARMOR) {
				return new Config ( $pluginDataFolder . self::DIR_KITS . strtolower ( $kitName ) . ".yml", Config::YAML, array (
						"kitName" => self::KIT_IRON_ARMOR,
						"isDefault" => false,
						"price" => 20,
						"quantity" => 1,
						"health" => 20,
						"itemOnHand" => 267,
						"armors" => array (
								"helmet" => array (
										Item::IRON_HELMET,
										"0",
										"1" 
								),
								"chestplate" => array (
										Item::IRON_CHESTPLATE,
										"0",
										"1" 
								),
								"leggings" => array (
										Item::IRON_LEGGINGS,
										"0",
										"1" 
								),
								"boots" => array (
										Item::IRON_BOOTS,
										"0",
										"1" 
								) 
						),
						"weapons" => array (
								Item::IRON_SHOVEL => array (
										Item::IRON_SHOVEL,
										"0",
										"1" 
								),
								item::SNOWBALL => array (
										item::SNOWBALL,
										"0",
										"64" 
								) 
						),
						"foods" => array (
								item::COOKED_BEEF => array (
										item::COOKED_BEEF,
										"0",
										"2" 
								),
								Item::COOKED_CHICKEN => array (
										Item::COOKED_CHICKEN,
										"0",
										"2" 
								) 
						) 
				) );
			} elseif ($kitName == self::KIT_CHAIN_ARMOR) {
				return new Config ( $pluginDataFolder . self::DIR_KITS . strtolower ( $kitName ) . ".yml", Config::YAML, array (
						"kitName" => self::KIT_CHAIN_ARMOR,
						"isDefault" => false,
						"price" => 50,
						"quantity" => 1,
						"health" => 20,
						"itemOnHand" => 267,
						"armors" => array (
								"helmet" => array (
										Item::CHAIN_HELMET,
										"0",
										"1" 
								),
								"chestplate" => array (
										Item::CHAIN_CHESTPLATE,
										"0",
										"1" 
								),
								"leggings" => array (
										Item::CHAIN_LEGGINGS,
										"0",
										"1" 
								),
								"boots" => array (
										Item::CHAIN_BOOTS,
										"0",
										"1" 
								) 
						),
						"weapons" => array (
								Item::IRON_SHOVEL => array (
										Item::IRON_SHOVEL,
										"0",
										"1" 
								),
								item::SNOWBALL => array (
										item::SNOWBALL,
										"0",
										"64" 
								) 
						),
						"foods" => array (
								item::COOKED_PORKCHOP => array (
										item::COOKED_PORKCHOP,
										"0",
										"2" 
								),
								Item::COOKED_CHICKEN => array (
										Item::COOKED_CHICKEN,
										"0",
										"2" 
								) 
						) 
				) );
			} elseif ($kitName == self::KIT_DIAMOND_ARMOR) {
				return new Config ( $pluginDataFolder . self::DIR_KITS . strtolower ( $kitName ) . ".yml", Config::YAML, array (
						"kitName" => self::KIT_DIAMOND_ARMOR,
						"isDefault" => false,
						"price" => 100,
						"quantity" => 1,
						"health" => 20,
						"itemOnHand" => 276,
						"armors" => array (
								"helmet" => array (
										Item::DIAMOND_HELMET,
										"0",
										"1" 
								),
								"chestplate" => array (
										Item::DIAMOND_CHESTPLATE,
										"0",
										"1" 
								),
								"leggings" => array (
										Item::DIAMOND_LEGGINGS,
										"0",
										"1" 
								),
								"boots" => array (
										Item::DIAMOND_BOOTS,
										"0",
										"1" 
								) 
						),
						"weapons" => array (
								Item::DIAMOND_SHOVEL => array (
										Item::DIAMOND_SHOVEL,
										"0",
										"1" 
								),
								item::SNOWBALL => array (
										item::SNOWBALL,
										"0",
										"64" 
								) 
						),
						"foods" => array (
								item::APPLE => array (
										item::APPLE,
										"0",
										"2" 
								),
								Item::CAKE => array (
										Item::CAKE,
										"0",
										"2" 
								) 
						) 
				) );
			} elseif ($kitName == self::KIT_LEATHER_ARMOR) {
				return new Config ( $pluginDataFolder . self::DIR_KITS . strtolower ( $kitName ) . ".yml", Config::YAML, array (
						"kitName" => self::KIT_CHAIN_ARMOR,
						"isDefault" => false,
						"price" => 20,
						"quantity" => 1,
						"health" => 20,
						"itemOnHand" => 276,
						"armors" => array (
								"helmet" => array (
										Item::LEATHER_CAP,
										"0",
										"1" 
								),
								"chestplate" => array (
										Item::LEATHER_TUNIC,
										"0",
										"1" 
								),
								"leggings" => array (
										Item::LEATHER_PANTS,
										"0",
										"1" 
								),
								"boots" => array (
										Item::LEATHER_BOOTS,
										"0",
										"1" 
								) 
						),
						"weapons" => array (
								Item::IRON_SHOVEL => array (
										Item::IRON_SHOVEL,
										"0",
										"1" 
								),
								item::SNOWBALL => array (
										item::SNOWBALL,
										"0",
										"64" 
								) 
						),
						"foods" => array (
								item::COOKED_PORKCHOP => array (
										item::COOKED_PORKCHOP,
										"0",
										"2" 
								),
								Item::COOKED_CHICKEN => array (
										Item::COOKED_CHICKEN,
										"0",
										"2" 
								) 
						) 
				) );
			} elseif ($kitName == self::KIT_FREE_NO_ARMOR) {
				return new Config ( $pluginDataFolder . self::DIR_KITS . strtolower ( $kitName ) . ".yml", Config::YAML, array (
						"kitName" => self::KIT_FREE_NO_ARMOR,
						"isDefault" => false,
						"price" => 0,
						"quantity" => 1,
						"health" => 20,
						"itemOnHand" => 268,
						"armors" => array (
								"helmet" => array (
										Item::AIR,
										"0",
										"0" 
								),
								"chestplate" => array (
										Item::AIR,
										"0",
										"0" 
								),
								"leggings" => array (
										Item::AIR,
										"0",
										"0" 
								),
								"boots" => array (
										Item::AIR,
										"0",
										"0" 
								) 
						),
						"weapons" => array (
								Item::WOODEN_SHOVEL => array (
										Item::WOODEN_SHOVEL,
										"0",
										"1" 
								),
								item::SNOWBALL => array (
										item::SNOWBALL,
										"0",
										"64" 
								) 
						),
						"foods" => array (
								item::COOKED_PORKCHOP => array (
										item::COOKED_PORKCHOP,
										"0",
										"2" 
								),
								Item::COOKED_CHICKEN => array (
										Item::COOKED_CHICKEN,
										"0",
										"2" 
								) 
						) 
				) );
			}
		} else {
			return new Config ( $pluginDataFolder . self::DIR_KITS . strtolower ( $kitName ) . ".yml", Config::YAML, array () );
		}
	}
	
	/**
	 * CHEST INVENTORY MANAGEMENT APIs
	 */
	final public static function fillRandomChestItems(Player &$player, $block) {
		$level = $block->level;
		if ($level != null && $block != null) {
			$tile = $level->getTile ( new Vector3 ( $block->x, $block->y, $block->z ) );
			if ($tile != null and $tile instanceof Chest) {
				$inv = $tile->getRealInventory ();
				if ($inv instanceof ChestInventory) {
					$inv->clearAll ();
					$inv->setItem ( 1, HungerGameKit::randomItems () );
					$inv->setItem ( 2, HungerGameKit::randomItems () );
					$inv->setItem ( 3, HungerGameKit::randomItems () );
					$inv->setItem ( 4, HungerGameKit::randomItems () );
					$inv->setItem ( 5, HungerGameKit::randomItems () );
					$inv->setItem ( 6, HungerGameKit::randomItems () );
					$inv->setItem ( 7, HungerGameKit::randomItems () );
					$inv->setItem ( 8, HungerGameKit::randomItems () );
					$inv->setItem ( 9, HungerGameKit::randomItems () );
					$inv->setItem ( 11, HungerGameKit::randomItems () );
					$inv->setItem ( 12, HungerGameKit::randomItems () );
					$inv->setItem ( 13, HungerGameKit::randomItems () );
					$inv->setItem ( 14, HungerGameKit::randomItems () );
					$inv->setItem ( 15, HungerGameKit::randomItems () );
					$inv->onOpen ( $player );
					$player->getInventory ()->sendContents ( $player );
					$player->getInventory ()->sendContents ( $player->getViewers () );
				}
			}
		}
	}
	final public static function fillRandomChestItemIncludingTnT(Player &$player, $block) {
		$level = $block->level;
		if ($level != null && $block != null) {
			$tile = $level->getTile ( new Vector3 ( $block->x, $block->y, $block->z ) );
			// echo "chest block ".$block->getId();
			if ($tile != null and $tile instanceof Chest) {
				$inv = $tile->getRealInventory ();
				if ($inv instanceof ChestInventory) {
					$inv->clearAll ();
					$inv->setItem ( 1, HungerGameKit::randomItems () );
					$inv->setItem ( 2, HungerGameKit::randomItems () );
					$inv->setItem ( 3, HungerGameKit::randomItems () );
					$inv->setItem ( 4, HungerGameKit::randomItems () );
					$inv->setItem ( 5, HungerGameKit::randomItems () );
					$inv->setItem ( 6, HungerGameKit::randomItems () );
					$inv->setItem ( 7, HungerGameKit::randomItems () );
					$inv->setItem ( 8, HungerGameKit::randomItems () );
					$inv->setItem ( 9, HungerGameKit::randomItems () );
					$inv->setItem ( 10, HungerGameKit::randomItems () );
					$inv->setItem ( 12, HungerGameKit::randomItems () );
					$inv->setItem ( 13, HungerGameKit::randomItems () );
					$inv->setItem ( 14, HungerGameKit::randomItems () );
					$inv->setItem ( 15, HungerGameKit::randomItems () );
					if ((rand ( 1, 5 )) > 2) {
						$inv->setItem ( 11, new Item ( Item::TNT, 0, mt_rand ( 1, 5 ) ) );
						$inv->setItem ( 12, new Item ( Item::FLINT_AND_STEEL, 0, mt_rand ( 1, 2 ) ) );
					}
					$inv->onOpen ( $player );
					// echo "\n".$level->getName () . "chest: refill chest\n";
					$player->getInventory ()->sendContents ( $player );
					$player->getInventory ()->sendContents ( $player->getViewers () );
				}
			}
		}
	}
	final public static function fillMapChestRandomChestItems(Tile $tile) {
		if (! is_null ( $tile ) and $tile instanceof Chest) {
			$inv = $tile->getRealInventory ();
			if ($inv instanceof ChestInventory) {
				$inv->clearAll ();
				$inv->setItem ( 2, HungerGameKit::randomItems () );
				$inv->setItem ( 4, HungerGameKit::randomItems () );
				$inv->setItem ( 7, HungerGameKit::randomItems () );
				$inv->setItem ( 8, HungerGameKit::randomItems () );
				$inv->setItem ( 9, HungerGameKit::randomItems () );
				$inv->setItem ( 11, HungerGameKit::randomItems () );
			}
		}
	}
	public static function clearChestItems(&$block) {
		$level = $block->level;
		if ($level != null && $block != null) {
			$tile = $level->getTile ( new Vector3 ( $block->x, $block->y, $block->z ) );
			if ($tile != null and $tile instanceof Tile) {
				$inv = $tile->getRealInventory ();
				if ($inv instanceof ChestInventory) {
					$inv->clearAll ();
				}
			}
		}
	}
	
	/**
	 * random
	 *
	 * @return \pocketmine\item\Item
	 */
	public static function randomItems() {
		$i = rand ( 0, 160 );
		if ($i == 0) {
			return new Item ( Item::BOW, 0, rand ( 1, 2 ) );
		}
		if ($i == 20) {
			return new Item ( Item::BOW, 0, rand ( 1, 2 ) );
		}
		if ($i == 1) {
			return new Item ( Item::ARROW, 0, rand ( 1, 12 ) );
		}
		if ($i == 2) {
			return new Item ( Item::APPLE, 0, 3 );
		}
		if ($i == 3) {
			return new Item ( Item::BREAD, 0, 3 );
		}
		if ($i == 4) {
			return new Item ( Item::ARROW, 0, 12 );
		}
		if ($i == 5) {
			return new Item ( Item::DIAMOND_CHESTPLATE, 0, 1 );
		}
		if ($i == 6) {
			return new Item ( Item::DIAMOND_BOOTS, 0, 1 );
		}
		if ($i == 7) {
			return new Item ( Item::DIAMOND_LEGGINGS, 0, 1 );
		}
		if ($i == 8) {
			return new Item ( Item::DIAMOND_HELMET, 0, 1 );
		}
		if ($i == 9) {
			return new Item ( Item::DIAMOND_SWORD, 0, 1 );
		}
		if ($i == 10) {
			return new Item ( Item::DIAMOND_AXE, 0, 1 );
		}
		if ($i == 11) {
			return new Item ( Item::DIAMOND_PICKAXE, 0, 1 );
		}
		if ($i == 12) {
			return new Item ( Item::IRON_BOOTS, 0, 1 );
		}
		if ($i == 13) {
			return new Item ( Item::IRON_CHESTPLATE, 0, 1 );
		}
		if ($i == 14) {
			return new Item ( Item::IRON_HELMET, 0, 1 );
		}
		if ($i == 15) {
			return new Item ( Item::IRON_LEGGINGS, 0, 1 );
		}
		if ($i == 16) {
			return new Item ( Item::IRON_SWORD, 0, 1 );
		}
		if ($i == 17) {
			return new Item ( Item::IRON_PICKAXE, 0, 1 );
		}
		if ($i == 18) {
			return new Item ( Item::IRON_SHOVEL, 0, 1 );
		}
		if ($i == 19) {
			return new Item ( Item::APPLE, 0, 5 );
		}
		if ($i == 20) {
			return new Item ( Item::CARROT, 0, 3 );
		}
		if ($i == 21) {
			return new Item ( Item::CAKE, 0, 2 );
		}
		if ($i == 22) {
			return new Item ( Item::APPLE, 0, 2 );
		}
		if ($i == 23) {
			return new Item ( Item::COOKED_BEEF, 0, 5 );
		}
		if ($i == 24) {
			return new Item ( Item::COOKED_CHICKEN, 0, 5 );
		}
		if ($i == 25) {
			return new Item ( Item::COOKED_PORKCHOP, 0, 5 );
		}
		if ($i == 26) {
			return new Item ( Item::BOW, 0, 1 );
		}
		if ($i == 27) {
			return new Item ( Item::APPLE, 0, 5 );
		}
		if ($i == 28) {
			return new Item ( Item::APPLE, 0, 5 );
		}
		if ($i == 29) {
			return new Item ( Item::BED, 0, 1 );
		}
		if ($i == 30) {
			return new Item ( Item::APPLE, 0, 4 );
		}
		if ($i == 31) {
			return new Item ( Item::ARROW, 0, rand ( 32, 64 ) );
		}
		if ($i == 32) {
			return new Item ( Item::BOW, 0, 1 );
		}
		if ($i == 33) {
			return new Item ( Item::IRON_AXE, 0, 1 );
		}
		if ($i == 34) {
			return new Item ( Item::IRON_SWORD, 0, 1 );
		}
		if ($i == 35) {
			return new Item ( Item::IRON_HOE, 0, 1 );
		}
		if ($i == 36) {
			return new Item ( Item::WOODEN_PICKAXE, 0, 1 );
		}
		if ($i == 37) {
			return new Item ( Item::WOODEN_SWORD, 0, 1 );
		}
		if ($i == 38) {
			return new Item ( Item::WOODEN_HOE, 0, 1 );
		}
		if ($i == 39) {
			return new Item ( Item::GOLD_AXE, 0, 1 );
		}
		if ($i == 40) {
			return new Item ( Item::GOLD_SWORD, 0, 1 );
		}
		if ($i == 41) {
			return new Item ( Item::GOLD_PICKAXE, 0, 1 );
		}
		
		if ($i == 42) {
			return new Item ( Item::DIAMOND_AXE, 0, 1 );
		}
		if ($i == 43) {
			return new Item ( Item::DIAMOND_SWORD, 0, 1 );
		}
		if ($i == 44) {
			return new Item ( Item::DIAMOND_PICKAXE, 0, 1 );
		}
		if ($i == 45) {
			return new Item ( Item::MELON, 0, 3 );
		}
		
		if ($i == 45) {
			return new Item ( Item::LEATHER_PANTS, 0, 1 );
		}
		if ($i == 46) {
			return new Item ( Item::LEATHER_CAP, 0, 1 );
		}
		if ($i == 47) {
			return new Item ( Item::LEATHER_BOOTS, 0, 1 );
		}
		if ($i == 48) {
			return new Item ( Item::LEATHER_TUNIC, 0, 1 );
		}
		
		if ($i == 49) {
			return new Item ( Item::WOODEN_SWORD, 0, 1 );
		}
		if ($i == 50) {
			return new Item ( Item::WOODEN_AXE, 0, 1 );
		}
		if ($i == 51) {
			return new Item ( Item::WOODEN_HOE, 0, 1 );
		}
		
		if ($i == 52) {
			return new Item ( Item::RAW_FISH, 0, rand ( 1, 5 ) );
		}
		if ($i == 53) {
			return new Item ( Item::RAW_PORKCHOP, 0, rand ( 1, 5 ) );
		}
		if ($i == 54) {
			return new Item ( Item::RAW_CHICKEN, 0, rand ( 1, 4 ) );
		}
		
		if ($i == 55) {
			return new Item ( Item::RAW_BEEF, 0, rand ( 2, 4 ) );
		}
		
		if ($i == 56) {
			return new Item ( Item::EMERALD, 0, 2 );
		}
		
		if ($i == 57) {
			return new Item ( Item::IRON_BAR, 0, rand ( 1, 5 ) );
		}
		
		if ($i == 58) {
			return new Item ( Item::BOW, 0, 1 );
		}
		
		if ($i == 59) {
			return new Item ( Item::DIAMOND_ORE, 0, 12 );
		}
		if ($i == 60) {
			return new Item ( Item::ARROW, 0, 6 );
		}
		if ($i == 61) {
			return new Item ( Item::BONE, 0, rand ( 0, 2 ) );
		}
		if ($i == 62) {
			return new Item ( Item::BOW, 0, 2 );
		}
		if ($i == 63) {
			return new Item ( Item::IRON_AXE, 0, 1 );
		}
		if ($i == 65) {
			return new Item ( Item::TORCH, 0, rand ( 3, 12 ) );
		}
		if ($i == 66) {
			return new Item ( Item::SLIMEBALL, 0, rand ( 1, 2 ) );
		}
		if ($i == 67) {
			return new Item ( Item::STRING, 0, rand ( 3, 12 ) );
		}
		if ($i == 68) {
			return new Item ( Item::ROSE, 0, rand ( 3, 12 ) );
		}
		if ($i == 68) {
			return new Item ( Item::RED_MUSHROOM, 0, rand ( 3, 12 ) );
		}
		if ($i == 69) {
			return new Item ( Item::COMPASS, 0, rand ( 1, 2 ) );
		}
		if ($i == 70) {
			return new Item ( Item::LEATHER, 0, rand ( 1, 12 ) );
		}
		if ($i == 71) {
			return new Item ( Item::ICE, 0, rand ( 1, 12 ) );
		}
		if ($i == 72) {
			return new Item ( Item::BUCKET, 0, rand ( 1, 2 ) );
		}
		if ($i == 73) {
			return new Item ( Item::BOOK, 0, rand ( 1, 2 ) );
		}
		if ($i == 74) {
			return new Item ( Item::DIAMOND, 0, rand ( 1, 2 ) );
		}		
		return new Item ( Item::AIR );
	}
	
	public static function getRandomChestItems(Level $level, $block) {
		if ($level == null || $block == null) {
			throw new \InvalidArgumentException ( "level or block may not be null" );
		}
		$tile = $level->getTile ( new Vector3 ( $block->x, $block->y, $block->z ) );
		
		if ($tile != null and $tile instanceof Chest) {
			$inv = $tile->getRealInventory ();
			$inv->setItem ( 1, HungerGameKit::randomItems () );
			$inv->setItem ( 2, HungerGameKit::randomItems () );
			$inv->setItem ( 3, HungerGameKit::randomItems () );
			$inv->setItem ( 4, HungerGameKit::randomItems () );
			$inv->setItem ( 5, HungerGameKit::randomItems () );
		}
	}
	public static function XrandomItems() {
		$i = rand ( 0, 50 );
		if ($i == 0) {
			return new Item ( Item::BOW, 0, 2 );
		}
		if ($i == 20) {
			return new Item ( Item::BOW, 0, 2 );
		}
		if ($i == 1) {
			return new Item ( Item::ARROW, 0, 64 );
		}
		if ($i == 21) {
			return new Item ( Item::ARROW, 0, 64 );
		}
		if ($i == 2) {
			return new Item ( Item::APPLE, 0, 3 );
		}
		if ($i == 3) {
			return new Item ( Item::BREAD, 0, 3 );
		}
		if ($i == 4) {
			return new Item ( Item::BED, 0, 1 );
		}
		if ($i == 5) {
			return new Item ( Item::DIAMOND_CHESTPLATE, 0, 1 );
		}
		if ($i == 6) {
			return new Item ( Item::DIAMOND_BOOTS, 0, 1 );
		}
		if ($i == 7) {
			return new Item ( Item::DIAMOND_LEGGINGS, 0, 1 );
		}
		if ($i == 8) {
			return new Item ( Item::DIAMOND_HELMET, 0, 1 );
		}
		if ($i == 9) {
			return new Item ( Item::DIAMOND_SWORD, 0, 1 );
		}
		if ($i == 10) {
			return new Item ( Item::DIAMOND_AXE, 0, 1 );
		}
		if ($i == 11) {
			return new Item ( Item::DIAMOND_PICKAXE, 0, 1 );
		}
		if ($i == 12) {
			return new Item ( Item::IRON_BOOTS, 0, 1 );
		}
		if ($i == 13) {
			return new Item ( Item::IRON_CHESTPLATE, 0, 1 );
		}
		if ($i == 14) {
			return new Item ( Item::IRON_HELMET, 0, 1 );
		}
		if ($i == 15) {
			return new Item ( Item::IRON_LEGGINGS, 0, 1 );
		}
		if ($i == 16) {
			return new Item ( Item::IRON_SWORD, 0, 1 );
		}
		if ($i == 17) {
			return new Item ( Item::IRON_PICKAXE, 0, 1 );
		}
		if ($i == 18) {
			return new Item ( Item::BOW, 0, 2 );
		}
		if ($i == 19) {
			return new Item ( Item::ARROW, 0, 64 );
		}
		
		return new Item ( Item::AIR );
	}
	public static function getJoinServerKit(Player $p) {
		if (! $p->getInventory ()->contains ( new Item ( Item::SIGN ) )) {
			$p->getInventory ()->addItem ( new Item ( Item::SIGN, 0, 3 ) );
		}
	}
	public static function addBreakBlock(Player $p, $b) {
		if ($b->getId () == Block::STONE) {
			$p->getInventory ()->addItem ( new Item ( Item::STONE, 0, 1 ) );
		}
		if ($b->getId () == Block::COBBLESTONE) {
			$p->getInventory ()->addItem ( new Item ( Item::COBBLESTONE, 0, 1 ) );
		}
	}
	public static function removePlayerChest(Player $bp) {
		if ($bp != null) {
			$bp->getInventory ()->setBoots ( new Item ( 0 ) );
			$bp->getInventory ()->setChestplate ( new Item ( 0 ) );
			$bp->getInventory ()->setHelmet ( new Item ( 0 ) );
			$bp->getInventory ()->setLeggings ( new Item ( 0 ) );
			// clear all items
			$bp->getInventory ()->remove ( new Item ( 272 ) );
			$bp->getInventory ()->remove ( new Item ( 261 ) );
			$bp->getInventory ()->remove ( new Item ( 262 ) );
			$bp->getInventory ()->remove ( new Item ( 260 ) );
			$bp->getInventory ()->remove ( new Item ( 366 ) );
			$bp->getInventory ()->remove ( new Item ( 320 ) );
			$bp->getInventory ()->remove ( new Item ( 54 ) );
			
			$bp->getInventory ()->setHeldItemIndex ( 0 );
			$bp->getInventory ()->sendArmorContents ( $bp );
			$bp->getInventory ()->sendContents ( $bp->getViewers () );
		}
	}
	public static function clearAllInventories(Player $player) {
		if ($player != null && ! $player->isOp ()) {
			if ($player->getGamemode () === Player::SURVIVAL) {
				if (! empty ( $player->getInventory () )) {
					$player->getInventory ()->setBoots ( new Item ( Item::AIR ) );
					$player->getInventory ()->setChestplate ( new Item ( Item::AIR ) );
					$player->getInventory ()->setHelmet ( new Item ( Item::AIR ) );
					$player->getInventory ()->setLeggings ( new Item ( Item::AIR ) );
					$player->getInventory ()->clearAll ();
					$player->getInventory ()->setItemInHand ( new Item ( Item::AIR ) );
					$player->getInventory ()->sendContents ( $player );
					if (!empty( $player->getViewers ())) {
						$player->getInventory ()->sendContents ( $player->getViewers () );
					}
				}
			}
		}
	}
	public static function giveBowArrowKit(Player $p) {
		if (! empty ( $p ) && ! empty ( $p->getInventory () )) {
			$p->setHealth ( 20 );
			if (! $p->getInventory ()->contains ( new Item ( Item::BOW ) )) {
				$p->getInventory ()->addItem ( new Item ( Item::BOW, 0, 1 ) );
			}
			if (! $p->getInventory ()->contains ( new Item ( Item::ARROW ) )) {
				$p->getInventory ()->addItem ( new Item ( Item::ARROW, 0, 32 ) );
			}
			$p->getInventory ()->setItemInHand ( new Item ( Item::BOW, 0, 1 ) );
			$p->getInventory ()->sendContents ( $p );
			//$p->getInventory ()->sendContents ( $p->getViewers () );
		}
	}
	public static function giveExplosiveKit(Player $p) {
		if (! empty ( $p ) && ! empty ( $p->getInventory () )) {
			$p->setHealth ( 20 );
			if (! $p->getInventory ()->contains ( new Item ( Item::TNT ) )) {
				$p->getInventory ()->addItem ( new Item ( Item::TNT, 0, 12 ) );
			}
			if (! $p->getInventory ()->contains ( new Item ( Item::FLINT_AND_STEEL ) )) {
				$p->getInventory ()->addItem ( new Item ( Item::FLINT_AND_STEEL, 0, 2 ) );
			}
			$p->getInventory ()->setItemInHand ( new Item ( Item::FLINT_AND_STEEL, 0, 1 ) );
			$p->getInventory ()->sendContents ( $p );
			$p->getInventory ()->sendContents ( $p->getViewers () );
		}
	}
}
