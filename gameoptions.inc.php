<?php
//
require_once('modules/PHP/constants.inc.php');
//
$game_options = [
//
	GAME =>
	['name' => totranslate('Game'), 'default' => STANDARD, 'values' => [
			INTRODUCTORY => ['name' => totranslate('Introductory Game'), 'description' => totranslate('Leave out the galactic goal for an introductory game'), 'tmdisplay' => totranslate('Introductory Game (no galactic goal)')],
			STANDARD => ['name' => totranslate('Standard Game')],
		]
	]
//
];
