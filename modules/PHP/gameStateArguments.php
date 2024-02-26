<?php

/**
 *
 * @author Lunalol
 */
trait gameStateArguments
{
	function argLevelOfDifficulty()
	{
		$player_id = self::getActivePlayerId();
//
		$datas = self::retrieveLegacyData($player_id, 'ALPHA');
		$legacy = $datas ? json_decode($datas['ALPHA']) : [0 => '', 1 => '', 2 => '', 3 => ''];
//
		return ['legacy' => $legacy];
	}
	function argStarPeople()
	{
		$private = [];
		foreach (Factions::list(false) as $color)
		{
			$player_id = Factions::getPlayer($color);
			if ($player_id > 0)
			{
				$private[$player_id]['color'] = $color;
				$private[$player_id]['starPeople'] = Factions::getStatus($color, 'starPeople');
				$private[$player_id]['alignment'] = false;
			}
		}
//
		return ['_private' => $private];
	}
	function argAlignment()
	{
		$private = [];
		foreach (Factions::list(false) as $color)
		{
			$player_id = Factions::getPlayer($color);
			if ($player_id > 0)
			{
				$private[$player_id]['color'] = $color;
				$private[$player_id]['starPeople'] = [Factions::getStarPeople($color)];
				$private[$player_id]['alignment'] = Factions::getStatus($color, 'alignment');
			}
		}
//
		return ['_private' => $private];
	}
	function argIndividualChoice()
	{
		$color = Factions::getActive();
		$player_id = Factions::getPlayer($color);
//
		$this->possible = ['counters' => [], 'color' => $color];
		foreach (array_keys($this->TECHNOLOGIES) as $technology) if (Factions::getTechnology($color, $technology) < 2) $this->possible['counters'][] = $technology;
//
		return ['_private' => [$player_id => $this->possible], 'active' => $color];
	}
	function argDomination()
	{
		$counters = [];
		foreach (Factions::list(true) as $otherColor)
		{
			$counters[$otherColor] = ['available' => [], 'used' => []];
			foreach (Factions::getStatus($otherColor, 'counters') ?? [] as $counter) $counters[$otherColor]['available'][] = $counter;
			foreach (Factions::getStatus($otherColor, 'used') ?? [] as $counter) $counters[$otherColor]['used'][] = $counter;
		}
//
		$event = $this->getObjectFromDB("SELECT * FROM stack WHERE new_state = 0 ORDER BY id DESC LIMIT 1");
		$phase = $this->gamestate->states[$event['trigger_state']]['name'];
//
		return ['counters' => $counters, 'phase' => $this->PHASES[$phase], 'lastChance' => +self::getGameStateValue('round') === 8 && $phase === 'scoringPhase'];
	}
	function argDominationCardExchange()
	{
		$color = Factions::getActive();
		$player_id = Factions::getPlayer($color);
//
		return ['active' => $color, '_private' => [$player_id => ['hand' => $this->domination->getPlayerHand($color)]]];
	}
	function argFleets()
	{
		$color = Factions::getActive();
		$player_id = Factions::getPlayer($color);
//
		$this->possible = [
			'ships' => [], 'fleets' => array_fill_keys(Ships::FLEETS, ['location' => null, 'ships' => 0]),
			'stars' => array_keys(Counters::getPopulations($color)),
			'view' => Factions::getStatus($color, 'view'),
			'declareWar' => Factions::atPeace($color)
		];
		foreach (Ships::getAll($color) as $ship)
		{
			$this->possible['ships'][] = $ship['id'];
			if ($ship['fleet'] === 'fleet')
			{
				$fleet = Ships::getStatus($ship['id'], 'fleet');
				$this->possible['fleets'][$fleet] = $ship;
				$this->possible['fleets'][$fleet]['ships'] = intval(Ships::getStatus($ship['id'], 'ships'));
			}
		}
//
		$ancientPyramids = Counters::getRelic(ANCIENTPYRAMIDS);
		if ($ancientPyramids && Counters::getStatus($ancientPyramids, 'owner') === $ship['color']) $this->possible['ancientPyramids'] = intval(Counters::getStatus($ancientPyramids, 'available'));
//
		return ['_private' => [$player_id => $this->possible],
			'active' => $color, 'undo' => +self::getUniqueValueFromDB("SELECT COALESCE(MAX(undoID), 0) FROM `undo` WHERE color = '$color'")];
	}
	function argAdvancedFleetTactics()
	{
		$this->possible = [];
		foreach (Factions::list(false) as $color)
		{
			$player_id = Factions::getPlayer($color);
			if ($player_id > 0)
			{
				$this->possible[$player_id]['color'] = $color;
				$this->possible[$player_id]['fleets'] = array_fill_keys(Ships::FLEETS, ['location' => null, 'ships' => 0]);
				foreach (Ships::getAll($color, 'fleet') as $ship)
				{
					$fleet = Ships::getStatus($ship['id'], 'fleet');
//
					$this->possible[$player_id]['fleets'][$fleet] = $ship;
					$this->possible[$player_id]['fleets'][$fleet]['ships'] = intval(Ships::getStatus($ship['id'], 'ships'));
				}
				$this->possible[$player_id]['advancedFleetTactics'] = Factions::getAllAdvancedFleetTactics($color);
			}
		}
//
		return ['_private' => $this->possible];
	}
	function argMovement()
	{
		$color = Factions::getActive();
		$player_id = Factions::getPlayer($color);
//
		$revealed = Counters::listRevealed($color);
		$this->possible = [
			'move' => [], 'scout' => [],
			'view' => Factions::getStatus($color, 'view'),
		];
//
		foreach (Ships::getAll($color) as $ship)
		{
			if ($ship['activation'] !== 'done' && $ship['location'] !== 'stock')
			{
				switch ($ship['fleet'])
				{
					case 'ship':
						$this->possible['move'][$ship['id']] = Ships::movement($ship);
						break;
					case 'fleet':
						$this->possible['move'][$ship['id']] = Ships::movement($ship);
						break;
				}
			}
			$counters = array_diff(array_merge(Counters::getAtLocation($ship['location'], 'star'), Counters::getAtLocation($ship['location'], 'relic')), $revealed);
			if ($counters) $this->possible['scout'][$ship['id']] = $counters;
		}
//
		foreach (Ships::getAll($color, 'fleet') as $ship)
		{
			$fleet = Ships::getStatus($ship['id'], 'fleet');
//
			$this->possible['fleets'][$fleet] = $ship;
			$this->possible['fleets'][$fleet]['ships'] = intval(Ships::getStatus($ship['id'], 'ships'));
		}
//
		$ancientPyramids = Counters::getRelic(ANCIENTPYRAMIDS);
		if ($ancientPyramids && Counters::getStatus($ancientPyramids, 'owner') === $ship['color']) $this->possible['ancientPyramids'] = intval(Counters::getStatus($ancientPyramids, 'available'));
//
		$PlanetaryDeathRay = Counters::getRelic(PLANETARYDEATHRAY);
		if ($PlanetaryDeathRay && Counters::getStatus($PlanetaryDeathRay, 'owner') === $ship['color'])
		{
			$this->possible['planetaryDeathRay'] = intval(Counters::getStatus($PlanetaryDeathRay, 'available'));
			$this->possible['planetaryDeathRayTargets'] = Sectors::range(Counters::get($PlanetaryDeathRay)['location'], 3);
		}
//
		return ['_private' => [$player_id => $this->possible],
			'active' => $color, 'undo' => +self::getUniqueValueFromDB("SELECT COALESCE(MAX(undoID), 0) FROM `undo` WHERE color = '$color'")];
	}
	function argCombatChoice()
	{
		$color = Factions::getActive();
//
		$this->possible = Ships::getConflictLocation($color);
		return ['combatChoice' => $this->possible, 'active' => $color];
	}
	function argRetreat()
	{
		$attacker = Factions::getActive();
		$defender = Factions::getStatus($attacker, 'retreat');
		$location = Factions::getStatus($attacker, 'combat');
//
		$this->possible = Ships::retreatLocations($defender, $location);
		return ['retreat' => $this->possible, 'active' => $defender, 'winner' => Factions::getStatus($attacker, 'winner'), 'location' => $location];
	}
	function argBattleLoss()
	{
		$attacker = Factions::getActive();
		$location = Factions::getStatus($attacker, 'combat');
//
		$defenders = Ships::getConflictFactions($attacker, $location);
		$winner = Factions::getStatus($attacker, 'winner');
		$totalVictory = Factions::getStatus($attacker, 'totalVictory');
//
		$this->possible = ['winner' => [], 'losers' => []];

		$ships = 0;
		foreach (Ships::getAtLocation($location, $attacker) as $shipID)
		{
			$ship = Ships::get($shipID);
			switch ($ship['fleet'])
			{
				case 'ship':
					$ships++;
					break;
				case 'fleet':
					$this->possible[$attacker === $winner ? 'winner' : 'losers'][$attacker][Ships::getStatus($shipID, 'fleet')] = intval(Ships::getStatus($shipID, 'ships'));
					break;
			}
			if ($ship > 0) $this->possible[($attacker === $winner) ? 'winner' : 'losers'][$attacker]['ships'] = $ships;
		}
//
		foreach ($defenders as $defender)
		{
			$ships = 0;
			foreach (Ships::getAtLocation($location, $defender) as $shipID)
			{
				$ship = Ships::get($shipID);
				switch ($ship['fleet'])
				{
					case 'ship':
						$ships++;
						break;
					case 'fleet':
						$this->possible[$attacker !== $winner ? 'winner' : 'losers'][$defender][Ships::getStatus($shipID, 'fleet')] = intval(Ships::getStatus($shipID, 'ships'));
						break;
				}
				if ($ship > 0) $this->possible[$attacker !== $winner ? 'winner' : 'losers'][$defender]['ships'] = $ships;
			}
		}
//
		return ['location' => $location, 'winner' => $this->possible['winner'], 'losers' => $this->possible['losers'], 'totalVictory' => $totalVictory, 'active' => $winner];
	}
	function argSelectCounters()
	{
		$this->possible = [];
		foreach (Factions::list(false) as $color)
		{
			$player_id = Factions::getPlayer($color);
			if ($player_id > 0)
			{
				$this->possible[$player_id]['color'] = $color;
				if ($this->gamestate->isPlayerActive($player_id))
				{
					$this->possible[$player_id]['counters'] = Factions::getStatus($color, 'stock');
//
					$this->possible[$player_id]['oval'] = 2 + (Factions::getStatus($color, 'bonus') === 'Grow' ? 1 : 0);
					$this->possible[$player_id]['additionalOvalCost'] = Factions::ADDITIONAL[Factions::getTechnology($color, 'Genetics')];
//
					$this->possible[$player_id]['square'] = (Factions::getTechnology($color, 'Robotics') >= 5 ? 2 : 1);
					$this->possible[$player_id]['additionalSquareCost'] = Factions::getTechnology($color, 'Robotics') === 5 ? 2 : 0;
//
					$homeStar = Ships::getHomeStarLocation($color);
//
					$this->possible[$player_id]['additional'] = 0;
					foreach (Counters::getPopulations($color, true) as $location => $population) if ($population >= 5 && $location !== $homeStar) $this->possible[$player_id]['additional']++;
				}
				else $this->possible[$player_id]['counters'] = Factions::getStatus($color, 'counters');
			}
		}
//
		return ['_private' => $this->possible];
	}
	function argBlockAction()
	{
		$this->possible = [];
//
		$color = Factions::getActive();
		foreach (Factions::list(false) as $otherColor)
		{
			$player_id = Factions::getPlayer($otherColor);
			if ($this->gamestate->isPlayerActive($player_id))
			{
				$action = Factions::getStatus($color, 'action');
//
				$this->possible[$player_id]['color'] = $otherColor;
				$this->possible[$player_id]['action'] = $action['name'];
				$this->possible[$player_id]['locations'] = [];
				foreach ($action['locations'] as $location) if (Ships::getAtLocation($location, $otherColor)) $this->possible[$player_id]['locations'][] = $location;
			}
		}
		return ['_private' => $this->possible, 'active' => $color, 'otherplayer' => Factions::getName($color), 'otherplayer_id' => Factions::getPlayer($color)];
	}
	function argBlockMovement()
	{
		$this->possible = [];
//
		$color = Factions::getActive();
		foreach (Factions::list(false) as $otherColor)
		{
			$player_id = Factions::getPlayer($otherColor);
			if ($this->gamestate->isPlayerActive($player_id))
			{
				$action = Factions::getStatus($color, 'action');
//
				$this->possible[$player_id]['color'] = $otherColor;
				$this->possible[$player_id]['from'] = $action['from'];
				$this->possible[$player_id]['to'] = $action['to'];
			}
		}
		return ['_private' => $this->possible, 'active' => $color, 'otherplayer' => Factions::getName($color), 'otherplayer_id' => Factions::getPlayer($color)];
	}
	function argResolveGrowthActions()
	{
		$color = Factions::getActive();
		$player_id = Factions::getPlayer($color);

		$this->possible = ['counters' => Factions::getStatus($color, 'counters'), 'color' => $color];
		$this->possible['population'] = 39 - Factions::getPopulation($color);
		$this->possible['populations'] = array_keys(Counters::getPopulations($color, true));
		foreach ($this->possible['counters'] as $counter)
		{
			switch ($counter)
			{
				case 'research':
					break;
				case 'gainStar':
					{
						$this->possible['gainStar'] = [];
						foreach (array_unique(array_column(Ships::getAll($color), 'location')) as $location)
						{
							$star = Counters::getAtLocation($location, 'star');
							if ($star) $this->possible['gainStar'][$location] = Counters::getOwner($location);
							$populations = Counters::getAtLocation($location, 'populationDisc');
							if ($populations && Counters::get($populations[0])['color'] !== $color) $this->possible['gainStar'][$location] = Counters::getOwner($location);
							if (in_array($location, Ships::getHomeStar()) && $location !== Ships::getHomeStarLocation($color)) $this->possible['gainStar'][$location] = Counters::getOwner($location);
						}
					}
					break;
				case 'growPopulation':
				case 'growPopulation+':
					{
						$ancientPyramids = Counters::getRelic(ANCIENTPYRAMIDS);
						$this->possible['ancientPyramids'] = ($ancientPyramids && Counters::getStatus($ancientPyramids, 'owner') === $color) ? Counters::get($ancientPyramids)['location'] : null;
//
						$this->possible['growPopulation'] = [];
						foreach (Counters::getPopulations($color, true) as $location => $population) $this->possible['growPopulation'][$location] = ['population' => intval($population), 'growthLimit' => Sectors::nearest($location, $color)];
						$this->possible['bonusPopulation'] = Factions::TECHNOLOGIES['Genetics'][Factions::getTechnology($color, 'Genetics')];
					}
					break;
				case 'buildShips':
					{
						$this->possible['newShips'] = Factions::ships($color);
						$this->possible['stars'] = [];
						foreach (Counters::getPopulations($color, true) as $location => $population) if ($population >= Factions::SHIPYARDS[Factions::getTechnology($color, 'Robotics')]) $this->possible['stars'][] = $location;
//
						foreach (Ships::getAll($color) as $ship)
						{
							$this->possible['ships'][] = $ship['id'];
							if ($ship['fleet'] === 'fleet')
							{
								$fleet = Ships::getStatus($ship['id'], 'fleet');
								$this->possible['fleets'][$fleet] = $ship;
								$this->possible['fleets'][$fleet]['ships'] = intval(Ships::getStatus($ship['id'], 'ships'));
							}
						}
					}
					break;
			}
		}
//
		$counters = [];
		foreach (Factions::list(true) as $otherColor)
		{
			$counters[$otherColor] = ['available' => [], 'used' => []];
			foreach (Factions::getStatus($otherColor, 'counters') ?? [] as $counter) $counters[$otherColor]['available'][] = $counter;
			foreach (Factions::getStatus($otherColor, 'used') ?? [] as $counter) $counters[$otherColor]['used'][] = $counter;
		}
//
		if (!in_array('teleportPopulation', $counters[$color]['used']))
		{
			$teleport = Factions::TELEPORT[Factions::getTechnology($color, 'Propulsion')];
			if ($teleport) $this->possible['teleportPopulation'] = $teleport;
		}
//
		$homeStar = Ships::getHomeStarLocation($color);
		$evacuation = false;
		foreach (Factions::atWar($color) as $otherColor) if (Ships::getAtLocation($homeStar, $otherColor)) $evacuation = true;
//
		return ['_private' => [$player_id => $this->possible], 'active' => $color, 'counters' => $counters, 'evacuation' => $evacuation
		];
	}
	function argRemovePopulation()
	{
		$color = Factions::getActive();
		$player_id = Factions::getPlayer($color);
//
		$this->possible = ['color' => $color];
		$this->possible['populations'] = array_keys(Counters::getPopulations($color, true));
//
		return ['_private' => [$player_id => $this->possible], 'active' => $color, 'population' => Factions::getPopulation($color) - 39];
	}
	function argStealTechnology()
	{
		$player_id = self::getActivePlayerId();
		$color = Factions::getColor($player_id);
//
		['from' => $from, 'levels' => $levels] = Factions::getStatus($color, 'steal');
//
		$this->possible = ['counters' => [], 'color' => $color];
		foreach (array_keys($this->TECHNOLOGIES) as $technology) foreach ($from as $otherColor) if (Factions::getTechnology($otherColor, $technology) > Factions::getTechnology($color, $technology)) $this->possible['counters'][] = $technology;
		$this->possible['counters'] = array_unique($this->possible['counters']);
//
		return ['_private' => [$player_id => $this->possible], 'active' => $color, 'levels' => $levels];
	}
	function argHomeStarEvacuation()
	{
		$this->possible = [];
//
		foreach (Factions::list(false) as $color)
		{
			$player_id = Factions::getPlayer($color);
			if ($player_id >= 0 && Factions::getStatus($color, 'evacuate'))
			{
				$this->possible[$player_id]['color'] = $color;
				$this->possible[$player_id]['homeStar'] = Ships::getHomeStarLocation($color);
				$this->possible[$player_id]['voluntary'] = Factions::getStatus($color, 'evacuate') === 'voluntary';
//
				$populations = Counters::getPopulations($color, true);
				unset($populations[Ships::getHomeStarLocation($color)]);
//
				if (!$populations)
				{
					$this->possible[$player_id]['evacuate'] = [];
					$sector = Sectors::get(Factions::getHomeStar($color));
					$current = Hex(0, 0, 0);
					for ($radius = 1; $radius <= 4; $radius++)
					{
						$current = hex_neighbor($current, 4);
						for ($direction = 0; $direction < 6; $direction++)
						{
							for ($count = 0; $count < $radius; $count++)
							{
								$hexagon = sprintf('%+2d%+2d%+2d', $current['q'], $current['r'], $current['s']);
								$location = Factions::getHomeStar($color) . ':' . $hexagon;
								$rotated = Sectors::rotate($hexagon, Sectors::getOrientation(Factions::getHomeStar($color)));
								if (!array_key_exists($rotated, Sectors::SECTORS[$sector]) && !Ships::getAtLocation($location))
								{
									$this->possible[$player_id]['evacuate'][] = $location;
								}
								$current = hex_neighbor($current, $direction);
							}
						}
					}
				}
				else $this->possible[$player_id]['evacuate'] = array_keys($populations, max($populations));
			}
		}
//
		return ['_private' => $this->possible];
	}
	function argEmergencyReserve()
	{
		$player_id = self::getActivePlayerId();
		$color = Factions::getColor($player_id);
//
		$this->possible = ['stars' => [Ships::getHomeStarLocation($color)], 'newShips' => 6];
		foreach (Ships::getAll($color) as $ship)
		{
			$this->possible['ships'][] = $ship['id'];
			if ($ship['fleet'] === 'fleet')
			{
				$fleet = Ships::getStatus($ship['id'], 'fleet');
				$this->possible['fleets'][$fleet] = $ship;
				$this->possible['fleets'][$fleet]['ships'] = intval(Ships::getStatus($ship['id'], 'ships'));
			}
		}
//
		return ['_private' => [$player_id => $this->possible], 'active' => $color];
	}
	function argBuriedShips()
	{
		$color = Factions::getActive();
		$player_id = Factions::getPlayer($color);
//
		['location' => $location, 'ships' => $buriedShips] = Factions::getStatus($color, 'buriedShips');
//
		$this->possible = ['stars' => [$location], 'newShips' => $buriedShips];
		foreach (Ships::getAll($color) as $ship)
		{
			$this->possible['ships'][] = $ship['id'];
			if ($ship['fleet'] === 'fleet')
			{
				$fleet = Ships::getStatus($ship['id'], 'fleet');
				$this->possible['fleets'][$fleet] = $ship;
				$this->possible['fleets'][$fleet]['ships'] = intval(Ships::getStatus($ship['id'], 'ships'));
			}
		}
//
		return ['_private' => [$player_id => $this->possible], 'active' => $color];
	}
	function argTradingPhase()
	{
		$private = [];
		foreach (Factions::list(false) as $from)
		{
//
			$player_id = Factions::getPlayer($from);
			if ($player_id > 0)
			{
				$private[$player_id]['color'] = $from;
//
				if (Factions::getActivation($from) === 'done') continue;
//
				$private[$player_id]['trade'][$from] = Factions::getStatus($from, 'trade');
				foreach (array_keys($this->TECHNOLOGIES) as $technology) $private[$player_id][$technology] = Factions::getTechnology($from, $technology);
				foreach (Factions::getStatus($from, 'inContact') as $to)
				{
					if (Factions::getActivation($to) !== 'done')
					{
						$private[$player_id]['trade'][$to] = array_filter(Factions::getStatus($to, 'trade'), fn($key) => $key === $from, ARRAY_FILTER_USE_KEY);
						foreach (array_keys($this->TECHNOLOGIES) as $technology)
						{
							$private[$player_id]['inContact'][$to][$technology] = Factions::getTechnology($to, $technology);
						}
					}
				}
			}
		}
		return ['_private' => $private, 'automa' => Factions::getAutoma()];
	}
	function argResearchPlus()
	{
		$color = Factions::getActive();
		$player_id = Factions::getPlayer($color);
//
		$this->possible = [];
//
		$technologies = Factions::getStatus($color, 'researchPlus');
		$technology = array_pop($technologies);
//
		switch ($technology)
		{
//
			case 'Military':
//
				foreach (Factions::atWar($color) as $otherColor) $this->possible['Military'][$otherColor] = array_diff(Factions::getStatus($otherColor, 'counters'), array_keys($this->TECHNOLOGIES));
				break;
//
			case 'Spirituality':
//
				$this->possible['color'] = $color;
				$this->possible['counters'] = [];
				foreach (Factions::getStatus($color, 'stock') as $counter) if (in_array($counter, $this->OVAL)) $this->possible['counters'][] = $counter;
				$this->possible['Spirituality'] = true;
//
				$cards = $this->domination->countCardInLocation('A', $color) + $this->domination->countCardInLocation('B', $color);
				$this->possible['dominationCardExchange'] = ($cards < 2) && is_null(Factions::getStatus($color, 'exchange'));
				if ($this->possible['dominationCardExchange']) $this->possible['hand'] = $this->domination->getPlayerHand($color);
		}
//
		$counters = [];
		foreach (Factions::list(true) as $otherColor)
		{
			$counters[$otherColor] = ['available' => [], 'used' => []];
			foreach (Factions::getStatus($otherColor, 'counters') ?? [] as $counter) $counters[$otherColor]['available'][] = $counter;
			foreach (Factions::getStatus($otherColor, 'used') ?? [] as $counter) $counters[$otherColor]['used'][] = $counter;
		}
//
		return ['_private' => [$player_id => $this->possible], 'active' => $color, 'counters' => $counters,
			'technology' => $technology, 'i18n' => ['technology']];
	}
	function argOneTimeEffect()
	{
		$color = Factions::getActive();
		$player_id = Factions::getPlayer($color);
//
		$this->possible = [];
//
		return ['_private' => [$player_id => $this->possible], 'active' => $color, 'dominationCard' => $this->DOMINATIONCARDS[ACQUISITION], 'i18n' => ['dominationCard']];
	}
}
