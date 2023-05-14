<?php

/**
 *
 * @author Lunalol
 */
trait gameStates
{
	function stStartOfSetup()
	{
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', '<span class="ERA-phase">${log}</span>', [
			'i18n' => ['log'], 'log' => clienttranslate('Setup')
		]);
//* -------------------------------------------------------------------------------------------------------- */
		$this->gamestate->nextState('next');
	}
	function stPrepareRoundAndDPTrack()
	{
//
// Place the gray pawn on the left-most position of the round track (where the gray arrow is).
//
		self::setGameStateInitialValue('round', 0);
//
// Randomly draw a galactic story tile and place it alongside the turn track in the long rectangle labeled “Galactic Story”.
//
		$galacticStory = array_rand($this->STORIES);
		self::setGameStateInitialValue('galacticStory', $galacticStory);
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', clienttranslate('Galactic Story: <B>${STORY}</B>'), [
			'i18n' => ['STORY'], 'STORY' => $this->STORIES[$galacticStory]
		]);
//* -------------------------------------------------------------------------------------------------------- */
//
// Randomly draw a galactic goal tile and place it on the spot of the same size below the turn track.
// Introductory Game: Leave out the galactic goal for an introductory game.
//
		$galacticGoal = NONE;
		if (self::getGameStateValue('game') != INTRODUCTORY) $galacticGoal = array_rand($this->GOALS);
		self::setGameStateInitialValue('galacticGoal', $galacticGoal);
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', clienttranslate('Galactic goal: <B>${GOAL}</B>'), [
			'i18n' => ['GOAL'], 'GOAL' => $this->GOALS[$galacticGoal]
		]);
//* -------------------------------------------------------------------------------------------------------- */
//
		$this->gamestate->nextState('next');
	}
	function stSetUpBoard()
	{
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', '<span class="ERA-phase">${log}</span>', [
			'i18n' => ['log'], 'log' => clienttranslate('Set Up Board')
		]);
//* -------------------------------------------------------------------------------------------------------- */
//
// Select randomly sectors and place them in location
//
		$setup = Sectors::setup(self::getPlayersNumber());
//
// Assign a color and a home sector to each player
//
		foreach (self::loadPlayersBasicInfos() as $player_id => $player) Factions::create($player['player_color'], $player_id, $setup[$player['player_no']]);
//
		foreach (Factions::list() as $color)
		{
			$sector = Factions::getHomeStar($color);
//
// Then every player takes two star counters of each of the three types (so a total of six).
//
			$counters = ['UNINHABITED', 'UNINHABITED', 'PRIMITIVE', 'PRIMITIVE', 'ADVANCED', 'ADVANCED'];
//
// Players who have a sector with eight stars take one additional “uninhabited” counter.
//
			$stars = array_filter(Sectors::SECTORS[Sectors::get($sector)], fn($e) => $e == Sectors::PLANET);
			if (sizeof($stars) === 7) $counters[] = 'UNINHABITED';
//
// Players then flip all their counters face down, shuffle them and place one on each hex with a star symbol (so not the central hex) in their home star sector.
//
			shuffle($counters);
			foreach (array_keys($stars) as $hexagon) Counters::create('neutral', 'star', $sector . ':' . $hexagon, ['back' => array_pop($counters)]);
//
			Ships::create($color, 'homeStar', $sector . ':+0+0+0');
		}
//
// Take three star counters of each of the three types (soa total of nine).
		$stars = ['UNINHABITED', 'UNINHABITED', 'UNINHABITED', 'PRIMITIVE', 'PRIMITIVE', 'PRIMITIVE', 'ADVANCED', 'ADVANCED', 'ADVANCED'];
// Shuffle these and place one face down on every star hex of the center sector tile, including the central hex.
		shuffle($stars);
		foreach (array_keys(array_filter(Sectors::SECTORS[Sectors::get(0)], fn($e) => $e == Sectors::HOME || $e == Sectors::PLANET)) as $hexagon) Counters::create('neutral', 'star', '0:' . $hexagon, ['back' => array_pop($stars)]);
//
// Shuffle the ten relic counters face down and place one on each of the stars in the center sector (on top of the star counters). 		}
//
		$relics = range(0, 9);
		shuffle($relics);
		foreach (array_keys(array_filter(Sectors::SECTORS[Sectors::get(0)], fn($e) => $e == Sectors::HOME || $e == Sectors::PLANET)) as $hexagon) Counters::create('neutral', 'relic', '0:' . $hexagon, ['back' => array_pop($relics)]);
//
		$this->gamestate->nextState('next');
	}
	function stDistributePlayerItems()
	{
//
// Shuffle the domination cards into a deck
//
		$dominationCards = $this->DOMINATION;
// Solo: Remove the “Exploratory” domination card
		if (self::getPlayersNumber() === 1) unset($dominationCards[EXPLORATORY]);
// Two-Player Game: Remove the “Alignment” domination card
		if (self::getPlayersNumber() === 1) unset($dominationCards[ALIGNMENT]);
//
		$this->domination->createCards($dominationCards);
		$this->domination->shuffle('deck');
//
// Deal one domination card face down to each player
//
		foreach (Factions::list() as $color) $this->domination->pickCard('deck', $color)['type'];
//
// Each player places 3 ship pieces of their color at their home star.
//
		foreach (Factions::list() as $color)
		{
			$sector = Factions::getHomeStar($color);
			Ships::create($color, 'ship', $sector . ':+0+0+0');
			Ships::create($color, 'ship', $sector . ':+0+0+0');
			Ships::create($color, 'ship', $sector . ':+0+0+0');
		}
//
// Remove the turn order counters from the game that have a number higher than the number of players.
// Shuffle the remaining ones and give one face up to each player
//
		$players = self::loadPlayersBasicInfos();
		if (self::getPlayersNumber() === 1)
		{
			$colors = array_diff($this->getGameinfos()['player_colors'], Factions::list());
			shuffle($colors);
//
			Factions::create(array_shift($colors), -1, 0);
			Factions::create(array_shift($colors), -2, 0);
//
			$order = [1, 2, 3];
			shuffle($order);
			foreach (Factions::list() as $color) Factions::setOrder($color, array_shift($order));
		}
		else foreach (Factions::list() as $color) Factions::setOrder($color, $players[Factions::getPlayer($color)]['player_no']);
//
		$this->gamestate->nextState('next');
	}
	function stStarPeople()
	{
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', '<span class = "ERA-phase">${log}</span>', [
			'i18n' => ['log'], 'log' => clienttranslate('Star People choice')
		]);
//* -------------------------------------------------------------------------------------------------------- */
		$starPeoples = array_keys($this->STARPEOPLES);
// Automas
		unset($starPeoples[array_search('Farmers', $starPeoples)]);
		unset($starPeoples[array_search('Slavers', $starPeoples)]);
// Two-Player Game: Remove the “ICC” star people tile
		if (self::getPlayersNumber() === 2) unset($starPeoples[array_search('ICC', $starPeoples)]);
//
		shuffle($starPeoples);
		foreach (Factions::list() as $color)
		{
			if (Factions::getPlayer($color) === FARMERS)
			{
				Factions::setStatus($color, 'starPeople', ['Farmers']);
				Factions::STO($color);
			}
			else if (Factions::getPlayer($color) === SLAVERS)
			{
				Factions::setStatus($color, 'starPeople', ['Slavers']);
				Factions::STS($color);
			}
			else Factions::setStatus($color, 'starPeople', [array_pop($starPeoples), array_pop($starPeoples)]);
		}
		/* PJL */
//		foreach (Factions::list() as $color) Factions::setStatus($color, 'starPeople', array_keys($this->STARPEOPLES));
		/* PJL */
//
		$this->gamestate->setAllPlayersMultiactive('next');
		$this->gamestate->nextState('next');
	}
	function stAlignment()
	{
		foreach (Factions::list() as $color)
		{
			$starPeople = Factions::getStatus($color, 'starPeople')[0];
			Factions::setStarPeople($color, $starPeople);
			Factions::setStatus($color, 'starPeople');
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} is playing <B>${STARPEOPLE}</B>'), [
				'player_name' => Players::getName(Factions::getPlayer($color)),
				'i18n' => ['STARPEOPLE'], 'STARPEOPLE' => $this->STARPEOPLES[$starPeople][Factions::getAlignment($color)],
				'faction' => ['color' => $color, 'starPeople' => $starPeople, 'alignment' => Factions::getAlignment($color)]
				]
			);
//* -------------------------------------------------------------------------------------------------------- */
		}
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', '<span class = "ERA-phase">${log}</span>', [
			'i18n' => ['log'], 'log' => clienttranslate('Alignment choice')
		]);
//* -------------------------------------------------------------------------------------------------------- */
		$this->gamestate->setAllPlayersMultiactive('next');
		$this->gamestate->nextState('next');
	}
	function stBonus()
	{
		Factions::setActivation(null, 'done');
		foreach (Factions::list() as $color)
		{
			if (Factions::getPlayer($color) === -1) Factions::setStatus($color, 'alignment', false);
			else if (Factions::getPlayer($color) === -2) Factions::setStatus($color, 'alignment', true);
//
			$starPeople = Factions::getStarPeople($color);
			if (Factions::getStatus($color, 'alignment')) Factions::STS($color);
			Factions::setStatus($color, 'alignment');
//
			$alignment = Factions::getAlignment($color);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} is playing <B>${ALIGNMENT}</B>'), [
				'player_name' => Players::getName(Factions::getPlayer($color)),
				'i18n' => ['STARPEOPLE', 'ALIGNMENT'], 'STARPEOPLE' => $this->STARPEOPLES[$starPeople][Factions::getAlignment($color)], 'ALIGNMENT' => Factions::getAlignment($color),
				'faction' => ['color' => $color, 'starPeople' => $starPeople, 'alignment' => $alignment]
			]);
//* -------------------------------------------------------------------------------------------------------- */
//
// Star people starting bonus
//
			$sector = Factions::getHomeStar($color);
			if ($sector === 0) continue;
//
			switch ($starPeople)
			{
				case 'Anchara':
// ANCHARA SPECIAL STO: Start with 2 additional DP.
					if ($alignment === 'STO')
					{
						Factions::gainDP($color, 2);
						self::dbSetScore(Factions::getPlayer($color), 2);
//* -------------------------------------------------------------------------------------------------------- */
						$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>2 DP</B>'), ['player_name' => Players::getName(Factions::getPlayer($color))]);
//* -------------------------------------------------------------------------------------------------------- */
					}
// ANCHARA SPECIAL STS: Start with 2 additional ships.
					if ($alignment === 'STS')
					{
						for ($i = 0; $i < 3; $i++)
						{
//* -------------------------------------------------------------------------------------------------------- */
							$this->notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B>'), [
								'player_name' => Players::getName(Factions::getPlayer($color)),
								'ship' => Ships::get($color, Ships::create($color, 'ship', $sector . ':+0+0+0'))
							]);
//* -------------------------------------------------------------------------------------------------------- */
						}
					}
					break;
				case 'Annunaki':
// SPECIAL STO & STS: Start with Genetics level 2.
					[$technology, $level] = ['Genetics', 2];
					Factions::setTechnology($color, $technology, $level);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${TECHNOLOGY} level ${LEVEL}</B>'), [
						'player_name' => Players::getName(Factions::getPlayer($color)),
						'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology],
						'LEVEL' => $level,
					]);
//* -------------------------------------------------------------------------------------------------------- */
					break;
				case 'Avians':
// AVIANS SPECIAL STO & STS: Start with Spirituality level 2 and Propulsion level 2.
					foreach ([['Spirituality', 2], ['Propulsion', 2]] as [$technology, $level])
					{
						Factions::setTechnology($color, $technology, $level);
//* -------------------------------------------------------------------------------------------------------- */
						$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${TECHNOLOGY} level ${LEVEL}</B>'), [
							'player_name' => Players::getName(Factions::getPlayer($color)),
							'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology],
							'LEVEL' => $level,
						]);
//* -------------------------------------------------------------------------------------------------------- */
					}
					break;
				case 'Caninoids':
// SPECIAL STO & STS: Start at level 2 in a technology field of your choice.
					Factions::setActivation($color, 'no');
//* -------------------------------------------------------------------------------------------------------- */
					break;
				case 'Dracos':
// SPECIAL STO & STS: Start with Military level 2 and 3 additional ships.
					[$technology, $level] = ['Military', 2];
					Factions::setTechnology($color, $technology, $level);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${TECHNOLOGY} level ${LEVEL}</B>'), [
						'player_name' => Players::getName(Factions::getPlayer($color)),
						'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology],
						'LEVEL' => $level,
					]);
//* -------------------------------------------------------------------------------------------------------- */
					for ($i = 0; $i < 3; $i++)
					{
//* -------------------------------------------------------------------------------------------------------- */
						$this->notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B>'), [
							'player_name' => Players::getName(Factions::getPlayer($color)),
							'ship' => Ships::get($color, Ships::create($color, 'ship', $sector . ':+0+0+0'))
						]);
//* -------------------------------------------------------------------------------------------------------- */
					}
					break;
				case 'Felines':
// SPECIAL STO & STS: Start with 1 additional ship.
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B>'), [
						'player_name' => Players::getName(Factions::getPlayer($color)),
						'ship' => Ships::get($color, Ships::create($color, 'ship', $sector . ':+0+0+0'))
					]);
//* -------------------------------------------------------------------------------------------------------- */
					break;
				case 'Greys':
// GREYS SPECIAL STO: Start with 1 ship less than normal.
					if ($alignment === 'STO')
					{
						$ships = Ships::getAll($color);
						$shipID = array_pop($ships)['id'];
//* -------------------------------------------------------------------------------------------------------- */
						$this->notifyAllPlayers('removeShip', clienttranslate('${player_name} loses one ship'), [
							'player_name' => Players::getName(Factions::getPlayer($color)),
							'ship' => Ships::get($color, $shipID),
						]);
//* -------------------------------------------------------------------------------------------------------- */
						Ships::destroy($shipID);
					}
// GREYS SPECIAL STS: Start with 1 extra ship.
					if ($alignment === 'STS')
					{
//* -------------------------------------------------------------------------------------------------------- */
						$this->notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B>'), [
							'player_name' => Players::getName(Factions::getPlayer($color)),
							'ship' => Ships::get($color, Ships::create($color, 'ship', $sector . ':+0+0+0'))
						]);
//* -------------------------------------------------------------------------------------------------------- */
					}
					break;
				case 'ICC':
					{
// SPECIAL STO: Start with Propulsion level 2.
						if ($alignment === 'STO')
						{
							[$technology, $level] = ['Propulsion', 2];
							Factions::setTechnology($color, $technology, $level);
//* -------------------------------------------------------------------------------------------------------- */
							$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${TECHNOLOGY} level ${LEVEL}</B>'), [
								'player_name' => Players::getName(Factions::getPlayer($color)),
								'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology],
								'LEVEL' => $level,
							]);
//* -------------------------------------------------------------------------------------------------------- */
						}
// SPECIAL STS: Start with Robotics level 2 and 1 additional ship.
						if ($alignment === 'STS')
						{
							[$technology, $level] = ['Robotics', 2];
							Factions::setTechnology($color, $technology, $level);
//* -------------------------------------------------------------------------------------------------------- */
							$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${TECHNOLOGY} level ${LEVEL}</B>'), [
								'player_name' => Players::getName(Factions::getPlayer($color)),
								'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology],
								'LEVEL' => $level,
							]);
//* -------------------------------------------------------------------------------------------------------- */
							$this->notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B>'), [
								'player_name' => Players::getName(Factions::getPlayer($color)),
								'ship' => Ships::get($color, Ships::create($color, 'ship', $sector . ':+0+0+0'))
							]);
//* -------------------------------------------------------------------------------------------------------- */
						}
					}
					break;
				case 'Mantids':
// SPECIAL STO: Start with 2 additional population discs at your home star.
					if ($alignment === 'STO')
					{
						for ($i = 0; $i < 2; $i++)
						{
							Factions::gainPopulation($color, 1);
//* -------------------------------------------------------------------------------------------------------- */
							$this->notifyAllPlayers('placeCounter', clienttranslate('${player_name} gains a <B>population</B>'), [
								'player_name' => Players::getName(Factions::getPlayer($color)),
								'counter' => Counters::get(Counters::create($color, 'populationDisk', $sector . ':+0+0+0'))
							]);
//* -------------------------------------------------------------------------------------------------------- */
						}
					}
// SPECIAL STS: Start with Genetics level 2.
					if ($alignment === 'STS')
					{
						[$technology, $level] = ['Genetics', 2];
						Factions::setTechnology($color, $technology, $level);
//* -------------------------------------------------------------------------------------------------------- */
						$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${TECHNOLOGY} level ${LEVEL}</B>'), [
							'player_name' => Players::getName(Factions::getPlayer($color)),
							'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology],
							'LEVEL' => $level,
						]);
//* -------------------------------------------------------------------------------------------------------- */
					}
					break;
				case 'Rogue':
// SPECIAL STO & STS: Start with Robotics level 2.
					[$technology, $level] = ['Robotics', 2];
					Factions::setTechnology($color, $technology, $level);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${TECHNOLOGY} level ${LEVEL}</B>'), [
						'player_name' => Players::getName(Factions::getPlayer($color)),
						'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology],
						'LEVEL' => $level,
					]);
//* -------------------------------------------------------------------------------------------------------- */
					break;
				case 'Yowies':
// SPECIAL STO & STS: Start with Spirituality level 3.
					[$technology, $level] = ['Spirituality', 3];
					Factions::setTechnology($color, $technology, $level);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${TECHNOLOGY} level ${LEVEL}</B>'), [
						'player_name' => Players::getName(Factions::getPlayer($color)),
						'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology],
						'LEVEL' => $level,
					]);
//* -------------------------------------------------------------------------------------------------------- */
					break;
			}
//
// Home Star bonus
//
			foreach (Sectors::BONUS[intdiv(Sectors::get($sector), 2)] as $bonus => $value)
			{
				switch ($bonus)
				{
					case 'Grow':
						Factions::setStatus($color, 'bonus', 'Grow');
//* -------------------------------------------------------------------------------------------------------- */
						$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains a free <B>growth action</B> in the first round'), [
							'player_name' => Players::getName(Factions::getPlayer($color)),
						]);
//* -------------------------------------------------------------------------------------------------------- */
						break;
					case 'Technology':
						{
							foreach ($value as $technology => $level)
							{
								$current = Factions::getTechnology($color, $technology);
								if ($current > 1)
								{
									Factions::setActivation($color, 'no');
//* -------------------------------------------------------------------------------------------------------- */
									$this->notifyAllPlayers('msg', clienttranslate('${player_name} has already <B>${TECHNOLOGY} level ${LEVEL}</B>'), [
										'player_name' => Players::getName(Factions::getPlayer($color)),
										'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology],
										'LEVEL' => $current,
									]);
//* -------------------------------------------------------------------------------------------------------- */
								}
								else
								{
									Factions::setTechnology($color, $technology, $level);
//* -------------------------------------------------------------------------------------------------------- */
									$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${TECHNOLOGY} level ${LEVEL}</B>'), [
										'player_name' => Players::getName(Factions::getPlayer($color)),
										'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology],
										'LEVEL' => $level,
									]);
//* -------------------------------------------------------------------------------------------------------- */
								}
							}
						}
						break;
					case 'Ships':
						{
							for ($i = 0; $i < $value; $i++)
							{
//* -------------------------------------------------------------------------------------------------------- */
								$this->notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B>'), [
									'player_name' => Players::getName(Factions::getPlayer($color)),
									'ship' => Ships::get($color, Ships::create($color, 'ship', $sector . ':+0+0+0'))
								]);
//* -------------------------------------------------------------------------------------------------------- */
							}
						}
						break;
					case 'Population':
						{
//* -------------------------------------------------------------------------------------------------------- */
							$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>2 populations</B>'), ['player_name' => Players::getName(Factions::getPlayer($color))]);
//* -------------------------------------------------------------------------------------------------------- */
						}
						break;
					case 'Grow':
						{
//* -------------------------------------------------------------------------------------------------------- */
							$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains a free <B>growth action</B>'), ['player_name' => Players::getName(Factions::getPlayer($color))]);
//* -------------------------------------------------------------------------------------------------------- */
						}
						break;
				}
			}

//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', '', ['faction' => Factions::get($color)]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', '<span class = "ERA-phase">${log}</span>', [
			'i18n' => ['log'], 'log' => clienttranslate('Individual choices')
		]);
//* -------------------------------------------------------------------------------------------------------- */
		$this->gamestate->nextState('next');
	}
	function stIndividualChoices()
	{
		$color = Factions::getNext();
		if ($color)
		{
			Factions::setActivation($color, 'yes');
			$this->gamestate->changeActivePlayer(Factions::getPlayer($color));
			return $this->gamestate->nextState('individualChoice');
		}
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', '<span class = "ERA-phase">${log}</span>', [
			'i18n' => ['log'], 'log' => clienttranslate('Start of game')
		]);
//* -------------------------------------------------------------------------------------------------------- */
		$this->gamestate->nextState('next');
	}
	function stStartOfRound()
	{
		$round = self::incGameStateValue('round', 1);
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('updateRound', '<span class = "ERA-phase">${log} ${round}/8</span>', [
			'i18n' => ['log'], 'log' => clienttranslate('Start of round'),
			'round' => $round
		]);
//* -------------------------------------------------------------------------------------------------------- */
		Ships::setActivation();
		Factions::setActivation();
//
		$this->gamestate->nextState('next');
	}
	function stMovementCombatPhase()
	{
		$color = Factions::getNext();
		if (!$color) return $this->gamestate->nextState('next');
//
		Factions::setActivation($color, 'yes');
		Factions::setStatus($color, 'view', Factions::TECHNOLOGIES['Spirituality'][Factions::getTechnology($color, 'Spirituality')]);
//
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', '<span class = "ERA-subphase">${log}</span>', [
			'log' => ['log' => clienttranslate('${player_name} Move/Combat Phase'), 'args' => ['player_name' => Players::getName(Factions::getPlayer($color))]]]);
//* -------------------------------------------------------------------------------------------------------- */
		$player_id = Factions::getPlayer($color);
		if ($player_id < 0)
		{
			Automas::movement($color);
			return $this->gamestate->nextState('continue');
		}
//
		$this->gamestate->changeActivePlayer($player_id);
		$this->gamestate->nextState('nextPlayer');
	}
	function stGrowthPhase()
	{
		Factions::setActivation();
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', '<span class = "ERA-subphase">${log}</span>', [
			'i18n' => ['log'], 'log' => clienttranslate('Growth Phase')
		]);
//* -------------------------------------------------------------------------------------------------------- */
		foreach (Factions::list() as $color)
		{
			if (Factions::getPlayer($color) < 0) Factions::setStatus($color, 'counters', Automas::growthActions($color));
			else Factions::setStatus($color, 'counters', ['research', 'growPopulation', 'gainStar', 'gainStar', 'buildShips', 'switchAlignment', 'Military', 'Spirituality', 'Propulsion', 'Robotics', 'Genetics', 'changeTurnOrderUp', 'changeTurnOrderDown']);
			Factions::setStatus($color, 'used', []);
		}
//
		$this->gamestate->setAllPlayersMultiactive('next');
		$this->gamestate->nextState('next');
	}
	function stSwitchAlignment()
	{
		foreach (Factions::list() as $color)
		{
			$counters = Factions::getStatus($color, 'counters');
			if (in_array('switchAlignment', $counters))
			{
				Factions::switchAlignment($color);
//
// ANCHARA SPECIAL STO & STS: If you have chosen the Switch Alignment growth action counter,
// then on your turn of the growth phase, you may select and execute an additional, unused growth action counter at no cost.
// To do Research, you must have already chosen a technology for your square counter choice
//
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} switches alignment (<B>${ALIGNMENT}</B>)'), [
					'player_name' => Players::getName(Factions::getPlayer($color)),
					'i18n' => ['ALIGNMENT'], 'ALIGNMENT' => Factions::getAlignment($color),
					'faction' => ['color' => $color, 'starPeople' => Factions::getStarPeople($color), 'alignment' => Factions::getAlignment($color)]
				]);
//* -------------------------------------------------------------------------------------------------------- */
				$counters = Factions::getStatus($color, 'counters');
				unset($counters[array_search('switchAlignment', $counters)]);
				Factions::setStatus($color, 'counters', array_values($counters));
				Factions::setStatus($color, 'used', array_values(array_merge(Factions::getStatus($color, 'used'), ['switchAlignment'])));
			}
		}
		$this->gamestate->nextState('next');
	}
	function stChangeTurnOrder()
	{
		$factions = Factions::list();
		foreach ($factions as $color)
		{
			$counters = Factions::getStatus($color, 'counters');
			if (in_array('changeTurnOrderUp', $counters))
			{
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('msg', clienttranslate('${player_name} goes <B>up</B> in turn order'), [
					'player_name' => Players::getName(Factions::getPlayer($color)),
				]);
//* -------------------------------------------------------------------------------------------------------- */
				$order = Factions::getOrder($color) - 1;
				if ($order >= 1)
				{
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('updateFaction', '', ['faction' => ['color' => Factions::getByOrder($order), 'order' => $order + 1]]);
					$this->notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'order' => $order]]);
					Factions::setOrder(Factions::getByOrder($order), $order + 1);
					Factions::setOrder($color, $order);
//* -------------------------------------------------------------------------------------------------------- */
				}
				unset($counters[array_search('changeTurnOrderUp', $counters)]);
				Factions::setStatus($color, 'counters', array_values($counters));
			}
		}
		foreach (array_reverse($factions) as $color)
		{
			$counters = Factions::getStatus($color, 'counters');
			if (in_array('changeTurnOrderDown', $counters))
			{
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('msg', clienttranslate('${player_name} goes <B>down</B> in turn order'), [
					'player_name' => Players::getName(Factions::getPlayer($color)),
				]);
//* -------------------------------------------------------------------------------------------------------- */
				$order = Factions::getOrder($color) + 1;
				if ($order <= sizeof($factions))
				{
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('updateFaction', '', ['faction' => ['color' => Factions::getByOrder($order), 'order' => $order - 1]]);
					$this->notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'order' => $order]]);
					Factions::setOrder(Factions::getByOrder($order), $order - 1);
					Factions::setOrder($color, $order);
//* -------------------------------------------------------------------------------------------------------- */
				}
				unset($counters[array_search('changeTurnOrderDown', $counters)]);
				Factions::setStatus($color, 'counters', array_values($counters));
			}
		}
		$this->gamestate->nextState('next');
	}
	function stGrowthActions()
	{
		$color = Factions::getNext();
		if (!$color) return $this->gamestate->nextState('next');
//
		Factions::setActivation($color, 'yes');
//
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', '<span class = "ERA-subphase">${log}</span>', [
			'log' => ['log' => clienttranslate('${player_name} Growth Phase'), 'args' => ['player_name' => Players::getName(Factions::getPlayer($color))]]
		]);
//* -------------------------------------------------------------------------------------------------------- */
		$player_id = Factions::getPlayer($color);
		if ($player_id < 0)
		{
			Automas::actions($this, $color);
			return $this->gamestate->nextState('continue');
		}
//
		$this->gamestate->changeActivePlayer($player_id);
		$this->gamestate->nextState('nextPlayer');
	}
	function stTradingPhase()
	{
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', '<span class = "ERA-subphase">${log}</span>', [
			'i18n' => ['log'], 'log' => clienttranslate('Trading Phase')
		]);
//* -------------------------------------------------------------------------------------------------------- */
		$players = [];
		foreach (Factions::list() as $color)
		{
			$inContact = Factions::inContact($color);
			Factions::setStatus($color, 'inContact', $inContact);
			if ($inContact) $players[] = Factions::getPlayer($color);
			Factions::setStatus($color, 'trade', []);
		}
		if ($this->gamestate->setPlayersMultiactive($players, 'next', true)) return;
		$this->gamestate->nextState('tradingPhase');
	}
	function stScoringPhase()
	{
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', '<span class = "ERA-subphase">${log}</span>', [
			'i18n' => ['log'], 'log' => clienttranslate('Scoring Phase')
		]);
//* -------------------------------------------------------------------------------------------------------- */
		$galacticStory = self::getGameStateValue('galacticStory');
		$era = [1 => 'First', 2 => 'First', 3 => 'Second', 4 => 'Second', 5 => 'Second', 6 => 'Second', 7 => 'Third', 8 => 'Third'][self::getGameStateValue('round')];
//
		foreach (Factions::list() as $color)
		{
			$alignment = Factions::getAlignment($color);
//
			switch ($era)
			{
				case 'First':
					{
// Every player with the STO alignment at the end of a round scores 1 DP.
						if ($alignment === 'STO')
						{
							Factions::gainDP($color, 1);
//* -------------------------------------------------------------------------------------------------------- */
							$this->notifyAllPlayers('msg', _('${player_name} gains ${DP} DP(s)'), ['DP' => 1,
								'player_name' => Players::getName(Factions::getPlayer($color))]);
//* -------------------------------------------------------------------------------------------------------- */
						}
						switch ($galacticStory)
						{
							case JOURNEYS:
// All players score 1 DP for every player they are “in contact” with at the end of the round (including the puppet in a 2-player game).
								$inContact = Factions::getStatus($color, 'contact');
								if ($inContact)
								{
									Factions::gainDP($color, size($inContact));
//* -------------------------------------------------------------------------------------------------------- */
									$this->notifyAllPlayers('msg', _('${player_name} gains ${DP} DP(s)'), ['DP' => 1,
										'player_name' => Players::getName(Factions::getPlayer($color))]);
//* -------------------------------------------------------------------------------------------------------- */
								}
								break;
							case MIGRATIONS:
// All players score 3 DP for every Grow Population action they do in this era.
// Only Grow Population actions that generated at least one additional population are counted.
								break;
							case RIVALRY:
// All players score 1 DP for every Gain Star action they do in this era.
								break;
							case WARS:
// All players score 2 DP for every Build Ships action they do in this era.
								break;
						}
					}
					break;
				case 'Second':
					{
// Every player with the STS alignment at the end of a round scores 1 DP.
						if ($alignment === 'STS')
						{
							Factions::gainDP($color, 1);
//* -------------------------------------------------------------------------------------------------------- */
							$this->notifyAllPlayers('msg', _('${player_name} gains ${DP} DP(s)'), ['DP' => 1,
								'player_name' => Players::getName(Factions::getPlayer($color))]);
//* -------------------------------------------------------------------------------------------------------- */
						}
						throw new BgaVisibleSystemException('Second Era not implemented');
						switch ($galacticStory)
						{
							case JOURNEYS:
								throw new BgaVisibleSystemException('Galactic story JOURNEYS not implemented');
								break;
							case MIGRATIONS:
								throw new BgaVisibleSystemException('Galactic story MIGRATIONS not implemented');
								break;
							case RIVALRY:
								throw new BgaVisibleSystemException('Galactic story RIVALRY not implemented');
								break;
							case WARS:
								throw new BgaVisibleSystemException('Galactic story WARS not implemented');
								break;
						}
					}
					break;
				case 'Third':
					{
// Every player with the STO alignment at the end of a round scores 1 DP.
						if ($alignment === 'STO')
						{
							Factions::gainDP($color, 1);
//* -------------------------------------------------------------------------------------------------------- */
							$this->notifyAllPlayers('msg', _('${player_name} gains 1 DP'), [
								'player_name' => Players::getName(Factions::getPlayer($color))]);
//* -------------------------------------------------------------------------------------------------------- */
						}
						throw new BgaVisibleSystemException('Third Era not implemented');
						switch ($galacticStory)
						{
							case JOURNEYS:
								throw new BgaVisibleSystemException('Galactic story JOURNEYS not implemented');
								break;
							case MIGRATIONS:
								throw new BgaVisibleSystemException('Galactic story MIGRATIONS not implemented');
								break;
							case RIVALRY:
								throw new BgaVisibleSystemException('Galactic story RIVALRY not implemented');
								break;
							case WARS:
								throw new BgaVisibleSystemException('Galactic story WARS not implemented');
								break;
						}
					}
					break;
			}
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		$this->gamestate->nextState('next');
	}
	function stEndOfRound()
	{
		$round = self::getGameStateValue('round');
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', '<span class = "ERA-phase">${log} ${round}/8</span>', [
			'i18n' => ['log'], 'log' => clienttranslate('End of round'),
			'round' => $round
		]);
//* -------------------------------------------------------------------------------------------------------- */
		foreach (Factions::list() as $color)
		{
			Factions::setStatus($color, 'counters');
			Factions::setStatus($color, 'used');
			Factions::setStatus($color, 'bonus');
			Factions::setStatus($color, 'contact');
			Factions::setStatus($color, 'trade');
		}
//
		$this->gamestate->nextState('nextRound');
	}
	function X()
	{
		$this->gamestate->setAllPlayersMultiactive('next');
		foreach (Factions::list() as $color)
		{
			$list = Factions::list();
			unset($list[array_search($color, $list)]);
			Factions::setStatus($color, 'inContact', $list);
			Factions::setStatus($color, 'trade', []);
		}
	}
}
