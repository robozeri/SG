<?php

namespace mcg76\hungergames\task;

use hungergames\arena\MapArenaModel;
use hungergames\main\HungerGamesPlugIn;
use hungergames\utils\LevelUtil;
use pocketmine\scheduler\PluginTask;


class HungerGamesLoaderTask extends PluginTask {
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
			LevelUtil::deleteSessionWorld ( $this->arena->levelName );
			$targetWorldName = $this->arena->levelName . "_PLAY";
			LevelUtil::createSessionWorld ( $this->arena->levelName, $targetWorldName );
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
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
	protected function log($msg) {
		 $this->plugin->log($msg);
	}
}
