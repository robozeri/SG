<?php

namespace hungergames\task;

use hungergames\arena\MapArenaModel;
use hungergames\main\HungerGamesPlugIn;
use hungergames\utils\LevelUtil;
use pocketmine\scheduler\PluginTask;
use hungergames\level\GameLevelModel;


class HungerGamesRecordLossTask extends PluginTask {
	private $plugin;
	private $lv;
	private $playerName;
	public function __construct(HungerGamesPlugIn $plugin, GameLevelModel $lv, $playerName) {
		$this->plugin = $plugin;
		$this->lv = $lv;
		$this->playerName = $playerName;
		parent::__construct ( $plugin );
	}
	
	/**
	 *
	 * @param
	 *        	$ticks
	 */
	public function onRun($ticks) {
		try {
			$start_time = microtime ( true );
			$this->plugin->profileManager->addPlayerLoss ( $this->playerName );
			$this->plugin->log ( "[HungerGamesRecordLossTask->addPlayerLoss took " . (microtime ( true ) - $start_time));
			$start_time = microtime ( true );
			if (!empty($this->lv->currentMap)) {
				$this->plugin->storyManager->upsetPlayerLevelLoss ( $this->playerName, $this->lv->type, $this->lv->currentMap->name );
				$this->plugin->log ( "[HungerGamesRecordLossTask->upsetPlayerLevelLoss took " . (microtime ( true ) - $start_time) );
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
	}
	public function onCancel() {
	}
}
