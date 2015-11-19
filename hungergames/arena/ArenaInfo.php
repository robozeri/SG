<?php

namespace hungergames\arena;

class ArenaInfo {
	
	const ARENA_DIRECTORY = "arena_data/";
	
	const STATUS_AVAILABLE = "available";
	const STATUS_RESET = "RESET";
	const STATUS_READY = "READY";
	const STATUS_PLAYER_JOINED = "PLAYER_JOINED";
	const STATUS_EMPTY = "EMPTY";
	const STATUS_WAITING = "WATING";
	const STATUS_ENTERING = "ENTERING";
	const STATUS_SPAWN = "SPAWN";
	const STATUS_INVISIBLE = "INVISBILE";
	const STATUS_VISIBLE = "INVISBILE";
	const STATUS_HUNTING = "HUNTING";
	const STATUS_DEATH_MATCH_START = "DEATH_MATCH_START";
	const STATUS_DEATH_MATCH_FINISH = "DEATH_MATCH_FINISH";
	const STATUS_GAME_OVER = "GAME_OVER";
	
	const COMMAND_ARENA_SIGN_STAT = "setSignStat";
	const COMMAND_ARENA_SIGN_JOIN = "setSignJoin";
	const COMMAND_ARENA_POSITION = "setArenaPos";
	const COMMAND_ARENA_SEEKER_DOOR = "setArenaSeekerDoorPos";
	const COMMAND_ARENA_POS1 = "setPos1";
	const COMMAND_ARENA_POS2 = "setPos2";
	const COMMAND_ARENA_ENTRANCE = "setArenaEnter";
	const COMMAND_ARENA_EXIT = "setArenaExit";
	const COMMAND_ARENA_NEW = "newarena";
	
			
}
 
