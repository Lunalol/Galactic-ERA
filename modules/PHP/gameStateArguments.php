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
			$private[$player_id]['color'] = $color;
			$private[$player_id]['starPeople'] = Factions::getStatus($color, 'starPeople');
			$private[$player_id]['alignment'] = false;
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
			$private[$player_id]['color'] = $color;
			$private[$player_id]['starPeople'] = [Factions::getStarPeople($color)];
			$private[$player_id]['alignment'] = Factions::getStatus($color, 'alignment');
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
	function argMovement()
	{
		$color = Factions::getActive();
		$player_id = Factions::getPlayer($color);
//
		$revealed = Counters::listRevealed($color);
		$this->possible = ['move' => [], 'scout' => []];
		foreach (Ships::getAll($color) as $ship)
		{
			if ($ship['fleet'] === 'ship' && $ship['activation'] !== 'done') $this->possible['move'][$ship['id']] = Ships::movement($ship);
//
			$counters = array_diff(array_merge(Counters::getAtLocation($ship['location'], 'star'), Counters::getAtLocation($ship['location'], 'relic')), $revealed);
			if ($counters) $this->possible['scout'][$ship['id']] = $counters;
		}
//
		return ['_private' => [$player_id => $this->possible], 'active' => $color];
	}
	function argSelectCounters()
	{
		$private = [];
		foreach (Factions::list() as $color)
		{
			$player_id = Factions::getPlayer($color);
			$private[$player_id]['color'] = $color;
			$private[$player_id]['counters'] = Factions::getStatus($color, 'counters');
			$private[$player_id]['N'] = 2;
		}
//
		return ['_private' => $private];
	}
	function argResolveGrowthActions()
	{
		$private = [];
		foreach (Factions::list() as $color)
		{
			$counters = Factions::getStatus($color, 'counters');
//
			$player_id = Factions::getPlayer($color);
			$private[$player_id]['color'] = $color;
			$private[$player_id]['counters'] = $counters;
//
			foreach ($counters as $counter)
			{
				switch ($counter)
				{
					case 'reseach':
						break;
					case 'gainStar':
						{
							$private[$player_id]['gainStar'] = [];
							foreach (Ships::getAll($color) as $ship)
							{
								$star = Counters::getAtLocation($ship['location'], 'star');
								if ($star) $private[$player_id]['gainStar'][] = $star;
							}
						}
						break;
					case 'growPopulation':
						{
							$private[$player_id]['growPopulation'] = [];
							foreach (Counters::getPopulation($color) as $location => $population)
							{
								$population = intval($population);
								$growthLimit = Sectors::nearest($location);
								if ($population < $growthLimit) $private[$player_id]['growPopulation'][$location] = ['population' => $population, 'growthLimit' => $growthLimit];
							}
						}
						break;
					case 'buildShips':
						{
							$private[$player_id]['buildShips'] = [];
							foreach (Counters::getPopulation($color) as $location => $population) if ($population >= Factions::TECHNOLOGIES['ShipYards'][Factions::getTechnology($color, 'Robotics')]) $private[$player_id]['buildShips'][] = $location;
//
							$build = 0;
							foreach (array_unique(array_column(Ships::getAll($color), 'location')) as $location) if (Sectors::terrainFromLocation($location) === Sectors::ASTEROIDS) $build++;
							foreach (Factions::BUILD as $population)
							{
								if ($population > Factions::getPopulation($color)) break;
								$build++;
							}
							$private[$player_id]['newShips'] = $build + Factions::TECHNOLOGIES['Robotics'][Factions::getTechnology($color, 'Robotics')];
						}
						break;
				}
			}
		}
//
		$counters = [];
		foreach (Factions::list() as $color)
		{
			foreach (Factions::getStatus($color, 'counters') as $counter) $counters[$color][$counter] = true;
			foreach (Factions::getStatus($color, 'used') as $counter) $counters[$color][$counter] = false;
		}
//
		$color = Factions::getActive();
		$player_id = Factions::getPlayer($color);
		$this->possible = $private[$player_id];
//
		return ['_private' => $private, 'active' => $color, 'counters' => $counters];
	}
	function argBonusPopulation()
	{
		$color = Factions::getActive();
		$player_id = Factions::getPlayer($color);
//
		$this->possible = ['bonusPopulation' => Counters::getPopulation($color)];
		$bonus = Factions::TECHNOLOGIES['Genetics'][Factions::getTechnology($color, 'Genetics')];
//
		return ['_private' => [$player_id => $this->possible], 'active' => $color, 'bonus' => $bonus];
	}
}
