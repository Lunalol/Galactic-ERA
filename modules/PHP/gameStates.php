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
		$this->notifyAllPlayers('message', '<span class="ERA-phase">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('Setup')]);
//* -------------------------------------------------------------------------------------------------------- */
		$this->gamestate->nextState('next');
	}
	function stPrepareRoundAndDPTrack()
	{
//
// Place the gra	y pawn on the left-most position of the round track (where the gray arrow is).
//
		self::setGameStateInitialValue('round', 0);
//
// Randomly draw a galactic story tile and place it alongside the turn track in the long rectangle labeled “Galactic Story”.
//
		$galacticStory = array_rand($this->STORIES);
		$galacticStory = JOURNEYS;
		self::setGameStateInitialValue('galacticStory', $galacticStory);
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', clienttranslate('Galactic Story: <B>${STORY}</B>'), ['i18n' => ['STORY'], 'STORY' => $this->STORIES[$galacticStory]]);
//* -------------------------------------------------------------------------------------------------------- */
//
// Randomly draw a galactic goal tile and place it on the spot of the same size below the turn track.
// Introductory Game: Leave out the galactic goal for an introductory game.
//
		$galacticGoal = (self::getGameStateValue('game') == INTRODUCTORY) ? NONE : array_rand($this->GOALS);
		self::setGameStateInitialValue('galacticGoal', $galacticGoal);
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', clienttranslate('Galactic goal: <B>${GOAL}</B>'), ['i18n' => ['GOAL'], 'GOAL' => $this->GOALS[$galacticGoal]]);
//* -------------------------------------------------------------------------------------------------------- */
		$this->gamestate->nextState('next');
	}
	function stSetUpBoard()
	{
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', '<span class="ERA-phase">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('Set Up Board')]);
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
			Ships::reveal($color, 'fleet', Ships::create($color, 'fleet', 'stock', ['fleet' => 'A']));
			Ships::reveal($color, 'fleet', Ships::create($color, 'fleet', 'stock', ['fleet' => 'B']));
			Ships::reveal($color, 'fleet', Ships::create($color, 'fleet', 'stock', ['fleet' => 'C']));
			$ship = Ships::create($color, 'fleet', 'stock', ['fleet' => 'D']);
			foreach (Factions::list() as $otherColor) Ships::reveal($otherColor, 'fleet', $ship);
			Ships::reveal($color, 'fleet', Ships::create($color, 'fleet', 'stock', ['fleet' => 'E']));
		}
//
// Take three star counters of each of the three types (so a total of nine).
//
		$stars = ['UNINHABITED', 'UNINHABITED', 'UNINHABITED', 'PRIMITIVE', 'PRIMITIVE', 'PRIMITIVE', 'ADVANCED', 'ADVANCED', 'ADVANCED'];
//
// Shuffle these and place one face down on every star hex of the center sector tile, including the central hex.
//
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
//
// Solo: Remove the “Exploratory” domination card
//
		if (self::getPlayersNumber() === 1) unset($dominationCards[EXPLORATORY]);
//
// Two-Player Game: Remove the “Alignment” domination card
//
		if (self::getPlayersNumber() === 2) unset($dominationCards[ALIGNMENT]);
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
		if (self::getPlayersNumber() === 1)
		{
			$colors = array_diff($this->getGameinfos()['player_colors'], Factions::list());
			shuffle($colors);
//
			$farmers = array_shift($colors);
			Factions::create($farmers, FARMERS, 0);
			Factions::setStarPeople($farmers, 'Farmers');
			Factions::STO($farmers);

			$slavers = array_shift($colors);
			Factions::create($slavers, SLAVERS, 0);
			Factions::setStarPeople($slavers, 'Slavers');
			Factions::STS($slavers);
			Factions::gainPopulation($slavers, Automas::DIFFICULTY[self::getGameStateValue('difficulty')]);
			Factions::gainDP($slavers, Automas::DIFFICULTY[self::getGameStateValue('difficulty')]);
//
			Factions::declareWar($farmers, $slavers);
			Factions::declareWar($slavers, $farmers);
		}
//
// Remove the turn order counters from the game that have a number higher than the number of players.
// Shuffle the remaining ones and give one face up to each player
//
		$order = range(1, sizeof(Factions::list()));
		shuffle($order);
		foreach (Factions::list() as $color) Factions::setOrder($color, array_shift($order));
//
		$this->gamestate->nextState('next');
	}
	function stStarPeople()
	{
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', '<span class="ERA-phase">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('Star People choice')]);
//* -------------------------------------------------------------------------------------------------------- */
		$starPeoples = array_keys($this->STARPEOPLES);
//
// Automas
//
		unset($starPeoples[array_search('Farmers', $starPeoples)]);
		unset($starPeoples[array_search('Slavers', $starPeoples)]);
//
// Two-Player Game: Remove the “ICC” star people tile
//
		if (self::getPlayersNumber() === 2) unset($starPeoples[array_search('ICC', $starPeoples)]);
//
		shuffle($starPeoples);
		foreach (Factions::list() as $color) if (Factions::getPlayer($color) > 0) Factions::setStatus($color, 'starPeople', [array_pop($starPeoples), array_pop($starPeoples)]);
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
			if (Factions::getPlayer($color) > 0)
			{
				$starPeople = Factions::getStatus($color, 'starPeople')[0];
				Factions::setStarPeople($color, $starPeople);
				Factions::setStatus($color, 'starPeople');
				$starPeople = Factions::getStarPeople($color);
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} is playing <B>${STARPEOPLE}</B>'), [
					'player_name' => Factions::getName($color),
					'i18n' => ['STARPEOPLE'], 'STARPEOPLE' => $this->STARPEOPLES[$starPeople][Factions::getAlignment($color)],
					'faction' => ['color' => $color, 'starPeople' => $starPeople, 'alignment' => Factions::getAlignment($color)]
					]
				);
//* -------------------------------------------------------------------------------------------------------- */
			}
		}
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', '<span class="ERA-phase">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('Alignment choice')]);
//* -------------------------------------------------------------------------------------------------------- */
		$this->gamestate->setAllPlayersMultiactive('next');
		$this->gamestate->nextState('next');
	}
	function stBonus()
	{
		Factions::setActivation('ALL', 'done');
		foreach (Factions::list() as $color)
		{
			if (Factions::getPlayer($color) > 0)
			{
				if (Factions::getStatus($color, 'alignment')) Factions::STS($color);
				Factions::setStatus($color, 'alignment');
			}
			$starPeople = Factions::getStarPeople($color);
			$alignment = Factions::getAlignment($color);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} is playing <B>${ALIGNMENT}</B>'), [
				'player_name' => Factions::getName($color),
				'i18n' => ['STARPEOPLE', 'ALIGNMENT'], 'STARPEOPLE' => $this->STARPEOPLES[$starPeople][Factions::getAlignment($color)], 'ALIGNMENT' => Factions::getAlignment($color),
				'faction' => ['color' => $color, 'starPeople' => $starPeople, 'alignment' => $alignment]
			]);
//* -------------------------------------------------------------------------------------------------------- */
//
// Star people starting bonus
//
			$sector = Factions::getHomeStar($color);
//
			switch ($starPeople)
			{
				case 'Anchara':
// ANCHARA SPECIAL STO: Start with 2 additional DP.
					if ($alignment === 'STO')
					{
						self::dbSetScore(Factions::getPlayer($color), Factions::gainDP($color, 2));
//* -------------------------------------------------------------------------------------------------------- */
						$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>2 DP</B>'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
					}
// ANCHARA SPECIAL STS: Start with 2 additional ships.
					if ($alignment === 'STS')
					{
						for ($i = 0; $i < 3; $i++)
						{
//* -------------------------------------------------------------------------------------------------------- */
							$this->notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B>'), [
								'player_name' => Factions::getName($color),
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
					$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${TECHNOLOGY} (${LEVEL})</B>'), [
						'player_name' => Factions::getName($color),
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
						$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${TECHNOLOGY} (${LEVEL})</B>'), [
							'player_name' => Factions::getName($color),
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
					$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${TECHNOLOGY} (${LEVEL})</B>'), [
						'player_name' => Factions::getName($color),
						'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology],
						'LEVEL' => $level,
					]);
//* -------------------------------------------------------------------------------------------------------- */
					for ($i = 0; $i < 3; $i++)
					{
//* -------------------------------------------------------------------------------------------------------- */
						$this->notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B>'), [
							'player_name' => Factions::getName($color),
							'ship' => Ships::get($color, Ships::create($color, 'ship', $sector . ':+0+0+0'))
						]);
//* -------------------------------------------------------------------------------------------------------- */
					}
					break;
				case 'Felines':
// SPECIAL STO & STS: Start with 1 additional ship.
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B>'), [
						'player_name' => Factions::getName($color),
						'ship' => Ships::get($color, Ships::create($color, 'ship', $sector . ':+0+0+0'))
					]);
//* -------------------------------------------------------------------------------------------------------- */
					break;
				case 'Greys':
// GREYS SPECIAL STO: Start with 1 ship less than normal.
					if ($alignment === 'STO')
					{
						$ships = Ships::getAll($color, 'ship');
						$shipID = array_pop($ships)['id'];
//* -------------------------------------------------------------------------------------------------------- */
						$this->notifyAllPlayers('removeShip', clienttranslate('${player_name} loses one ship'), [
							'player_name' => Factions::getName($color),
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
							'player_name' => Factions::getName($color),
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
							$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${TECHNOLOGY} (${LEVEL})</B>'), [
								'player_name' => Factions::getName($color),
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
							$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${TECHNOLOGY} (${LEVEL})</B>'), [
								'player_name' => Factions::getName($color),
								'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology],
								'LEVEL' => $level,
							]);
//* -------------------------------------------------------------------------------------------------------- */
							$this->notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B>'), [
								'player_name' => Factions::getName($color),
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
								'player_name' => Factions::getName($color),
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
						$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${TECHNOLOGY} (${LEVEL})</B>'), [
							'player_name' => Factions::getName($color),
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
					$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${TECHNOLOGY} (${LEVEL})</B>'), [
						'player_name' => Factions::getName($color),
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
					$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${TECHNOLOGY} (${LEVEL})</B>'), [
						'player_name' => Factions::getName($color),
						'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology],
						'LEVEL' => $level,
					]);
//* -------------------------------------------------------------------------------------------------------- */
					break;
				case 'Slavers':
				case 'Farmers':
//
// Each automa also gets a start bonus
//
					$dice = bga_rand(1, 6);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('msg', clienttranslate('${player_name} rolls ${DICE}'), [
						'player_name' => Factions::getName($color), 'DICE' => $dice]);
//* -------------------------------------------------------------------------------------------------------- */
					foreach (Automas::startBonus($color, $dice) as $technology => $level)
					{
						if ($technology !== 'offboard')
						{
							Factions::setTechnology($color, $technology, $level);
//* -------------------------------------------------------------------------------------------------------- */
							$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains <B>${TECHNOLOGY} (${LEVEL})</B>'), [
								'player_name' => Factions::getName($color), 'faction' => Factions::get($color),
								'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology], 'LEVEL' => $level]);
//* -------------------------------------------------------------------------------------------------------- */
						}
						else self::acSpecial($color, 2);
					}
					break;
			}
//
// Home Star bonus
//
			if ($sector !== 0)
			{
				foreach (Sectors::BONUS[intdiv(Sectors::get($sector), 2)] as $bonus => $value)
				{
					switch ($bonus)
					{
						case 'Grow':
							Factions::setStatus($color, 'bonus', 'Grow');
//* -------------------------------------------------------------------------------------------------------- */
							$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains a free <B>growth action</B> in the first round'), [
								'player_name' => Factions::getName($color),
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
										$this->notifyAllPlayers('msg', clienttranslate('${player_name} has already <B>${TECHNOLOGY} (${LEVEL})</B>'), [
											'player_name' => Factions::getName($color),
											'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology],
											'LEVEL' => $current,
										]);
//* -------------------------------------------------------------------------------------------------------- */
									}
									else
									{
										Factions::setTechnology($color, $technology, $level);
//* -------------------------------------------------------------------------------------------------------- */
										$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${TECHNOLOGY} (${LEVEL})</B>'), [
											'player_name' => Factions::getName($color),
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
										'player_name' => Factions::getName($color),
										'ship' => Ships::get($color, Ships::create($color, 'ship', $sector . ':+0+0+0'))
									]);
//* -------------------------------------------------------------------------------------------------------- */
								}
							}
							break;
						case 'Population':
							{
//* -------------------------------------------------------------------------------------------------------- */
								$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>2 populations</B>'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
							}
							break;
						case 'Grow':
							{
//* -------------------------------------------------------------------------------------------------------- */
								$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains a free <B>growth action</B>'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
							}
							break;
					}
				}
			}
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', '', ['faction' => Factions::get($color)]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', '<span class="ERA-phase">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('Individual choices')]);
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
		$this->notifyAllPlayers('message', '<span class="ERA-phase">${log}</span>', [
			'i18n' => ['log'], 'log' => clienttranslate('Start of game')
		]);
//* -------------------------------------------------------------------------------------------------------- */
		$this->gamestate->nextState('next');
	}
	function stStartOfRound()
	{
		$round = self::incGameStateValue('round', 1);
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('updateRound', '<span class="ERA-phase">${log} ${round}/8</span>', [
			'i18n' => ['log'], 'log' => clienttranslate('Start of round'), 'round' => $round]);
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
		$this->notifyAllPlayers('message', '<span class="ERA-subphase">${log}</span>', [
			'log' => ['log' => clienttranslate('${player_name} Move/Combat Phase'), 'args' => ['player_name' => Factions::getName($color)]]]);
//* -------------------------------------------------------------------------------------------------------- */
		$player_id = Factions::getPlayer($color);
		if ($player_id < 0)
		{
			if (Ships::getAll($color))
			{
				$dice = bga_rand(1, 6);
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('msg', clienttranslate('${DICE} is rolled'), ['player_name' => Factions::getName($color), 'DICE' => $dice]);
//				$this->notifyAllPlayers('msg', clienttranslate('${player_name} rolls ${DICE}'), ['player_name' => Factions::getName($color), 'DICE' => $dice]);
//* -------------------------------------------------------------------------------------------------------- */
				Automas::movement($this, $color, $dice);
			}
			else
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('msg', clienttranslate('No ships to move'), ['player_name' => Factions::getName($color)]);
//				$this->notifyAllPlayers('msg', clienttranslate('${player_name} has no ships to move'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */

			Factions::setActivation($color, 'done');
			return $this->gamestate->nextState('continue');
		}
//
		$this->gamestate->changeActivePlayer($player_id);
		$this->gamestate->nextState('nextPlayer');
	}
	function stCombatChoice()
	{
		self::argCombatChoice();
		if (!$this->possible['combatChoice'])
		{
			Factions::setActivation(Factions::getActive(), 'done');
			return $this->gamestate->nextState('nextPlayer');
		}
		$this->gamestate->nextState('combatChoice');
	}
	function stRetreat()
	{
		$attacker = Factions::getActive();
		$location = Factions::getStatus($attacker, 'combat');
//
		$defenders = Ships::getConflictFactions($attacker, $location);
		if (!$defenders) return $this->gamestate->nextState('endCombat');
		foreach ($defenders as $defender)
		{
			$canRetreat = Factions::getTechnology($defender, 'Spirituality') > Factions::getTechnology($attacker, 'Spirituality');
			$canRetreat |= Factions::getTechnology($defender, 'Propulsion') > Factions::getTechnology($attacker, 'Propulsion');
			if ($canRetreat)
			{
				$player_id = Factions::getPlayer($defender);
				if ($player_id < 0)
				{
					Automas::retreat($this, $defender);
					return $this->gamestate->nextState('continue');
				}
//
				$this->gamestate->changeActivePlayer($player_id);
				return $this->gamestate->nextState('retreat');
			}
		}
//
		$this->gamestate->nextState('combat');
	}
	function stCombat()
	{
		$attacker = Factions::getActive();
		$location = Factions::getStatus($attacker, 'combat');
//
		$defenders = Ships::getConflictFactions($attacker, $location);
		if (!$defenders) return $this->gamestate->nextState('endCombat');
//
		$CVs = [];
		foreach (array_merge([$attacker], $defenders) as $color)
		{
			$CVs[$color] = Ships::CV($color, $location);
		}
//
		$winners = array_keys($CVs, max($CVs));
		$attackerIsWinner = in_array($attacker, $winners);
		if (sizeof($winners) > 1) $attackerIsWinner = $attackerIsWinner && (max(0, ...array_map(fn($color) => Factions::getTechnology($color, 'Military'), $defenders)) < Factions::getTechnology($attacker, 'Military'));
//
		var_dump($attackerIsWinner);
		die;
//
		$this->gamestate->changeActivePlayer($player_id);
		return $this->gamestate->nextState('winner');
	}
	function stSwitchAlignment()
	{
		foreach (Factions::list() as $color)
		{
			if (Factions::getPlayer($color) < 0)
			{
				$dice = bga_rand(1, 6);
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('msg', clienttranslate('${player_name} rolls ${DICE}'), ['player_name' => Factions::getName($color), 'DICE' => $dice]);
//* -------------------------------------------------------------------------------------------------------- */
				Factions::setStatus($color, 'counters', Automas::growthActions($color, intval(self::getGameStateValue('difficulty')), $dice));
			}
		}
//
		foreach (Factions::list() as $color)
		{
			$counters = Factions::getStatus($color, 'counters');
			if (in_array('switchAlignment', $counters))
			{
				foreach (Factions::atWar($color) as $otherColor)
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('updateFaction', '', ['faction' => Factions::get($otherColor)]);
//* -------------------------------------------------------------------------------------------------------- */
				Factions::switchAlignment($color);
//
// ANCHARA SPECIAL STO & STS: If you have chosen the Switch Alignment growth action counter,
// then on your turn of the growth phase, you may select and execute an additional, unused growth action counter at no cost.
// To do Research, you must have already chosen a technology for your square counter choice
//
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} switches alignment (<B>${ALIGNMENT}</B>)'), [
					'player_name' => Factions::getName($color), 'i18n' => ['ALIGNMENT'], 'ALIGNMENT' => Factions::getAlignment($color),
					'faction' => Factions::get($color)]);
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
					'player_name' => Factions::getName($color),
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
					'player_name' => Factions::getName($color),
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
	function stGrowthPhase()
	{
		Factions::setActivation();
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', '<span class="ERA-subphase">${log}</span>', [
			'i18n' => ['log'], 'log' => clienttranslate('Growth Phase')
		]);
//* -------------------------------------------------------------------------------------------------------- */
		foreach (Factions::list() as $color)
		{
			Factions::setStatus($color, 'counters', ['research', 'growPopulation', 'gainStar', 'gainStar', 'buildShips', 'switchAlignment', 'Military', 'Spirituality', 'Propulsion', 'Robotics', 'Genetics', 'changeTurnOrderUp', 'changeTurnOrderDown']);
			Factions::setStatus($color, 'used', []);
		}
//
		$this->gamestate->setAllPlayersMultiactive('next');
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
		$this->notifyAllPlayers('message', '<span class="ERA-subphase">${log}</span>', [
			'log' => ['log' => clienttranslate('${player_name} Growth Phase'), 'args' => ['player_name' => Factions::getName($color)]]
		]);
//* -------------------------------------------------------------------------------------------------------- */
		$player_id = Factions::getPlayer($color);
		if ($player_id < 0)
		{
			Automas::actions($this, $color);
			Factions::setActivation($color, 'done');
			return $this->gamestate->nextState('continue');
		}
//
		$this->gamestate->changeActivePlayer($player_id);
		$this->gamestate->nextState('nextPlayer');
	}
	function stTradingPhase()
	{
		Factions::setActivation('ALL', 'done');
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', '<span class="ERA-subphase">${log}</span>', [
			'i18n' => ['log'], 'log' => clienttranslate('Trading Phase')
		]);
//* -------------------------------------------------------------------------------------------------------- */
		$players = [];
		foreach (Factions::list() as $color)
		{
			$player_id = Factions::getPlayer($color);
			if ($player_id > 0)
			{
				$inContact = Factions::inContact($color);
				if ($inContact)
				{
					foreach ($inContact as $index => $with)
					{
						if (Factions::getPlayer($with) < 0)
						{
							$technologies = array_filter(array_keys(Factions::TECHNOLOGIES), fn($technology) => Factions::getTechnology($color, $technology) > Factions::getTechnology($with, $technology));
							if ($technologies)
							{
								$roll = Automas::trading($with, Factions::getAlignment($color));
								if ($roll)
								{
									$dice = bga_rand(1, 6);
//* -------------------------------------------------------------------------------------------------------- */
									$this->notifyAllPlayers('msg', clienttranslate('${player_name} rolls ${DICE}'), [
										'player_name' => Factions::getName($with), 'DICE' => $dice]);
//* -------------------------------------------------------------------------------------------------------- */
								}
								if ($roll && $dice > $roll)
								{
//* -------------------------------------------------------------------------------------------------------- */
									$this->notifyAllPlayers('msg', clienttranslate('${player_name} denies trading'), ['player_name' => Factions::getName($with)]);
//* -------------------------------------------------------------------------------------------------------- */
									unset($inContact[$index]);
								}
								else
								{
//* -------------------------------------------------------------------------------------------------------- */
									$this->notifyAllPlayers('msg', clienttranslate('${player_name} accepts trading'), ['player_name' => Factions::getName($with)]);
//* -------------------------------------------------------------------------------------------------------- */
									Factions::setStatus($with, 'trade', [$color => ['technology' => $technologies[array_rand($technologies)], 'pending' => false]]);
									Factions::setActivation($with, 'no');
								}
							}
							else unset($inContact[$index]);
						}
					}
					if ($inContact)
					{
						Factions::setActivation($color, 'no');
						Factions::setStatus($color, 'inContact', $inContact);
						Factions::setStatus($color, 'trade', []);
						$players[] = Factions::getPlayer($color);
					}
				}
			}
		}
		if ($this->gamestate->setPlayersMultiactive($players, 'next', true)) return;
		$this->gamestate->nextState('tradingPhase');
	}
	function stScoringPhase()
	{
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('message', '<span class="ERA-subphase">${log}</span>', [
			'i18n' => ['log'], 'log' => clienttranslate('Scoring Phase')
		]);
//* -------------------------------------------------------------------------------------------------------- */
		$galacticStory = self::getGameStateValue('galacticStory');
		$era = [1 => 'First', 2 => 'First', 3 => 'Second', 4 => 'Second', 5 => 'Second', 6 => 'Second', 7 => 'Third', 8 => 'Third'][self::getGameStateValue('round')];
//
		foreach (Factions::list() as $color)
		{
			if (Factions::getPlayer($color) < 0) continue;
//	
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
							$this->notifyAllPlayers('msg', _('${player_name} gains ${DP} DP(s)'), ['DP' => 1, 'player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
						}
						switch ($galacticStory)
						{
							case JOURNEYS:
// All players score 1 DP for every player they are “in contact” with at the end of the round (including the puppet in a 2-player game).
								$inContact = Factions::inContact($color, 'contact');
								if ($inContact)
								{
									Factions::gainDP($color, sizeof($inContact));
//* -------------------------------------------------------------------------------------------------------- */
									$this->notifyAllPlayers('msg', _('${player_name} gains ${DP} DP(s)'), ['DP' => 1, 'player_name' => Factions::getName($color)]);
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
							$this->notifyAllPlayers('msg', _('${player_name} gains ${DP} DP(s)'), ['DP' => 1, 'player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
							$this->notifyAllPlayers('msg', _('Second Era not implemented'), []);
						}
						$this->notifyAllPlayers('msg', _('Second Era not implemented'), []);
						switch ($galacticStory)
						{
							case JOURNEYS:
								$this->notifyAllPlayers('msg', _('Galactic story JOURNEYS not implemented'), []);
								break;
							case MIGRATIONS:
								$this->notifyAllPlayers('msg', _('Galactic story MIGRATIONS not implemented'), []);
								break;
							case RIVALRY:
								$this->notifyAllPlayers('msg', _('Galactic story RIVALRY not implemented'), []);
								break;
							case WARS:
								$this->notifyAllPlayers('msg', _('Galactic story WARS not implemented'), []);
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
							$this->notifyAllPlayers('msg', _('${player_name} gains 1 DP'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
						}
						$this->notifyAllPlayers('msg', _('Third Era not implemented'), []);
						switch ($galacticStory)
						{
							case JOURNEYS:
								$this->notifyAllPlayers('msg', _('Galactic story JOURNEYS not implemented'), []);
								break;
							case MIGRATIONS:
								$this->notifyAllPlayers('msg', _('Galactic story MIGRATIONS not implemented'), []);
								break;
							case RIVALRY:
								$this->notifyAllPlayers('msg', _('Galactic story RIVALRY not implemented'), []);
								break;
							case WARS:
								$this->notifyAllPlayers('msg', _('Galactic story WARS not implemented'), []);
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
		$this->notifyAllPlayers('message', '<span class="ERA-phase">${log} ${round}/8</span>', [
			'i18n' => ['log'], 'log' => clienttranslate('End of round'), 'round' => $round]);
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
}
