<?php

namespace hungergames\utils;

use game\tntrun\itemcase\ItemCaseBuilder;
use pocketmine\network\Network;
use pocketmine\network\protocol\AddMobPacket;
use pocketmine\network\protocol\RotateHeadPacket;
use pocketmine\network\protocol\TextPacket;
use pocketmine\Player;
use pocketmine\Server;


class TextUtil {

    /**
     * scores = ["kills=>0, hits=>1"]
     *
     * @param Player        $player
     * @param array| $scores
     */
	public static function showPlayerPopUpScores(Player $player, $viewers,$scores = array()) {
		
		$message="SCOREs: ";
		foreach ($scores as $type=>$value) {
			$message.="|".$type.": ".$value." ";
		}
				
		$packet = new TextPacket ();
		$packet->type = TextPacket::TYPE_POPUP;
		$packet->message = $message;
		$packet->setChannel ( Network::CHANNEL_TEXT );
		$player->dataPacket ( $packet );
		if ($viewers != null) {
			$player->getServer ()->broadcastPacket ( $viewers, $packet );
		}
	}
	
	public static function sendPopUpText(Player $player, $message, $viewers = null) {
		$packet = new TextPacket ();
		$packet->type = TextPacket::TYPE_POPUP;
		$packet->message = $message;
		$packet->setChannel ( Network::CHANNEL_TEXT );
		$player->dataPacket ( $packet );
		if ($viewers != null) {
			$player->getServer ()->broadcastPacket ( $viewers, $packet );
		}
	}
	
	public static function broadcastPopUpText($message, $viewers = null) {
		$packet = new TextPacket ();
		$packet->type = TextPacket::TYPE_POPUP;
		$packet->message = $message;
		$packet->setChannel ( Network::CHANNEL_TEXT );
		//$player->dataPacket ( $packet );
		if ($viewers != null) {
			Server::getInstance()->broadcastPacket ( $viewers, $packet );
		}
	}
	
	public static function broadcastPopUpTips($message, $viewers = null) {
		$packet = new TextPacket ();
		$packet->type = TextPacket::TYPE_TIP;
		$packet->message = $message;
		$packet->setChannel ( Network::CHANNEL_TEXT );
		//$player->dataPacket ( $packet );
		if ($viewers != null) {
			Server::getInstance()->broadcastPacket ( $viewers, $packet );
		}
	}
	
	public static function sendTips(Player $player, $message, $viewers = null) {
		$packet = new TextPacket ();
		$packet->type = TextPacket::TYPE_TIP;
		$packet->message = $message;
		$packet->setChannel ( Network::CHANNEL_TEXT );
		$player->dataPacket ( $packet );
		if ($viewers != null) {
			$player->getServer ()->broadcastPacket ( $viewers, $packet );
		}
	}
	public static function sendChat(Player $player, $message, $viewers = null) {
		$packet = new TextPacket ();
		$packet->type = TextPacket::TYPE_CHAT;
		$packet->message = $message;
		$packet->setChannel ( Network::CHANNEL_TEXT );
		$player->dataPacket ( $packet );
		if ($viewers != null) {
			$player->getServer ()->broadcastPacket ( $viewers, $packet );
		}
	}
	public static function sendRawText(Player $player, $message, $viewers = null) {
		$packet = new TextPacket ();
		$packet->type = TextPacket::TYPE_RAW;
		$packet->message = $message;
		$packet->setChannel ( Network::CHANNEL_TEXT );
		$player->dataPacket ( $packet );
		if ($viewers != null) {
			$player->getServer ()->broadcastPacket ( $viewers, $packet );
		}
	}
	public function sendTranslation(Player $player, $message, array $parameters = [], $viewers = null) {
		$packet = new TextPacket ();
		$packet->type = TextPacket::TYPE_TRANSLATION;
		$packet->message = $player->getServer ()->getLanguage ()->translateString ( $message, [ ], "pocketmine." );
		foreach ( $parameters as $i => $p ) {
			$parameters [$i] = $player->getServer ()->getLanguage ()->translateString ( $p, [ ], "pocketmine." );
		}
		$packet->parameters = $parameters;		
		$packet->type = TextPacket::TYPE_RAW;
		$packet->message = $player->getServer ()->getLanguage ()->translateString ( $message, $parameters );		
		$player->dataPacket ( $packet );
		if ($viewers != null) {
			$player->getServer ()->broadcastPacket ( $viewers, $packet );
		}
	}
}
