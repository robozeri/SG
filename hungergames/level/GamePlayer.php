<?php

namespace hungergames\level;

use pocketmine\Player;

class GamePlayer {
	
	public $name;
	public $player;
	public $kit;
	public $rank;
	public $kills;
	public $hits;
	public $shoots;
	public $levelName;
	public $arenaName;
	public $exitlevelName;
	public $status;
	
	public $online = true;
	public $invisible = false;
	public $onGround = false;
	public $invisibleTime = 0;
	
	public $arenaSpawnPos;
	public $arenaEnterPos;
	public $arenaExitPos;
	public $arenaDeathMatchPos;
	public $arenaWaitPos;
	
	public $forceResetDoor = true;
	
	public function __construct($name) {
		$this->name = $name;
	}
	
	public function sendPlayerToDeathMatch() {
		$this->player->teleport($this->arenaDeathMatchPos);
	}
	
	public function leaveArena() {
        $this->player->teleport($this->arenaExitPos);
	}
	
	public function sendPlayerToArenaSpawnPoint() {
        $this->player->teleport($this->arenaSpawnPos);
	}
	
	public function sendPlayerToWaitingRoom() {
        $this->player->teleport($this->arenaWaitPos);
	}
	
	public function keepPlayerOnGround() {
		$this->onGround = true;
        $this->player->onGround = true;
	}
	
	public function releasePlayer() {
		$this->onGround = false;
        $this->player->onGround = false;
	}
	
	public function hidePlayerFrom(Player &$op) {
        $this->player->hidePlayer($op);
	}
	public function showPlayerTo(Player &$op) {
        $this->player->showPlayer($op);
	}
	
	public function notify($message) {
        $this->player->sendMessage($message);
	}
	
}
