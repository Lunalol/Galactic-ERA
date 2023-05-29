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
		'name' => 'bonus',
		'description' => clienttranslate('Sector Starting Bonus'),
		'type' => 'game',
		'action' => 'stBonus',
		'transitions' => ['next' => 90]
	],
	90 => [
		'name' => 'individualChoices',
		'description' => clienttranslate('Players have to make some individual choices'),
		'type' => 'game',
		'action' => 'stIndividualChoices',
		'transitions' => ['individualChoice' => 95, 'next' => 100]
	],
	95 => [
		'name' => 'individualChoice',
		'description' => clienttranslate('${actplayer} have to make some individual choices'),
		'descriptionmyturn' => clienttranslate('${you} must choose a different technology field to start with level 2'),
		'type' => 'activeplayer',
		'args' => 'argIndividualChoice',
		'possibleactions' => ['individualChoice'],
		'transitions' => ['nextPlayer' => 90]
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
		'transitions' => ['continue' => 200, 'nextPlayer' => 210, 'next' => 300]
	],
	210 => [
		'name' => 'fleets',
		'description' => clienttranslate('${actplayer} can Create/Swap fleets'),
		'descriptionmyturn' => clienttranslate('${you} can Create/Swap fleets'),
		'type' => 'activeplayer',
		'args' => 'argFleets',
		'possibleactions' => ['shipsToFleet', 'fleetToShips', 'swapFleets', 'remoteViewing', 'done', 'declareWar'],
		'transitions' => ['continue' => 210, 'next' => 220]
	],
	220 => [
		'name' => 'movement',
		'description' => clienttranslate('${actplayer} may move any or all ships'),
		'descriptionmyturn' => clienttranslate('${you} may move any or all ships'),
		'type' => 'activeplayer',
		'args' => 'argMovement',
		'possibleactions' => ['undo', 'move', 'scout', 'remoteViewing', 'done', 'declareWar'],
		'transitions' => ['continue' => 220, 'next' => 230]
	],
	230 => [
		'name' => 'combatChoice',
		'type' => 'game',
		'action' => 'stCombatChoice',
		'transitions' => ['combatChoice' => 235, 'nextPlayer' => 200]
	],
	235 => [
		'name' => 'combatChoice',
		'description' => clienttranslate('${actplayer} must resolve battles'),
		'descriptionmyturn' => clienttranslate('${you} must resolve battles'),
		'type' => 'activeplayer',
		'args' => 'argCombatChoice',
		'possibleactions' => ['combatChoice'],
		'transitions' => ['engage' => 240]
	],
	240 => [
		'name' => 'retreat',
		'type' => 'game',
		'action' => 'stRetreat',
		'transitions' => ['continue' => 240, 'retreat' => 245, 'combat' => 250, 'endCombat' => 230]
	],
	245 => [
		'name' => 'retreat',
		'description' => clienttranslate('${actplayer} must resolve battles'),
		'descriptionmyturn' => clienttranslate('${you} must resolve battles'),
		'type' => 'activeplayer',
		'args' => 'argRetreat',
		'possibleactions' => ['retreat', 'combat'],
		'transitions' => ['continue' => 240]
	],
	250 => [
		'name' => 'combat',
		'type' => 'game',
		'action' => 'stCombat',
		'transitions' => ['attacker' => 255, 'continue' => 260, 'endCombat' => 230]
	],
	255 => [
		'name' => 'winner',
		'description' => clienttranslate('${actplayer} must resolve battles'),
		'descriptionmyturn' => clienttranslate('${you} must resolve battles'),
		'type' => 'activeplayer',
		'args' => 'argWinner',
		'possibleactions' => ['done'],
		'transitions' => ['continue' => 260]
	],
	260 => [
		'name' => 'loser',
		'type' => 'game',
		'action' => 'stLoser',
		'transitions' => ['loser' => 265, 'endCombat' => 230]
	],
	265 => [
		'name' => 'loser',
		'description' => clienttranslate('${actplayer} must resolve battles'),
		'descriptionmyturn' => clienttranslate('${you} must resolve battles'),
		'type' => 'activeplayer',
		'args' => 'argLoser',
		'possibleactions' => ['done'],
		'transitions' => ['endCombat' => 230]
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
		'transitions' => ['continue' => 400, 'nextPlayer' => 410, 'next' => 500]
	],
	410 => [
		'name' => 'resolveGrowthActions',
		'description' => clienttranslate('${actplayer} resolves all their remaining growth actions'),
		'descriptionmyturn' => clienttranslate('${you} resolve all remaining growth actions'),
		'type' => 'activeplayer',
		'args' => 'argResolveGrowthActions',
		'possibleactions' => ['research', 'growPopulation', 'gainStar', 'buildShips', 'pass'],
		'transitions' => ['continue' => 410, 'next' => 400]
	],
	500 => [
		'name' => 'tradingPhase',
		'type' => 'game',
		'action' => 'stTradingPhase',
		'transitions' => ['tradingPhase' => 510, 'next' => 550]
	],
	510 => [
		'name' => 'tradingPhase',
		'description' => clienttranslate('Players at peace and in contact may trade technology'),
		'descriptionmyturn' => clienttranslate('${you} may trade technology'),
		'type' => 'multipleactiveplayer',
		'args' => 'argTradingPhase',
		'possibleactions' => ['trade', 'pass'],
		'transitions' => ['continue' => 510, 'next' => 550]
	],
	550 => [
		'name' => 'scoringPhase',
		'type' => 'game',
		'action' => 'stScoringPhase',
		'transitions' => ['next' => 600]
	],
	600 => [
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
