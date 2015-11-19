<?php

namespace hungergames\task;

use hungergames\arena\MapArenaModel;
use hungergames\main\HungerGamesPlugIn;
use \hungergames\utils\LevelUtil;
use pocketmine\scheduler\PluginTask;
use hungergames\level\GameLevelModel;


class HungerGamesRecordWinsTask extends PluginTask {
	private $plugin;
	private $lv;
	private $playerName;
	private $points;
	private $pmap;
	public function __construct(HungerGamesPlugIn $plugin, GameLevelModel $lv,$pmap, $playerName, $points) {
		$this->plugin = $plugin;
		$this->lv = $lv;
		$this->playerName = $playerName;
		$this->points = $points;
		$this->pmap = $pmap;
		parent::__construct ( $plugin );
	}
	
	/**
	 *
	 * @param
	 *        	$ticks
	 */
	public function onRun($ticks) {
		try {
			if (empty ( $this->lv ) && empty ( $this->$playerName )) {
				return;
			}
			if (empty ( $this->lv->winnerCoins )) {
				$this->lv->winnerCoins = 5;
			}
			$start_time = microtime(true);
			$this->plugin->profileManager->upsetPlayerWinning ( $this->playerName, $this->lv->winnerCoins );
			$this->plugin->log("[HungerGamesRecordWinTask->upsetPlayerWinning took ".(microtime(true)-$start_time));
			
			$points = empty ( $this->points ) ? 0 : $this->points;
			$plevel = $this->lv->type;
			$pname = $this->playerName;
			$start_time = microtime(true);
			$this->plugin->storyManager->upsetPlayerLevelWinning ( $pname, $plevel, $this->pmap, $points );
			$this->plugin->log("[HungerGamesRecordWinTask->upsetPlayerLevelWinning took ".(microtime(true)-$start_time));
						
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
	}
	public function onCancel() {
	}
}
