<?php

namespace hungergames\main;

use hungergames\portal\MapPortal;
use hungergames\arena\MapArenaModel;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use pocketmine\level\Position;



class HungerGameCommand {
	private $plugin;
	
	/**
	 *
	 * @param HungerGamesPlugIn $pg        	
	 */
	public function __construct(HungerGamesPlugIn $pg) {
		$this->plugin = $pg;
	}
	public function showPlayerHelpCommands(CommandSender $sender) {
		$sender->sendMessage ( TextFormat::RED . "hg [player commands]" );
		$sender->sendMessage ( TextFormat::BLUE . "-----------------------" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg lobby     - teleport player to lobby" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg exit      - teleport player to lobby" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg vip       - teleport player to VIP lodge" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg lodge     - teleport player to VIP lodge" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg parkour   - teleport player to lobby parkour" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg players   - display players count" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg toprank   - list top 3 players " );
		$sender->sendMessage ( TextFormat::GREEN . "/hg mywins    - list my game wins" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg mypoints  - list my game points" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg mybalance - show my current balance" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg myprofile - show my balance, wins and loss" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg help      - list command available to players" );
		$sender->sendMessage ( TextFormat::BLUE . "----------------------" );
	}
	public function showAdminHelpCommands(CommandSender $sender) {
		$sender->sendMessage ( TextFormat::RED . "hg [admin Commands]" );
		$sender->sendMessage ( TextFormat::BLUE . "-----------------------" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg addvip [player] - add player as VIP list " );
		$sender->sendMessage ( TextFormat::GREEN . "/hg delvip [player] - remove player from VIP list" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg getvip [player] - retrieve VIP player profile" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg setbalance [player] [amount]  - set player balance" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg createprofile [player] - manually create a player profile" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg setserverlobby  - set server lobby use player current location" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg setserverlobby  - set game lobby use player current location" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg blockon         - enable block location display" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg blockoff        - disable block location display" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg xyz             - show player current location" );
		$sender->sendMessage ( TextFormat::BLUE . "----------------------" );
	}
	public function showAdminMapHelpCommands(CommandSender $sender) {
		$sender->sendMessage ( TextFormat::RED . "hg [admin Map Help Commands]" );
		$sender->sendMessage ( TextFormat::BLUE . "-----------------------" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg admin       - teleport admin to arena admin panel" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg vipadmin    - teleport admin to VIP arean admin panel" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg tpa [arena] - teleport admin to arena map" );
		$sender->sendMessage ( TextFormat::BLUE . "----------------------" );
	}
	public function showAdminLevelSetupHelpCommands(CommandSender $sender) {
		$sender->sendMessage ( TextFormat::RED . "hg [admin Level Setup Commands]" );
		$sender->sendMessage ( TextFormat::BLUE . "-----------------------" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg levelwand [level] - launch level setup wand for pos#1 and pos#2" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg setlevelpos1 [level] - manually set level portal pos#1" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg setlevelpos2 [level] - manually set level portal pos#2" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg levelgatewand [level] - launch level portal gate setup wand" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg setlevelgatepos1 [level] - manually create a player profile" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg setlevelgatepos2 [level] - set server lobby use player current location" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg setserverlobby  - set game lobby use player current location" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg setlevelenter   - set level portal entrance position" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg setlevelexit    - set level portal exit position" );
		$sender->sendMessage ( TextFormat::BLUE . "----------------------" );
	}
	public function showAdminArenaSetupHelpCommands(CommandSender $sender) {
		$sender->sendMessage ( TextFormat::RED . "hg [admin Arena Setup Commands]" );
		$sender->sendMessage ( TextFormat::BLUE . "-----------------------" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg newarena [arena name] - create a new arena record" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg arenawand [arena] - launch a new arena setup wand for pos#1 and pos#2" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg newarena [arena]  - create a new arena record" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg setarenapos1 [arena]  - manually set level portal pos#1 using player current position" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg setarenapos2 [arena]  - manually set level portal pos#2 using player current position" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg setarenaenter [arena] - set arena entrance position" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg setarenaexit  [arena] - set Arena exit position" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg setarenawall  [arena] - set Arena walls" );
		// spawn points
		$sender->sendMessage ( TextFormat::GREEN . "/hg startspawnwand [arena] - enable picking of player spawn positions" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg stopspawnwand          - disable picking of player spawn positions" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg setarenaspawn [arena]  - add player spawn position using player current location" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg clearspawns [arena]  - remove all player spawn positions from arena" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg listspawns [arena]  - show all player spawn positions from arena" );
		// death match
		$sender->sendMessage ( TextFormat::GREEN . "/hg matchwand [arena] - launch death-match arena setup wand" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg setmatchpos1 [arena] - manually set death-match position#1" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg setmatchpos2 [arena] - manually set death-match position#2" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg setmatchenter [arena] - set death-match arena entrance position" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg setmatchwall [arena] - set death-match arena walls" );
		
		// arena must assing to levels
		$sender->sendMessage ( TextFormat::GREEN . "/hg addlevelarena [arena] - add arena to level map list" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg dellevelarena [arena] - remove arena from level map list" );
		// arena must publish
		$sender->sendMessage ( TextFormat::GREEN . "/hg publish [arena] - publish arena to make it available to players" );
		$sender->sendMessage ( TextFormat::GREEN . "/hg unpublish [arena] - unpublish arena from play list" );
		$sender->sendMessage ( TextFormat::BLUE . "----------------------" );
	}
	
	/**
	 * onCommand
	 *
	 * @param CommandSender $sender        	
	 * @param Command $command        	
	 * @param unknown $label        	
	 * @param array $args        	
	 * @return boolean
	 */
	public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
        $output =  TextFormat::GRAY . $this->plugin->version."\n" ;
        $output.=  TextFormat::GRAY . "--------------------------------------\n";
		if ((strtolower ( $command->getName () ) === "hg") && isset ( $args [0] ) || ($command->getName () === "hg2")) {
			/**
			 * PLAYER COMMANDS
			 */
			if (strtolower ( $args [0] ) == "help") {
				$this->showPlayerHelpCommands ( $sender );
				return true;
			}
			
			if (strtolower ( $args [0] ) == "adminhelp") {
				if (! $sender->isOp ()) {
					$output = TextFormat::YELLOW . "You are not authorized to run this command.\n";
					$sender->sendMessage ( $output );
					return;
				}
				$this->showAdminHelpCommands ( $sender );
				return true;
			}
			
			if (strtolower ( $args [0] ) == "maphelp") {
				if (! $sender->isOp ()) {
					$output = TextFormat::YELLOW . "You are not authorized to run this command.\n";
					$sender->sendMessage ( $output );
					return;
				}
				$this->showAdminMapHelpCommands ( $sender );
				return true;
			}
			
			if (strtolower ( $args [0] ) == "levelhelp") {
				if (! $sender->isOp ()) {
					$output = TextFormat::YELLOW . "You are not authorized to run this command.\n";
					$sender->sendMessage ( $output );
					return;
				}
				$this->showAdminLevelSetupHelpCommands ( $sender );
				return true;
			}
			
			if (strtolower ( $args [0] ) == "arenahelp") {
				if (! $sender->isOp ()) {
					$output = TextFormat::YELLOW . "You are not authorized to run this command.\n";
					$sender->sendMessage ( $output );
					return;
				}
				$this->showAdminArenaSetupHelpCommands ( $sender );
				return true;
			}
			
			if (strtolower ( $args [0] ) === "parkour") {
				if ($sender instanceof Player) {
					if ($sender->getLevel ()->getName () != $this->plugin->hubLevelName) {
						$sender->sendMessage ( "[HG] You are not at Lobby!" );
						return;
					}
					if (! empty ( $this->plugin->hubLobbyParkourPos )) {
						$sender->sendMessage ( TextFormat::GRAY . "[HG] TP to Parkour..." );
						$sender->teleport ( $this->plugin->hubLobbyParkourPos );
					}
				}
				return;
			}
			
			if (strtolower ( $args [0] ) === "players") {
				if ($sender instanceof Player) {
					$sender->sendMessage ( "[HG] online players " . TextFormat::AQUA.count ( $this->plugin->getServer ()->getOnlinePlayers ()));
				}
				return;
			}
			
			// player lobby
			if (strtolower ( $args [0] ) === "exit" || strtolower ( $args [0] ) === "lobby" || strtolower ( $args [0] ) === "home") {
				$sender->sendMessage ( TextFormat::GRAY . "[HG] TP to Lobby..." );
				if ($sender instanceof Player) {
					if ($sender instanceof Player) {
						MapPortal::teleportingToLobby ( $sender, $this->plugin->hubLevelName, $this->plugin->hubSpawnPos );
						$this->plugin->gameLevelManager->handlePlayerLeaveTheGame ( $sender );
					}
				}
				return;
			}
			
			// player VIP+ lobby
			if (strtolower ( $args [0] ) === "vip" || strtolower ( $args [0] ) === "viplodge" || strtolower ( $args [0] ) === "lodge") {
				if ($sender instanceof Player) {
					if ($sender instanceof Player) {
						if ($this->plugin->vipenforceaccess) {
							$vip = $this->plugin->profileManager->isPlayerVIP ( $sender->getName () );
							if (! $vip) {
								$sender->sendMessage ( TextFormat::YELLOW . "[HG] Required" . TextFormat::RED . " VIP+ " . TextFormat::YELLOW . "membership!" );
								return;
							}
							$sender->sendMessage ( TextFormat::GRAY . "[HG] TP to VIP+ lodge..." );
							MapPortal::teleportingToLobby ( $sender, $this->plugin->vipLevelName, $this->plugin->vipSpawnPos );
							$this->plugin->gameLevelManager->handlePlayerLeaveTheGame ( $sender );
						} else {
							$sender->sendMessage ( TextFormat::GRAY . "[HG] TP to VIP+ lodge..." );
							MapPortal::teleportingToLobby ( $sender, $this->plugin->vipLevelName, $this->plugin->vipSpawnPos );
							$this->plugin->gameLevelManager->handlePlayerLeaveTheGame ( $sender );
						}
					}
				}
				return;
			}
			
			if (strtolower ( $args [0] ) === "mybalance") {
				$data = $this->plugin->profileManager->retrievePlayerBalance ( $sender->getName () );
				if ($data == null || count ( $data ) === 0) {
					$sender->sendMessage ( TextFormat::GRAY . "[HG] No profile found" );
				} else {
					$sender->sendMessage ( TextFormat::WHITE . "[HG] My balance is " . TextFormat::GOLD . $data [0] ["balance"] . TextFormat::WHITE . " coins." );
				}
				return;
			}
			
			if (strtolower ( $args [0] ) === "toprank") {
				$topWinners = $this->plugin->profileManager->retrieveTopPlayers ();
				if (is_null ( $topWinners )) {
					$sender->sendMessage ( TextFormat::GRAY . "[HG] No record found!" );
					return;
				}
				$message= TextFormat::BOLD.TextFormat::DARK_RED."HALL OF FAME\n";
				$message= TextFormat::BOLD.TextFormat::AQUA."BEST OF THE BEST\n";
				if (count ( $topWinners ) == 1) {
					$goldPlayerName = $topWinners [0] ["pname"];
					$goldPlayerWins = $topWinners [0] ["wins"];
					$message .= TextFormat::GOLD . "Top #1 ";
					$message .= TextFormat::GOLD . " ".$goldPlayerWins .TextFormat::WHITE." Wins " . TextFormat::LIGHT_PURPLE.$goldPlayerName;
					$sender->sendMessage ( $message );
				} elseif (count ( $topWinners ) == 2) {
					$goldPlayerName = $topWinners [0] ["pname"];
					$goldPlayerWins = $topWinners [0] ["wins"];
					$silverPlayerName = $topWinners [1] ["pname"];
					$silverPlayerWins = $topWinners [1] ["wins"];
					
					$message .= TextFormat::GOLD . "Top #1 ";
					$message .= TextFormat::GOLD . " ".$goldPlayerWins .TextFormat::WHITE." Wins " . TextFormat::LIGHT_PURPLE.$goldPlayerName;
					$message .= TextFormat::GOLD . "\nTop #2 ";
					$message .= TextFormat::GOLD . " ".$silverPlayerWins .TextFormat::WHITE." Wins " . TextFormat::LIGHT_PURPLE.$silverPlayerName;
					$sender->sendMessage ( $message );
				} elseif (count ( $topWinners ) == 3) {
					$goldPlayerName = $topWinners [0] ["pname"];
					$goldPlayerWins = $topWinners [0] ["wins"];
					$silverPlayerName = $topWinners [1] ["pname"];
					$silverPlayerWins = $topWinners [1] ["wins"];
					$brownsePlayerName = $topWinners [2] ["pname"];
					$brownsePlayerNameWins = $topWinners [2] ["wins"];
					
					$message .= TextFormat::GOLD . "Top #1 ";
					$message .= TextFormat::GOLD . " ".$goldPlayerWins .TextFormat::WHITE." Wins " . TextFormat::LIGHT_PURPLE.$goldPlayerName;
					$message .= TextFormat::GOLD . "\nTop #2 ";
					$message .= TextFormat::GOLD . " ".$silverPlayerWins .TextFormat::WHITE." Wins " . TextFormat::LIGHT_PURPLE.$silverPlayerName;
					$message .= TextFormat::GOLD . "\nTop #3 ";
					$message .= TextFormat::GOLD . " ".$brownsePlayerNameWins .TextFormat::WHITE." Wins " . TextFormat::LIGHT_PURPLE.$brownsePlayerName;
					$sender->sendMessage ( $message );
				}
				
				$data = $this->plugin->profileManager->retrievePlayerStats ( $sender->getName () );
				if (count ( $data ) > 0) {
					$output =TextFormat::GREEN."\nMy Wins: " . $data [0] ["wins"];
					$sender->sendMessage ( $output );
				}
				return;
			}
					
			if (strtolower ( $args [0] ) === "myprofile") {
				$data = $this->plugin->profileManager->retrievePlayerByName ( $sender->getName () );
				if ($data == null || count ( $data ) == 0) {
					$output = "[HG] No profile found";
				} else {
					$output = "";
					$output .= TextFormat::BOLD."[HG] Profile Information> \n";
					$output .= "     balance: " . $data [0] ["balance"] . "\n";
					$output .= "     wins: " . $data [0] ["wins"] . "\n";
					$output .= "     loss: " . $data [0] ["loss"] . "\n";
				}
				$sender->sendMessage ( TextFormat::GRAY . $output );
				return;
			}
			
			if (strtolower ( $args [0] ) === "mywins") {
// 				$data = $this->plugin->storyManager->retrievePlayerWinsByLevelMap ( $sender->getName () );
// 				if ($data == null || count ( $data ) == 0) {
// 					$output = "[HG] No level record found";
// 				} else {
// 					$output= TextFormat::BOLD.TextFormat::AQUA."MY WINS BY LEVEL\n";
// 					$output .= TextFormat::GRAY ."| level | wins | loss | points | map  |\n";
// 					foreach ( $data as $record ) {
// 						$output.="| ";
// 						$output .= TextFormat::GREEN .$record ["level"] . TextFormat::GRAY . " | ";
// 						$output .= TextFormat::GOLD .$record ["wins"] . TextFormat::GRAY . " | ";
// 						$output .= TextFormat::WHITE .$record ["loss"] . TextFormat::GRAY . " | ";
// 						$output .= TextFormat::BLUE . $record ["points"] .TextFormat::GRAY." | ";						
// 						$output .= TextFormat::ITALIC.TextFormat::WHITE .$record ["map"] . TextFormat::GRAY . " \n";
// 					}
// 					$output .= TextFormat::GRAY . "\n";
// 				}
// 				$sender->sendMessage ( TextFormat::GRAY . $output );
				$this->showMyWins($sender, $args);
 				return;
			}
			
			if (strtolower ( $args [0] ) === "mypoints") {
				$data = $this->plugin->storyManager->retrievePlayerWinsByPoints ( $sender->getName () );
				if ($data == null || count ( $data ) == 0) {
					$output = "[HG] No level record found";
				} else {
					$output= TextFormat::BOLD.TextFormat::AQUA."MY POINTS BY MAP\n";
					$output .= TextFormat::GRAY ."| level | wins | loss | points | map  |\n";
					foreach ( $data as $record ) {
						$output.= "| ";
						$output .= TextFormat::WHITE .$record ["level"] . TextFormat::GRAY . " | ";
						$output .= TextFormat::BLUE.$record ["wins"] . TextFormat::GRAY . " | ";
						$output .= TextFormat::WHITE .$record ["loss"] . TextFormat::GRAY . " | ";
						$output .= TextFormat::GOLD . $record ["points"] .TextFormat::GRAY. " | ";
						$output .= TextFormat::UNDERLINE.TextFormat::GREEN .$record ["map"] . TextFormat::GRAY . " \n";
					}
					$output .= TextFormat::GRAY . "\n";
				}
				$sender->sendMessage ( TextFormat::GRAY . $output );
				return;
			}			
			
			if (strtolower ( $args [0] ) === "topwins") {
				if (count($args)!=2) {
					$message="[HG]-Usage: /hg topwins [level|map]";
					$sender->sendMessage($message);
					return;
				}
				if ($args[1]!="level" && $args[1]!="map") {
					$message="[HG] Usage: /hg topwins [level|map]";
					$sender->sendMessage($message);
					return;					
				}
				$data=[];
				$output="";
				if ($args[1]==="level") {
					$output= TextFormat::BOLD.TextFormat::BLUE."TOP WINS BY [LEVEL]\n";
					$data = $this->plugin->storyManager->retrieveTopLevelPlayers();
				}
				if ($args[1]==="map") {
					$output= TextFormat::BOLD.TextFormat::BLUE."TOP WINS BY [MAP]\n";					
					$data = $this->plugin->storyManager->retrieveTopMapPlayers();
				}
				if ($data === null || count ( $data ) === 0) {
					$output = "[HG] No records found";
				} else {
					if ($args[1]==="map") {
					$output .= TextFormat::GRAY ."| level | wins | loss | points | map  | player |\n";
					} else {
						$output .= TextFormat::GRAY . "| level | wins | loss | points | player |\n";						
					}
					foreach ( $data as $record ) {
						$output.="| ";
						if ($args[1]==="map") {
							$output .= TextFormat::WHITE .$record ["level"] . TextFormat::GRAY . " | ";
						} else {
							$output .= TextFormat::GREEN .$record ["level"] . TextFormat::GRAY . " | ";							
						}
						$output .= TextFormat::GOLD .$record ["wins"] . TextFormat::GRAY . " | ";
						$output .= TextFormat::WHITE .$record ["loss"] . TextFormat::GRAY . " | ";
						$output .= TextFormat::WHITE . $record ["points"] . " | ";
												
						if ($args[1]==="map") {
							$output .= TextFormat::GREEN .$record ["map"] . TextFormat::GRAY . " | ";
						} 
						$output .= TextFormat::AQUA . $record ["pname"] . TextFormat::GRAY . " \n";
					}
					$output .= TextFormat::GRAY . "\n";
				}
				$sender->sendMessage ( TextFormat::GRAY . $output );
				return;
			}
									
			/*
			 * MAP ADMINISTRATION
			 */
			
			// player admin panel
			if (strtolower ( $args [0] ) === "adminpanel" || strtolower ( $args [0] ) === "admin") {
				if (! $sender->isOp ()) {
					$output = TextFormat::YELLOW . "You are not authorized to run this command.\n";
					$sender->sendMessage ( $output );
					return;
				}
				$sender->sendMessage ( TextFormat::GRAY . "[HG] TP to admin panel..." );
				if ($sender instanceof Player) {
					MapPortal::teleportingToLobby ( $sender, $this->plugin->hubAdminPanelLevelName, $this->plugin->hubAdminPanelPos );
					$this->plugin->gameLevelManager->handlePlayerLeaveTheGame ( $sender );
				}
				return;
			}
			
			// player admin panel
			if (strtolower ( $args [0] ) === "vipadminpanel" || strtolower ( $args [0] ) === "vipadmin") {
				if (! $sender->isOp ()) {
					$output = TextFormat::YELLOW . "You are not authorized to run this command.\n";
					$sender->sendMessage ( $output );
					return;
				}
				$sender->sendMessage ( TextFormat::GRAY . "[HG] TP to VIP admin panel..." );
				if ($sender instanceof Player) {
					MapPortal::teleportingToLobby ( $sender, $this->plugin->hubAdminVIPPanelLevelName, $this->plugin->hubAdminVIPPanelPos );
					$this->plugin->gameLevelManager->handlePlayerLeaveTheGame ( $sender );
				}
				return;
			}
			// teleport to arenas
			if (strtolower ( $args [0] ) === "tpa") {
				if (! $sender->isOp ()) {
					$output = TextFormat::YELLOW . "You are not authorized to run this command.\n";
					$sender->sendMessage ( $output );
					return;
				}
				$sender->sendMessage ( TextFormat::GRAY . "[HG] TP to arena..." );
				if ($sender instanceof Player) {
					if (count ( $args ) != 2) {
						$sender->sendMessage ( "usage: /hg tpa [arena name]" );
						return;
					}
					if (! isset ( $this->plugin->arenaManager->arenas [$args [1]] )) {
						$sender->sendMessage ( "[HG] Arena name not found: " . $args [1] );
						return;
					}
					$arena = $this->plugin->arenaManager->arenas [$args [1]];
					if (empty ( $arena )) {
						$sender->sendMessage ( "[HG] Arena not found: " . $args [1] );
						return;
					}
					if ($arena instanceof MapArenaModel) {
						if (empty ( $arena->enterLevelName ) || empty ( $arena->arenaEnterPos )) {
							$sender->sendMessage ( "[HG] Missing Arena level configuration: " . $args [1] );
							return;
						}
						$sender->sendMessage ( TextFormat::GRAY . "[HG] TP to VIP+ lodge..." );
						MapPortal::teleportingToLobby ( $sender, $arena->enterLevelName, $arena->arenaEnterPos );
					}
				}
				return;
			}
			
			/*
			 * PLAYER PROFILE ADMINISTRATION
			 */
			
			if (strtolower ( $args [0] ) === "setbalance") {
				if (! $sender->isOp ()) {
					$output = TextFormat::YELLOW . "You are not authorized to run this command.\n";
					$sender->sendMessage ( $output );
					return;
				}
				if (count ( $args ) != 3) {
					$sender->sendMessage ( "usage: /hg setbalance [player name] [amount]" );
					return;
				}
				// check if player exit in the server
				$playerName = $args [1];
				$olp = Server::getInstance ()->getPlayerExact ( $playerName );
				if (empty ( $olp )) {
					$offp = Server::getInstance ()->getOfflinePlayer ( $playerName );
					if (empty ( $offp )) {
						$sender->sendMessage ( TextFormat::YELLOW . "[HG] Player Not Found! "  );
						return;
					}
				}
				$rs = $this->plugin->profileManager->setBalance ( $args [1], $args [2] );
				$sender->sendMessage ( TextFormat::GREEN . "[HG] player [" . $playerName . "] balance updated | " . $rs );
				return;
			}
			
			if (strtolower ( $args [0] ) === "addvip") {
				if (! $sender->isOp ()) {
					$output = TextFormat::YELLOW . "You are not authorized to run this command.\n";
					$sender->sendMessage ( $output );
					return;
				}
				if (count ( $args ) != 2) {
					$sender->sendMessage ( TextFormat::GREEN . "usage: /hg addvip [player name]" );
					return;
				}
				$playerName = $args [1];
				$rs = $this->plugin->profileManager->upsetVIP ( $playerName, "true" );
				$sender->sendMessage ( TextFormat::GREEN . "[HG] VIP [" . TextFormat::GOLD . $playerName . TextFormat::GREEN . "] Added (" . $rs . ")!" );
				return;
			}
			
			if (strtolower ( $args [0] ) === "getvip") {
				if (! $sender->isOp ()) {
					$output = TextFormat::YELLOW . "You are not authorized to run this command.\n";
					$sender->sendMessage ( $output );
					return;
				}
				if (count ( $args ) != 2) {
					$sender->sendMessage ( TextFormat::GREEN . "usage: /hg getvip [player name]" );
					return;
				}
				$playerName = $args [1];
				$data = $this->plugin->profileManager->retrievePlayerVIP ( $playerName );
				if (empty ( $data ) || count ( $data ) === 0) {
					$sender->sendMessage ( TextFormat::YELLOW . "[HG] VIP profile Not Found!" );
					return;
				}
				$output = "";
				$output .= TextFormat::BOLD . "[HG] VIP Player Profile: \n";
				$output .= TextFormat::DARK_GREEN . "- player: " . TextFormat::AQUA . $data [0] ["pname"] . "\n";
				$output .= TextFormat::DARK_GREEN . "- vip: " . TextFormat::GOLD . $data [0] ["vip"] . "\n";
				$output .= TextFormat::DARK_GREEN . "- balance: " . TextFormat::WHITE . $data [0] ["balance"] . "\n";
				$output .= TextFormat::DARK_GREEN . "- wins: " . TextFormat::WHITE . $data [0] ["wins"] . "\n";
				$output .= TextFormat::DARK_GREEN . "- loss: " . TextFormat::WHITE . $data [0] ["loss"] . "\n";
				$output .= TextFormat::DARK_GREEN . "- ludt: " . TextFormat::WHITE . $data [0] ["lupt"] . "\n";
				$sender->sendMessage ( $output );
				return;
			}
			
			if (strtolower ( $args [0] ) === "delvip") {
				if (! $sender->isOp ()) {
					$output = TextFormat::YELLOW . "You are not authorized to run this command.\n";
					$sender->sendMessage ( $output );
					return;
				}
				if (count ( $args ) != 2) {
					$sender->sendMessage ( "usage: /hg delvip [player name]" );
					return;
				}
				// check if player exit in the server
				$playerName = $args [1];
				$olp = Server::getInstance ()->getPlayerExact ( $playerName );
				if (empty ( $olp )) {
					$offp = Server::getInstance ()->getOfflinePlayer ( $playerName );
					if (empty ( $offp )) {
						$sender->sendMessage ( TextFormat::YELLOW . "[HG] Player Not Found! " );
						return;
					}
				}
				
				$rs = $this->plugin->profileManager->upsetVIP ( $playerName, "false" );
				$sender->sendMessage ( TextFormat::GREEN . "[HG] VIP [" . TextFormat::GOLD . $playerName . TextFormat::GREEN . "] delete (" . $rs . ")!" );
				return;
			}
			
			if (strtolower ( $args [0] ) === "createprofile") {
				if (! $sender->isOp ()) {
					$output = TextFormat::YELLOW . "You are not authorized to run this command.\n";
					$sender->sendMessage ( $output );
					return;
				}
				if (count ( $args ) != 2) {
					$sender->sendMessage ( "usage: /hg createprofile [player name]" );
					return;
				}
				$rs = $this->plugin->profileManager->addPlayer ( $args [1] );
				$sender->sendMessage ( TextFormat::GREEN . "[HG] player profile created! " . $rs );
				return;
			}
			
			if (strtolower ( $args [0] ) === "changename") {
				if (! $sender->isOp ()) {
					$output = TextFormat::YELLOW . "You are not authorized to run this command.\n";
					$sender->sendMessage ( $output );
					return;
				}
				if (count ( $args ) != 3) {
					$sender->sendMessage ( "usage: /hg changename [old name] [new name]" );
					return;
				}
				$pname = $args [1];
				$newname = $args [2];
				$rs1 = $this->plugin->profileManager->isPlayerExist($pname);
				if (empty($rs1) || count($rs1)===0) {
					$sender->sendMessage ( TextFormat::GREEN . "[HG] player profile record not found!");
					return;					
				}
				$rs2 = $this->plugin->profileManager->isPlayerExist($pname);
				if (empty($rs2) || count($rs2)===0) {
					$sender->sendMessage ( TextFormat::GREEN . "[HG] player story record not found!");
					return;
				}				
				try {
					$this->plugin->profileManager->changePlayerName($pname, $newname);
					$this->plugin->storyManager->changePlayerName($pname, $newname);
					$sender->sendMessage ( TextFormat::GREEN . "[HG] Success! change from [".$pname."] to [".$newname."]");
				} catch (Exception $e) {
					$this->plugin->printError($e);
					$sender->sendMessage ( TextFormat::GREEN . "[HG] Failed! ".$e->getMessage());
				}				
				return;
			}
					
			/**
			 * ADMIN COMMANDS
			 */
			
			if (strtolower ( $args [0] ) === "setgamelobby") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleSetGameLobbyCommand ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "setserverlobby") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleSetServerLobbyCommand ( $sender->getPlayer (), $args );
				return;
			}
			
			/**
			 * SETUP ARENA COMMANDS
			 */
			
			if (strtolower ( $args [0] ) === "newarena") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] In-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->createArena ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "arenawand") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleArenaWandCommand ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "setarenapos1") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleSetArenaPosition1Command ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "setarenapos2") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleSetArenaPosition2Command ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "setarenawall") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleSetArenaWallCommand ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "setarenaspawn") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleSetArenaPlayerSpawnCommand ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "startspawnwand") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleArenaWandPlayerSpawnPointCommand ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "stopspawnwand") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				
				$session = &$this->plugin->arenaManager->session ( $sender );
				if ($session != null && $session ["wand-usage"] === true) {
					$session ["wand-usage"] = false;
					if (! isset ( $session ["spawn-pos"] )) {
						unset ( $session ["spawn-pos"] );
					}
				}
				$output = TextFormat::GREEN . "[HG] Player Spawn Wand Stopped!";
				$sender->sendMessage ( $output );
				return;
			}
			
			if (strtolower ( $args [0] ) === "listspawns") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->ListArenaPlayerSpawnCommand ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "clearspawns") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->clearArenaPlayerSpawnPointsCommand ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "setarenaenter") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleSetArenaEntranceCommand ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "setarenaexit") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleSetArenaExitCommand ( $sender->getPlayer (), $args );
				return;
			}
			
			/**
			 * SETUP DEATH-MATCH ARENA COMMANDS
			 */
			
			if (strtolower ( $args [0] ) === "matchwand") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleArenaDeathMatchWandCommand ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "setmatchpos1") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleSetArenaDeathMatchPosition1Command ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "setmatchpos2") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleSetArenaDeathMatchPosition2Command ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "setmatchenter") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleSetArenaDeathMatchEntranceCommand ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "setmatchwall") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleSetDeathMatchWallsCommand ( $sender->getPlayer (), $args );
				return;
			}
			
			/**
			 * Arena Publishing
			 */
			if (strtolower ( $args [0] ) === "publish") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleActivateArenaCommand ( $sender->getPlayer (), $args );
				$this->plugin->arenaManager->preloadArenas ();
				return;
			}
			
			if (strtolower ( $args [0] ) === "unpublish") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleDeActivateArenaCommand ( $sender->getPlayer (), $args );
				$this->plugin->arenaManager->preloadArenas ();
				return;
			}
			
			/**
			 * Level Management
			 */
			
			if (strtolower ( $args [0] ) === "addlevelarena") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->gameLevelManager->handleAdditionOfLevelArenaCommand ( $sender->getPlayer (), $args );
				$this->plugin->gameLevelManager->preloadArenas ();
				return;
			}
			
			if (strtolower ( $args [0] ) === "dellevelarena") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->gameLevelManager->handleRemovalOfLevelArenaCommand ( $sender->getPlayer (), $args );
				$this->plugin->gameLevelManager->preloadArenas ();
				return;
			}
			
			/**
			 * SETUP GAME LEVEL
			 */
			
			if (strtolower ( $args [0] ) === "levelwand") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->gameLevelManager->handleLevelWandCommand ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "setlevelpos1") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleSetGamePortalPosition1Command ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "setlevelpos2") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleSetGamePortalPosition2Command ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "levelgatewand") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->gameLevelManager->handleLevelGateWandCommand ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "setlevelgatepos1") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleSetGamePortalGate1Command ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "setlevelgatepos2") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->arenaManager->handleSetGamePortalGate2Command ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "setlevelenter") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->gameLevelManager->handleSetLevelEntranceCommand ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "setlevelexit") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if (! ($sender instanceof Player)) {
					$output .= "[HG] in-game command only!";
					$sender->sendMessage ( $output );
					return;
				}
				$this->plugin->gameLevelManager->handleSetLevelExitCommand ( $sender->getPlayer (), $args );
				return;
			}
			
			if (strtolower ( $args [0] ) === "blockon") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				$this->plugin->blockhud = true;
				$sender->sendMessage ( "[HG] Admin Block Location Display " . TextFormat::GREEN . "[ON]" );
				return;
			}
			
			if (strtolower ( $args [0] ) === "blockoff") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				$this->plugin->blockhud = false;
				$sender->sendMessage ( "[HG] Admin Block Location Display " . TextFormat::RED . "[OFF]" );
				return;
			}
			
			if (strtolower ( $args [0] ) === "xyz") {
				if (! $sender->isOp ()) {
					$sender->sendMessage ( "[HG] No authorization!" );
					return;
				}
				if ($sender instanceof Player) {
					$portalLevel = $sender->level->getName ();
					$sender->sendMessage ( "You are in world [" . $portalLevel . "] at \n[ " . round ( $sender->x ) . " " . round ( $sender->y ) . " " . round ( $sender->z ) . " ]" );
				}
				return;
			}
		}
	}
	
	public function showMyWins(Player $sender) {
			$data = $this->plugin->storyManager->retrievePlayerWinsByLevelMap ( $sender->getName () );
			if ($data == null || count ( $data ) == 0) {
				$output = "[HG] No record found";
			} else {
				$output= TextFormat::BOLD.TextFormat::AQUA."MY WINS BY LEVEL\n";
				$output .= TextFormat::GRAY ."| level | wins | loss | points | map  |\n";
				foreach ( $data as $record ) {
					$output.="| ";
					$output .= TextFormat::GREEN .$record ["level"] . TextFormat::GRAY . " | ";
					$output .= TextFormat::GOLD .$record ["wins"] . TextFormat::GRAY . " | ";
					$output .= TextFormat::WHITE .$record ["loss"] . TextFormat::GRAY . " | ";
					$output .= TextFormat::BLUE . $record ["points"] .TextFormat::GRAY." | ";
					$output .= TextFormat::ITALIC.TextFormat::WHITE .$record ["map"] . TextFormat::GRAY . " \n";
				}
				$output .= TextFormat::GRAY . "\n";
			}
			$sender->sendMessage ( TextFormat::GRAY . $output );
	}
	
	/**
	 * Get World Spawn Location
	 *
	 * @param CommandSender $sender        	
	 * @param unknown $levelname        	
	 */
	public function getWorldSpawnLocation(CommandSender $sender, $levelname) {
		if ($levelname == null) {
			$sender->sendMessage ( "[HG] Warning, no world name specified!" );
			return;
		}
		
		if (! $sender->getServer ()->isLevelLoaded ( $levelname )) {
			$ret = $sender->getServer ()->loadLevel ( $levelname );
			if (! $ret) {
				$sender->sendMessage ( "[HG] Error, unable load World: " . $levelname . ". please contact server administrator." );
				return;
			}
		}
		if (! $sender->getServer ()->isLevelGenerated ( $levelname )) {
			$sender->sendMessage ( $levelname . " - world generation still running, not ready yet! try later." );
			return;
		}
		
		$level = $sender->getServer ()->getLevelByName ( $levelname );
		if ($level == null) {
			$sender->sendMessage ( "[HG] Error, unable access world: " . $levelname . ". please contact server administrator." );
			return;
		}
		// position
		$px = $level->getSpawnLocation ()->getX ();
		$py = $level->getSpawnLocation ()->getY ();
		$pz = $level->getSpawnLocation ()->getZ ();
		$sender->sendMessage ( "[HG] " . $levelname . " Spawn Location is at [" . round ( $px ) . " " . round ( $py ) . " " . round ( $pz ) . "]" );
	}
	
	/**
	 * Load World
	 *
	 * @param CommandSender $sender        	
	 * @param unknown $levelname        	
	 */
	public function loadWorld(CommandSender $sender, $levelname) {
		if ($levelname == null) {
			$sender->sendMessage ( "[HG] Warning, no world name specified!" );
			return;
		}
		$sender->sendMessage ( "Load World: " . $levelname );
		if (! $sender->getServer ()->isLevelLoaded ( $levelname )) {
			$ret = $sender->getServer ()->loadLevel ( $levelname );
			if ($ret) {
				$sender->sendMessage ( "world loaded! " );
			} else {
				$sender->sendMessage ( "Error, unable load World: " . $levelname . " contact server administrator." );
			}
		}
	}
	public function unloadWorld(CommandSender $sender, $levelname) {
		if ($levelname == null) {
			$sender->sendMessage ( "Warning, no world name specified!" );
			return;
		}
		// $sender->sendMessage("=SIMPLE WORLDS=");
		$sender->sendMessage ( "unLoad World: " . $levelname );
		if ($sender->getServer ()->isLevelLoaded ( $levelname )) {
			$level = $sender->getServer ()->getLevelByName ( $levelname );
			if ($level == null) {
				$sender->sendMessage ( "Error, unable access world: " . $levelname . ". please contact server administrator." );
				return;
			}
			$ret = $sender->getServer ()->unloadLevel ( $level );
			if ($ret) {
				$sender->sendMessage ( "world unloaded! " );
			} else {
				$sender->sendMessage ( "Error, unable unload World: " . $levelname . ". please contact server administrator." );
			}
		}
		$this->listWorld ( $sender );
	}
	public function deleteWorld(CommandSender $sender, $levelname) {
		if ($levelname == null) {
			$sender->sendMessage ( "Warning, no world name specified!" );
			return;
		}
		
		if ($sender instanceof Player) {
			if ($levelname == $sender->getLevel ()->getName ()) {
				$sender->sendMessage ( "Warning, You can not delete world your currently on!" );
				return;
			}
		}
		$sender->sendMessage ( "delete World: " . $levelname );
		if ($sender->getServer ()->isLevelLoaded ( $levelname )) {
			$ret = $sender->getServer ()->unloadLevel ( $levelname );
			if ($ret) {
				$sender->sendMessage ( "unloaded! " );
			} else {
				$sender->sendMessage ( "Error, unable unload World: " . $levelname . ". please contact server administrator." );
			}
		}
		// delete folder
		$levelpath = $sender->getServer ()->getDataPath () . "worlds/" . $levelname . "/";
		// @unlink($levelpath);
		// rmdir($levelpath);
		$this->unlinkRecursive ( $levelpath, true );
		
		$sender->sendMessage ( "world deleted forever!" );
		$this->listAllWorld ( $sender );
	}
	
	/**
	 * List all worlds in server memory
	 *
	 * @param CommandSender $sender        	
	 */
	public function listMaps(CommandSender $sender) {
		$sender->sendMessage ( "[HG] Maps: " );
		$i = 1;
		foreach ( $this->plugin->maps as $map ) {
			$sender->sendMessage ( "  " . $i . "> " . $map->name );
			$i ++;
		}
	}
	
	/**
	 * List All Worlds in server folder
	 *
	 * @param CommandSender $sender        	
	 */
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
	
	/**
	 * Recursively delete a directory
	 *
	 * @param string $dir
	 *        	Directory name
	 * @param boolean $deleteRootToo
	 *        	Delete specified top-level directory as well
	 */
	public function unlinkRecursive($dir, $deleteRootToo) {
		if (! $dh = @opendir ( $dir )) {
			return;
		}
		while ( false !== ($obj = readdir ( $dh )) ) {
			if ($obj == '.' || $obj == '..') {
				continue;
			}
			
			if (! @unlink ( $dir . '/' . $obj )) {
				$this->unlinkRecursive ( $dir . '/' . $obj, true );
			}
		}
		
		closedir ( $dh );
		
		if ($deleteRootToo) {
			@rmdir ( $dir );
		}
		
		return;
	}
	private function hasCommandAccess(CommandSender $sender) {
		if ($sender->getName () == "CONSOLE") {
			return true;
		} elseif ($sender->isOp ()) {
			return true;
		}
		return false;
	}
	
	/**
	 * Logging util function
	 *
	 * @param unknown $msg        	
	 */
	private function log($msg) {
		$this->plugin->getLogger ()->debug( $msg );
	}
}
