<?php
$machinestates = [
// The initial state. Please do not modify.
	1 => [
		'name' => 'gameSetup',
		'description' => '',
		'type' => 'manager',
		'action' => 'stGameSetup',
		'transitions' => ['' => 10]
	],
//
// Setup
//
	10 => [
		'name' => 'startOfSetup',
		'type' => 'game',
		'action' => 'stStartOfSetup',
		'transitions' => ['next' => 20]
	],
	20 => [
		'name' => 'prepareRoundAndDPTrack',
		'description' => clienttranslate('Prepare Round & DP Track'),
		'type' => 'game',
		'action' => 'stPrepareRoundAndDPTrack',
		'transitions' => ['next' => 40]
	],
	40 => [
		'name' => 'setUpBoard',
		'description' => clienttranslate('Set Up Board'),
		'type' => 'game',
		'action' => 'stSetUpBoard',
		'transitions' => ['next' => 50]
	],
	50 => [
		'name' => 'distributePlayerItems',
		'description' => clienttranslate('Distribute Player Items'),
		'type' => 'game',
		'action' => 'stDistributePlayerItems',
		'transitions' => ['next' => 60]
	],
	60 => [
		'name' => 'starPeople',
		'description' => clienttranslate('Players have to choose a Star People'),
		'type' => 'game',
		'action' => 'stStarPeople',
		'transitions' => ['next' => 65]
	],
	65 => [
		'name' => 'starPeopleChoice',
		'description' => clienttranslate('Players have to choose a Star People'),
		'descriptionmyturn' => clienttranslate('${you} have to choose a Star People'),
		'type' => 'multipleactiveplayer',
		'args' => 'argStarPeople',
		'possibleactions' => ['starPeopleChoice'],
		'transitions' => ['next' => 70]
	],
	70 => [
		'name' => 'alignement',
		'description' => clienttranslate('Players have to choose their Alignment'),
		'type' => 'game',
		'action' => 'stAlignment',
		'transitions' => ['next' => 75]
	],
	75 => [
		'name' => 'alignmentChoice',
		'description' => clienttranslate('Players have to choose their Alignment'),
		'descriptionmyturn' => clienttranslate('${you} have to select an Alignment'),
		'type' => 'multipleactiveplayer',
		'args' => 'argAlignment',
		'possibleactions' => ['alignmentChoice'],
		'transitions' => ['next' => 80]
	],
	80 => [
		'name' => 'individualChoices',
		'description' => clienttranslate('Players have to make some individual choices'),
		'type' => 'game',
		'action' => 'stIndividualChoices',
		'transitions' => ['next' => 90]
	],
	85 => [
		'name' => 'individualChoices',
		'description' => clienttranslate('${actplayer} have to make some individual choices'),
		'descriptionmyturn' => clienttranslate('${you} have to make some individual choices'),
		'type' => 'activeplayer',
		'possibleactions' => ['choices'],
		'transitions' => ['nextPlayer' => 80]
	],
	90 => [
		'name' => 'endOfSetup',
		'type' => 'game',
		'action' => 'stEndOfSetup',
		'transitions' => ['next' => 100]
	],
//
// Play
//
	100 => [
		'name' => 'startOfRound',
		'type' => 'game',
		'action' => 'stStartOfRound',
		'transitions' => ['next' => 200]
	],
	200 => [
		'name' => 'movementCombatPhase',
		'type' => 'game',
		'action' => 'stMovementCombatPhase',
		'transitions' => ['nextPlayer' => 220, 'next' => 300]
	],
	210 => [
		'name' => 'fleets',
		'description' => clienttranslate('${actplayer} can Create/Swap fleets'),
		'descriptionmyturn' => clienttranslate('${you} can Create/Swap fleets'),
		'type' => 'activeplayer',
		'args' => 'argFleets',
		'possibleactions' => ['create', 'swap', 'dissolve'],
		'transitions' => ['continue' => 210, 'next' => 220]
	],
	220 => [
		'name' => 'movement',
		'description' => clienttranslate('${actplayer} may move any or all ships'),
		'descriptionmyturn' => clienttranslate('${you} may move any or all ships'),
		'type' => 'activeplayer',
		'args' => 'argMovement',
		'possibleactions' => ['undo', 'move', 'scout', 'view', 'pass'],
		'transitions' => ['continue' => 220, 'next' => 200]
	],
	230 => [
		'name' => 'combat',
		'description' => clienttranslate('${actplayer} must resolve battles'),
		'descriptionmyturn' => clienttranslate('${you} must resolve battles'),
		'type' => 'activeplayer',
		'args' => 'argCombat',
		'possibleactions' => ['retreat', 'attack'],
		'transitions' => ['continue' => 230, 'nextPlayer' => 200]
	],
	300 => [
		'name' => 'growthPhase',
		'type' => 'game',
		'action' => 'stGrowthPhase',
		'transitions' => ['nextPlayer' => 220, 'next' => 305]
	],
	305 => [
		'name' => 'selectCounters',
		'description' => clienttranslate('Players have to select growth actions'),
		'descriptionmyturn' => clienttranslate('${you} have to select growth actions'),
		'type' => 'multipleactiveplayer',
		'args' => 'argSelectCounters',
		'possibleactions' => ['selectCounters'],
		'transitions' => ['next' => 310]
	],
	310 => [
		'name' => 'switchAlignment',
		'type' => 'game',
		'action' => 'stSwitchAlignment',
		'transitions' => ['next' => 320]
	],
	320 => [
		'name' => 'changeTurnOrder',
		'type' => 'game',
		'action' => 'stChangeTurnOrder',
		'transitions' => ['next' => 400]
	],
	400 => [
		'name' => 'growthActions',
		'type' => 'game',
		'action' => 'stGrowthActions',
		'transitions' => ['nextPlayer' => 410, 'next' => 500]
	],
	410 => [
		'name' => 'resolveGrowthActions',
		'description' => clienttranslate('${actplayer} resolves all their remaining growth actions'),
		'descriptionmyturn' => clienttranslate('${you} resolve all remaining growth actions'),
		'type' => 'activeplayer',
		'args' => 'argResolveGrowthActions',
		'possibleactions' => ['research', 'growPopulation', 'gainStar', 'buildShips', 'pass'],
		'transitions' => ['bonusPopulation' => 420, 'continue' => 410, 'next' => 400]
	],
	420 => [
		'name' => 'bonusPopulation',
		'description' => clienttranslate('${actplayer} may add “bonus population” discss'),
		'descriptionmyturn' => clienttranslate('${you} may add ${bonus} “bonus population” discs'),
		'type' => 'activeplayer',
		'args' => 'argBonusPopulation',
		'possibleactions' => ['bonusPopulation'],
		'transitions' => ['continue' => 410]
	],
	500 => [
		'name' => 'endOfRound',
		'type' => 'game',
		'action' => 'stEndOfRound',
		'transitions' => ['gameEnd' => 99, 'nextRound' => 100]
	],
	99 => [
		'name' => 'gameEnd',
		'description' => clienttranslate('End of game'),
		'type' => 'manager',
		'action' => 'stGameEnd',
		'args' => 'argGameEnd'
	]
];
