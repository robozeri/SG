<?php

namespace hungergames\task;

use hungergames\main\HungerGamesPlugIn;
use hungergames\statue\StatueBuilder;
use hungergames\statue\StatueModel;
use hungergames\utils\LevelUtil;
use hungergames\arena\MapArenaModel;
use hungergames\level\GameLevelModel;
use hungergames\level\GamePlayer;
use hungergames\portal\MapPortal;
use hungergames\main\HungerGameKit;
use hungergames\utils\MagicUtil;
use hungergames\utils\TextUtil;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\entity\Effect;
use pocketmine\level\sound\LaunchSound;
use pocketmine\utils\TextFormat;
use pocketmine\level\sound\FizzSound;
use pocketmine\level\sound\PopSound;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\level\sound\ClickSound;
use pocketmine\level\sound\DoorSound;


class HungerGamesStateMachineTask extends PluginTask {
	private $plugin;
	public function __construct(HungerGamesPlugIn $plugin) {
		$this->plugin = $plugin;
		parent::__construct ( $plugin );
	}
	
	/**
	 *
	 * @param
	 *        	$ticks
	 */
	public function onRun($ticks) {
		$start_time = microtime ( true );
		foreach ( $this->plugin->getAvailableLevels () as &$lv ) {
			try {
				if ($lv instanceof GameLevelModel) {
					if ($lv->forceResetDoor) {
						$this->plugin->openGate ( $lv );
						$lv->forceResetDoor = false;
						$this->plugin->log ( $lv->name . "- open gate (59)" );
					}
					if (count ( $lv->joinedPlayers ) === 0) {
						continue;
					}
					if ($lv->status === GameLevelModel::STATUS_AVAILABLE && count ( $lv->joinedPlayers ) >= $lv->minPlayers) {
						$this->plugin->log ( "#1" . $lv->name . " - level " . $lv->type . " - " . $lv->status . " step :" . $lv->currentStep );
						if ($lv->joinDownCounter > 0 && count ( $lv->joinedPlayers ) > 0) {
							$lv->currentStep = GameLevelModel::STEP_JOINING;
							$lv->joinDownCounter --;
							$message = TextFormat::WHITE . "Join " . $lv->displayName . " in [" . TextFormat::BOLD . TextFormat::GOLD . $lv->joinDownCounter . TextFormat::WHITE . "] seconds";
							TextUtil::broadcastPopUpText ( $message, $lv->joinedPlayers );
							$lv->level->addSound ( new ClickSound ( $lv->gatePos1 ), $lv->joinedPlayers );
							continue;
						} elseif ($lv->joinDownCounter === 0 && count ( $lv->joinedPlayers ) > 0) {
							Server::getInstance ()->broadcastMessage ( TextFormat::GRAY . "[HG] " . TextFormat::AQUA . "Vote for a map " . TextFormat::GRAY . "or" . TextFormat::GOLD . " random pick?", $lv->joinedPlayers );
							$lv->startTime = microtime ( true );
							$lv->finishTime = $lv->startTime + $lv->mapSelectionWaitTime + 10;
							$lv->status = GameLevelModel::STATUS_MAP_SELECTION;
							$lv->currentStep = GameLevelModel::STEP_MAP_SELECTION;
							$lv->joinDownCounter = 10;
							continue;
						}
					} elseif ($lv->currentStep === GameLevelModel::STATUS_MAP_SELECTION) {
						$this->plugin->log ( "#2" . $lv->name . " - level " . $lv->type . " - " . $lv->status . " step :" . $lv->currentStep );
						if (count ( $lv->joinedPlayers ) === 0) {
							$lv->status = GameLevelModel::STATUS_AVAILABLE;
							$lv->currentStep = GameLevelModel::STEP_JOINING;
							$this->plugin->openGate ( $lv );
							$lv->joinDownCounter = 10;
							continue;
						}
						$message = TextFormat::BOLD . TextFormat::GREEN . "- Vote - ";
						TextUtil::broadcastPopUpTips ( $message, $lv->joinedPlayers );
						$lv->status = GameLevelModel::STATUS_RUNNING;
						$currentTime = microtime ( true );
						$diff = $currentTime - $lv->finishTime;
						if ($diff > 0 && $diff < 8) {
							$message = TextFormat::GRAY . "Are you ready?";
							TextUtil::broadcastPopUpTips ( $message, $lv->joinedPlayers );
							$lv->joinDownCounter = 18;
						}
						if ($currentTime > $lv->finishTime) {
							if ($lv->joinDownCounter === 17) {
								$lv->currentMap = $this->getLevelMap ( $lv->maps );
								$lv->openchests = [ ];
								$lv->mapselectLevelName = $lv->currentMap->displayName;
								Server::getInstance ()->broadcastMessage ( TextFormat::GRAY . "[HG] Map selected: [" . TextFormat::GOLD . $lv->currentMap->displayName . TextFormat::GRAY . "]", $lv->joinedPlayers );
								$lv->level->addSound ( new PopSound ( $lv->gatePos1 ), $lv->joinedPlayers );
								if (is_null ( $lv->newtask )) {
									$this->plugin->closeGate ( $lv );
									$lv->newtask = new HungerGamesNewSessionMapTask ( $this->plugin, $lv->currentMap->levelName );
									$this->plugin->getServer ()->getScheduler ()->scheduleDelayedTask ( $lv->newtask, 1 );
								}
								$lv->joinDownCounter --;
								continue;
							} else {
								if ($lv->joinDownCounter > 0) {
									$lv->joinDownCounter --;
									$this->plugin->log ( $lv->name . " [count down] " . $lv->joinDownCounter . "- level " . $lv->type . " - " . $lv->status . " step :" . $lv->currentStep );
									$message = TextFormat::WHITE . "preparing map, ready in [" . TextFormat::GOLD . $lv->joinDownCounter . TextFormat::WHITE . "] seconds.";
									TextUtil::broadcastPopUpTips ( $message, $lv->joinedPlayers );
									$message = TextFormat::GREEN . "HINTS: " . TextFormat::YELLOW . " To exit the game use command " . TextFormat::WHITE . "[/hg exit]";
									TextUtil::broadcastPopUpText ( $message, $lv->joinedPlayers );
									$lv->level->addSound ( new PopSound ( $lv->gatePos1 ), $lv->joinedPlayers );
									continue;
								}
							}
							if ($lv->joinDownCounter === 0) {
								$lv->currentMap->status = MapArenaModel::STATUS_ENTERING;
								$lv->currentMap->livePlayers = [ ];
								$lv->newtask = null;
								if ($lv->currentMap != null) {
									$lv->currentMap->resetVoteCounter ();
									$lv->joinDownCounter = 10;
								}
								// double check
								$output = "";
								$targetWorldName = $lv->currentMap->levelName . "_TEMP";
								$mapCreated = LevelUtil::loadWorld ( $targetWorldName, $output );
								$this->plugin->log($output);
								if (! $mapCreated) {
									$lv->currentStep = GameLevelModel::STEP_GAME_OVER;
									$lv->status = GameLevelModel::STATUS_AVAILABLE;
									Server::getInstance ()->broadcastMessage ( "[HG] Problem with creation of session map. please re-join the portal to re-try or contact server admin.", $lv->joinedPlayers );
									continue;
								}
								if ($mapCreated) {
									Server::getInstance ()->broadcastMessage ( TextFormat::GRAY . "[HG] Loaded map [" . TextFormat::GREEN . $lv->currentMap->displayName . TextFormat::GRAY . "]", $lv->joinedPlayers );									
									foreach ( $lv->joinedPlayers as $player ) {
										if ($player instanceof Player) {
											$gamer = $lv->currentMap->joiningArena ( $player );
											$lv->currentMap->livePlayers [$player->getName ()] = $gamer;
											$lv->currentMap->joinedPlayers [$player->getName ()] = $player;
											if (! empty ( $player->getInventory () )) {
												HungerGameKit::clearAllInventories ( $player );
												$player->setHealth ( 20 );
											}
											$lv->level->addSound ( new LaunchSound ( $player ), $lv->joinedPlayers );
											if (! $player->isSurvival ()) {
												$player->setGamemode ( Player::SURVIVAL );
											}
											$lv->currentMap->playerscores [$player->getName ()] = array (
													"name" => $player->getName (),
													"shots" => 0,
													"hits" => 0,
													"kills" => 0 
											);
											if ($lv->type === GameLevelModel::LEVEL_THREE) {
												HungerGameKit::giveBowArrowKit ( $player );
											}
										}
									}
								}
								$this->plugin->getServer ()->getScheduler ()->scheduleDelayedTask ( new HungerGamesLevelPlayerToArenaTask ( $this->plugin, $lv ), rand ( 2, 4 ) );
								$lv->status = GameLevelModel::STEP_INVISIBLE;
								$lv->currentStep = GameLevelModel::STEP_WAITING;
								continue;
							}
						}
					} elseif ($lv->currentStep === GameLevelModel::STEP_WAITING) {
						if (count ( $lv->currentMap->livePlayers ) <= 1) {
							$lv->currentStep = GameLevelModel::STEP_GAME_OVER;
							continue;
						}
						$lv->status = GameLevelModel::STATUS_RUNNING;
						$this->plugin->log ( "#4" . $lv->name . " - level " . $lv->type . " - " . $lv->status . " step :" . $lv->currentStep );
						if ($lv->currentMap->playStartCountdown > 0) {
							$lv->currentMap->playStartCountdown --;
							$message = TextFormat::AQUA . "Let the game begin in " . TextFormat::LIGHT_PURPLE . $lv->currentMap->playStartCountdown . TextFormat::AQUA . " seconds";
							TextUtil::broadcastPopUpTips ( $message, $lv->currentMap->joinedPlayers );
							$lv->level->addSound ( new LaunchSound ( $lv->currentMap->arenaEnterPos ), $lv->joinedPlayers );
							$lv->type = $lv->type != null ? $lv->type : 0;
							if ($lv->type > 1) {
								foreach ( $lv->currentMap->livePlayers as $gamer ) {
									if ($gamer instanceof GamePlayer) {
										MagicUtil::addEffect ( $gamer->player, Effect::CONFUSION, 150 );
									}
								}
							} else {
								foreach ( $lv->currentMap->livePlayers as $gamer ) {
									if ($gamer instanceof GamePlayer) {
										MagicUtil::addEffect ( $gamer->player, Effect::WATER_BREATHING, 150 );
									}
								}
							}
							$lv->level->addSound ( new PopSound ( $lv->gatePos1 ), $lv->joinedPlayers );
						} elseif ($lv->currentMap->playStartCountdown === 0) {
							Server::getInstance ()->broadcastMessage ( TextFormat::GRAY . "[HG] " . TextFormat::RED . "The Hunger Game " . TextFormat::WHITE . "started. Run! ", $lv->currentMap->joinedPlayers );
							for($i = 0; $i < 5; $i ++) {
								$lv->level->addSound ( new DoorSound ( $lv->currentMap->arenaEnterPos ), $lv->joinedPlayers );
							}
							if ($lv->type > 1) {
								Server::getInstance ()->broadcastMessage ( TextFormat::GREEN . "[HG] You are visible!", $lv->currentMap->joinedPlayers );
								$lv->currentMap->huntingStartTime = microtime ( true );
								$lv->currentMap->huntingFinishTime = $lv->currentMap->huntingStartTime + $lv->currentMap->huntingDuration;
								$lv->currentStep = GameLevelModel::STEP_HUNTING;
								Server::getInstance ()->broadcastMessage ( TextFormat::GRAY . "[HG] Death-Match in " . TextFormat::LIGHT_PURPLE . $lv->currentMap->huntingDuration . TextFormat::GRAY . " seconds", $lv->currentMap->joinedPlayers );
								foreach ( $lv->currentMap->livePlayers as &$gamer ) {
									if ($gamer instanceof GamePlayer) {
										foreach ( $lv->currentMap->livePlayers as &$gp ) {
											$gamer->showPlayerTo ( $gp->player );
										}
									}
								}
							} else {
								foreach ( $lv->currentMap->livePlayers as $gamer ) {
									if ($gamer instanceof GamePlayer) {
										foreach ( $lv->currentMap->livePlayers as $gp ) {
											$gamer->hidePlayerFrom ( $gp->player );
										}
										$duration = ! empty ( $lv->currentMap->playInvisibleCountdown ) ? $lv->currentMap->playInvisibleCountdown : 10;
										MagicUtil::addEffect ( $gamer->player, Effect::INVISIBILITY, $duration );
									}
								}
								$lv->currentStep = GameLevelModel::STEP_INVISIBLE;
								continue;
							}
						}
					} elseif ($lv->currentStep === GameLevelModel::STEP_INVISIBLE) {
						if (count ( $lv->currentMap->livePlayers ) <= 1) {
							$lv->currentStep = GameLevelModel::STEP_GAME_OVER;
							continue;
						}
						$this->plugin->log ( $lv->name . " invisbile - level " . $lv->type . " - " . $lv->status . " step :" . $lv->currentStep );
						$lv->status = GameLevelModel::STATUS_RUNNING;
						if ($lv->currentMap->playInvisibleCountdown > 0) {
							$message = TextFormat::AQUA . "Invisible remains " . TextFormat::GOLD . $lv->currentMap->playInvisibleCountdown;
							TextUtil::broadcastPopUpTips ( $message, $lv->currentMap->joinedPlayers );
							$lv->currentMap->playInvisibleCountdown --;
							continue;
						} elseif ($lv->currentMap->playInvisibleCountdown === 0) {
							Server::getInstance ()->broadcastMessage ( TextFormat::GREEN . "[HG] You are visible now.", $lv->currentMap->joinedPlayers );
							foreach ( $lv->currentMap->livePlayers as $gamer ) {
								if ($gamer instanceof GamePlayer) {
									foreach ( $lv->currentMap->livePlayers as $gp ) {
										$gamer->showPlayerTo ( $gp->player );
									}
								}
							}
							if (empty ( $lv->currentMap->huntingDuration ) || $lv->currentMap->huntingDuration < 10) {
								$lv->currentMap->huntingDuration = 300;
							}
							$lv->currentMap->huntingStartTime = microtime ( true );
							$lv->currentMap->huntingFinishTime = $lv->currentMap->huntingStartTime + $lv->currentMap->huntingDuration;
							$lv->currentStep = GameLevelModel::STEP_HUNTING;
							$lv->openchests = [ ];
							$lv->chestsresetcounter = 0;
							Server::getInstance ()->broadcastMessage ( TextFormat::GRAY . "[HG] Death-Match in " . $lv->currentMap->huntingDuration . " seconds", $lv->currentMap->joinedPlayers );
							continue;
						}
					} elseif ($lv->currentStep === GameLevelModel::STEP_HUNTING) {
						$this->plugin->log ( "#6" . $lv->name . " - level " . $lv->type . " - " . $lv->status . " step :" . $lv->currentStep );
						$lv->chestsresetcounter ++;
						if ($lv->chestsresetcounter > $this->plugin->chestrefillcycle) {
							$lv->openchests [$lv->type] = [ ];
							$lv->chestsresetcounter = 0;
							$this->plugin->log ( $lv->name . "[" . $lv->type . "] chests cycled" );
						}
						if (count ( $lv->currentMap->livePlayers ) <= 1) {
							$lv->currentStep = GameLevelModel::STEP_GAME_OVER;
							continue;
						}
						$currentTime = microtime ( true );
						$diff = $currentTime - $lv->currentMap->huntingFinishTime;
						$this->plugin->log ( $lv->name . " Hunting finish in " . round ( $diff ) . " seconds" );
						$lv->status = GameLevelModel::STATUS_RUNNING;
						
						if ($currentTime > $lv->currentMap->huntingFinishTime) {
							if ($lv->currentMap->deathMatchStartCountdown > 0) {
								$lv->currentMap->deathMatchStartCountdown --;
								$message = TextFormat::AQUA . "Death-match start in " . TextFormat::GOLD . round ( $lv->currentMap->deathMatchStartCountdown ) . TextFormat::AQUA . " seconds.";
								TextUtil::broadcastPopUpTips ( $message, $lv->currentMap->joinedPlayers );
								continue;
							} elseif ($lv->currentMap->deathMatchStartCountdown === 0) {
								$this->plugin->getServer ()->getScheduler ()->scheduleDelayedTask ( new HungerGamesPlayerToDeathMatchTask ( $this->plugin, $lv ), rand ( 2, 4 ) );
								if (empty ( $lv->currentMap->deathMatchDuration ) || $lv->currentMap->deathMatchDuration < 10) {
									$lv->currentMap->deathMatchDuration = 120;
								}
								$lv->currentMap->deathMatchStartTime = microtime ( true );
								$lv->currentMap->deathMatchFinishTime = $lv->currentMap->deathMatchStartTime + $lv->currentMap->deathMatchDuration;
								$lv->currentStep = GameLevelModel::STEP_DEATH_MATCH;
								Server::getInstance ()->broadcastMessage ( TextFormat::GRAY . "[HG] " . TextFormat::RED . "Death-Match " . TextFormat::WHITE . "started. Run! " . TextFormat::GRAY . "[timeout " . TextFormat::GOLD . round ( $lv->currentMap->deathMatchDuration ) . TextFormat::GRAY . "s]", $lv->currentMap->joinedPlayers );
								for($i = 0; $i < 5; $i ++) {
									$lv->level->addSound ( new DoorSound ( $lv->currentMap->arenaEnterPos ), $lv->joinedPlayers );
								}
								$lv->level->addSound ( new PopSound ( $lv->currentMap->arenaEnterPos ), $lv->joinedPlayers );
							}
						}
					} elseif ($lv->currentStep === GameLevelModel::STEP_DEATH_MATCH) {
						$this->plugin->log ( "#7" . $lv->name . " - level " . $lv->type . " - " . $lv->status . " step :" . $lv->currentStep );
						if (count ( $lv->currentMap->livePlayers ) <= 1) {
							$lv->currentStep = GameLevelModel::STEP_GAME_OVER;
							continue;
						}
						$currentTime = microtime ( true );
						$diff = $currentTime - $lv->currentMap->deathMatchFinishTime;
						$this->plugin->log ( $lv->name . "> Death-match finish in " . round ( $diff ) . " seconds" );
						$lv->status = GameLevelModel::STATUS_RUNNING;
						if ($diff > 0 && $diff < 28) {
							$message = TextFormat::GRAY . $lv->currentMap->name . " death-match end in " . TextFormat::GOLD . round ( $diff ) . TextFormat::GRAY . " seconds.";
							TextUtil::broadcastPopUpTips ( $message, $lv->currentMap->joinedPlayers );
						}
						if ($currentTime > $lv->currentMap->deathMatchFinishTime) {
							Server::getInstance ()->broadcastMessage ( TextFormat::GRAY . "[HG] " . TextFormat::RED . "Game Over! -" . TextFormat::GRAY . "[" . $lv->currentMap->name . "]", $lv->currentMap->joinedPlayers );
							$lv->currentStep = GameLevelModel::STEP_GAME_OVER;
							continue;
						}
					} elseif ($lv->currentStep === GameLevelModel::STEP_GAME_OVER) {
						$this->handleLevelGameOver ( $lv );
						$this->plugin->openGate ( $lv );
						$lv->status = GameLevelModel::STATUS_AVAILABLE;
						unset ( $lv->currentStep );
						$lv->currentStep = GameLevelModel::STEP_JOINING;
						unset ( $lv->joinedPlayers );
						$lv->joinedPlayers = [ ];
						unset ( $lv->openchests );
						$lv->openchests = [ ];
						$lv->joinDownCounter = 10;
						$lv->finishTime = null;
						$lv->mapselectLevelName = null;
						$lv->mapselectpos = null;
						if (isset ( $lv->currentMap )) {
							$this->resetGameLevelArena ( $lv->currentMap );
							unset ( $lv->currentMap->playerscores );
							unset ( $lv->currentMap->livePlayers );
							unset ( $lv->currentMap->votedPlayers );
							unset ( $lv->currentMap );
						}
						$this->plugin->getLevelManager ()->resetLevel ( $lv->name );
						continue;
					}
				}
			} catch ( \Exception $e ) {
				$this->plugin->printError ( $e );
				$lv->currentStep = GameLevelModel::STEP_GAME_OVER;
				$this->plugin->openGate ( $lv );
				$this->plugin->log ( $lv->name . ">onException - open gate (327)" );
			}
		}
		// $this->plugin->log ( "HungerGamesStateMachineTask-> took " . (microtime ( true ) - $start_time));
	}
	
	/**
	 *
	 * @param GameLevelModel $lv        	
	 */
	private function checkInGamePlayerStatus(GameLevelModel &$lv) {
		if (! empty ( $lv->currentMap )) {
			if ($lv->currentStep === GameLevelModel::STEP_WAITING || $lv->currentStep === GameLevelModel::STEP_HUNTING || $lv->currentStep === GameLevelModel::STEP_INVISIBLE || $lv->currentStep === GameLevelModel::STEP_DEATH_MATCH) {
				foreach ( $lv->joinedPlayers as $player ) {
					if ($player instanceof Player) {
					}
				}
			}
		}
	}
	
	/**
	 *
	 * @param GameLevelModel $lv        	
	 */
	public function handleLevelGameOver(GameLevelModel &$lv) {
		$start = microtime ( true );
		if (! empty ( $lv->currentMap )) {
			if (count ( $lv->currentMap->livePlayers ) === 0) {
				Server::getInstance ()->broadcastMessage ( TextFormat::AQUA . "[HG] Game ended without winner!", $lv->currentMap->joinedPlayers );
			}
			if (count ( $lv->currentMap->livePlayers ) > 0) {
				foreach ( $lv->currentMap->livePlayers as $gamer ) {
					if ($gamer instanceof GamePlayer) {
						$lv->level->addSound ( new FizzSound ( $gamer->player ), array (
								$gamer->player 
						) );
						$lv->level->addSound ( new PopSound ( $gamer->player ), array (
								$gamer->player 
						) );
						if (count ( $lv->currentMap->livePlayers ) === 1) {
							if (isset ( $lv->currentMap->playerscores [$gamer->player->getName ()] )) {
								$scores = $lv->currentMap->playerscores [$gamer->player->getName ()];
								if ($scores != null) {
									$message = TextFormat::WHITE . "Final : ";
									$message .= TextFormat::AQUA . "shots " . TextFormat::LIGHT_PURPLE . $scores ["shots"] . TextFormat::GRAY . " | ";
									$message .= TextFormat::AQUA . "points " . TextFormat::LIGHT_PURPLE . $scores ["hits"] . TextFormat::GRAY . " | ";
									$message .= "\n";
									
									$winmsg = TextFormat::GOLD . "--------------------------------------\n";
									$winmsg .= TextFormat::GOLD . TextFormat::WHITE . " " . TextFormat::BOLD . TextFormat::LIGHT_PURPLE . "BRAVO " . TextFormat::WHITE . "[" . TextFormat::GOLD . $gamer->player->getName () . TextFormat::WHITE . "] Won [" . TextFormat::GOLD . $lv->winnerCoins . TextFormat::WHITE . "] coins \n";
									$winmsg .= TextFormat::WHITE . " Level: " . TextFormat::GREEN . $lv->displayName . TextFormat::WHITE . " | Map: " . TextFormat::GREEN . $lv->currentMap->name . "\n";
									$winmsg .= TextFormat::GREEN . " " . $message . "\n";
									$winmsg .= TextFormat::GOLD . "--------------------------------------\n";
									Server::getInstance ()->broadcastMessage ( $winmsg, $lv->currentMap->joinedPlayers );
									
									if (isset ( $lv->currentMap->playerscores [$gamer->player->getName ()] )) {
										unset ( $lv->currentMap->playerscores [$gamer->player->getName ()] );
									}
									if (isset ( $lv->currentMap->votedPlayers [$gamer->player->getName ()] )) {
										unset ( $lv->currentMap->votedPlayers [$gamer->player->getName ()] );
									}
									$pmap = $lv->currentMap->name;
									$recordwins = new HungerGamesRecordWinsTask ( $this->plugin, $lv, $pmap, $gamer->player->getName (), $scores ["hits"] );
									$this->plugin->getServer ()->getScheduler ()->scheduleDelayedTask ( $recordwins, mt_rand ( 2, 5 ) );
									for($i = 0; $i < 50; $i ++) {
										$gamer->player->sendTip ( TextFormat::BOLD . TextFormat::GOLD . "[ = V I C T O R Y ! = ]" );
									}
								}
							}
						} else {
							if (isset ( $lv->currentMap->playerscores [$gamer->player->getName ()] )) {
								$scores = $lv->currentMap->playerscores [$gamer->player->getName ()];
								if ($scores != null) {
									$message = TextFormat::WHITE . "Final : ";
									$message .= TextFormat::AQUA . "shots " . TextFormat::LIGHT_PURPLE . $scores ["shots"] . TextFormat::GRAY . " | ";
									$message .= TextFormat::AQUA . "points " . TextFormat::LIGHT_PURPLE . $scores ["hits"] . TextFormat::GRAY . " | ";
									$message .= "\n";
								}
							}
							for($i = 0; $i < 50; $i ++) {
								$gamer->player->sendTip ( TextFormat::BOLD . TextFormat::AQUA . "[ * IT'S A DRAW ! * ]" );
							}
							$drawmsg = TextFormat::RED . "--------------------------------------\n";
							$drawmsg .= TextFormat::RED . " -more than 1 player remains [" . count ( $lv->currentMap->livePlayers ) . "]\n";
							$drawmsg .= TextFormat::WHITE . " " . TextFormat::BOLD . TextFormat::YELLOW . "IT's A DRAW " . TextFormat::WHITE . "[" . TextFormat::GOLD . $gamer->player->getName () . TextFormat::WHITE . "]" . TextFormat::WHITE . "- try again?\n";
							$drawmsg .= TextFormat::GREEN . " " . $message . "\n";
							$drawmsg .= TextFormat::RED . "--------------------------------------" . "\n";
							Server::getInstance ()->broadcastMessage ( $drawmsg, $lv->currentMap->joinedPlayers );
						}
						$lv->level->addSound ( new FizzSound ( $gamer->player ), array (
								$gamer->player 
						) );
						MapPortal::teleportToMap ( $lv->levelName, $gamer->player );
						for($i = 0; $i < 500; $i ++) {
						}
						MapPortal::safeTeleporting ( $gamer->player, $lv->currentMap->arenaExitPos );
						foreach ( $lv->currentMap->livePlayers as $gp ) {
							$gamer->showPlayerTo ( $gp->player );
						}
						if (! empty ( $gamer->player->getInventory () )) {
							HungerGameKit::clearAllInventories ( $gamer->player );
							$gamer->player->getInventory ()->clearAll ();
							$gamer->player->setHealth ( 20 );
						}
						MagicUtil::addEffect ( $gamer->player, Effect::JUMP, 10 );
						MagicUtil::addParticles ( $gamer->player->level, "heart", $gamer->player->getPosition (), 30 );
						$players [] = $gamer->player;
						$lv->level->addSound ( new PopSound ( $gamer->player ), $players );
					}
				}
			}
			try {
				$task = new HungerGamesMapResetTask ( $this->plugin, $lv->currentMap );
				$this->plugin->getServer ()->getScheduler ()->scheduleDelayedTask ( $task, 12 );
				foreach ( $lv->maps as $mapname ) {
					if (isset ( $this->plugin->getAvailableArenas ()[$mapname] )) {
						$this->plugin->log ( $lv->name . "> * reset votes for map: " . $mapname );
						$arena = $this->plugin->getAvailableArenas ()[$mapname];
						$arena->vote = 0;
						$arena->votedPlayers = [ ];
						$this->plugin->getAvailableArenas ()[$mapname] = $arena;
						break;
					}
				}
				$lv->joinDownCounter = 10;
			} catch ( Exception $e ) {
				$this->plugin->printError ( $e );
			}
		}
		$this->plugin->log ( "#8 " . $lv->name . " handleLevelGameOver took " . (microtime ( true ) - $start) . " ms" );
	}
	public function onCancel() {
	}
	private function resetArena($arenaName) {
		$bak = $this->plugin->arenas [$arenaName];
		return $bak != null ? clone ($bak) : null;
	}
	private function resetGameLevelArena(MapArenaModel &$arena) {
		$bak = $this->plugin->getArenaManager ()->arenasBackup [$arena->name];
		unset ( $this->plugin->getAvailableArenas ()[$arena->name] );
		$this->plugin->getAvailableArenas ()[$arena->name] = clone ($bak);
		$this->plugin->log ( "* ResetGameLevelArena: " . $arena->name . "* " );
	}
	public function filterPublishedArenas() {
		$publishedArenas = [ ];
		foreach ( $this->plugin->getAvailableArenas () as $arena ) {
			if ($arena instanceof MapArenaModel) {
				if ($arena->published) {
					$publishedArenas [$arena->name] = $arena;
				}
			}
		}
		return $publishedArenas;
	}
	private function getLevelMap($levelmaps) {
		$mapvotes = [ ];
		$noVote = true;
		$arena = null;
		$arenaName = null;
		
		foreach ( $levelmaps as $mapname ) {
			if (isset ( $this->filterPublishedArenas ()[$mapname] )) {
				$arena = $this->filterPublishedArenas ()[$mapname];
				if ($arena->vote > 0) {
					$noVote = false;
				}
				$mapvotes [$arena->name] = $arena->vote;
			}
		}
		if ($noVote) {
			$keys = array_keys ( $mapvotes );
			array_rand ( $keys );
			$arenaName = array_shift ( $keys );
			$this->plugin->log ( "[HG] random picked map:" . $arenaName );
		} else {
			arsort ( $mapvotes );
			$keys = array_keys ( $mapvotes );
			$arenaName = array_shift ( $keys );
		}
		$arena = $this->plugin->getArenaByName ( $arenaName );
		$this->plugin->log ( "[HG] selected map: " . $arenaName );
		return $arena != null ? clone ($arena) : null;
	}
}
