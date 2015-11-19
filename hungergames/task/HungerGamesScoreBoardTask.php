<?php

namespace hungergames\task;

use hungergames\arena\MapArenaModel;
use hungergames\main\HungerGamesPlugIn;
use hungergames\level\GameLevelModel;
use hungergames\statue\StatueBuilder;
use hungergames\statue\StatueModel;
use hungergames\utils\MagicUtil;
use hungergames\utils\TextUtil;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\scheduler\PluginTask;
use pocketmine\utils\TextFormat;
use hungergames\level\GameLevelManager;
use hungergames\main\HungerGameKit;
use pocketmine\tile\Tile;
use pocketmine\tile\Sign;
use pocketmine\tile\Chest;
use pocketmine\math\Vector3;


class HungerGamesScoreBoardTask extends PluginTask {
	private $plugin;
	public function __construct(HungerGamesPlugIn $plugin) {
		$this->plugin = $plugin;
		parent::__construct ( $plugin );
	}
	private function showHelpFloatingText() {
		if ((mt_rand ( 1, 12 )) > 10) {
			if ($this->plugin->showLobbyHelp) {
				MagicUtil::addFloatingText ( $this->plugin->hubLevel, $this->plugin->hubLobbyHelpText, TextFormat::BOLD . TextFormat::AQUA . $this->plugin->hubLobbyHelpTitle, $this->plugin->hubLobbyHelpPos );
			}
		}
	}
	private function showHallOfFrameWinners() {
		if ((mt_rand ( 1, 20 )) > 15) {
			$builder = new StatueBuilder ( $this->plugin );
			$builder->updateHallOfFrameWinners ();
			unset($builder);
		}
	}
	private function showPortalParticles(GameLevelModel $lv) {
		if ((mt_rand ( 1, 12 )) > 9) {
			$px = round ( ($lv->gatePos2->x + $lv->gatePos1->x) / 2 );
			$py = ($lv->gatePos2->y);
			$pz = round ( ($lv->gatePos2->z + $lv->gatePos1->z) / 2 ) + 1;
			$pos = new Position ( $px, $py, $pz );
			if ($lv->particles != null) {
				MagicUtil::addParticles ( $lv->level, $lv->particles, $pos, 100 );
			}
		}
	}
	private function moveStatues(GameLevelModel $lv) {
		$i = mt_rand ( 1, 16 );
		if ($i < 5) {
			foreach ( $this->plugin->statueManager->npcs as $npc ) {
				$i = mt_rand ( 1, 12 );
				if ($i === 2 || $i === 3 || $i === 5) {
					if ($npc->type === "npc") {
						StatueBuilder::moveStatue ( $npc );
						StatueBuilder::animateStatue ( $npc );
					}
				} elseif ($i === 2 || $i === 5 || $i === 7) {
					if ($npc instanceof StatueModel) {
						if ($npc->type === "npc") {
							if ($npc->particles != null) {
								MagicUtil::addParticles ( $lv->level, $npc->particles, new Position ( $npc->position->x, $npc->position->y + 1, $npc->position->z ), 200 );
							}
						}
					}
				}
			}
		}
	}
	private function displayGameStats(GameLevelModel $lv) {
		if (is_null ( $lv ) || is_null ( $lv->currentMap ) || (count ( $lv->currentMap->livePlayers ) === 0)) {
			$this->plugin->log ( "HungerGamesScoreBoardTask: error NULL parameters" );
			return;
		}
		foreach ( $lv->currentMap->livePlayers as $gamer ) {
			if (isset ( $lv->currentMap->playerscores [$gamer->player->getName ()] )) {
				$scores = $lv->currentMap->playerscores [$gamer->player->getName ()];
				if (! is_null ( $scores ) && ! is_null ( $lv->currentStep )) {
					$message = $gamer->player->isOp () ? TextFormat::RED . "OP-" : "";
					$message .= TextFormat::GRAY . "HG-" . $lv->type . ": " . TextFormat::GREEN . $lv->currentStep;
					$message .= TextFormat::GRAY . " alive: " . TextFormat::WHITE . count ( $lv->currentMap->livePlayers );
					$message .= TextFormat::GRAY . " | ";
					$message .= TextFormat::GRAY . "shots " . TextFormat::WHITE . $scores ["shots"] . " ";
					$message .= TextFormat::GRAY . "points " . TextFormat::WHITE . $scores ["hits"] . " ";
					$diff = 0;
					if ($lv->currentStep === GameLevelModel::STEP_HUNTING) {
						$diff = round ( $lv->currentMap->huntingFinishTime ) - (microtime ( true ));
						$message .= TextFormat::GRAY . "| " . TextFormat::AQUA . round ( $diff, 2 );
					} elseif ($lv->currentStep === GameLevelModel::STEP_DEATH_MATCH) {
						$diff = round ( $lv->currentMap->deathMatchFinishTime - (microtime ( true )) );
						$message .= TextFormat::GRAY . "| " . TextFormat::AQUA . round ( $diff, 2 );
					}
					for($i = 0; $i < 15; $i ++) {
						TextUtil::sendPopUpText ( $gamer->player, $message );
					}
				}
			}
		}
	}
	public function updateHallOfFrameSigns() {
		if (! empty ( $this->plugin->hubHallOfFrameSignExitPos ) && ! empty ( $this->plugin->hubLevel )) {
			$sign = $this->plugin->hubLevel->getTile ( $this->plugin->hubHallOfFrameSignTitlePos );
			if (! is_null ( $sign ) and $sign instanceof Sign) {
				$sign->setText ( TextFormat::DARK_GREEN . "HG", TextFormat::GOLD . "Hall Of Frame", TextFormat::RED . "[EXIT]", TextFormat::WHITE . "LOBBY" );
			}
			unset ( $sign );
		}
		if (! empty ( $this->plugin->hubHallOfFrameSignTitlePos ) && ! empty ( $this->plugin->hubLevel )) {
			$sign = $this->plugin->hubLevel->getTile ( $this->plugin->hubHallOfFrameSignTitlePos );
			if (! is_null ( $sign ) and $sign instanceof Sign) {
				$sign->setText ( TextFormat::DARK_GREEN . "HG", TextFormat::GOLD . "Hall Of Frame", TextFormat::RED . "", TextFormat::WHITE . "BEST OF BEST" );
			}
			unset ( $sign );
		}
	}
	public function updateVIPSigns() {
		if (! empty ( $this->plugin->vipSignPos ) and ! empty ( $this->plugin->hubLevel )) {
			$sign = $this->plugin->hubLevel->getTile ( new Vector3 ( $this->plugin->vipSignPos->x, $this->plugin->vipSignPos->y, $this->plugin->vipSignPos->z ) );
			if (! is_null ( $sign ) and $sign instanceof Sign) {
				$sign->setText ( TextFormat::GOLD . "-VIP members-", TextFormat::GREEN . "Enter", TextFormat::BOLD . TextFormat::WHITE . "[ " . TextFormat::RED . "VIP+ " . TextFormat::AQUA . "Lodge" . TextFormat::WHITE . " ]", TextFormat::WHITE . "---" );
				MagicUtil::addParticles ( $this->plugin->hubLevel, "reddust", $this->plugin->vipSignPos, 8 );
			}
			unset ( $sign );
		}
		
		if (! empty ( $this->plugin->vipExitSignPos ) and ! empty ( $this->plugin->vipLevel )) {
			$sign = $this->plugin->vipLevel->getTile ( new Vector3 ( $this->plugin->vipExitSignPos->x, $this->plugin->vipExitSignPos->y, $this->plugin->vipExitSignPos->z ) );
			if (! is_null ( $sign ) and $sign instanceof Sign) {
				$sign->setText ( TextFormat::GOLD . "---", TextFormat::RED . "EXIT", TextFormat::BOLD . TextFormat::WHITE . "[ " . TextFormat::AQUA . "HG " . TextFormat::AQUA . "Lobby" . TextFormat::WHITE . " ]", TextFormat::WHITE . "---" );
				MagicUtil::addParticles ( $this->plugin->hubLevel, "reddust", $this->plugin->vipSignPos, 8 );
			}
			unset ( $sign );
		}
	}
	
	/**
	 *
	 * @param
	 *        	$ticks
	 */
	public function onRun($ticks) {
		try {
			$start_time = microtime ( true );
			$this->showHelpFloatingText ();
			$this->showHallOfFrameWinners ();
			$this->updateVIPSigns ();
			foreach ( $this->plugin->getAvailableLevels () as &$lv ) {
				if ($lv instanceof GameLevelModel) {
					if ($lv->forceSignUpdate) {
						$this->updateLevelSigns ( $lv->level, $lv );
						$this->updateHallOfFrameSigns ();
						$lv->forceSignUpdate = false;
					}
					$this->showPortalParticles ( $lv );
					$this->moveStatues ( $lv );
					$this->updateLevelSigns ( $lv->level, $lv );
					if (count ( $lv->joinedPlayers ) === 0) {
						continue;
					}
					if ($lv->currentStep === GameLevelModel::STEP_WAITING || $lv->currentStep === GameLevelModel::STEP_INVISIBLE) {
						$this->displayGameStats ( $lv );
					}
					if ($lv->currentStep === GameLevelModel::STEP_HUNTING || $lv->currentStep === GameLevelModel::STEP_DEATH_MATCH) {
						$this->displayGameStats ( $lv );
					}
					if ($lv->currentStep === GameLevelModel::STEP_GAME_OVER) {
						$this->showHallOfFrameWinners ();
					}
				}
			}
			// $this->plugin->log ( "[HungerGamesScoreBoardTask->onRun took " . (microtime ( true ) - $start_time) );
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
	}
	
	/**
	 * Update Level Signs
	 *
	 * @param Level $level        	
	 * @param GameLevelModel $lv        	
	 * @internal param MapArenaModel $arena
	 */
	private function updateLevelSigns(Level $level, GameLevelModel &$lv) {
		try {
			if (is_null ( $level )) {
				$this->plugin->log ( "[HG] game sign level is null " );
				return;
			}
			if (! empty ( $lv->signJoin )) {
				$sign = $level->getTile ( $lv->signJoin );
				if (! is_null ( $sign ) and $sign instanceof Sign) {
					if ($lv->type === 1) {
						$sign->setText ( TextFormat::AQUA . "Welcome ", TextFormat::GREEN . $lv->name, TextFormat::WHITE . "join " . count ( $lv->joinedPlayers ) . " |min " . $lv->minPlayers, TextFormat::GOLD . "[" . $lv->currentStep . "]" );
					}
					if ($lv->type === 2) {
						$sign->setText ( TextFormat::AQUA . "Welcome ", TextFormat::YELLOW . $lv->name, TextFormat::WHITE . "join " . count ( $lv->joinedPlayers ) . " |min " . $lv->minPlayers, TextFormat::GOLD . "[" . $lv->currentStep . "]" );
					}
					if ($lv->type === 3) {
						$sign->setText ( TextFormat::AQUA . "Welcome ", TextFormat::BLUE . $lv->name, TextFormat::WHITE . "join " . count ( $lv->joinedPlayers ) . " |min " . $lv->minPlayers, TextFormat::GOLD . "[" . $lv->currentStep . "]" );
					}
					if ($lv->type === 4) {
						$sign->setText ( TextFormat::AQUA . "Welcome ", TextFormat::RED . $lv->name, TextFormat::WHITE . "join " . count ( $lv->joinedPlayers ) . " |min " . $lv->minPlayers, TextFormat::GOLD . "[" . $lv->currentStep . "]" );
					}
				}
				unset ( $sign );
			}			
			if (! empty ( $lv->signJoin2 )) {
				$sign = $level->getTile ( $lv->signJoin2 );
				if (! is_null ( $sign ) and $sign instanceof Sign) {
					if ($lv->type === 1) {
						$sign->setText ( TextFormat::AQUA . "Welcome ", TextFormat::GREEN . $lv->name, TextFormat::WHITE . "join " . count ( $lv->joinedPlayers ) . " |min " . $lv->minPlayers, TextFormat::GOLD . "[" . $lv->currentStep . "]" );
					}
					if ($lv->type === 2) {
						$sign->setText ( TextFormat::AQUA . "Welcome ", TextFormat::YELLOW . $lv->name, TextFormat::WHITE . "join " . count ( $lv->joinedPlayers ) . " |min " . $lv->minPlayers, TextFormat::GOLD . "[" . $lv->currentStep . "]" );
					}
					if ($lv->type === 3) {
						$sign->setText ( TextFormat::AQUA . "Welcome ", TextFormat::BLUE . $lv->name, TextFormat::WHITE . "join " . count ( $lv->joinedPlayers ) . " |min " . $lv->minPlayers, TextFormat::GOLD . "[" . $lv->currentStep . "]" );
					}
					if ($lv->type === 4) {
						$sign->setText ( TextFormat::AQUA . "Welcome ", TextFormat::RED . $lv->name, TextFormat::WHITE . "join " . count ( $lv->joinedPlayers ) . " |min " . $lv->minPlayers, TextFormat::GOLD . "[" . $lv->currentStep . "]" );
					}
				}
				unset ( $sign );
			}			
			if (! empty ( $lv->signStats )) {
				$sign = $level->getTile ( $lv->signStats );
				if (! is_null ( $sign ) && $sign instanceof Sign) {
					try {
						$livePlayers = count ( $lv->joinedPlayers );
						$sign->setText ( TextFormat::RED . $lv->name, TextFormat::WHITE . "=" . $lv->status . "=", TextFormat::GRAY . "join " . TextFormat::GREEN . count ( $lv->joinedPlayers ) . TextFormat::GRAY . " |min." . TextFormat::BLUE . $lv->minPlayers, TextFormat::GOLD . "[" . $lv->currentStep . "]" );
					} catch ( \Exception $e ) {
						$this->plugin->printError ( $e );
						$sign->setText ( TextFormat::RED . $lv->name, TextFormat::WHITE . "=" . $lv->status . "=", TextFormat::GRAY . "join " . count ( $lv->joinedPlayers ), TextFormat::GOLD . "[" . $lv->currentStep . "]" );
					}
				}
				unset ( $sign );
			}
			if (! empty ( $lv->signExit )) {
				$sign = $level->getTile ( $lv->signExit );
				if (! is_null ( $sign ) and $sign instanceof Sign) {
					$sign->setText ( TextFormat::GREEN . $lv->name, " ", TextFormat::RED . "[ EXIT ]", TextFormat::WHITE . "LOBBY" );
				}
				unset ( $sign );
			}
			$this->updateArenaSigns ( $lv );
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
	}
	
	/**
	 *
	 * @param GameLevelModel $lv        	
	 * @param MapArenaModel $arena        	
	 */
	private function updateArenaSigns(GameLevelModel &$lv) {
		try {
			foreach ( $this->plugin->getAvailableArenas () as &$arena ) {
				if ($arena instanceof MapArenaModel) {
					if (is_null ( $arena->exitLevel )) {
						return;
					}
					if (! empty ( $lv->currentStep )) {
						if ($lv->currentStep === GameLevelModel::STEP_WAITING || $lv->currentStep === GameLevelModel::STEP_GAME_OVER) {
							$arena->vote = 0;
						}
					}
					if (! empty ( $arena->signVote )) {
						if ($lv->type === GameLevelModel::LEVEL_VIP) {
							$sign = $lv->level->getTile ( new Vector3($arena->signVote->x,$arena->signVote->y,$arena->signVote->z));
						} else {
							$sign = $arena->exitLevel->getTile ( $arena->signVote );
						}
						if (! is_null ( $sign ) and $sign instanceof Sign) {
							if ($arena->published) {
								$sign->setText ( TextFormat::WHITE . "tap to vote", TextFormat::DARK_GREEN . "map", TextFormat::GOLD . $arena->displayName, TextFormat::WHITE . "votes: [ " . TextFormat::BOLD . TextFormat::GOLD . $arena->vote . TextFormat::WHITE . " ]" );
							} else {
								$sign->setText ( TextFormat::DARK_GRAY . "Not Available", TextFormat::DARK_GRAY . "map", TextFormat::DARK_GRAY . $arena->displayName, TextFormat::DARK_GRAY . "Not Available" );
							}
						}
						unset ( $sign );
					}
					if (! empty ( $arena->signJoin )) {
						$sign = $arena->exitLevel->getTile ( $arena->signJoin );
						if (! is_null ( $sign ) and $sign instanceof Sign) {
							if ($arena->published) {
								$sign->setText ( TextFormat::WHITE . "Admin Map TP", TextFormat::GOLD . $arena->displayName, TextFormat::DARK_GREEN . "Joined:" . count ( $arena->joinedPlayers ), TextFormat::WHITE . "Live: " . count ( $arena->livePlayers ) );
							} else {
								$sign->setText ( TextFormat::DARK_GRAY . "Not Available", TextFormat::DARK_GRAY . "map", TextFormat::DARK_GRAY . $arena->displayName, TextFormat::DARK_GRAY . "Not Available" );
							}
						}
						unset ( $sign );
					}
				}
			}
		} catch ( \Exception $e ) {
			$this->plugin->printError ( $e );
		}
	}
	public function onCancel() {
	}
}
