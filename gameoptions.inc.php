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
		],
		'default' => STANDARD,
	],
//
	DIFFICULTY => [
		'name' => totranslate('Difficulty Level'), 'values' => [
			0 => ['name' => totranslate('Easy')],
			1 => ['name' => totranslate('Standard')],
			2 => ['name' => totranslate('Hard')],
			3 => ['name' => totranslate('Insane')],
		],
		'displaycondition' => [['type' => 'maxplayers', 'value' => 1]],
	]

//
];
