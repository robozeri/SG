<?php

namespace hungergames\main;

use hungergames\arena\MapArenaListener;
use hungergames\arena\MapArenaManager;
use hungergames\level\GameLevelListener;
use hungergames\level\GameLevelManager;
use hungergames\portal\MapPortal;
use hungergames\portal\MapPortalManager;
use hungergames\score\ScoreListener;
use hungergames\statue\StatueListener;
use hungergames\statue\StatueManager;
use hungergames\task\HungerGamesScoreBoardTask;
use hungergames\task\HungerGamesWorldEventTask;
use pocketmine\command\Command;
use pocketmine\command\CommandExecutor;
use pocketmine\command\CommandSender;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use hungergames\task\HungerGamesStateMachineTask;
use hungergames\task\HungerGamesPortalResetTask;
use hungergames\level\GameLevelModel;
use hungergames\profile\PlayerProfileProvider;
use hungergames\profile\PlayerStoryProvider;

class HungerGamesPlugIn extends PluginBase implements CommandExecutor {
	public $version;
	public $config;
	public $commandHandler;
	// helpers;
	public $arenaManager;
	public $portalManager;
	public $gameLevelManager;
	public $statueManager;
	public $profileManager;
	public $gamekitManager;
	public $storyManager;
	// main
	public $hubLevel;
	public $hubLevelName;
	public $hubSpawnPos;
	public $vipLevel;
	public $vipLevelName;
	public $vipSpawnPos;
	public $vipSignPos;
	public $vipExitSignPos;
	public $hubLobbyParkourPos;
	public $hubAdminPanelPos;
	public $hubAdminVIPPanelLevelName;
	public $hubAdminVIPPanelPos;
	public $hubAdminPanelLevelName;
	//help
	public $showLobbyHelp = true;
	public $hubLobbyHelpPos;
	public $hubLobbyHelpText = "[N/A]";
	public $hubLobbyHelpTitle = "[N/A]";
	public $firstTimeWelcomeText = "";
	public $enableTimeWelcomeText = true;

	public $enableWelcomeBack = true;
	public $welcomeBackText1 = "";
	public $welcomeBackText2 = "";
	
	public $hubHallOfFramePos;
	public $hubHallOfFrameSignExitPos;
	public $hubHallOfFrameSignTitlePos;
	//control
	public $alwayspawnToHub = false;
	public $vipenforceaccess = true;
	public $storyenforceaccess = true;
	public $blockhud = false;
	public $chestrefillcycle = 30;
	
	/**
	 * OnLoad
	 * (non-PHPdoc)
	 *
	 * @see \pocketmine\plugin\PluginBase::onLoad()
	 */
	public function onLoad() {
		$this->commandHandler = new HungerGameCommand ( $this );
	}
	
	/**
	 * OnEnable
	 *
	 * (non-PHPdoc)
	 *
	 * @see \pocketmine\plugin\PluginBase::onEnable()
	 */
	public function onEnable() {
		$this->registerConfigFile ();
		$this->enabled = true;
		$this->registerListeners ();
		$this->registerHelpers ();
		$this->registerScheduler ();
		$this->registerGameLobby ();
		$this->log ( TextFormat::GREEN . "HungerGames - Enabled!" );
	}
	private function registerListeners() {
		$this->getServer ()->getPluginManager ()->registerEvents ( new PlayerWelcomeListener ( $this ), $this );
		$this->getServer ()->getPluginManager ()->registerEvents ( new PlayerLobbyListener ( $this ), $this );
		$this->getServer ()->getPluginManager ()->registerEvents ( new StatueListener ( $this ), $this );
		$this->getServer ()->getPluginManager ()->registerEvents ( new MapArenaListener ( $this ), $this );
		$this->getServer ()->getPluginManager ()->registerEvents ( new GameLevelListener ( $this ), $this );
		$this->getServer ()->getPluginManager ()->registerEvents ( new ScoreListener ( $this ), $this );
		$this->getServer ()->getPluginManager ()->registerEvents ( new VIPPlayerListener ( $this ), $this );
	}
	private function registerScheduler() {
		$event_cycle = $this->getConfig()->get("runtime_event_cycle",360);
		$this->getServer ()->getScheduler ()->scheduleRepeatingTask ( new HungerGamesWorldEventTask ( $this ), $event_cycle );
		$flow_cycle = $this->getConfig()->get("runtime_flow_cycle",60);
		$this->getServer ()->getScheduler ()->scheduleRepeatingTask ( new HungerGamesStateMachineTask ( $this ), $flow_cycle );
		$score_cycle = $this->getConfig()->get("runtime_score_cycle",180);
		$this->getServer ()->getScheduler ()->scheduleRepeatingTask ( new HungerGamesScoreBoardTask ( $this ), $score_cycle );
		$this->info("runtime_event_cycle : ".$event_cycle);
		$this->info("runtime_flow_cycle : ".$flow_cycle);
		$this->info("runtime_score_cycle : ". $score_cycle);
	}
	
	private function registerHelpers() {
		$this->portalManager = new MapPortalManager ( $this );
		$this->portalManager->preloadPortals ();		
		$this->statueManager = new StatueManager ( $this );
		$this->statueManager->loadStatues ();		
		$this->gameLevelManager = new GameLevelManager ( $this );
		$this->gameLevelManager->preloadArenas ();		
		$this->arenaManager = new MapArenaManager ( $this );
		$this->arenaManager->preloadArenas ();		
		$this->gamekitManager = new HungerGameKit ( $this );
		$this->gamekitManager->initlize ();		
		$this->profileManager = new PlayerProfileProvider ( $this );
		$this->profileManager->initlize ();		
		$this->storyManager = new PlayerStoryProvider ( $this );
		$this->storyManager->initlize ();
	}
	
	private function registerConfigFile() {
		try {
			$this->saveDefaultConfig ();
			if (! file_exists ( $this->getDataFolder () )) {
				@mkdir ( $this->getDataFolder (), 0755, true );
				file_put_contents ( $this->getDataFolder () . "config.yml", $this->getResource ( "config.yml" ) );
			}
			$this->reloadConfig ();
			$this->getConfig ()->getAll ();
		} catch ( \Exception $e ) {
			$this->getLogger ()->error ( $e->getMessage () );
		}
	}
	private function registerGameLobby() {
		try {
			$this->getConfig ()->getAll ();
			$x = $this->getConfig ()->get ( "hg_lobby_spawn_x" );
			$y = $this->getConfig ()->get ( "hg_lobby_spawn_y" );
			$z = $this->getConfig ()->get ( "hg_lobby_spawn_z" );
			$levelname = $this->getConfig ()->get ( "hg_lobby_levelname" );
			
			Server::getInstance ()->loadLevel ( $levelname );
			$hubLevel = Server::getInstance ()->getLevelByName ( $levelname );
			
			if (! empty ( $x ) && ! empty ( $y ) && ! empty ( $z ) && ! empty ( $hubLevel )) {
				$this->hubLevel = $hubLevel;
				$this->hubSpawnPos = new Position ( $x, $y, $z, $hubLevel );
				$this->hubLevelName = $levelname;
			} else {
				$this->info ( TextFormat::RED . "[HG] Warning! missing HG lobby configuration. required immediate attentions." );
			}
			// hall of frame
			$x = $this->getConfig ()->get ( "hg_lobby_hof_x" );
			$y = $this->getConfig ()->get ( "hg_lobby_hof_y" );
			$z = $this->getConfig ()->get ( "hg_lobby_hof_z" );
			if (! empty ( $x ) && ! empty ( $y ) && ! empty ( $z ) && ! empty ( $hubLevel )) {
				$this->hubHallOfFramePos = new Position ( $x, $y, $z, $hubLevel );
			} else {
				$this->info ( TextFormat::RED . "[HG] Warning! missing Hall of Frame Configuration. required immediate attentions." );
			}
			
			$x = $this->getConfig ()->get ( "hg_lobby_hof_sign_exit_x" );
			$y = $this->getConfig ()->get ( "hg_lobby_hof_sign_exit_y" );
			$z = $this->getConfig ()->get ( "hg_lobby_hof_sign_exit_z" );
			if (! empty ( $x ) && ! empty ( $y ) && ! empty ( $z ) && ! empty ( $hubLevel )) {
				$this->hubHallOfFrameSignExitPos = new Position ( $x, $y, $z, $hubLevel );
			} else {
				$this->info ( TextFormat::RED . "[HG] Warning! missing Hall of Frame Exit Sign Configuration. required immediate attentions." );
			}
			
			$x = $this->getConfig ()->get ( "hg_lobby_hof_sign_title_x" );
			$y = $this->getConfig ()->get ( "hg_lobby_hof_sign_title_y" );
			$z = $this->getConfig ()->get ( "hg_lobby_hof_sign_title_z" );
			if (! empty ( $x ) && ! empty ( $y ) && ! empty ( $z ) && ! empty ( $hubLevel )) {
				$this->hubHallOfFrameSignTitlePos = new Position ( $x, $y, $z, $hubLevel );
			} else {
				$this->info ( TextFormat::RED . "[HG] Warning! missing Hall of Frame Title Sign Configuration. required immediate attentions." );
			}			
			// vip
			$x = $this->getConfig ()->get ( "hg_vip_lodge_x" );
			$y = $this->getConfig ()->get ( "hg_vip_lodge_y" );
			$z = $this->getConfig ()->get ( "hg_vip_lodge_z" );
			if (! empty ( $x ) && ! empty ( $y ) && ! empty ( $z ) && ! empty ( $hubLevel )) {
				$this->hubHallOfFrameSignTitlePos = new Position ( $x, $y, $z, $hubLevel );
			} else {
				$this->info ( TextFormat::RED . "[HG] Warning! missing HG VIP Lodge Sign Configuration. required immediate attentions." );
			}
			// vip lodge
			Server::getInstance ()->loadLevel ( $levelname );
			$vipLevel = Server::getInstance ()->getLevelByName ( $levelname );
			$this->vipLevelName = $this->getConfig ()->get ( "hg_vip_lodge_levelname" );
			$this->vipSpawnPos = new Position ( $x, $y, $z, $vipLevel );
			$this->vipLevel = $vipLevel;
			
			$x = $this->getConfig ()->get ( "hg_vip_enter_x" );
			$y = $this->getConfig ()->get ( "hg_vip_enter_y" );
			$z = $this->getConfig ()->get ( "hg_vip_enter_z" );
			$this->vipSignPos = new Position ( $x, $y, $z, $hubLevel );
			
			$x = $this->getConfig ()->get ( "hg_vip_exit_x" );
			$y = $this->getConfig ()->get ( "hg_vip_exit_y" );
			$z = $this->getConfig ()->get ( "hg_vip_exit_z" );
			$this->vipExitSignPos = new Position ( $x, $y, $z, $vipLevel );
			
			// admin room
			$this->hubAdminPanelLevelName = $this->getConfig ()->get ( "hg_admin_enter_levelname" );
			Server::getInstance ()->loadLevel ( $this->hubAdminPanelLevelName );
			$adminlevel = Server::getInstance ()->getLevelByName ( $this->hubAdminPanelLevelName );
			$x = $this->getConfig ()->get ( "hg_admin_enter_x" );
			$y = $this->getConfig ()->get ( "hg_admin_enter_y" );
			$z = $this->getConfig ()->get ( "hg_admin_enter_z" );
			if (! empty ( $x ) && ! empty ( $y ) && ! empty ( $z )) {
				$this->hubAdminPanelPos = new Position ( $x, $y, $z, $adminlevel );
			}
			unset ( $adminlevel );
			
			// vip admin room
			$this->hubAdminVIPPanelLevelName = $this->getConfig ()->get ( "hg_admin_vip_enter_levelname" );
			Server::getInstance ()->loadLevel ( $this->hubAdminVIPPanelLevelName );
			$adminlevel = Server::getInstance ()->getLevelByName ( $this->hubAdminVIPPanelLevelName );
			$x = $this->getConfig ()->get ( "hg_admin_vip_enter_x" );
			$y = $this->getConfig ()->get ( "hg_admin_vip_enter_y" );
			$z = $this->getConfig ()->get ( "hg_admin_vip_enter_z" );
			if (! empty ( $x ) && ! empty ( $y ) && ! empty ( $z )) {
				$this->hubAdminVIPPanelPos = new Position ( $x, $y, $z, $adminlevel );
			}
			// lobby parkour
			$x = $this->getConfig ()->get ( "hg_lobby_parkour_x" );
			$y = $this->getConfig ()->get ( "hg_lobby_parkour_y" );
			$z = $this->getConfig ()->get ( "hg_lobby_parkour_z" );
			if (! empty ( $x ) && ! empty ( $y ) && ! empty ( $z )) {
				$this->hubLobbyParkourPos = new Position ( $x, $y, $z, $hubLevel );
			}
			// enforce vip access
			$this->vipenforceaccess = $this->getConfig ()->get ( "enforce_vip_access", true );
			// enforce game play
			$this->storyenforceaccess = $this->getConfig ()->get ( "enforce_story_level_access", true );			
			// help text toggle
			$this->showLobbyHelp = $this->getConfig ()->get ( "show_lobby_help", true );
			
			$x = $this->getConfig ()->get ( "hg_lobby_help_x" );
			$y = $this->getConfig ()->get ( "hg_lobby_help_y" );
			$z = $this->getConfig ()->get ( "hg_lobby_help_z" );
			if (! empty ( $x ) && ! empty ( $y ) && ! empty ( $z )) {
				$this->hubLobbyHelpPos = new Position ( $x, $y, $z, $hubLevel );
			}
			// grab text
			$this->hubLobbyHelpText = $this->getLobbyHelpText ();
			$this->hubLobbyHelpTitle = $this->getConfig ()->get ( "hg_lobby_help_title", "[N/A]" );
			
			$this->version = $this->getConfig ()->get ( "version","The Hunger Games-v2");	
			$this->chestrefillcycle = $this->getConfig()->get("runtime_chest_refill_cycle",30);
			
			// first timer welcome text toggle
			$this->enableTimeWelcomeText = $this->getConfig ()->get ( "enable_first_time_msg", true );
			$this->firstTimeWelcomeText = $this->getFirstTimerWelcomeText();
			// welcome back
			$this->enableWelcomeBack = $this->getConfig ()->get ( "enable_welcome_back_msg", true );
			$this->welcomeBackText1 = $this->getConfig ()->get ( "hg_welcome_back1", "" );
			$this->welcomeBackText2 = $this->getConfig ()->get ( "hg_welcome_back2", "" );
			
		} catch ( \Exception $e ) {
			$this->printError ( $e );
		}
	}
	public function getLobbyHelpText() {
		$title = $this->getConfig ()->get ( "hg_lobby_help_title" );
		$exit = $this->getConfig ()->get ( "hg_lobby_help_text_exit" );
		$help = $this->getConfig ()->get ( "hg_lobby_help_text_help" );
		$scores = $this->getConfig ()->get ( "hg_lobby_help_text_scores" );
		$rank = $this->getConfig ()->get ( "hg_lobby_help_text_rank" );
		$stats = $this->getConfig ()->get ( "hg_lobby_help_text_stats" );
		$balance = $this->getConfig ()->get ( "hg_lobby_help_text_balance" );
		$vip = $this->getConfig ()->get ( "hg_lobby_help_text_vip" );
		$parkour = $this->getConfig ()->get ( "hg_lobby_help_text_parkour" );
		$profile = $this->getConfig ()->get ( "hg_lobby_help_text_profile" );
		$players = $this->getConfig ()->get ( "hg_lobby_help_text_players" );
		$topwins = $this->getConfig ()->get ( "hg_lobby_help_text_topwins" );
		
		$text = TextFormat::WHITE . $exit . "\n" . TextFormat::RED . $vip . "\n" . TextFormat::GOLD . $parkour . "\n" . TextFormat::WHITE . $scores . "\n" . TextFormat::GOLD . $rank . "\n" . TextFormat::GREEN . $topwins . "\n" . TextFormat::WHITE . $stats . "\n" . TextFormat::GOLD . $balance . "\n" . TextFormat::WHITE . $profile . "\n" . TextFormat::GOLD . $players . "\n" . TextFormat::WHITE . $help;
		return $text;
	}
	
	public function getFirstTimerWelcomeText() {
		$msg1 = $this->getConfig ()->get ( "hg_welcome_msg1" );
		$msg2 = $this->getConfig ()->get ( "hg_welcome_msg2" );
		$msg3 = $this->getConfig ()->get ( "hg_welcome_msg3" );
		$msg4 = $this->getConfig ()->get ( "hg_welcome_msg4" );
		$msg5 = $this->getConfig ()->get ( "hg_welcome_msg5" );
		$msg6 = $this->getConfig ()->get ( "hg_welcome_msg6" );
		$text = TextFormat::BOLD.TextFormat::RED . $msg1 . "\n" . TextFormat::WHITE . $msg2 . "\n" . TextFormat::GRAY . $msg3 . "\n" . TextFormat::WHITE . $msg4 . "\n" . TextFormat::GRAY . $msg5 . "\n" . TextFormat::GREEN . $msg6 ;
		return $text;
	}
	
	// navigation API
	public function isOnSpawnTeleportPlayerToLobby() {
		return $this->getConfig ()->get ( "on_spawn_tp_player_to_lobby", true );
	}
	public function isOnJoinTeleportPlayerToLobby() {
		return $this->getConfig ()->get ( "on_join_tp_player_to_lobby", true );
	}
	public function isOnJoinClearAllPlayerInventory() {
		return $this->getConfig ()->get ( "on_join_clear_player_inventory", true );
	}
	public function isOnJoinClearAllPlayerEffects() {
		return $this->getConfig ()->get ( "on_join_clear_player_effects", true );
	}
	public function isAllowBlockBreakInGameMap() {
		return $this->getConfig ()->get ( "allow_in_game_map_break_block", true );
	}
	public function setGameDefaultPermissionNode(Player $player) {
		$player->addAttachment ( $this, "plugin.hungergames", true );
		$player->addAttachment ( $this, "pocketmine.broadcast.user", true );
		$player->addAttachment ( $this, "pocketmine.broadcast.admin", true );
	}
	public function getLevelManager() {
		return $this->gameLevelManager;
	}
	public function getAvailableLevels() {
		return $this->gameLevelManager->levels;
	}
	public function getArenaManager() {
		return $this->arenaManager;
	}
	public function getStatueManager() {
		return $this->statueManager;
	}
	public function getAvailableArenas() {
		return $this->arenaManager->arenas;
	}
	public function getArenaByName($name) {
		return $this->arenaManager->arenas [$name];
	}
	public function onDisable() {
		$this->enabled = false;
		$this->log ( TextFormat::RED . "HungerGames" );
	}
	
	/**
	 * OnCommand
	 * (non-PHPdoc)
	 *
	 * @see \pocketmine\plugin\PluginBase::onCommand()
	 * @param CommandSender $sender        	
	 * @param Command $command        	
	 * @param string $label        	
	 * @param array $args        	
	 * @return bool|void
	 * @internal param $ $label* $label
	 */
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
		$this->commandHandler->onCommand ( $sender, $command, $label, $args );
	}
	public function openGate(GameLevelModel $lv) {
		$this->getServer ()->getScheduler ()->scheduleDelayedTask ( new HungerGamesPortalResetTask ( $this, $lv, "open" ), mt_rand ( 3, 10 ) );		
	}
	public function closeGate(GameLevelModel $lv) {
		$this->getServer ()->getScheduler ()->scheduleDelayedTask ( new HungerGamesPortalResetTask ( $this, $lv, "close" ), mt_rand ( 3, 8 ) );
	}
		
	// common Logging APIs
	public function log($msg) {
		$this->getLogger ()->debug ( $msg );
	}
	public function printError(\Exception $e) {
		$message = "[HG-Error] " . $e->getMessage () . " : " . $e->getCode () . " | line# " . $e->getLine () . "| \n Trace: [" . $e->getTraceAsString () . "]";
		$this->getLogger ()->info($message );
	}
	public function info($msg) {
		$this->getLogger ()->info ( $msg );
	}
}
