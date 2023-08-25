<?php

/**
 *
 * @author Lunalol
 */
trait gameStateArguments
{
	function argStarPeople()
	{
		$private = [];
		foreach (Factions::list() as $color)
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
		foreach (Factions::list() as $color)
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
	function argFleets()
	{
		$color = Factions::getActive();
		$player_id = Factions::getPlayer($color);
//
		$this->possible = [
			'ships' => [], 'fleets' => array_fill_keys(Ships::FLEETS, ['location' => null, 'ships' => 0]),
			'stars' => array_keys(Counters::getPopulation($color)),
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
		return ['_private' => [$player_id => $this->possible], 'active' => $color];
	}
	function argAdvancedFleetTactic()
	{
		$this->possible = [];
		foreach (Factions::list() as $color)
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
				$this->possible[$player_id]['advancedFleetTactic'] = Factions::getAdvancedFleetTactics($color);
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
			'declareWar' => Factions::atPeace($color)
		];
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
		return ['_private' => [$player_id => $this->possible], 'active' => $color];
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

		$this->possible = Ships::retreatLocations($defender, Factions::getStatus($attacker, 'combat'));
		return ['retreat' => $this->possible, 'active' => $defender, 'winner' => Factions::getStatus($attacker, 'winner')];
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
			$ship = Ships::get($attacker, $shipID);
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
				$ship = Ships::get($defender, $shipID);
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
		return ['winner' => $this->possible['winner'], 'losers' => $this->possible['losers'], 'totalVictory' => $totalVictory, 'active' => $winner];
	}
	function argSelectCounters()
	{
		$this->possible = [];
		foreach (Factions::list() as $color)
		{
			$player_id = Factions::getPlayer($color);
			if ($player_id > 0)
			{
				$this->possible[$player_id]['color'] = $color;
				$this->possible[$player_id]['counters'] = Factions::getStatus($color, 'counters');
				$this->possible[$player_id]['oval'] = 2 + (Factions::getStatus($color, 'bonus') === 'Grow' ? 1 : 0);
				$this->possible[$player_id]['additionalOvalCost'] = Factions::ADDITIONAL[Factions::getTechnology($color, 'Genetics')];
				$this->possible[$player_id]['square'] = 1 + (Factions::getTechnology($color, 'Robotics') >= 5 ? 1 : 0);
				$this->possible[$player_id]['additionalSquareCost'] = Factions::getTechnology($color, 'Robotics') === 5 ? 2 : 0;
//
				$homeStar = Ships::getHomeStar($color);
//
				$this->possible[$player_id]['additional'] = 0;
				foreach (Counters::getPopulation($color) as $location => $population) if ($population >= 5 && $location !== $homeStar) $this->possible[$player_id]['additional']++;
			}
		}
//
		return ['_private' => $this->possible];
	}
	function argResolveGrowthActions()
	{
		$private = [];
		foreach (Factions::list() as $color)
		{
			$counters = Factions::getStatus($color, 'counters');
//
			$player_id = Factions::getPlayer($color);
			if ($player_id > 0)
			{
				$private[$player_id]['color'] = $color;
				$private[$player_id]['counters'] = $counters;
//
				foreach ($counters as $counter)
				{
					switch ($counter)
					{
						case 'research':
							break;
						case 'gainStar':
							{
								$private[$player_id]['gainStar'] = [];
								foreach (array_unique(array_column(Ships::getAll($color), 'location')) as $location)
								{
									$star = Counters::getAtLocation($location, 'star');
									if ($star) $private[$player_id]['gainStar'][$location] = $star;
									$population = Counters::getAtLocation($location, 'populationDisk');
									if ($population) $private[$player_id]['gainStar'][$location] = $population;
								}
							}
							break;
						case 'growPopulation':
							{
								$private[$player_id]['growPopulation'] = [];
								foreach (Counters::getPopulation($color) as $location => $population) $private[$player_id]['growPopulation'][$location] = ['population' => intval($population), 'growthLimit' => Sectors::nearest($location)];
								$private[$player_id]['bonusPopulation'] = Factions::TECHNOLOGIES['Genetics'][Factions::getTechnology($color, 'Genetics')];
							}
							break;
						case 'buildShips':
							{
								$private[$player_id]['newShips'] = Factions::ships($color);
								$private[$player_id]['buildShips'] = [];
								foreach (Counters::getPopulation($color) as $location => $population) if ($population >= Factions::SHIPYARDS[Factions::getTechnology($color, 'Robotics')]) $private[$player_id]['buildShips'][] = $location;
							}
							break;
					}
				}
			}
		}
//
		$counters = [];
		foreach (Factions::list() as $color)
		{
			$counters[$color] = ['available' => [], 'used' => []];
			foreach (Factions::getStatus($color, 'counters') as $counter) $counters[$color]['available'][] = $counter;
			foreach (Factions::getStatus($color, 'used') as $counter) $counters[$color]['used'][] = $counter;
		}
//
		$color = Factions::getActive();
		$player_id = Factions::getPlayer($color);
		$this->possible = $private[$player_id];
//
		return ['_private' => $private, 'active' => $color, 'counters' => $counters];
	}
	function argTradingPhase()
	{
		$private = [];
		foreach (Factions::list() as $from)
		{
			$player_id = Factions::getPlayer($from);
			if ($player_id > 0)
			{
				$private[$player_id]['color'] = $from;
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
		return ['_private' => $private];
	}
}
