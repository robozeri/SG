<?php

namespace hungergames\task;

use hungergames\arena\MapArenaModel;
use hungergames\main\HungerGamesPlugIn;
use hungergames\utils\LevelUtil;
use pocketmine\scheduler\PluginTask;
use hungergames\level\GameLevelModel;


class HungerGamesPortalResetTask extends PluginTask {
	private $plugin;
	private $lv;
	private $action;
	public function __construct(HungerGamesPlugIn $plugin, GameLevelModel $lv, $action) {
		$this->plugin = $plugin;
		$this->lv = $lv;
		$this->action = $action;
		parent::__construct ( $plugin );
	}
	
	/**
	 *
	 * @param
	 *        	$ticks
	 */
	public function onRun($ticks) {
		try {
			if (!empty($this->action) && $this->action==="open") {
				$output="";
				$this->lv->setPortalGate ( "open", $output);
				$this->plugin->log($this->lv->name."> Gate Open [".$output."]");
			}
			if (!empty($this->action) && $this->action==="close") {
				$output="";
				$this->lv->setPortalGate ( "close", $output);
				$this->plugin->log($this->lv->name."> Gate Close [".$output."]");
			}			
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
	}

	public function onCancel() {
	}
}
