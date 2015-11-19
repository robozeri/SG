<?php

namespace hungergames\task;

use hungergames\arena\MapArenaModel;
use hungergames\main\HungerGamesPlugIn;
use hungergames\utils\LevelUtil;
use pocketmine\scheduler\PluginTask;


class HungerGamesMapResetTask extends PluginTask {
	private $plugin;
	private $arena;
	public function __construct(HungerGamesPlugIn $plugin, MapArenaModel $arena) {
		$this->plugin = $plugin;
		$this->arena = $arena;
		parent::__construct ( $plugin );
	}
	
	/**
	 *
	 * @param
	 *        	$ticks
	 */
	public function onRun($ticks) {
		try {
			$start_time = microtime(true);
			$newLevel = $this->resetMap ( $this->arena );
			$this->plugin->log("[HungerGamesMapResetTask->resetMap took ".(microtime(true)-$start_time));
		} catch ( \Exception $e ) {
			$this->plugin->printError($e);
		}
	}
	private function resetMap(MapArenaModel $arena) {
		$targetWorldName = $arena->levelName . "_TEMP";
		LevelUtil::deleteSessionWorld ( $targetWorldName );
		$this->plugin->log("[HG] deleted [" . $targetWorldName . "]");
	}
	private function getArenaLevel(MapArenaModel $arena) {
		$level = null;
		foreach ( $this->plugin->getServer ()->getLevels () as $le ) {
			if ($le->getName () === $arena->levelName) {
				$level = $le;
				break;
			}
		}
		if ($level == null) {
			$this->plugin->getServer ()->loadLevel ( $arena->levelName );
			$level = $this->plugin->getServer ()->getLevelByName ( $arena->levelName );
		}
		return $level;
	}
	public function onCancel() {
	}
}
