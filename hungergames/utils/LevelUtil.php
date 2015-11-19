<?php

namespace hungergames\utils;

use pocketmine\Server;
use pocketmine\utils\TextFormat;


class LevelUtil {
	const WORLD_FOLDER = "worlds/";
	public static function createSessionWorld($sourceWorldName, $targetWorldName) {
		$status = false;
		$fileutil = new FileUtil ();
		$source = Server::getInstance ()->getDataPath () . self::WORLD_FOLDER . $sourceWorldName . "/";
		$destination = Server::getInstance ()->getDataPath () . self::WORLD_FOLDER . $targetWorldName . "/";
		if ($fileutil->xcopy ( $source, $destination )) {
			try {
				$status = Server::getInstance ()->loadLevel ( $destination );
			} catch ( \Exception $e ) {
				echo "[HG]createSessionWorld error: " . $e->getMessage ();
			}
		} else {
			echo TextFormat::RED . "[HG] problem creating HG world. please contact administrator.";
		}
		return $status;
	}
	public static function deleteSessionWorld($worldname) {
		$status = false;
		try {
			$levelpath = Server::getInstance ()->getDataPath () . self::WORLD_FOLDER . $worldname . "/";
			if (file_exists ( $levelpath )) {
				if (Server::getInstance ()->isLevelLoaded ( $worldname )) {
					$level = Server::getInstance ()->getLevelByName ( $worldname );
					Server::getInstance ()->unloadLevel ( $level, true );
				}
				$fileutil = new FileUtil ();
				$fileutil->unlinkRecursive ( $levelpath, true );
			}
		} catch ( \Exception $e ) {
			echo TextFormat::RED . "[HG]deleteSession World error: " . $e->getMessage () . "\n";
		}
		return $status;
	}
	
	public static function loadWorld($levelname, &$output) {
		$ret = false;
		if ($levelname === null) {
			$output= "Warning, no world name specified!";
			return;
		}
		$output= TextFormat::DARK_GRAY . "[HG] Loading map " . $levelname ;
		if (!Server::getInstance()->isLevelLoaded ( $levelname )) {
			$ret = Server::getInstance()->loadLevel ( $levelname );
			if ($ret) {
				$output = TextFormat::DARK_GRAY . "[HG] " . $levelname . " map loaded! ";
			} else {
				$output = TextFormat::YELLOW . " [HG] Error, unable load map [" . $levelname . "]. please contact server admin.";
			}
		}
		return $ret;
	}
	
	public function listAllWorld(CommandSender $sender) {
		$out = "The following levels are available:";
		$i = 0;
		if ($handle = opendir ( $levelpath = $sender->getServer ()->getDataPath () . "worlds/" )) {
			while ( false !== ($entry = readdir ( $handle )) ) {
				if ($entry [0] != ".") {
					$i ++;
					$out .= "\n " . $i . ">" . $entry . " ";
				}
			}
			closedir ( $handle );
		}
		$sender->sendMessage ( $out );
	}
}
