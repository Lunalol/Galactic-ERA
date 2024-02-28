<?php

/**
 *
 * @author Lunalol
 */
trait gameStates
{
	/**
	 * @state: startOfSetup
	 */
	function stStartOfSetup()
	{
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-phase">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('Setup')]);
//* -------------------------------------------------------------------------------------------------------- */
		$this->gamestate->nextState('next');
	}
	/**
	 * @state: prepareRoundAndDPTrack
	 */
	function stPrepareRoundAndDPTrack()
	{
//
// Place the gray pawn on the left-most position of the round track (where the gray arrow is)
//
		self::setGameStateInitialValue('round', 0);
//
// Randomly draw a galactic story tile
//
		$galacticStory = self::getGameStateValue('galacticStory');
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', clienttranslate('Galactic Story: <B>${STORY}</B>'), ['i18n' => ['STORY'], 'STORY' => $this->STORIES[$galacticStory]]);
//* -------------------------------------------------------------------------------------------------------- */
//
// Randomly draw a galactic goal tile
//
		$galacticGoal = self::getGameStateValue('galacticGoal');
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', clienttranslate('Galactic goal: <B>${GOAL}</B>'), ['i18n' => ['GOAL'], 'GOAL' => $this->GOALS[$galacticGoal]]);
//* -------------------------------------------------------------------------------------------------------- */
		if ($galacticGoal != NONE) self::notifyAllPlayers('msg', clienttranslate('---- Galactic goal not implemented ----'), []);
//* -------------------------------------------------------------------------------------------------------- */
		$this->gamestate->nextState('next');
	}
	/**
	 * @state: setUpBoard
	 */
	function stSetUpBoard()
	{
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-phase">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('Set Up Board')]);
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
// Setup Automas for SOLO game
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
			Ships::reveal($slavers, 'fleet', Ships::create($slavers, 'fleet', 'stock', ['fleet' => 'A']));
			Ships::reveal($slavers, 'fleet', Ships::create($slavers, 'fleet', 'stock', ['fleet' => 'B']));
			Ships::reveal($slavers, 'fleet', Ships::create($slavers, 'fleet', 'stock', ['fleet' => 'C']));
			$ship = Ships::create($slavers, 'fleet', 'stock', ['fleet' => 'D']);
			foreach (Factions::list(false) as $otherColor) Ships::reveal($otherColor, 'fleet', $ship);
			Ships::reveal($slavers, 'fleet', Ships::create($slavers, 'fleet', 'stock', ['fleet' => 'E']));
//
			Factions::declareWar($farmers, $slavers);
			Factions::declareWar($slavers, $farmers);
		}
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
			unset($starPeoples[array_search('Farmers', $starPeoples)]);
			unset($starPeoples[array_search('Slavers', $starPeoples)]);
//
			shuffle($starPeoples);
			$starPeople = array_pop($starPeoples);
//
			Factions::setStarPeople($automa, $starPeople);
			Factions::STO($automa);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} is playing <B>${STARPEOPLE}</B> <B>${ALIGNMENT}</B>'), [
				'player_name' => Factions::getName($automa),
				'i18n' => ['ALIGNMENT', 'STARPEOPLE'],
				'ALIGNMENT' => Factions::getAlignment($automa), 'STARPEOPLE' => $this->STARPEOPLES[$starPeople][Factions::getAlignment($automa)],
				'faction' => ['color' => $automa, 'starPeople' => $starPeople, 'alignment' => Factions::getAlignment($automa)]
			]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
// Setup home sectors
//
		foreach (Factions::list() as $color)
		{
			$sector = Factions::getHomeStar($color);
			if ($sector === 0) continue;
//
			$orientation = Sectors::getOrientation($sector);
//
			Ships::create($color, 'homeStar', "$sector:+0+0+0");
//
			$stars = array_filter(Sectors::SECTORS[Sectors::get($sector)], fn($e) => $e == Sectors::PLANET);
//
			if (Factions::getPlayer($color) === 0)
			{
				$chops = [0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 2, 2, 5, 5, 10];
				shuffle($chops);
//
				$ships = array_sum(array_slice($chops, 0, 4));
//
				$fleets = ['A', 'B', 'C', 'E'];
				shuffle($fleets);
//
				Ships::reveal($color, 'fleet', Ships::create($color, 'fleet', "$sector:+0+0+0", ['fleet' => array_pop($fleets), 'ships' => $ships]));
				foreach ($fleets as $fleet) Ships::create($color, 'fleet', 'stock', ['fleet' => $fleet]);
//
				foreach (array_keys($stars) as $hexagon)
				{
					$rotated = Sectors::rotate($hexagon, -$orientation);
					$distance = hex_length(Hex(...sscanf($rotated, '%2d%2d%2d')));
					for ($i = 0; $i < $distance; $i++) Counters::create($color, 'populationDisc', "$sector:$rotated");
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'population' => Factions::gainPopulation($color, $distance)]]);
//* -------------------------------------------------------------------------------------------------------- */
					Ships::create($color, 'ship', "$sector:$rotated");
					Ships::create($color, 'ship', "$sector:$rotated");
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
				foreach (array_keys($stars) as $hexagon)
				{
					$rotated = Sectors::rotate($hexagon, -$orientation);
					Counters::create('neutral', 'star', "$sector:$rotated", ['back' => array_pop($counters)]);
				}
//
				Ships::reveal($color, 'fleet', Ships::create($color, 'fleet', 'stock', ['fleet' => 'A']));
				Ships::reveal($color, 'fleet', Ships::create($color, 'fleet', 'stock', ['fleet' => 'B']));
				Ships::reveal($color, 'fleet', Ships::create($color, 'fleet', 'stock', ['fleet' => 'C']));
				$ship = Ships::create($color, 'fleet', 'stock', ['fleet' => 'D']);
				foreach (Factions::list(false) as $otherColor) Ships::reveal($otherColor, 'fleet', $ship);
				Ships::reveal($color, 'fleet', Ships::create($color, 'fleet', 'stock', ['fleet' => 'E']));
			}
		}
//
// Take three star counters of each of the three types (so a total of nine).
//
		$stars = ['UNINHABITED', 'UNINHABITED', 'UNINHABITED', 'PRIMITIVE', 'PRIMITIVE', 'PRIMITIVE', 'ADVANCED', 'ADVANCED', 'ADVANCED'];
//
		$orientation = Sectors::getOrientation(0);
//
// Shuffle these and place one face down on every star hex of the center sector tile, including the central hex.
//
		shuffle($stars);
		foreach (array_keys(array_filter(Sectors::SECTORS[Sectors::get(0)], fn($e) => $e == Sectors::HOME || $e == Sectors::PLANET)) as $hexagon)
		{
			$rotated = Sectors::rotate($hexagon, -$orientation);
			Counters::create('neutral', 'star', "0:$rotated", ['back' => array_pop($stars)]);
		}
//
// Shuffle the ten relic counters face down and place one on each of the stars in the center sector (on top of the star counters). 		}
//
		$relics = range(0, 9);
		shuffle($relics);
		foreach (array_keys(array_filter(Sectors::SECTORS[Sectors::get(0)], fn($e) => $e == Sectors::HOME || $e == Sectors::PLANET)) as $hexagon)
		{
			$rotated = Sectors::rotate($hexagon, -$orientation);
			Counters::create('neutral', 'relic', "0:$rotated", ['back' => array_pop($relics)]);
		}
//
		$this->gamestate->nextState('next');
	}
	/**
	 * @state: distributePlayerItems
	 */
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
		if (self::getPlayersNumber() === 2)
		{
			unset($dominationCards[ALIGNMENT]);
			unset($dominationCards[CENTRAL]);
		}
//
		$this->domination->createCards($dominationCards);
		$this->domination->shuffle('deck');
//
// Deal one domination card face down to each player
//
		if (FAST_START)
		{
			foreach (Factions::list(false) as $index => $color)
			{
				$cards = $this->domination->getCardsOfType($index + 0);
				$card = array_pop($cards);
				$this->domination->moveCard($card['id'], 'hand', $color);
			}
		}
		else foreach (Factions::list(false) as $color) $this->domination->pickCard('deck', $color);
//
// Each player places 3 ship pieces of their color at their home star.
//
		foreach (Factions::list(false) as $color)
		{
			$homeStar = Ships::getHomeStarLocation($color);
			Ships::create($color, 'ship', $homeStar);
			Ships::create($color, 'ship', $homeStar);
			Ships::create($color, 'ship', $homeStar);
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
	/**
	 * @state: starPeople
	 */
	function stStarPeople()
	{
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-phase">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('Star People choice')]);
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
			if (in_array('ICC', $starPeoples)) unset($starPeoples[array_search('ICC', $starPeoples)]);
			unset($starPeoples[array_search(Factions::getStarPeople(Factions::getAutoma()), $starPeoples)]);
		}
//
		shuffle($starPeoples);
//
		if (FAST_START)
		{
			foreach (Factions::list(false)as $color) Factions::setStatus($color, 'starPeople', [array_pop($starPeoples)]);
			$this->gamestate->nextState('next');
		}
		else
		{
			if (sizeof($starPeoples) < 2 * self::getPlayersNumber() || DEBUG) foreach (Factions::list(false) as $color) Factions::setStatus($color, 'starPeople', $starPeoples);
			else foreach (Factions::list(false) as $color) if (Factions::getPlayer($color) >= 0) Factions::setStatus($color, 'starPeople', [array_pop($starPeoples), array_pop($starPeoples)]);
//
			$this->gamestate->setAllPlayersMultiactive('next');
		}
//
		$this->gamestate->nextState('next');
	}
	/**
	 * @state: alignement
	 */
	function stAlignment()
	{
		foreach (Factions::list(false) as $color)
		{
			Factions::setStarPeople($color, Factions::getStatus($color, 'starPeople')[0]);
			Factions::setStatus($color, 'starPeople');
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} is playing <B>${STARPEOPLE}</B>'), [
				'player_name' => Factions::getName($color), 'i18n' => ['STARPEOPLE'], 'STARPEOPLE' => $this->STARPEOPLES[Factions::getStarPeople($color)][Factions::getAlignment($color)],
				'faction' => ['color' => $color, 'starPeople' => Factions::getStarPeople($color), 'alignment' => Factions::getAlignment($color)]
			]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-phase">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('Alignment choice')]);
//* -------------------------------------------------------------------------------------------------------- */
		if (FAST_START)
		{
			foreach (Factions::list(false)as $color) Factions::setStatus($color, 'alignment', true);
			$this->gamestate->nextState('next');
		}
		else $this->gamestate->setAllPlayersMultiactive('next');
//
		self::updateScoring();
		$this->gamestate->nextState('next');
	}
	/**
	 * @state: bonus
	 */
	function stBonus()
	{
		Factions::setActivation('ALL', 'done');
//
		foreach (Factions::list() as $color)
		{
			if (Factions::getPlayer($color) >= 0)
			{
				if (Factions::getStatus($color, 'alignment')) Factions::STS($color);
				Factions::setStatus($color, 'alignment');
			}
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} is playing <B>${STARPEOPLE}</B> <B>${ALIGNMENT}</B>'), [
				'player_name' => Factions::getName($color), 'i18n' => ['ALIGNMENT', 'STARPEOPLE'],
				'ALIGNMENT' => Factions::getAlignment($color), 'STARPEOPLE' => $this->STARPEOPLES[Factions::getStarPeople($color)][Factions::getAlignment($color)],
				'faction' => ['color' => $color, 'starPeople' => Factions::getStarPeople($color), 'alignment' => Factions::getAlignment($color)]
			]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-phase">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('Star People Starting Bonus')]);
//* -------------------------------------------------------------------------------------------------------- */
//
// Star people starting bonus
//
		foreach (Factions::list() as $color)
		{
			$player_id = Factions::getPlayer($color);
			$starPeople = Factions::getStarPeople($color);
			$alignment = Factions::getAlignment($color);
			$sector = Factions::getHomeStar($color);
//
			switch ($starPeople)
			{
				case 'Anchara':
// ANCHARA SPECIAL STO: Start with 2 additional DP
					if ($alignment === 'STO')
					{
						if ($player_id > 0)
						{
							$DP = 2;
							self::gainDP($color, $DP);
							self::incStat($DP, 'DP_SP', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>2 DP</B>'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
						}
					}
// ANCHARA SPECIAL STS: Start with 2 additional ships
					if ($alignment === 'STS')
					{
						for ($i = 0; $i < 3; $i++)
						{
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B>'), [
								'player_name' => Factions::getName($color),
								'ship' => Ships::get(Ships::create($color, 'ship', $sector . ':+0+0+0'))
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
							'ship' => Ships::get(Ships::create($color, 'ship', $sector . ':+0+0+0'))
						]);
//* -------------------------------------------------------------------------------------------------------- */
					}
					break;
				case 'Felines':
// SPECIAL STO & STS: Start with 1 additional ship
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B>'), [
						'player_name' => Factions::getName($color),
						'ship' => Ships::get(Ships::create($color, 'ship', $sector . ':+0+0+0'))
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
							'ship' => Ships::get($shipID),
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
							'ship' => Ships::get(Ships::create($color, 'ship', $sector . ':+0+0+0'))
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
								'ship' => Ships::get(Ships::create($color, 'ship', $sector . ':+0+0+0'))
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
								'counter' => Counters::get(Counters::create($color, 'populationDisc', $sector . ':+0+0+0'))
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
					self::notifyAllPlayers('msg', clienttranslate('${player_name} roll ${DICE}'), [
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
		self::notifyAllPlayers('msg', '<span class="ERA-phase">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('Sector Starting Bonus')]);
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
										self::notifyAllPlayers('msg', clienttranslate('Yowies may not have Robotics higher than level 1'), ['player_name' => Factions::getName($color)]);
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
										'ship' => Ships::get(Ships::create($color, 'ship', $sector . ':+0+0+0'))
									]);
//* -------------------------------------------------------------------------------------------------------- */
								}
							}
							break;
						case 'Population':
							{
								for ($i = 0; $i < $value; $i++)
								{
									Factions::gainPopulation($color, 1);
//* -------------------------------------------------------------------------------------------------------- */
									self::notifyAllPlayers('placeCounter', clienttranslate('${player_name} gains a <B>population</B>'), [
										'player_name' => Factions::getName($color),
										'counter' => Counters::get(Counters::create($color, 'populationDisc', $sector . ':+0+0+0'))
									]);
//* -------------------------------------------------------------------------------------------------------- */
								}
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
		self::notifyAllPlayers('msg', '<span class="ERA-phase">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('Individual choices')]);
//* -------------------------------------------------------------------------------------------------------- */
		$this->gamestate->nextState('next');
	}
	/**
	 * @state: individualChoices
	 */
	function stIndividualChoices()
	{
		self::updateScoring();
//
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
		self::notifyAllPlayers('msg', '<span class="ERA-phase">${LOG}</span>', [
			'i18n' => ['LOG'], 'LOG' => clienttranslate('Start of game')
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
		self::updateScoring();
		$this->gamestate->nextState('next');
	}
	/**
	 * @state: startOfGame
	 */
	function stStartOfGame()
	{
		Factions::setActivation();
//
		if (self::getPlayersNumber() > 1) $this->gamestate->nextState('next');
		else $this->gamestate->nextState('levelOfDifficulty');
	}
	/**
	 * @state: startOfRound
	 */
	function stStartOfRound()
	{
		$round = intval(self::incGameStateValue('round', 1));
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('updateRound', '<span class="ERA-phase">${LOG} ${round}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('Start of round'), 'round' => $round]);
//* -------------------------------------------------------------------------------------------------------- */
		Ships::setActivation();
		Factions::setActivation();
//
// Availabilty of Ancient Pyramids (0) and Planetary Death Ray (7)
//
		foreach ([0, 7] as $relic)
		{
			$relicID = Counters::getRelic($relic);
			if ($relicID) Counters::setStatus($relicID, 'available', 1);
		}
//------------------------
// A-section: Alignment //
//------------------------
		self::setGameStateValue('alignment', 0);
//--------------------------
// A-section: Alignment //
//------------------------
//
		if ($round === 3 || $round === 7) return $this->gamestate->nextState('dominationCardExchange');
//
		self::triggerEvent(DOMINATION, 'neutral');
		self::triggerAndNextState('next');
	}
	function stDomination()
	{
		self::updateScoring();
//
		$players = [];
		foreach (Factions::list(false) as $color)
		{
			Factions::setStatus($color, 'exchange');
			if ($this->domination->countCardInLocation('hand', $color) > 0) $players[] = Factions::getPlayer($color);
		}
//
		$this->gamestate->setPlayersMultiactive($players, 'end', true);
	}
	function stDominationCardExchange()
	{
		$round = intval(self::getGameStateValue('round'));
//
		while ($color = Factions::getNext())
		{
			$player_id = Factions::getPlayer($color);
			if ($player_id > 0)
			{
				Factions::setActivation($color, 'yes');
//
				$cards = $this->domination->countCardInLocation('A', $color) + $this->domination->countCardInLocation('B', $color);
				if ($cards < 2)
				{
					if ($cards === 1 || $round === 3)
					{
						$this->gamestate->changeActivePlayer($player_id);
						return $this->gamestate->nextState('dominationCardExchange');
					}
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${player_name} draw a new card'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
					$this->domination->pickCard('deck', $color);
//
					$faction = ['color' => $color, 'domination' => []];
					foreach ($this->domination->getPlayerHand($color) as $domination) $faction['domination'][$domination['id']] = $domination['type'];
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyPlayer($player_id, 'updateFaction', '', ['faction' => $faction]);
//* -------------------------------------------------------------------------------------------------------- */
					foreach ($this->domination->getPlayerHand($color) as $domination) $faction['domination'][$domination['id']] = 'back';
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('updateFaction', '', ['player_id' => $player_id, 'faction' => $faction]);
//* -------------------------------------------------------------------------------------------------------- */
				}
			}
			Factions::setActivation($color, 'done');
		}
//
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
		if (Factions::getTechnology($color, 'Spirituality') === 6)
		{
			$toReveal = [];
			foreach (Sectors::getAll() as $sector) foreach (array_keys($this->SECTORS[Sectors::get($sector)]) as $hexagon)
				{
					$rotated = Sectors::rotate($hexagon, -Sectors::getOrientation($sector));
					$location = "$sector:$rotated";
					array_push($toReveal, ...Counters::getAtLocation($location, 'star'), ...Counters::getAtLocation($location, 'relic'));
				}
			foreach (array_diff($toReveal, Counters::listRevealed($color)) as $counter) self::reveal($color, 'counter', $counter);
		}
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-subphase">${LOG}</span>', ['LOG' => ['log' => clienttranslate('${player_name} Move/Combat Phase'), 'args' => ['player_name' => Factions::getName($color)]]]);
//* -------------------------------------------------------------------------------------------------------- */
		$player_id = Factions::getPlayer($color);
		if ($player_id < 0)
		{
			$ships = false;
			foreach (Ships::getAll($color) as $ship) if ($ship['location'] !== 'stock') $ships = true;
			if ($ships)
			{
				$dice = bga_rand(1, 6);
//
// #offboard population : 4 - Slavers roll 2 dice and use lower one for movement and growth action results
//
				if ($player_id === SLAVERS && Factions::getDP($color) >= 4)
				{
					$dice1 = bga_rand(1, 6);
					$dice2 = bga_rand(1, 6);
					$dice = min($dice1, $dice2);
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
			{
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('No ships to move'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
			}

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
			if ($player_id >= 0)
			{
				if ($winner || $canRetreat)
				{
					if ($player_id === 0) $player_id = Factions::getPlayer(Factions::getNotAutomas($attacker));
//
					Factions::setStatus($attacker, 'retreat', $defender);
//
					$this->gamestate->changeActivePlayer($player_id);
					return $this->gamestate->nextState('retreat');
				}
//
				$Evade = Ships::get(Ships::getFleet($defender, 'E'))['location'] === $location;
				if ($Evade)
				{
					if ($player_id === 0) $player_id = Factions::getPlayer(Factions::getNotAutomas($attacker));
//
					Factions::setStatus($attacker, 'retreat', $defender);
//
					if (Factions::getAdvancedFleetTactics($defender, 'E') === '2x')
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
									'log' => clienttranslate('${ships} ship piece(s)'),
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
					self::notifyAllPlayers('msg', clienttranslate('${player_name} roll ${DICE}'), [
						'player_name' => Factions::getName($defender), 'DICE' => $dice]);
//* -------------------------------------------------------------------------------------------------------- */
				}
				if ($roll && $dice >= $roll)
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
		$fleet = Ships::getFleet($attacker, 'A');
		$attackerAssault = $fleet && Ships::get($fleet)['location'] === $location;
		$defenderAssault = false;
		foreach ($defenders as $defender)
		{
			$fleet = Ships::getFleet($defender, 'A');
			if ($fleet && Ships::get($fleet)['location'] === $location)
			{
				$defenderAssault = true;
				break;
			}
		}
//
// ALLIANCE OF LIGHT (STS) : Your ships get +2 CV each when you are the attacker in combat against an STO player (even if there are also STS players involved)
//
		$STO = false;
		foreach ($defenders as $defender) if (Factions::getAlignment($defender) === 'STO') $STO = true;
		$allianceOfDarkness = Factions::getStarPeople($attacker) === 'Alliance' && Factions::getAlignment($attacker) == 'STS' && $STO;
//
		$attackerCVs = Ships::CV($attacker, $location, $defenderAssault, false, $allianceOfDarkness);
		foreach ($attackerCVs['fleets'] as $fleet => ['CV' => $CV, 'ships' => $ships])
		{
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', '<div style="color:black;background:#${color};">${LOG}</div>', ['color' => $attacker,
				'LOG' => [
					'log' => clienttranslate('<B>CV ${CV}</B>: <B>${fleet}</B> fleet with ${ships} ship(s)'),
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
					'log' => clienttranslate('<B>CV ${CV}</B>: ${ships} ship piece(s)'),
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
// ALLIANCE OF LIGHT (STO) : Your ships get +4 CV each when defending in combat
//
			$allianceOfLight = Factions::getStarPeople($defender) === 'Alliance' && Factions::getAlignment($defender) == 'STO';
//
			$defenderCVs = Ships::CV($defender, $location, $attackerAssault, $allianceOfLight, false);
			foreach ($defenderCVs['fleets'] as $fleet => ['CV' => $CV, 'ships' => $ships])
			{
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', '<div style="color:black;background:#${color};">${LOG}</div>', ['color' => $defender,
					'LOG' => [
						'log' => clienttranslate('<B>CV ${CV}</B>: <B>${fleet}</B> fleet with ${ships} ship(s)'),
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
						'log' => clienttranslate('<B>CV ${CV}</B>: ${ships} ship piece(s)'),
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
			foreach (Ships::getAtLocation($location, $attacker, 'fleet')as $fleet) $bonus |= Factions::getAdvancedFleetTactics($attacker, Ships::getStatus($fleet, 'fleet')) === 'DP';
			if ($bonus)
			{
				$DP = 3;
				self::gainDP($attacker, $DP);
				self::incStat($DP, 'DP_AFT', Factions::getPlayer($attacker));
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} +${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($attacker), 'faction' => ['color' => $attacker, 'DP' => Factions::getDP($attacker)]]);
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
				foreach (Ships::getAtLocation($location, $defender, 'fleet') as $fleet) $bonus |= Factions::getAdvancedFleetTactics($defender, Ships::getStatus($fleet, 'fleet')) === 'DP';
				if ($bonus)
				{
					$DP = 3;
					self::gainDP($defender, $DP);
					self::incStat($DP, 'DP_AFT', Factions::getPlayer($defender));
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} +${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($defender), 'faction' => ['color' => $defender, 'DP' => Factions::getDP($defender)]]);
//* -------------------------------------------------------------------------------------------------------- */
				}
//
				$ships[$defender] = 0;
				foreach (Ships::getAtLocation($location, $defender) as $shipID)
				{
					$ship = Ships::get($shipID);
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
		if (self::getGameStateValue('galacticStory') == JOURNEYS && self::ERA() === 'Second')
		{
			foreach (($attackerIsWinner ? [$attacker] : $defenders) as $color)
			{
				$player_id = Factions::getPlayer($color);
				if (intval($location[0]) !== Factions::getHomeStar($color) && $player_id > 0)
				{
//* -------------------------------------------------------------------------------------------------------- */
					if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('All players score 2 DP for every battle they win outside of their home star sector')]);
//* -------------------------------------------------------------------------------------------------------- */
					$DP = 2;
					self::gainDP($color, $DP);
					self::incStat($DP, 'DP_GS', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} +${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
				}
			}
		}
//
// MIGRATIONS Second : All players score 1 DP for every battle they win
// Battles where all opposing ships retreated before combat are not counted
//
		if (self::getGameStateValue('galacticStory') == MIGRATIONS && self::ERA() === 'Second')
		{
			foreach (($attackerIsWinner ? [$attacker] : $defenders) as $color)
			{
				$player_id = Factions::getPlayer($color);
				if ($player_id > 0)
				{
//* -------------------------------------------------------------------------------------------------------- */
					if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('All players score 1 DP for every battle they win. Battles where all opposing ships retreated before combat are not counted')]);
//* -------------------------------------------------------------------------------------------------------- */
					$DP = 1;
					self::gainDP($color, $DP);
					self::incStat($DP, 'DP_GS', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} scores ${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
				}
			}
		}
//
		$player_id = Factions::getPlayer(Factions::getStatus($attacker, 'winner'));
		if ($player_id < 0) return self::acBattleLoss($winner, Automas::battleLoss($attacker, $defenders, $totalVictory), true);
		if ($player_id === 0) $player_id = Factions::getPlayer(Factions::getNotAutomas($attacker));
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
			$stock = Factions::getStatus($color, 'stock');
			foreach ($counters as $counter)
			{
				$index = array_search($counter, $stock);
				if ($index === false) throw new BgaVisibleSystemException('Invalid counter: ' . $counter);
				unset($stock[$index]);
			}
			Factions::setStatus($color, 'stock', array_values($stock));
//
			$oval = 2 + (Factions::getStatus($color, 'bonus') === 'Grow' ? 1 : 0);
			$square = 1;
			foreach ($counters as $counter)
			{
				if (in_array($counter, ['research', 'growPopulation', 'gainStar', 'gainStar', 'buildShips', 'switchAlignment'])) $oval--;
				if (in_array($counter, array_keys(Factions::TECHNOLOGIES,))) $square--;
			}
//
			$DP = 0;
			if ($oval < 0) $DP += $oval * Factions::ADDITIONAL[Factions::getTechnology($color, 'Genetics')];
			if ($square < 0 && Factions::getTechnology($color, 'Robotics') < 6) $DP += $square * 2;
			if ($DP)
			{
				self::gainDP($color, $DP);
				self::incStat($DP, 'DP_LOST', Factions::getPlayer($color));
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} loses ${DP} DP'), ['DP' => -$DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
			}
		}
		return $this->gamestate->nextState('next');
	}
	function stBuriedShips()
	{
		$color = Factions::getActive();
		['location' => $location, 'ships' => $buriedShips] = Factions::getStatus($color, 'buriedShips');
		if ($buriedShips === 0) return $this->gamestate->nextState('next');
	}
	function stStealTechnology()
	{
		$player_id = self::getActivePlayerId();
		$color = Factions::getColor($player_id);
//
		self::argStealTechnology();
		if (!$this->possible['counters'])
		{
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyPlayer($player_id, 'msg', clienttranslate('No technology to learn'), []);
//* -------------------------------------------------------------------------------------------------------- */
			$this->gamestate->nextState('continue');
		}
	}
	function stResearchPlus()
	{
		$player_id = self::getActivePlayerId();
		$color = Factions::getColor($player_id);
//
		$technologies = Factions::getStatus($color, 'researchPlus');
		if ($technologies)
		{
			$technology = array_pop($technologies);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${player_name} gains a <B>${TECHNOLOGY}+ effect</B>'), ['player_name' => Factions::getName($color), 'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology]]);
//* -------------------------------------------------------------------------------------------------------- */
			switch ($technology)
			{
				case 'Military':
					return;
				case 'Spirituality':
					return;
				case 'Propulsion':
					Factions::setStatus($color, 'counters', array_merge(Factions::getStatus($color, 'counters'), ['gainStar', 'gainStar']));
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${player_name} gets 2 free Gain Star actions'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
					break;
				case 'Robotics':
					if ($technologies) Factions::setStatus($color, 'researchPlus', $technologies);
					else Factions::setStatus($color, 'researchPlus');
					$otherTechnology = Factions::getStatus($color, 'otherTechnology');
					if ($otherTechnology)
					{
						self::gainTechnology($color, $otherTechnology);
//
						$DP = -2;
						self::gainDP($color, $DP);
						self::incStat($DP, 'DP_LOST', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} loses ${DP} DP'), ['DP' => -$DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
					}
					break;
				case 'Genetics':
					if ($technologies) Factions::setStatus($color, 'researchPlus', $technologies);
					else Factions::setStatus($color, 'researchPlus');
					Factions::setStatus($color, 'counters', array_merge(Factions::getStatus($color, 'counters'), ['growPopulation+']));
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${player_name} gets a free Grow Population action with 2 additional bonus population'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
					break;
			}
		}
//
		if ($technologies) Factions::setStatus($color, 'researchPlus', $technologies);
		else Factions::setStatus($color, 'researchPlus');
//
		self::triggerAndNextState('end');
	}
	function stEmergencyReserve()
	{
		$player_id = self::getActivePlayerId();
		$color = Factions::getColor($player_id);
//
		Factions::useEmergencyReserve($color);
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('useEmergencyReserve', clienttranslate('${player_name} uses emergency reserve'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
	}
	function stAdvancedFleetTactics()
	{
		$players = array_values(Factions::advancedFleetTactics());
		$this->gamestate->setPlayersMultiactive($players, 'next', true);
	}
	function stGrowthPhase()
	{
		Factions::setActivation();
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-subphase">${LOG}</span>', [
			'i18n' => ['LOG'], 'LOG' => clienttranslate('Growth Phase')
		]);
//* -------------------------------------------------------------------------------------------------------- */
		foreach (Factions::list() as $color)
		{
			Factions::setStatus($color, 'stock', ['research', 'growPopulation', 'gainStar', 'gainStar', 'buildShips', 'switchAlignment', 'Military', 'Spirituality', 'Propulsion', 'Robotics', 'Genetics', 'changeTurnOrderUp', 'changeTurnOrderDown']);
			Factions::setStatus($color, 'counters', []);
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
			if (Factions::getPlayer($color) === 0) Factions::setStatus($color, 'counters', []);
			if (Factions::getPlayer($color) < 0)
			{
				if (Factions::getPlayer($color) === FARMERS && !Ships::getAll($color))
				{
					$dice = 6;
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${player_name} uses ${DICE}'), ['player_name' => Factions::getName($color), 'DICE' => $dice]);
//* -------------------------------------------------------------------------------------------------------- */
				}
				else if (Factions::getPlayer($color) === SLAVERS && Factions::getDP($color) >= 4)
				{
					$dice1 = bga_rand(1, 6);
					$dice2 = bga_rand(1, 6);
					$dice = min($dice1, $dice2);
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${player_name} roll ${DICE1} ${DICE2}'), ['player_name' => Factions::getName($color), 'DICE1' => $dice1, 'DICE2' => $dice2]);
					self::notifyAllPlayers('msg', clienttranslate('${DICE} is used'), ['player_name' => Factions::getName($color), 'DICE' => $dice]);
//* -------------------------------------------------------------------------------------------------------- */
				}
				else
				{
					$dice = bga_rand(1, 6);
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${player_name} roll ${DICE}'), ['player_name' => Factions::getName($color), 'DICE' => $dice]);
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
//
// ANCHARA SPECIAL STO & STS: If you have chosen the Switch Alignment growth action counter, then on your turn of the growth phase, you may select and execute an additional, unused growth action counter at no cost
// To do Research, you must have already chosen a technology for your square counter choice
//
				if (Factions::getTechnology($color, 'Spirituality') < 5) self::switchAlignment($color);
//* -------------------------------------------------------------------------------------------------------- */
				else self::notifyAllPlayers('msg', clienttranslate('${player_name} can not switch alignment'), ['player_name' => Factions::getName($color)]);
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
//
//		self::triggerEvent(DOMINATION, 'neutral');
		self::triggerAndNextState('next');
	}
	function stGrowthActions()
	{
		$color = Factions::getNext();
		if (!$color) return $this->gamestate->nextState('next');
//
		Factions::setActivation($color, 'yes');
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-subphase">${LOG}</span>', ['LOG' => ['log' => clienttranslate('${player_name} Growth Phase'), 'args' => ['player_name' => Factions::getName($color)]]]);
//* -------------------------------------------------------------------------------------------------------- */
		$player_id = Factions::getPlayer($color);
		if ($player_id <= 0)
		{
			if ($player_id < 0) Automas::actions($this, $color);
			Factions::setActivation($color, 'done');
//
//			foreach (Factions::list(false) as $color)
//			{
//				if (Factions::getStatus($color, 'evacuate'))
//				{
//					$this->gamestate->changeActivePlayer(Factions::getPlayer($color));
//					return $this->gamestate->nextState('evacuate');
//				}
//			}
			return self::triggerAndNextState('continue');
		}
//
		$this->gamestate->changeActivePlayer($player_id);
		$this->gamestate->nextState('nextPlayer');
	}
	function stTradingPhase()
	{
		Factions::setActivation('ALL', 'done');
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-subphase">${LOG}</span>', [
			'i18n' => ['LOG'], 'LOG' => clienttranslate('Trading Phase')
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
									self::notifyAllPlayers('msg', clienttranslate('${player_name} roll ${DICE}'), [
										'player_name' => Factions::getName($with), 'DICE' => $dice]);
//* -------------------------------------------------------------------------------------------------------- */
								}
								if ($roll && $dice > $roll)
								{
//* -------------------------------------------------------------------------------------------------------- */
									self::notifyAllPlayers('msg', clienttranslate('${player_name} not willing to trade'), ['player_name' => Factions::getName($with)]);
//* -------------------------------------------------------------------------------------------------------- */
									unset($inContact[$index]);
								}
								else
								{
//* -------------------------------------------------------------------------------------------------------- */
									self::notifyAllPlayers('msg', clienttranslate('${player_name} willing to trade'), ['player_name' => Factions::getName($with)]);
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
		if (self::getPlayersNumber() === 2 && sizeof($players) > 1)
		{
			foreach (Factions::list(false) as $color)
			{
				$player_id = Factions::getPlayer($color);
				if (in_array($player_id, $players))
				{
					if (Factions::getAlignment($color) === 'STO')
					{
						$players = [$player_id];
						break;
					}
				}
			}
			$players = array_slice($players, 0, 1);
		}
//
		if ($this->gamestate->setPlayersMultiactive($players, 'next', true)) return;
		$this->gamestate->nextState('tradingPhase');
	}
	function stTradingPhaseEnd()
	{
//
// #offboard population : 3 - Slavers gain 1 technology level in a trading phase in which they did not trade.
//
		$slavers = Factions::getAutoma(SLAVERS);
		if ($slavers && Factions::getDP($slavers) >= 3 && !Factions::getStatus($slavers, 'trade'))
		{
			$technologies = [];
			Automas::randomTechnology($slavers, $technologies);
			while ($technology = array_shift($technologies)) self::acResearch($slavers, [$technology], true);
		}
//
		$this->gamestate->nextState('next');
	}
	function stScoringPhase()
	{
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-subphase">${LOG}</span>', [
			'i18n' => ['LOG'], 'LOG' => clienttranslate('Scoring Phase')
		]);
//* -------------------------------------------------------------------------------------------------------- */
		$galacticStory = self::getGameStateValue('galacticStory');
//
		switch (self::ERA())
		{
			case 'First':
				{
//
// First : Every player with the STO alignment at the end of a round scores 1 DP
//
					if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('Every player with the STO alignment at the end of a round scores 1 DP')]);
					foreach (Factions::list(false) as $color)
					{
						if (Factions::getAlignment($color) === 'STO')
						{
							$DP = 1;
							self::gainDP($color, $DP);
							self::incStat($DP, 'DP_GS', Factions::getPlayer($color));
							Factions::setStatus($color, 'alignment', 'gain');
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} +${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
						}
					}
					switch ($galacticStory)
					{
						case JOURNEYS:
//
// JOURNEYS First : All players score 1 DP for every player they are “in contact” with at the end of the round (including the automa in a 2-player game)
//
							if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('All players score 1 DP for every player they are “in contact” with at the end of the round (including the automa in a 2-player game')]);
							foreach (Factions::list(false) as $color)
							{
								$DP = sizeof(Factions::inContact($color, 'contact'));
								if ($DP)
								{
									self::gainDP($color, $DP);
									self::incStat($DP, 'DP_GS', Factions::getPlayer($color));
//* -------------------------------------------------------------------------------------------------------- */
									self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} +${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
								}
							}
							break;
//
						case MIGRATIONS:
						case RIVALRY:
						case WAR:
//
							break;
					}
				}
				break;
			case 'Second':
				{
//
// Second : Every player with the STS alignment at the end of a round scores 1 DP
//
					if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('Every player with the STS alignment at the end of a round scores 1 DP')]);
					foreach (Factions::list(false) as $color)
					{
						if (Factions::getAlignment($color) === 'STS')
						{
							$DP = 1;
							self::gainDP($color, $DP);
							self::incStat($DP, 'DP_GS', Factions::getPlayer($color));
							Factions::setStatus($color, 'alignment', 'gain');
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} +${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
						}
					}
					switch ($galacticStory)
					{
//
						case JOURNEYS:
//
// JOURNEYS Second : Every player “at war” with at least one other player at the end of the round scores 1 DP
//
							if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('Every player “at war” with at least one other player at the end of the round scores 1 DP')]);
							foreach (Factions::list(false) as $color)
							{
								$DP = sizeof(Factions::atWar($color)) === 0 ? 0 : 1;
								if ($DP)
								{
									self::gainDP($color, $DP);
									self::incStat($DP, 'DP_GS', Factions::getPlayer($color));
//* -------------------------------------------------------------------------------------------------------- */
									self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} +${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
								}
							}
							break;
//
						case MIGRATIONS:
//
// MIGRATIONS Second : Every player “at war” with at least one other player at the end of the round scores 1 DP
//
							if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('Every player “at war” with at least one other player at the end of the round scores 1 DP')]);
							foreach (Factions::list(false) as $color)
							{
								$DP = sizeof(Factions::atWar($color)) === 0 ? 0 : 1;
								if ($DP)
								{
									self::gainDP($color, $DP);
									self::incStat($DP, 'DP_GS', Factions::getPlayer($color));
//* -------------------------------------------------------------------------------------------------------- */
									self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} +${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
								}
							}
							break;
//
						case RIVALRY:
//
// RIVALRY Second : Every player “at war” with at least one other player at the end of the round scores 1 DP
//
							if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('Every player “at war” with at least one other player at the end of the round scores 1 DP')]);
							foreach (Factions::list(false) as $color)
							{
								$DP = sizeof(Factions::atWar($color)) === 0 ? 0 : 1;
								if ($DP)
								{
									self::gainDP($color, $DP);
									self::incStat($DP, 'DP_GS', Factions::getPlayer($color));
//* -------------------------------------------------------------------------------------------------------- */
									self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} +${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
								}
							}
//
// RIVALRY Second : All players score 1 DP for every star of another player they are blocking at the end of the round (i.e., for each hostile star where they are present)
// Multiple players can score for the same star they are blocking
//
							if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('All players score 1 DP for every star of another player they are blocking at the end of the round')]);
							foreach (Factions::list(false) as $color)
							{
								foreach (Factions::atWar($color) as $otherColor)
								{
									if (Factions::getTechnology($otherColor, 'Spirituality') >= 5) continue;
									foreach (Counters::getPopulations($color, false) as $location) if (Ships::getAtLocation($location, $color)) $DP++;
								}
								if ($DP)
								{
									self::gainDP($color, $DP);
									self::incStat($DP, 'DP_GS', Factions::getPlayer($color));
//* -------------------------------------------------------------------------------------------------------- */
									self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} +${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
								}
							}
							break;
//
						case WAR:
//
// WAR Second : Every player “at war” with at least one other player at the end of the round scores 2 DP
//
							if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('Every player “at war” with at least one other player at the end of the round scores 2 DP')]);
							foreach (Factions::list(false) as $color)
							{
								$DP = sizeof(Factions::atWar($color)) === 0 ? 0 : 2;
								if ($DP)
								{
									self::gainDP($color, $DP);
									self::incStat($DP, 'DP_GS', Factions::getPlayer($color));
//* -------------------------------------------------------------------------------------------------------- */
									self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} +${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
								}
							}
							break;
					}
				}
				break;
			case 'Third':
				{
//
// Third : Every player with the STO alignment at the end of a round scores 1 DP
//
					if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('Every player with the STO alignment at the end of a round scores 1 DP')]);
					foreach (Factions::list(false) as $color)
					{
						if (Factions::getAlignment($color) === 'STO')
						{
							$DP = 1;
							self::gainDP($color, $DP);
							self::incStat($DP, 'DP_GS', Factions::getPlayer($color));
							Factions::setStatus($color, 'alignment', 'gain');
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} +${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
						}
					}
					switch ($galacticStory)
					{
//
						case JOURNEYS:
//
// JOURNEYS Third : At the end of the round, each player who researched Spirituality in that round and has the highest level (ties allowed) in that field among all the players who also researched that, scores 7 minus their Spirituality level
// The same applies for Propulsion. A Research action that did not result in an increased technology level does not count, neither for scoring nor for preventing scoring (*)
//
							foreach (['Spirituality', 'Propulsion'] as $technology)
							{
								if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${LOG}</span>', ['LOG' => ['log' => clienttranslate('At the end of the round, each player who researched ${technology} in that round and has the highest level (ties allowed) in that field among all the players who also researched that, scores 7 minus their ${technology} level'), 'args' => ['technology' => $technology]]]);
								foreach (Factions::list(false) as $color)
								{
									if (in_array($technology, Factions::getStatus($color, 'used')))
									{
										$best = [];
										foreach (Factions::list() as $otherColor) if (in_array($technology, Factions::getStatus($otherColor, 'used'))) $best[$otherColor] = Factions::getTechnology($otherColor, $technology);
										if (in_array($color, array_keys($best, max($best))))
										{
											$DP = 7 - Factions::getTechnology($color, $technology);
											self::gainDP($color, $DP);
											self::incStat($DP, 'DP_GS', Factions::getPlayer($color));
//* -------------------------------------------------------------------------------------------------------- */
											self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} +${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
										}
									}
								}
							}
							break;
//
						case MIGRATIONS:
//
// MIGRATIONS Third : Every player who is the only player to research a certain technology field in a round in this era scores 4 DP (per such field)
// Technology levels gained by any other means (such as taking a star from another player) do not count for this, neither for scoring nor for preventing scoring
//
							if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('Every player who is the only player to research a certain technology field in a round in this era scores 4 DP (per such field)')]);
							foreach (array_keys(Factions::TECHNOLOGIES) as $technology)
							{
								$research = [];
								foreach (Factions::list(true) as $color) if (in_array($technology, Factions::getStatus($color, 'used'))) $research[] = $color;
								if (sizeof($research) === 1)
								{
									$color = array_pop($research);
									$player_id = Factions::getPlayer($color);
									if ($player_id > 0)
									{
										$DP = 4;
										self::gainDP($color, $DP);
										self::incStat($DP, 'DP_GS', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
										self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} +${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
									}
								}
							}
							break;
//
						case RIVALRY:
//
// RIVALRY Third : For every technology field, the player who has the highest level in that field at the end of the round scores 3 DP (even if tied with other players)
//
							if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('For every technology field, the player who has the highest level in that field at the end of the round scores 3 DP (even if tied with other players)')]);
							foreach (array_keys(Factions::TECHNOLOGIES) as $technology)
							{
								$research = [];
								foreach (Factions::list(true) as $color) $research[$color] = Factions::getTechnology($color, $technology);
								foreach (array_keys($research, max($research)) as $color)
								{
									$player_id = Factions::getPlayer($color);
									if ($player_id > 0)
									{
										$DP = 3;
										self::gainDP($color, $DP);
										self::incStat($DP, 'DP_GS', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
										self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} +${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
									}
								}
							}
							break;
//
						case WAR:
//
// WAR Third : At the end of the round, each player who researched Military in that round and has the highest level (ties allowed) in that field among all the players who also researched that, scores 7 minus their Military level
// The same applies for Robotics. A Research action that did not result in an increased technology level does not count, neither for scoring nor for preventing scoring (*)
//
							foreach (['Military', 'Robotics'] as $technology)
							{
								if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${LOG}</span>', ['LOG' => ['log' => clienttranslate('At the end of the round, each player who researched ${technology} in that round and has the highest level (ties allowed) in that field among all the players who also researched that, scores 7 minus their ${technology} level'), 'args' => ['technology' => $technology]]]);
								foreach (Factions::list(false) as $color)
								{
									if (in_array($technology, Factions::getStatus($color, 'used')))
									{
										$best = [];
										foreach (Factions::list() as $otherColor) if (in_array($technology, Factions::getStatus($otherColor, 'used'))) $best[$otherColor] = Factions::getTechnology($otherColor, $technology);
										if (in_array($color, array_keys($best, max($best))))
										{
											$DP = 7 - Factions::getTechnology($color, $technology);
											self::gainDP($color, $DP);
											self::incStat($DP, 'DP_GS', Factions::getPlayer($color));
//* -------------------------------------------------------------------------------------------------------- */
											self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} +${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
										}
									}
								}
							}
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
		self::triggerEvent(DOMINATION, 'neutral');
		self::triggerAndNextState('next');
	}
	function stEndOfRound()
	{
		$round = self::getGameStateValue('round');
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-phase">${LOG} ${round}</span>', [
			'i18n' => ['LOG'], 'LOG' => clienttranslate('End of round'), 'round' => $round]);
//* -------------------------------------------------------------------------------------------------------- */
		foreach (Factions::list() as $color)
		{
			Factions::setStatus($color, 'counters');
			Factions::setStatus($color, 'stock');
			Factions::setStatus($color, 'used');
//
			Factions::setStatus($color, 'alignment');
			Factions::setStatus($color, 'view');
			Factions::setStatus($color, 'etheric');
			Factions::setStatus($color, 'otherTechnology');
			Factions::setStatus($color, 'exchange');
//
			Factions::setStatus($color, 'trade');
			Factions::setStatus($color, 'inContact');
//
			$toClean = self::getUniqueValueFromDB("SELECT status FROM factions WHERE color = '$color'");
			if ($toClean !== '{}') self::notifyAllPlayers('msg', $toClean, []);
		}
//
		self::updateScoring();
		if ($round < 8) return $this->gamestate->nextState('nextRound');
//
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-phase">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('Game End Scoring')]);
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-subphase">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('Every player scores DP equal to the highest number on their population track without a disc')]);
//* -------------------------------------------------------------------------------------------------------- */
		foreach (Factions::list(false) as $color)
		{
			$populationScore[$color] = Factions::POPULATION[Factions::getPopulation($color)];
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${player_name} +${DP} DP'), ['DP' => $populationScore[$color], 'player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
// Final scoring
//
		foreach (Factions::list(false) as $color)
		{
			self::gainDP($color, $populationScore[$color]);
			self::incStat($populationScore[$color], 'DP_POP', Factions::getPlayer($color));
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', '<span class="ERA-subphase">${LOG}</span>', ['i18n' => ['LOG'], 'LOG' => clienttranslate('For every sector, the player with the most ships there scores 4 DP (in the case of a tie all tied players score this)')]);
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
						self::incStat($DP, 'DP_MAJ', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('msg', clienttranslate('${player_name} +${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
					}
				}
			}
		}
		foreach (Factions::list(false) as $color)
		{
			$player_id = Factions::getPlayer($color);
			self::setStat(self::dbGetScore($player_id), 'DP', $player_id);
//
// If players are tied then the one with the highest number of stars among the tied wins.
// If this is also a tie, then use the turn order
// The player who is first in turn order among those tied wins
//
			$tie = sizeof(Counters::getPopulations($color)) * 10 + (self::getPlayersNumber() - Factions::getOrder($color));
//
			self::dbSetScore($player_id, self::dbGetScore($player_id), $tie);
		}
//
// Legacy
//
		if (self::getPlayersNumber() === 1)
		{
			$player_id = Factions::getPlayer(Factions::getNotAutomas());
			$difficulty = intval(self::getGameStateValue('difficulty'));
			$score = self::dbGetScore($player_id);
//
			$datas = self::retrieveLegacyData($player_id, LEGACYDATA);
			$legacy = $datas ? json_decode($datas[LEGACYDATA]) : [0 => '', 1 => '', 2 => '', 3 => ''];
			$legacy[$difficulty] = ($legacy[$difficulty] === '') ? $score : max($score, $legacy[$difficulty]);
//
			if ($legacy[0] !== '') self::setStat($legacy[0], 'easy', $player_id);
			if ($legacy[1] !== '') self::setStat($legacy[1], 'standard', $player_id);
			if ($legacy[2] !== '') self::setStat($legacy[2], 'hard', $player_id);
			if ($legacy[3] !== '') self::setStat($legacy[3], 'insane', $player_id);
//
			self::storeLegacyData($player_id, LEGACYDATA, $legacy);
		}
//
		$this->gamestate->nextState('gameEnd');
	}
}
