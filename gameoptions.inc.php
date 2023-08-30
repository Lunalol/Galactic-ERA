<?php
//
require_once('modules/PHP/constants.inc.php');
//
$game_options = [
//
	GAME => [
		'name' => totranslate('Game'),
		'values' => [
			INTRODUCTORY => ['name' => totranslate('Introductory Game'), 'description' => totranslate('Leave out the galactic goal for an introductory game'), 'tmdisplay' => totranslate('Introductory Game (no galactic goal)')],
			STANDARD => ['name' => totranslate('Standard Game')],
			MANUAL => ['name' => totranslate('Manual setting')],
		],
		'default' => INTRODUCTORY,
	],
	GALACTICSTORY => [
		'name' => totranslate('Galactic story'),
		'values' => [
			JOURNEYS => ['name' => totranslate('Journeys'), 'tmdisplay' => totranslate('Journeys')],
			MIGRATIONS => ['name' => totranslate('Migrations'), 'tmdisplay' => totranslate('Journeys')],
			RIVALRY => ['name' => totranslate('Rivalry'), 'tmdisplay' => totranslate('Migrations')],
			WARS => ['name' => totranslate('War'), 'tmdisplay' => totranslate('War')],
		],
		'displaycondition' => [['type' => 'otheroption', 'id' => GAME, 'value' => MANUAL]],
	],
	GALACTICGOAL => [
		'name' => totranslate('Galactic goal'),
		'values' => [
			CONTROL => ['name' => totranslate('Control'), 'tmdisplay' => totranslate('Control'), 'description' => totranslate('Players score 10 DP per star they have in the center of a sector at game end')],
			COOPERATION => ['name' => totranslate('Cooperation'), 'tmdisplay' => totranslate('Cooperation'), 'description' => totranslate('Players immediately lose 3 DP when they declare war on a player. Later declarations of war by the same player on the same player cost nothing though (i.e. you only lose this once per player). Mark this by placing a hidden ship chip beneath the war/peace counter for that player. Players immediately score 2 DP per technology trade they are part of')],
			DISCOVERY => ['name' => totranslate('Discovery'), 'tmdisplay' => totranslate('Discovery'), 'description' => totranslate('Players keep the star counters of neutral stars they took during the course of the game (a primitive neutral that was “advanced” by the STO Annunaki still counts as a primitive for this purpose). At game end, the player with the most star counters of a type scores 10 DP')],
			LEADERSHIP => ['name' => totranslate('Leadership'), 'tmdisplay' => totranslate('Leadership'), 'description' => totranslate('At the end of every era (after the scoring phase), the player with the most DP of all players belonging to an alignment places a ship of their color (from the supply or the map) on the galactic goal tile. In case of a tie each player among the tied does this. At the end of the third era do this before adding any game end DP. The player with the most ships on the galactic goal tile at game end scores 10 DP (solo variant: 20 DP)')],
			LEGACY => ['name' => totranslate('Legacy'), 'tmdisplay' => totranslate('Legacy'), 'description' => totranslate('Player scores 10 DP per star they have with a relic at game end (the one-time effect relics do not count)')],
			PERSONALGROWTH => ['name' => totranslate('Personal Growth'), 'tmdisplay' => totranslate('Personal Growth'), 'description' => totranslate('Players score double for domination cards (i.e., all effects on a card that directly give DP). Fractions are not rounded down (any half DP become whole)')],
			POWER => ['name' => totranslate('Power'), 'tmdisplay' => totranslate('Power'), 'description' => totranslate('Players score 8 DP if they have more ships in a sector than all other players’ ships there combined (no DP in case of a tie)')],
			PRESENCE => ['name' => totranslate('Presence'), 'tmdisplay' => totranslate('Presence'), 'description' => totranslate('Players score 10 DP per sector where they have at least 2 stars at game end')],
		],
		'displaycondition' => [['type' => 'otheroption', 'id' => GAME, 'value' => MANUAL]],
	],
	DIFFICULTY => [
		'name' => totranslate('Difficulty Level'), 'values' => [
			0 => ['name' => totranslate('Easy')],
			1 => ['name' => totranslate('Standard')],
			2 => ['name' => totranslate('Hard')],
			3 => ['name' => totranslate('Insane')],
		],
		'displaycondition' => [['type' => 'maxplayers', 'value' => 1]],
	]
];
//
$game_preferences = [
	SPEED => ['name' => totranslate('Animation speed'), 'needReload' => false, 'default' => NORMAL,
		'values' => [
			SLOW => ['name' => totranslate('Slow')],
			NORMAL => ['name' => totranslate('Normal')],
			FAST => ['name' => totranslate('Fast')],
		]],
	CONFIRM => ['name' => totranslate('Confirm action'), 'needReload' => false, 'default' => MOBILE,
		'values' => [
			ALWAYS => ['name' => totranslate('Always')],
			MOBILE => ['name' => totranslate('Mobile only')],
			NEVER => ['name' => totranslate('Never')],
		]],
];
