<?php

namespace hungergames\statue;

use pocketmine\utils\Config;


class StatueModel {
	const STATUE_TYPE_NPC = 'npc';
	const STATUE_TYPE_MOD = 'mod';
	const STATUS_CATEGORY_KIT = "kit";
	const STATUS_CATEGORY_MOD = "mod";
	public $eid;
	public $type;
	public $category = "mod";
	public $displayName;
	public $name;
	public $networkId;
	public $position;
	public $levelName;
	public $armorHelmet;
	public $armorChestplate;
	public $armorLegging;
	public $armorBoots;
	public $itemOnHand;
	public $kitName = "free_kit";
	public $kitBlock = 20;
	public $cost = 0;
	public $destinationPos;
	public $destinationLevelName;
	public $message;
	public $particles;
	public function __construct($name, $type, $networkId, $pos, $levelname, $itemOnHand = null, $armorHelmet = null, $armorChestplate = null, $armorLegging = null, $armorBoots = null) {
		$this->name = $name;
		$this->type = $type;
		$this->networkId = $networkId;
		$this->position = $pos;
		$this->levelName = $levelname;
		$this->itemOnHand = $itemOnHand;
		$this->armorHelmet = $armorHelmet;
		$this->armorChestplate = $armorChestplate;
		$this->armorLegging = $armorLegging;
		$this->armorBoots = $armorBoots;
	}
	public static function generateEID() {
		$eid = substr ( strval ( round ( microtime ( true ) + 1 ) ), 4 );
		return $eid;
	}
	public function save($path) {
		$name = $this->name;
		$data = new Config ( $path . "$name.yml", Config::YAML );
		$data->set ( "name", $name );
		$data->set ( "eid", $this->eid );
		$data->set ( "type", $this->type );
		$data->set ( "networkId", $this->networkId );
		$data->set ( "levelName", $this->levelName );
		$data->set ( "itemOnHand", $this->itemOnHand );
		$data->set ( "armorHelmet", $this->armorHelmet );
		$data->set ( "armorChestplate", $this->armorChestplate );
		$data->set ( "armorLegging", $this->armorLegging );
		$data->set ( "armorBoots", $this->armorBoots );
		if ($this->position != null && $this->position != false) {
			$data->set ( "positionX", round ( $this->position->x ) );
			$data->set ( "positionY", round ( $this->position->y ) );
			$data->set ( "positionZ", round ( $this->position->z ) );
		}
		$data->set ( "kitBlock", $this->kitBlock );
		$data->set ( "particles", $this->particles );
		$data->set ( "destinationLevelName", $this->destinationLevelName );
		if ($this->destinationPos != null && $this->destinationPos != false) {
			$data->set ( "destinationPosX", round ( $this->destinationPos->x ) );
			$data->set ( "destinationPosY", round ( $this->destinationPos->y ) );
			$data->set ( "destinationPosZ", round ( $this->destinationPos->z ) );
		}
		$data->set ( "cost", $this->cost );
		$data->set ( "message", $this->message );
		$data->set ( "category", $this->category );
		$data->save ();
	}
	public function delete($path) {
		$name = $this->name;
		@unlink ( $path . "$name.yml" );
	}
}
