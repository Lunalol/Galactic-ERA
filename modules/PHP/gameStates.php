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
		self::notifyAllPlayers('msg', '<span class="ERA-phase">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('Setup')]);
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
		$galacticStory = self::getGameStateValue('galacticStory');
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', clienttranslate('Galactic Story: <B>${STORY}</B>'), ['i18n' => ['STORY'], 'STORY' => $this->STORIES[$galacticStory]]);
//* -------------------------------------------------------------------------------------------------------- */
		$galacticGoal = self::getGameStateValue('galacticGoal');
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', clienttranslate('Galactic goal: <B>${GOAL}</B>'), ['i18n' => ['GOAL'], 'GOAL' => $this->GOALS[$galacticGoal]]);
//* -------------------------------------------------------------------------------------------------------- */
		if ($galacticGoal !== NONE) self::notifyAllPlayers('msg', clienttranslate('---- Galactic goal not implemented ----'), []);
//* -------------------------------------------------------------------------------------------------------- */
		$this->gamestate->nextState('next');
	}
	function stSetUpBoard()
	{
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-phase">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('Set Up Board')]);
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
// Setup Automa for two players game
//
		if (self::getPlayersNumber() === 2)
		{
			$colors = array_diff($this->getGameinfos()['player_colors'], Factions::list());
			shuffle($colors);
//
			$automa = array_shift($colors);
			Factions::create($automa, 0, $setup[3]);

			$starPeoples = array_keys($this->STARPEOPLES);
//
// Automas
//
			unset($starPeoples[array_search('Farmers', $starPeoples)]);
			unset($starPeoples[array_search('Slavers', $starPeoples)]);
//
			shuffle($starPeoples);
// PJL		$starPeople = array_pop($starPeoples);
			$starPeople = 'Yowies';
			Factions::setStarPeople($automa, $starPeople);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} is playing <B>${STARPEOPLE}</B>'), [
				'player_name' => Factions::getName($automa),
				'i18n' => ['STARPEOPLE'], 'STARPEOPLE' => $this->STARPEOPLES[$starPeople][Factions::getAlignment($automa)],
				'faction' => ['color' => $automa, 'starPeople' => $starPeople, 'alignment' => Factions::getAlignment($automa)]
				]
			);
//* -------------------------------------------------------------------------------------------------------- */
			Factions::STO($automa);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} is playing <B>${ALIGNMENT}</B>'), [
				'player_name' => Factions::getName($automa),
				'i18n' => ['ALIGNMENT'], 'ALIGNMENT' => Factions::getAlignment($automa),
				'faction' => ['color' => $automa, 'starPeople' => $starPeople, 'alignment' => 'STO']
			]);
		}
//
		foreach (Factions::list() as $color)
		{
			$sector = Factions::getHomeStar($color);
			Ships::create($color, 'homeStar', $sector . ':+0+0+0');
//
			$stars = array_filter(Sectors::SECTORS[Sectors::get($sector)], fn($e) => $e == Sectors::PLANET);
			if (Factions::getPlayer($color) === 0)
			{
				$chops = [0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 2, 2, 5, 5, 10];
				shuffle($chops);
				$ships = array_sum(array_slice($chops, 0, 4));
//
				$fleets = ['A', 'B', 'C', 'E'];
				shuffle($fleets);
//
				Ships::reveal($color, 'fleet', Ships::create($color, 'fleet', "$sector:+0+0+0", ['fleet' => array_pop($fleets), 'ships' => $ships]));
//
				foreach (array_keys($stars) as $hexagon)
				{
					$distance = hex_length(Hex(...sscanf($hexagon, '%2d%2d%2d')));
					for ($i = 0; $i < $distance; $i++) Counters::create($color, 'populationDisk', "$sector:$hexagon");
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'population' => Factions::gainPopulation($color, $distance)]]);
//* -------------------------------------------------------------------------------------------------------- */
					Ships::create($color, 'ship', "$sector:$hexagon");
					Ships::create($color, 'ship', "$sector:$hexagon");
				}
			}
			else
			{
//
// Then every player takes two star counters of each of the three types (so a total of six).
//
				$counters = ['UNINHABITED', 'UNINHABITED', 'PRIMITIVE', 'PRIMITIVE', 'ADVANCED', 'ADVANCED'];
//
// Players who have a sector with eight stars take one additional “uninhabited” counter.
//
				if (sizeof($stars) === 7) $counters[] = 'UNINHABITED';
//
// Players then flip all their counters face down, shuffle them and place one on each hex with a star symbol (so not the central hex) in their home star sector.
//
				shuffle($counters);
				foreach (array_keys($stars) as $hexagon) Counters::create('neutral', 'star', "$sector:$hexagon", ['back' => array_pop($counters)]);
//
				Ships::reveal($color, 'fleet', Ships::create($color, 'fleet', 'stock', ['fleet' => 'A']));
				Ships::reveal($color, 'fleet', Ships::create($color, 'fleet', 'stock', ['fleet' => 'B']));
				Ships::reveal($color, 'fleet', Ships::create($color, 'fleet', 'stock', ['fleet' => 'C']));
				$ship = Ships::create($color, 'fleet', 'stock', ['fleet' => 'D']);
				foreach (Factions::list() as $otherColor) Ships::reveal($otherColor, 'fleet', $ship);
				Ships::reveal($color, 'fleet', Ships::create($color, 'fleet', 'stock', ['fleet' => 'E']));
//
			}
		}
//
// Take three star counters of each of the three types (so a total of nine).
//
		$stars = ['UNINHABITED', 'UNINHABITED', 'UNINHABITED', 'PRIMITIVE', 'PRIMITIVE', 'PRIMITIVE', 'ADVANCED', 'ADVANCED', 'ADVANCED'];
//
// Shuffle these and place one face down on every star hex of the center sector tile, including the central hex.
//
		shuffle($stars);
		foreach (array_keys(array_filter(Sectors::SECTORS[Sectors::get(0)], fn($e) => $e == Sectors::HOME || $e == Sectors::PLANET)) as $hexagon) Counters::create('neutral', 'star', "0:$hexagon", ['back' => array_pop($stars)]);
//
// Shuffle the ten relic counters face down and place one on each of the stars in the center sector (on top of the star counters). 		}
//
		$relics = range(0, 9);
		shuffle($relics);
		foreach (array_keys(array_filter(Sectors::SECTORS[Sectors::get(0)], fn($e) => $e == Sectors::HOME || $e == Sectors::PLANET)) as $hexagon) Counters::create('neutral', 'relic', "0:$hexagon", ['back' => array_pop($relics)]);
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
// Two-Player Game: Remove the “Alignment” and “Central” domination cards from play before starting
//
		if (self::getPlayersNumber() === 2) unset($dominationCards[ALIGNMENT]);
		if (self::getPlayersNumber() === 2) unset($dominationCards[CENTRAL]);
//
		$this->domination->createCards($dominationCards);
		$this->domination->shuffle('deck');
//
// Deal one domination card face down to each player
//
		foreach (Factions::list(false) as $color) $this->domination->pickCard('deck', $color)['type'];
//
// Each player places 3 ship pieces of their color at their home star.
//
		foreach (Factions::list(false) as $color)
		{
			$homeStar = Ships::getHomeStar($color);
			Ships::create($color, 'ship', $homeStar);
			Ships::create($color, 'ship', $homeStar);
			Ships::create($color, 'ship', $homeStar);
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
			self::gainDP($slavers, Automas::DIFFICULTY[self::getGameStateValue('difficulty')]);
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
		self::notifyAllPlayers('msg', '<span class="ERA-phase">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('Star People choice')]);
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
		if (self::getPlayersNumber() === 2)
		{
// PJL		unset($starPeoples[array_search('ICC', $starPeoples)]);
// PJL		unset($starPeoples[array_search(Factions::getStarPeople(Factions::getAutoma()), $starPeoples)]);
		}
//
		shuffle($starPeoples);
// PJL  foreach (Factions::list(false) as $color) if (Factions::getPlayer($color) >= 0) Factions::setStatus($color, 'starPeople', [array_pop($starPeoples), array_pop($starPeoples)]);
//
// PJL
		foreach (Factions::list(false) as $color) if (Factions::getPlayer($color) >= 0) Factions::setStatus($color, 'starPeople', $starPeoples);
//
		if (FAST_START)
		{
			foreach (Factions::list(false)as $color) Factions::setStatus($color, 'starPeople', ['Avians']);
			$this->gamestate->nextState('next');
		}
		else $this->gamestate->setAllPlayersMultiactive('next');
//
		$this->gamestate->nextState('next');
	}
	function stAlignment()
	{
		foreach (Factions::list(false) as $color)
		{
			$starPeople = Factions::getStatus($color, 'starPeople')[0];
			Factions::setStarPeople($color, $starPeople);
			Factions::setStatus($color, 'starPeople');
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} is playing <B>${STARPEOPLE}</B>'), [
				'player_name' => Factions::getName($color), 'i18n' => ['STARPEOPLE'], 'STARPEOPLE' => $this->STARPEOPLES[$starPeople][Factions::getAlignment($color)],
				'faction' => ['color' => $color, 'starPeople' => $starPeople, 'alignment' => Factions::getAlignment($color)]
				]
			);
//* -------------------------------------------------------------------------------------------------------- */
		}
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-phase">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('Alignment choice')]);
//* -------------------------------------------------------------------------------------------------------- */
		if (FAST_START)
		{
			foreach (Factions::list(false)as $color) Factions::setStatus($color, 'alignment', false);
			$this->gamestate->nextState('next');
		}
		else $this->gamestate->setAllPlayersMultiactive('next');
//
		$this->gamestate->nextState('next');
	}
	function stBonus()
	{
		Factions::setActivation('ALL', 'done');
		foreach (Factions::list() as $color)
		{
			if (Factions::getPlayer($color) >= 0)
			{
				if (Factions::getStatus($color, 'alignment')) Factions::STS($color);
				Factions::setStatus($color, 'alignment');
			}
			$starPeople = Factions::getStarPeople($color);
			$alignment = Factions::getAlignment($color);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} is playing <B>${STARPEOPLE}</B> <B>${ALIGNMENT}</B>'), [
				'player_name' => Factions::getName($color),
				'i18n' => ['ALIGNMENT', 'STARPEOPLE'],
				'ALIGNMENT' => Factions::getAlignment($color), 'STARPEOPLE' => $this->STARPEOPLES[$starPeople][Factions::getAlignment($color)],
				'faction' => ['color' => $color, 'starPeople' => $starPeople, 'alignment' => $alignment]
			]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-phase">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('Star People Starting Bonus')]);
//* -------------------------------------------------------------------------------------------------------- */
//
// Star people starting bonus
//
		foreach (Factions::list() as $color)
		{
			$starPeople = Factions::getStarPeople($color);
			$sector = Factions::getHomeStar($color);
//
			switch ($starPeople)
			{
				case 'Anchara':
// ANCHARA SPECIAL STO: Start with 2 additional DP
					if ($alignment === 'STO')
					{
						self::dbSetScore(Factions::getPlayer($color), self::gainDP($color, 2));
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>2 DP</B>'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
					}
// ANCHARA SPECIAL STS: Start with 2 additional ships
					if ($alignment === 'STS')
					{
						for ($i = 0; $i < 3; $i++)
						{
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B>'), [
								'player_name' => Factions::getName($color),
								'ship' => Ships::get($color, Ships::create($color, 'ship', $sector . ':+0+0+0'))
							]);
//* -------------------------------------------------------------------------------------------------------- */
						}
					}
					break;
				case 'Annunaki':
// SPECIAL STO & STS: Start with Genetics level 2
					self::gainTechnology($color, 'Genetics');
					break;
				case 'Avians':
// AVIANS SPECIAL STO & STS: Start with Spirituality level 2 and Propulsion level 2
					self::gainTechnology($color, 'Spirituality');
					self::gainTechnology($color, 'Propulsion');
					break;
				case 'Caninoids':
// SPECIAL STO & STS: Start at level 2 in a technology field of your choice
					Factions::setActivation($color, 'no');
//* -------------------------------------------------------------------------------------------------------- */
					break;
				case 'Dracos':
// SPECIAL STO & STS: Start with Military level 2 and 3 additional ships
					self::gainTechnology($color, 'Military');
					for ($i = 0; $i < 3; $i++)
					{
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B>'), [
							'player_name' => Factions::getName($color),
							'ship' => Ships::get($color, Ships::create($color, 'ship', $sector . ':+0+0+0'))
						]);
//* -------------------------------------------------------------------------------------------------------- */
					}
					break;
				case 'Felines':
// SPECIAL STO & STS: Start with 1 additional ship
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B>'), [
						'player_name' => Factions::getName($color),
						'ship' => Ships::get($color, Ships::create($color, 'ship', $sector . ':+0+0+0'))
					]);
//* -------------------------------------------------------------------------------------------------------- */
					break;
				case 'Greys':
// GREYS SPECIAL STO: Start with 1 ship less than normal
					if ($alignment === 'STO')
					{
						$ships = Ships::getAll($color, 'ship');
						$shipID = array_pop($ships)['id'];
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('removeShip', clienttranslate('${player_name} loses one ship'), [
							'player_name' => Factions::getName($color),
							'ship' => Ships::get($color, $shipID),
						]);
//* -------------------------------------------------------------------------------------------------------- */
						Ships::destroy($shipID);
					}
// GREYS SPECIAL STS: Start with 1 extra ship
					if ($alignment === 'STS')
					{
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B>'), [
							'player_name' => Factions::getName($color),
							'ship' => Ships::get($color, Ships::create($color, 'ship', $sector . ':+0+0+0'))
						]);
//* -------------------------------------------------------------------------------------------------------- */
					}
					break;
				case 'ICC':
					{
// ICC SPECIAL STO: Start with Propulsion level 2
						if ($alignment === 'STO') self::gainTechnology($color, 'Propulsion');
// ICC SPECIAL STS: Start with Robotics level 2 and 1 additional ship
						if ($alignment === 'STS')
						{
							self::gainTechnology($color, 'Robotics');
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B>'), [
								'player_name' => Factions::getName($color),
								'ship' => Ships::get($color, Ships::create($color, 'ship', $sector . ':+0+0+0'))
							]);
//* -------------------------------------------------------------------------------------------------------- */
						}
					}
					break;
				case 'Mantids':
// MANTIDS SPECIAL STO: Start with 2 additional population discs at your home star
					if ($alignment === 'STO')
					{
						for ($i = 0; $i < 2; $i++)
						{
							Factions::gainPopulation($color, 1);
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyAllPlayers('placeCounter', clienttranslate('${player_name} gains a <B>population</B>'), [
								'player_name' => Factions::getName($color),
								'counter' => Counters::get(Counters::create($color, 'populationDisk', $sector . ':+0+0+0'))
							]);
//* -------------------------------------------------------------------------------------------------------- */
						}
					}
// MANTIDS SPECIAL STS: Start with Genetics level 2
					if ($alignment === 'STS') self::gainTechnology($color, 'Genetics');
					break;
				case 'Rogue':
// ROGUE SPECIAL STO & STS: Start with Robotics level 2.
					self::gainTechnology($color, 'Robotics');
					break;
				case 'Yowies':
// YOWIES SPECIAL STO & STS: Start with Spirituality level 3
					self::gainTechnology($color, 'Spirituality');
					self::gainTechnology($color, 'Spirituality');
					break;
				case 'Slavers':
				case 'Farmers':
//
// Each automa also gets a start bonus
//
					$dice = bga_rand(1, 6);
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${player_name} rolls ${DICE}'), [
						'player_name' => Factions::getName($color), 'DICE' => $dice]);
//* -------------------------------------------------------------------------------------------------------- */
					foreach (Automas::startBonus($color, $dice) as $technology => $level)
					{
						if ($technology !== 'offboard') self::gainTechnology($color, $technology);
						else self::acSpecial($color, 2);
					}
					break;
			}
		}
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-phase">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('Sector Starting Bonus')]);
//* -------------------------------------------------------------------------------------------------------- */
//
// Home Star bonus
//
		foreach (Factions::list() as $color)
		{
			$starPeople = Factions::getStarPeople($color);
			$sector = Factions::getHomeStar($color);
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
							self::notifyAllPlayers('msg', clienttranslate('${player_name} gains a free <B>growth action</B> in the first round'), [
								'player_name' => Factions::getName($color),
							]);
//* -------------------------------------------------------------------------------------------------------- */
							break;
						case 'Technology':
							{
								foreach ($value as $technology => $level)
								{
									$current = Factions::getTechnology($color, $technology);
//
// YOWIES SPECIAL STO & STS: When you get Robotics as your sector starting bonus, you choose a different technology field to start with level 2 instead.
//
									if ($starPeople === 'Yowies' && $technology === 'Robotics')
									{
										Factions::setActivation($color, 'no');
//* -------------------------------------------------------------------------------------------------------- */
										self::notifyAllPlayers('msg', clienttranslate('${player_name} may not have Robotics higher than level 1'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
									}
									else if ($current > 1)
									{
										Factions::setActivation($color, 'no');
//* -------------------------------------------------------------------------------------------------------- */
										self::notifyAllPlayers('msg', clienttranslate('${player_name} has already <B>${TECHNOLOGY}</B>'), ['player_name' => Factions::getName($color), 'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology],]);
//* -------------------------------------------------------------------------------------------------------- */
									}
									else self::gainTechnology($color, $technology);
								}
							}
							break;
						case 'Ships':
							{
								for ($i = 0; $i < $value; $i++)
								{
//* -------------------------------------------------------------------------------------------------------- */
									self::notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B>'), [
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
								self::notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>2 populations</B>'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
							}
							break;
						case 'Grow':
							{
//* -------------------------------------------------------------------------------------------------------- */
								self::notifyAllPlayers('msg', clienttranslate('${player_name} gains a free <B>growth action</B>'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
							}
							break;
					}
				}
			}
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', '', ['faction' => Factions::get($color)]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-phase">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('Individual choices')]);
//* -------------------------------------------------------------------------------------------------------- */
		$this->gamestate->nextState('next');
	}
	function stIndividualChoices()
	{
		$color = Factions::getNext();
		if ($color)
		{
			Factions::setActivation($color, 'yes');

			$player_id = Factions::getPlayer($color);
			if ($player_id === 0)
			{
				$technologies = [];
				foreach (array_keys($this->TECHNOLOGIES) as $technology)
				{
					if (Factions::getTechnology($color, $technology) < 2)
					{
//
// YOWIES SPECIAL STO & STS: When you get Robotics as your sector starting bonus, you choose a different technology field to start with level 2 instead.
//
						if (Factions::getStarPeople($color) === 'Yowies' && $technology === 'Robotics') continue;
//
						$technologies[] = $technology;
					}
				}
				if ($technologies)
				{
					shuffle($technologies);
					self::gainTechnology($color, array_pop($technologies));
				}
//
				return $this->gamestate->nextState('continue');
			}
			$this->gamestate->changeActivePlayer(Factions::getPlayer($color));
			return $this->gamestate->nextState('individualChoice');
		}
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-phase">${log}</span>', [
			'i18n' => ['log'], 'log' => clienttranslate('Start of game')
		]);
//* -------------------------------------------------------------------------------------------------------- */
		$automa = Factions::getAutoma();
		if ($automa)
		{
			$technologies = array_keys(Factions::TECHNOLOGIES);
//
// YOWIES SPECIAL STO & STS: You may not have Robotics higher than level 1
//
			if (Factions::getStarPeople($automa) === 'Yowies') unset($technologies[array_search('Robotics', $technologies)]);
//
			$technology = $technologies[array_rand($technologies)];
			self::gainTechnology($automa, $technology);
			self::gainTechnology($automa, $technology);
			$technology = $technologies[array_rand($technologies)];
			self::gainTechnology($automa, $technology);
			self::gainTechnology($automa, $technology);
		}
//
		$players = array_values(Factions::advancedFleetTactics());
		if ($this->gamestate->setPlayersMultiactive($players, 'next', true)) return;
		$this->gamestate->nextState('advancedFleetTactic');
	}
	function stStartOfRound()
	{
		$round = self::incGameStateValue('round', 1);
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('updateRound', '<span class="ERA-phase">${log} ${round}/8</span>', [
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
		self::notifyAllPlayers('msg', '<span class="ERA-subphase">${log}</span>', [
			'log' => ['log' => clienttranslate('${player_name} Move/Combat Phase'), 'args' => ['player_name' => Factions::getName($color)]]]);
//* -------------------------------------------------------------------------------------------------------- */
		$player_id = Factions::getPlayer($color);
		if ($player_id < 0)
		{
			if (Ships::getAll($color))
			{
				$dice = bga_rand(1, 6);
//
// #offboard population : 4 - Slavers roll 2 dice and use lower one for movement and growth action results
//
				if ($player_id === SLAVERS && Factions::getDP($color) >= 4)
				{
					$dice1 = bga_rand(1, 6);
					$dice2 = bga_rand(1, 6);
					$dice = max($dice1, $dice2);
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${DICE1} ${DICE2} are rolled'), ['player_name' => Factions::getName($color), 'DICE1' => $dice1, 'DICE2' => $dice2]);
					self::notifyAllPlayers('msg', clienttranslate('${DICE} is used'), ['player_name' => Factions::getName($color), 'DICE' => $dice]);
//* -------------------------------------------------------------------------------------------------------- */
				}
				else
				{
					$dice = bga_rand(1, 6);
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${DICE} is rolled'), ['player_name' => Factions::getName($color), 'DICE' => $dice]);
//* -------------------------------------------------------------------------------------------------------- */
				}
				Automas::movement($this, $color, $dice);
			}
			else
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('No ships to move'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */

			return $this->gamestate->nextState('continue');
		}
		if ($player_id === 0) return $this->gamestate->nextState('continue');
//
		$this->gamestate->changeActivePlayer($player_id);
		$this->gamestate->nextState('nextPlayer');
	}
	function stCombatChoice()
	{
		$color = Factions::getActive();
//
		self::argCombatChoice();
		if (!$this->possible)
		{
			Factions::setActivation($color, 'done');
			return $this->gamestate->nextState('nextPlayer');
		}

		$player_id = Factions::getPlayer($color);
		if ($player_id <= 0)
		{
			shuffle($this->possible);
			return self::acCombatChoice($color, array_pop($this->possible), true);
		}
//
		$this->gamestate->changeActivePlayer($player_id);
		$this->gamestate->nextState('combatChoice');
	}
	function stRetreat()
	{
		$attacker = Factions::getActive();
		$location = Factions::getStatus($attacker, 'combat');
//
		$defenders = Ships::getConflictFactions($attacker, $location);
		if (!$defenders) return $this->gamestate->nextState('endCombat');
//
		$winner = Factions::getStatus($attacker, 'winner');
		foreach ((in_array($winner, $defenders)) ? [$attacker] : $defenders as $defender)
		{
			if (Factions::getStatus($defender, 'retreat')) continue;
//
			$canRetreat = Factions::getTechnology($defender, 'Spirituality') > Factions::getTechnology($attacker, 'Spirituality');
			$canRetreat |= Factions::getTechnology($defender, 'Propulsion') > Factions::getTechnology($attacker, 'Propulsion');
//
			$player_id = Factions::getPlayer($defender);
			if ($player_id > 0)
			{
				if ($winner || $canRetreat)
				{
					Factions::setStatus($attacker, 'retreat', $defender);
//
					$this->gamestate->changeActivePlayer($player_id);
					return $this->gamestate->nextState('retreat');
				}
//
				$Evade = Ships::get($defender, Ships::getFleet($defender, 'E'))['location'] === $location;
				if ($Evade)
				{
					Factions::setStatus($attacker, 'retreat', $defender);
//
					if (Factions::getAdvancedFleetTactic($defender, 'E') === '2x')
					{
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('msg', clienttranslate('${player_name} reveals an (E)vade fleet (2x)'), ['player_name' => Factions::getName($defender)]);
//* -------------------------------------------------------------------------------------------------------- */
						$attackerCVs = Ships::CV($attacker, $location);
						foreach ($attackerCVs['fleets'] as $fleet => ['ships' => $ships])
						{
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyAllPlayers('msg', '<div style="color:black;background:#${color};">${LOG}</div>', ['color' => $attacker,
								'LOG' => [
									'log' => clienttranslate('<B>${fleet}</B> fleet with ${ships} ship(s)'),
									'args' => ['fleet' => $fleet, 'ships' => $ships]
								]
							]);
//* -------------------------------------------------------------------------------------------------------- */
						}
						if ($attackerCVs['ships']['ships'])
						{
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyAllPlayers('msg', '<div style="color:black;background:#${color};">${LOG}</div>', ['color' => $attacker,
								'LOG' => [
									'log' => clienttranslate('${ships} single ship(s))'),
									'args' => ['ships' => $attackerCVs['ships']['ships']]
								]
							]);
//* -------------------------------------------------------------------------------------------------------- */
						}
					}
					else
					{
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('msg', clienttranslate('${player_name} reveals an (E)vade fleet'), ['player_name' => Factions::getName($defender)]);
//* -------------------------------------------------------------------------------------------------------- */
					}
//
					$this->gamestate->changeActivePlayer($player_id);
					return $this->gamestate->nextState('retreatE');
				}
//

				Factions::setStatus($defender, 'retreat', 'no');
				return $this->gamestate->nextState('continue');
			}
//
// Automas
//
			if (!$winner && $canRetreat)
			{
				$roll = Automas::retreat($defender);
				if ($roll)
				{
					$dice = bga_rand(1, 6);
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${player_name} rolls ${DICE}'), [
						'player_name' => Factions::getName($defender), 'DICE' => $dice]);
//* -------------------------------------------------------------------------------------------------------- */
				}
				if ($roll && $dice > $roll)
				{
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${player_name} accepts combat'), ['player_name' => Factions::getName($defender)]);
//* -------------------------------------------------------------------------------------------------------- */
					Factions::setStatus($defender, 'retreat', 'no');
					return $this->gamestate->nextState('continue');
				}
			}
//
			if ($winner || $canRetreat)
			{
				$locations = Ships::retreatLocations($defender, Factions::getStatus($attacker, 'combat'));
				shuffle($locations);
//
				return self::acRetreat($defender, array_pop($locations), true);
			}
		}
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
		Factions::setStatus($attacker, 'retreat');
//
		$attackerCVs = Ships::CV($attacker, $location);
		foreach ($attackerCVs['fleets'] as $fleet => ['CV' => $CV, 'ships' => $ships])
		{
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', '<div style="color:black;background:#${color};">${LOG}</div>', ['color' => $attacker,
				'LOG' => [
					'log' => clienttranslate('<B>+ ${CV}</B>: <B>${fleet}</B> fleet with ${ships} ship(s)'),
					'args' => ['CV' => $CV, 'fleet' => $fleet, 'ships' => $ships]
				]
			]);
//* -------------------------------------------------------------------------------------------------------- */
		}
		if ($attackerCVs['ships']['ships'])
		{
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', '<div style="color:black;background:#${color};">${LOG}</div>', ['color' => $attacker,
				'LOG' => [
					'log' => clienttranslate('<B>+ ${CV}</B>: ${ships} single ship(s))'),
					'args' => ['CV' => $attackerCVs['ships']['CV'], 'ships' => $attackerCVs['ships']['ships']]
				]
			]);
//* -------------------------------------------------------------------------------------------------------- */
		}
		$attackerCV = $attackerCVs['total'];
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', clienttranslate('Attacker side: ${CV} CV'), ['CV' => $attackerCV]);
//* -------------------------------------------------------------------------------------------------------- */
		$defenderCV = 0;
		foreach ($defenders as $defender)
		{
			Factions::setStatus($defender, 'retreat');
//
			$defenderCVs = Ships::CV($defender, $location);
			foreach ($defenderCVs['fleets'] as $fleet => ['CV' => $CV, 'ships' => $ships])
			{
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', '<div style="color:black;background:#${color};">${LOG}</div>', ['color' => $defender,
					'LOG' => [
						'log' => clienttranslate('<B>+ ${CV}</B>: <B>${fleet}</B> fleet with ${ships} ship(s)'),
						'args' => ['CV' => $CV, 'fleet' => $fleet, 'ships' => $ships]
					]
				]);
//* -------------------------------------------------------------------------------------------------------- */
			}
//* -------------------------------------------------------------------------------------------------------- */
			if ($defenderCVs['ships']['ships'])
			{
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', '<div style="color:black;background:#${color};">${LOG}</div>', ['color' => $defender,
					'LOG' => [
						'log' => clienttranslate('<B>+ ${CV}</B>: ${ships} single ship(s))'),
						'args' => ['CV' => $defenderCVs['ships']['CV'], 'ships' => $defenderCVs['ships']['ships']]
					]
				]);
//* -------------------------------------------------------------------------------------------------------- */
			}
			$defenderCV += $defenderCVs['total'];
		}
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', clienttranslate('Defender side: ${CV} CV'), ['CV' => $defenderCV]);
//* -------------------------------------------------------------------------------------------------------- */
//
		if ($attackerCV > $defenderCV) $attackerIsWinner = true;
		else if ($attackerCV < $defenderCV) $attackerIsWinner = false;
		else $attackerIsWinner = (max(0, ...array_map(fn($color) => Factions::getTechnology($color, 'Military'), $defenders)) < Factions::getTechnology($attacker, 'Military'));
//
		if ($attackerIsWinner)
		{
			$totalVictory = $attackerCV >= 3 * $defenderCV;
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('<B>Attacker</B> side wins the battle'), []);
//* -------------------------------------------------------------------------------------------------------- */
			$bonus = false;
			foreach (Ships::getAtLocation($location, $attacker, 'fleet')as $fleet) $bonus |= Factions::getAdvancedFleetTactic($attacker, Ships::getStatus($fleet, 'fleet')) === '2x';
			if ($bonus)
			{
				$DP = 3;
				self::gainDP($attacker, $DP);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('updateFaction', _('${player_name} gains ${DP} DP(s)'), ['DP' => $DP, 'player_name' => Factions::getName($attacker), 'faction' => ['color' => $attacker, 'DP' => Factions::getDP($attacker)]]);
//* -------------------------------------------------------------------------------------------------------- */
			}
			$winner = $attacker;
		}
		else
		{
			$totalVictory = $defenderCV >= 3 * $attackerCV;
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('<B>Defender</B> side wins the battle'), []);
//* -------------------------------------------------------------------------------------------------------- */
			$ships = [];
			foreach ($defenders as $defender)
			{
				$bonus = false;
				foreach (Ships::getAtLocation($location, $defender, 'fleet') as $fleet) $bonus |= Factions::getAdvancedFleetTactic($defender, Ships::getStatus($fleet, 'fleet')) === 'DP';
				if ($bonus)
				{
					$DP = 3;
					self::gainDP($defender, $DP);
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('updateFaction', _('${player_name} gains ${DP} DP(s)'), ['DP' => $DP, 'player_name' => Factions::getName($defender), 'faction' => ['color' => $defender, 'DP' => Factions::getDP($defender)]]);
//* -------------------------------------------------------------------------------------------------------- */
				}
//
				$ships[$defender] = 0;
				foreach (Ships::getAtLocation($location, $defender) as $shipID)
				{
					$ship = Ships::get($defender, $shipID);
					switch ($ship['fleet'])
					{
						case 'ship':
							$ships[$defender]++;
							break;
						case 'fleet':
							$ships[$defender] += intval(Ships::getStatus($shipID, 'ships'));
							break;
					}
				}
				$ships[$defender] = $ships[$defender] * 10 - Factions::getOrder($defender);
			}
//
			$winner = array_search(max($ships), $ships);
		}
//
		Factions::setStatus($attacker, 'winner', $winner);
		Factions::setStatus($attacker, 'totalVictory', $totalVictory);
//
// JOURNEYS Second : All players score 2 DP for every battle they win outside of their home star sector
// Battles where all opposing ships retreated before combat are not counted
//
		if (self::getGameStateValue('galacticStory') === 'Journeys' && self::ERA() === 'Second')
		{
			foreach (($attackerIsWinner ? [$attacker] : $defenders) as $color)
			{
				$player_id = Factions::getPlayer($color);
				if (intval($location[0]) !== Factions::getHomeStar($color) && $player_id > 0)
				{
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', _('All players score 2 DP for every battle they win outside of their home star sector'), []);
//* -------------------------------------------------------------------------------------------------------- */
					$DP = 2;
					self::gainDP($color, $DP);
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('updateFaction', _('${player_name} gains ${DP} DP(s)'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
				}
			}
		}
//
		$player_id = Factions::getPlayer(Factions::getStatus($attacker, 'winner'));
		if ($player_id <= 0) return self::acBattleLoss($winner, Automas::battleLoss($attacker, $defenders, $totalVictory), true);
//
		$this->gamestate->changeActivePlayer($player_id);
		return $this->gamestate->nextState('battleLoss');
	}
	function stAdditionalGrowthActions()
	{
		foreach (Factions::list(false) as $color)
		{
			$counters = Factions::getStatus($color, 'counters');
//
			$oval = 2 + (Factions::getStatus($color, 'bonus') === 'Grow' ? 1 : 0);
			foreach ($counters as $counter) if (in_array($counter, ['research', 'growPopulation', 'gainStar', 'gainStar', 'buildShips', 'switchAlignment'])) $oval--;
			if ($oval < 0)
			{
				$DP = $oval * Factions::ADDITIONAL[Factions::getTechnology($color, 'Genetics')];
				self::gainDP($color, $DP);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('updateFaction', _('${player_name} loses ${DP} DP(s)'), ['DP' => -$DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
			}
		}
		return $this->gamestate->nextState('next');
	}
	function stSwitchAlignment()
	{
		foreach (Factions::list() as $color)
		{
			if (Factions::getPlayer($color) < 0)
			{
				if (Factions::getPlayer($color) === FARMERS && !Ships::getAll($color))
				{
					$dice = 6;
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${player_name} uses ${DICE}'), ['player_name' => Factions::getName($color), 'DICE' => $dice]);
//* -------------------------------------------------------------------------------------------------------- */
				}
				else
				{
					$dice = bga_rand(1, 6);
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${player_name} rolls ${DICE}'), ['player_name' => Factions::getName($color), 'DICE' => $dice]);
//* -------------------------------------------------------------------------------------------------------- */
				}
				Factions::setStatus($color, 'counters', Automas::growthActions($color, intval(self::getGameStateValue('difficulty')), $dice));
			}
		}
//
		foreach (Factions::list() as $color)
		{
			$counters = Factions::getStatus($color, 'counters');
			if (in_array('switchAlignment', $counters))
			{
				Factions::switchAlignment($color);
//
				foreach (Factions::atWar($color) as $otherColor)
				{
					Factions::declarePeace($otherColor, $color);
					Factions::declarePeace($color, $otherColor);
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('updateFaction', '', ['faction' => Factions::get($otherColor)]);
//* -------------------------------------------------------------------------------------------------------- */
				}
//
// ANCHARA SPECIAL STO & STS: If you have chosen the Switch Alignment growth action counter,
// then on your turn of the growth phase, you may select and execute an additional, unused growth action counter at no cost.
// To do Research, you must have already chosen a technology for your square counter choice
//
				if (Factions::getTechnology($color, 'Spirituality') < 5)
				{
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} switches alignment (<B>${ALIGNMENT}</B>)'), [
						'player_name' => Factions::getName($color), 'i18n' => ['ALIGNMENT'], 'ALIGNMENT' => Factions::getAlignment($color),
						'faction' => Factions::get($color)]);
//* -------------------------------------------------------------------------------------------------------- */
				}
				else
				{
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${player_name} can not switch alignment'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
				}
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
				self::notifyAllPlayers('msg', clienttranslate('${player_name} goes <B>up</B> in turn order'), [
					'player_name' => Factions::getName($color),
				]);
//* -------------------------------------------------------------------------------------------------------- */
				$order = Factions::getOrder($color) - 1;
				if ($order >= 1)
				{
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => Factions::getByOrder($order), 'order' => $order + 1]]);
					self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'order' => $order]]);
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
				self::notifyAllPlayers('msg', clienttranslate('${player_name} goes <B>down</B> in turn order'), [
					'player_name' => Factions::getName($color),
				]);
//* -------------------------------------------------------------------------------------------------------- */
				$order = Factions::getOrder($color) + 1;
				if ($order <= sizeof($factions))
				{
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => Factions::getByOrder($order), 'order' => $order - 1]]);
					self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'order' => $order]]);
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
		self::notifyAllPlayers('msg', '<span class = "ERA-subphase">${log}</span>', [
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
		self::notifyAllPlayers('msg', '<span class = "ERA-subphase">${log}</span>', [
			'log' => ['log' => clienttranslate('${player_name} Growth Phase'), 'args' => ['player_name' => Factions::getName($color)]]
		]);
//* -------------------------------------------------------------------------------------------------------- */
		$player_id = Factions::getPlayer($color);
		if ($player_id <= 0)
		{
			if ($player_id < 0) Automas::actions($this, $color);
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
		self::notifyAllPlayers('msg', '<span class = "ERA-subphase">${log}</span>', [
			'i18n' => ['log'], 'log' => clienttranslate('Trading Phase')
		]);
//* -------------------------------------------------------------------------------------------------------- */
		$players = [];
		foreach (Factions::list() as $color)
		{
			$player_id = Factions::getPlayer($color);
			if ($player_id > 0)
			{
				Factions::setActivation($color, 'no');
				Factions::setStatus($color, 'inContact', []);
				Factions::setStatus($color, 'trade', []);
//
				$inContact = array_diff(Factions::inContact($color), Factions::atWar($color));
				foreach (Factions::atPeace($color) as $with)
				{
					if (!in_array($with, $inContact))
					{
						if (Factions::getTechnology($color, 'Spirituality') >= 4) $inContact[] = $with;
						else if (Factions::getTechnology($with, 'Spirituality') >= 4) $inContact[] = $with;
					}
				}
//
				if ($inContact)
				{
					foreach ($inContact as $index => $with)
					{
						if (Factions::getPlayer($with) === 0)
						{
							Factions::setStatus($with, 'trade', []);
							Factions::setActivation($with, 'no');
						}
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
									self::notifyAllPlayers('msg', clienttranslate('${player_name} rolls ${DICE}'), [
										'player_name' => Factions::getName($with), 'DICE' => $dice]);
//* -------------------------------------------------------------------------------------------------------- */
								}
								if ($roll && $dice > $roll)
								{
//* -------------------------------------------------------------------------------------------------------- */
									self::notifyAllPlayers('msg', clienttranslate('${player_name} denies trading'), ['player_name' => Factions::getName($with)]);
//* -------------------------------------------------------------------------------------------------------- */
									unset($inContact[$index]);
								}
								else
								{
//* -------------------------------------------------------------------------------------------------------- */
									self::notifyAllPlayers('msg', clienttranslate('${player_name} accepts trading'), ['player_name' => Factions::getName($with)]);
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
						Factions::setStatus($color, 'inContact', $inContact);
						$players[] = Factions::getPlayer($color);
					}
				}
			}
		}
//
		if ($this->gamestate->setPlayersMultiactive($players, 'next', true)) return;
		$this->gamestate->nextState('tradingPhase');
	}
	function stTradingPhaseEnd()
	{
		$players = array_values(Factions::advancedFleetTactics());
		if ($this->gamestate->setPlayersMultiactive($players, 'next', true)) return;
		$this->gamestate->nextState('advancedFleetTactic');
	}
	function stScoringPhase()
	{
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class = "ERA-subphase">${log}</span>', [
			'i18n' => ['log'], 'log' => clienttranslate('Scoring Phase')
		]);
//* -------------------------------------------------------------------------------------------------------- */
		$galacticStory = self::getGameStateValue('galacticStory');
//
		switch (self::ERA())
		{
			case 'First':
				{
//
// JOURNEYS First : Every player with the STO alignment at the end of a round scores 1 DP
//
					self::notifyAllPlayers('msg', _('Every player with the STO alignment at the end of a round scores 1 DP'), []);
					foreach (Factions::list(false) as $color)
					{
						if (Factions::getAlignment($color) === 'STO')
						{
							$DP = 1;
							if ($DP)
							{
								self::gainDP($color, $DP);
//* -------------------------------------------------------------------------------------------------------- */
								self::notifyAllPlayers('updateFaction', _('${player_name} gains ${DP} DP(s)'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
							}
						}
					}
					switch ($galacticStory)
					{
						case JOURNEYS:
//
// JOURNEYS First : All players score 1 DP for every player they are “in contact” with at the end of the round (including the puppet in a 2-player game)
//
							self::notifyAllPlayers('msg', _('All players score 1 DP for every player they are “in contact” with at the end of the round (including the puppet in a 2-player game)'), []);
							foreach (Factions::list(false) as $color)
							{
								$DP = sizeof(Factions::inContact($color, 'contact'));
								if ($DP)
								{
									self::gainDP($color, $DP);
//* -------------------------------------------------------------------------------------------------------- */
									self::notifyAllPlayers('updateFaction', _('${player_name} gains ${DP} DP(s)'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
								}
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
//
// JOURNEYS Second : Every player with the STS alignment at the end of a round scores 1 DP
//
					self::notifyAllPlayers('msg', _('Every player with the STS alignment at the end of a round scores 1 DP'), []);
					foreach (Factions::list(false) as $color)
					{
						if (Factions::getAlignment($color) === 'STS')
						{
							$DP = 1;
							if ($DP)
							{
								self::gainDP($color, $DP);
//* -------------------------------------------------------------------------------------------------------- */
								self::notifyAllPlayers('updateFaction', _('${player_name} gains ${DP} DP(s)'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
							}
						}
					}
					switch ($galacticStory)
					{
						case JOURNEYS:
//
// JOURNEYS Second : Every player “at war” with at least one other player at the end of the round scores 1 DP
//
							self::notifyAllPlayers('msg', _('Every player “at war” with at least one other player at the end of the round scores 1 DP'), []);
							foreach (Factions::list(false) as $color)
							{
								$DP = sizeof(Factions::atWar($color)) === 0 ? 0 : 1;
								if ($DP)
								{
									self::gainDP($color, $DP);
//* -------------------------------------------------------------------------------------------------------- */
									self::notifyAllPlayers('updateFaction', _('${player_name} gains ${DP} DP(s)'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
								}
							}
							break;
						case MIGRATIONS:
//
// MIGRATIONS Second : Every player “at war” with at least one other player at the end of the round scores 1 DP
//
							self::notifyAllPlayers('msg', _('Every player “at war” with at least one other player at the end of the round scores 1 DP'), []);
							foreach (Factions::list(false) as $color)
							{
								$DP = sizeof(Factions::atWar($color)) === 0 ? 0 : 1;
								if ($DP)
								{
									self::gainDP($color, $DP);
//* -------------------------------------------------------------------------------------------------------- */
									self::notifyAllPlayers('updateFaction', _('${player_name} gains ${DP} DP(s)'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
								}
							}
							self::notifyAllPlayers('msg', _('Galactic story MIGRATIONS not implemented'), []);
							break;
						case RIVALRY:
//
// RIVALRY Second : Every player “at war” with at least one other player at the end of the round scores 1 DP
//
							self::notifyAllPlayers('msg', _('Every player “at war” with at least one other player at the end of the round scores 1 DP'), []);
							foreach (Factions::list(false) as $color)
							{
								$DP = sizeof(Factions::atWar($color)) === 0 ? 0 : 1;
								if ($DP)
								{
									self::gainDP($color, $DP);
//* -------------------------------------------------------------------------------------------------------- */
									self::notifyAllPlayers('updateFaction', _('${player_name} gains ${DP} DP(s)'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
								}
							}
							self::notifyAllPlayers('msg', _('Galactic story RIVALRY not implemented'), []);
							break;
						case WARS:
//
// RIVALRY Second : Every player “at war” with at least one other player at the end of the round scores 2 DP
//
							self::notifyAllPlayers('msg', _('Every player “at war” with at least one other player at the end of the round scores 2 DP'), []);
							foreach (Factions::list(false) as $color)
							{
								$DP = sizeof(Factions::atWar($color)) === 0 ? 0 : 2;
								if ($DP)
								{
									self::gainDP($color, $DP);
//* -------------------------------------------------------------------------------------------------------- */
									self::notifyAllPlayers('updateFaction', _('${player_name} gains ${DP} DP(s)'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
								}
							}
							self::notifyAllPlayers('msg', _('Galactic story WARS not implemented'), []);
							break;
					}
				}
				break;
			case 'Third':
				{
//
// JOURNEYS Third : Every player with the STO alignment at the end of a round scores 1 DP
//
					self::notifyAllPlayers('msg', _('Every player with the STO alignment at the end of a round scores 1 DP'), []);
					foreach (Factions::list(false) as $color)
					{
						if (Factions::getAlignment($color) === 'STO')
						{
							$DP = 1;
							if ($DP)
							{
								self::gainDP($color, $DP);
//* -------------------------------------------------------------------------------------------------------- */
								self::notifyAllPlayers('updateFaction', _('${player_name} gains ${DP} DP(s)'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
							}
						}
					}
					switch ($galacticStory)
					{
						case JOURNEYS:
//
// JOURNEYS Third : At the end of the round, each player who researched Spirituality in that round and has the highest level (ties allowed) in that field among all the players who also researched that, scores 7 minus their Spirituality level
// The same applies for Propulsion. A Research action that did not result in an increased technology level does not count, neither for scoring nor for preventing scoring (*)
//
							foreach (Factions::list(false) as $color)
							{
								foreach (['Spirituality', 'Propulsion'] as $technology)
								{
									self::notifyAllPlayers('msg', _('At the end of the round, each player who researched ${technology} in that round and has the highest level (ties allowed) in that field among all the players who also researched that, scores 7 minus their ${technology} level'), ['technology' => $technology]);
									if (in_array($technology, Factions::getStatus($color, 'used')))
									{
										$best = [];
										foreach (Factions::list() as $otherColor) if (in_array($technology, Factions::getStatus($otherColor, 'used'))) $best[$otherColor] = Factions::getTechnology($otherColor, $technology);
										if (in_array($color, array_keys($best, max($best))))
										{
											$DP = 7 - Factions::getTechnology($color, $technology);
											self::gainDP($color, $DP);
//* -------------------------------------------------------------------------------------------------------- */
											self::notifyAllPlayers('updateFaction', _('${player_name} gains ${DP} DP(s)'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
										}
									}
								}
							}
							break;
						case MIGRATIONS:
							self::notifyAllPlayers('msg', _('Galactic story MIGRATIONS not implemented'), []);
							break;
						case RIVALRY:
							self::notifyAllPlayers('msg', _('Galactic story RIVALRY not implemented'), []);
							break;
						case WARS:
							self::notifyAllPlayers('msg', _('Galactic story WARS not implemented'), []);
							break;
					}
				}
				break;
		}
//
		foreach (Factions::list(false) as $color)
		{
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		$this->gamestate->nextState('next');
	}
	function stEndOfRound()
	{
		$round = self::getGameStateValue('round');
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class = "ERA-phase">${log} ${round}/8</span>', [
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
		if ($round < 8) return $this->gamestate->nextState('nextRound');
//
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class = "ERA-phase">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('Game End Scoring')]);
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-subphase">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('Every player scores DP equal to the highest number on their population track without a disc')]);
//* -------------------------------------------------------------------------------------------------------- */
		foreach (Factions::list(false) as $color)
		{
			$populationScore[$color] = Factions::POPULATION[Factions::getPopulation($color)];
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', _('${player_name} gains ${DP} DP(s)'), ['DP' => $populationScore[$color], 'player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
// Final scoring
//
		for ($i = 0; $i < max($populationScore); $i++)
		{
			foreach (Factions::list(false) as $color)
			{
				if ($i < $populationScore[$color])
				{
					self::gainDP($color, 1);
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
				}
			}
		}
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-subphase">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('For every sector, the player with the most ships there scores 4 DP (in the case of a tie all tied players score this)')]);
//* -------------------------------------------------------------------------------------------------------- */
		$sectors = [];
		foreach (Factions::list() as $color)
		{
			foreach (array_keys(Sectors::getAllDatas()) as $location) $sectors[$location][$color] = 0;
			foreach (Ships::getAll($color, 'ship') as $ship) $sectors[$ship['location'][0]][$ship['color']]++;
			foreach (Ships::getAll($color, 'fleet') as $id => $fleet) if ($fleet['location'] !== 'stock') $sectors[$fleet['location'][0]][$fleet['color']] += Ships::getStatus($id, 'ships');
		}
//
		foreach (Sectors::getAllDatas() as $location => $sector)
		{
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${GPS} Sector of ${PLANET}'), ['i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector['sector']]['+0+0+0'], 'GPS' => "$location:+0+0+0"]);
//* -------------------------------------------------------------------------------------------------------- */
			$max = max($sectors[$location]);
			if ($max > 0)
			{
				foreach (array_keys($sectors[$location], $max) as $color)
				{
					$player_id = Factions::getPlayer($color);
					if ($player_id > 0)
					{
						$DP = 4;
						self::gainDP($color, $DP);
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('msg', _('${player_name} gains ${DP} DP(s)'), ['DP' => $DP, 'player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
					}
				}
			}
		}
		foreach (Factions::list(false) as $color)
		{
//
// If players are tied then the one with the highest number of stars among the tied wins.
// If this is also a tie, then use the turn order
// The player who is first in turn order among those tied wins
//
			$tie = sizeof(Counters::getPopulation($color)) * 10 + (self::getPlayersNumber() - Factions::getOrder($color));
//
			$player_id = Factions::getPlayer($color);
			self::dbSetScore($player_id, self::dbGetScore($player_id), $tie);
		}
//
		$this->gamestate->nextState('gameEnd');
	}
}
