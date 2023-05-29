<?php

/**
 *
 * @author Lunalol
 */
class Automas extends APP_GameClass
{
	const DIFFICULTY = [0, 0, 1, 2];
	const WORMHOLES = ['0:-2+4-2', '1:-4+2+2', '1:+2+2-4'];
//
	function getName(string $color): array
	{
		switch (Factions::getPlayer($color))
		{
			case FARMERS:
				return [
					'log' => '<span style="color:#' . $color . ';font-weight:bold;">${NAME}</span>',
					'args' => ['NAME' => clienttranslate('Farmers'), 'i18n' => ['NAME']]];
			case SLAVERS:
				return [
					'log' => '<span style="color:#' . $color . ';font-weight:bold;">${NAME}</span>',
					'args' => ['NAME' => clienttranslate('Slavers'), 'i18n' => ['NAME']]];
			default:
				throw new BgaVisibleSystemException('Invalid automas: ' . $color);
		}
	}
	function startBonus(string $color, int $dice): array
	{
		switch (Factions::getPlayer($color))
		{
			case FARMERS:
				switch ($dice)
				{
					case 1: return ['Military' => 2];
					case 2: return ['Spirituality' => 2];
					case 3: return ['Propulsion' => 2];
					case 4: return ['Robotics' => 2];
					case 5: return ['Genetics' => 2];
					case 6:
						{
							$technologies = array_keys(Factions::TECHNOLOGIES);
							shuffle($technologies);
							return [array_shift($technologies) => 2, array_shift($technologies) => 2];
						}
				}
			case SLAVERS:
				switch ($dice)
				{
					case 1: return ['Military' => 3];
					case 2: return ['Spirituality' => 2, 'Military' => 2];
					case 3: return ['Propulsion' => 2, 'Military' => 2];
					case 4: return ['Robotics' => 2, 'Military' => 2];
					case 5: return ['Genetics' => 2, 'Military' => 2];
					case 6: return ['offboard' => 2];
				}
			default:
				throw new BgaVisibleSystemException('Invalid automas: ' . $color);
		}
	}
	function movement(object $bgagame, string $color, int $dice): void
	{
		foreach (Ships::getAll($color) as $ship)
		{
			$MP = Factions::TECHNOLOGIES['Propulsion'][Factions::getTechnology($color, 'Propulsion')];
			if (Sectors::terrainFromLocation($ship['location']) === Sectors::NEBULA) $MP += 2;
			if (Ships::getStatus($ship['id'], 'fleet') === 'D') $MP += 1;
			Ships::setMP($ship['id'], $MP);
		}
//
		switch (Factions::getPlayer($color))
		{
			case FARMERS:
//
				foreach (Ships::getAll($color) as $ship)
				{
					switch ($dice)
					{
						case 1:
// Each ship moves to (or as close as possible to) the nearest one of your stars
							$locations = array_keys(Counters::getPopulation(Factions::getNotAutomas()));
							$path = self::paths($ship, $locations);
							$bgagame->possible['move'][$ship['id']] = $path['possible'];
							$bgagame->acMove($color, $path['location'], [$ship['id']], true);
							break;
						case 2:
// Each ship moves to (or as close as possible to) the nearest star (other than the one it may be at already)
							$locations = [];
							foreach (Sectors::getAll() as $sector)
							{
								foreach (array_keys($bgagame->SECTORS[Sectors::get($sector)]) as $hexagon)
								{
									$location = $sector . ':' . $hexagon;
									if ($location !== $ship['location']) $locations[] = $location;
								}
							}
							$path = self::paths($ship, $locations);
							$bgagame->possible['move'][$ship['id']] = $path['possible'];
							$bgagame->acMove($color, $path['location'], [$ship['id']], true);
							break;
						case 3:
// Each ship moves as close as possible to the center hex of its sector
							$locations = [$ship['location'][0] . ':+0+0+0'];
							$path = self::paths($ship, $locations);
							$bgagame->possible['move'][$ship['id']] = $path['possible'];
							$bgagame->acMove($color, $path['location'], [$ship['id']], true);
							break;
						case 4:
// Each ship moves to any star within range. If there is no star within range then it moves as close as possible to the nearest one
							$locations = [];
							foreach (Sectors::getAll() as $sector) foreach (array_keys($bgagame->SECTORS[Sectors::get($sector)]) as $hexagon) $locations[] = $sector . ':' . $hexagon;
							$path = self::paths($ship, $locations);
							$bgagame->possible['move'][$ship['id']] = $path['possible'];
							$bgagame->acMove($color, $path['location'], [$ship['id']], true);
							break;
						case 5:
// Each ship moves its full range in a random direction
							throw new BgaVisibleSystemException('Farmers move not implemented');
							break;
						case 6:
// No movement
							break;
					}
				}
				break;
			case SLAVERS:
				{
//
					$shipList = [];
					foreach (array_unique(array_column(Ships::getAll($color), 'location')) as $location) $shipList[$location] = Ships::getAtLocation($location, $color);
					foreach ($shipList as $location => $ships)
					{
						switch ($dice)
						{
							case 1:
//
// If at peace with you, they first declare war on you
//
								foreach (Factions::atPeace($color) as $otherColor) $bgagame->acDeclareWar($color, $otherColor, true);
//
// All ships then move to (or as close as possible to) the nearest one of your stars
//
								$locations = array_keys(Counters::getPopulation(Factions::getNotAutomas()));
								$toMove = [];
								foreach ($ships as $shipID)
								{
									$ship = Ships::get($color, $shipID);
									$path = self::paths($ship, $locations);
									$bgagame->possible['move'][$ship['id']] = $path['possible'];
									if ($ship['fleet'] === 'fleet') $bgagame->acMove($color, $path['location'], [$ship['id']], true);
									else $toMove[] = $shipID;
								}
								if ($toMove) $bgagame->acMove($color, $path['location'], $toMove, true);
								break;
							case 2:
//
// If at peace with you, they first declare war on you
//
								foreach (Factions::atPeace($color) as $otherColor) $bgagame->acDeclareWar($color, $otherColor, true);
//
								break;
							case 3:
// All ships move as close as possible to the center hex of their sector
								$locations = [$location[0] . ':+0+0+0'];
								$toMove = [];
								foreach ($ships as $shipID)
								{
									$ship = Ships::get($color, $shipID);
									$path = self::paths($ship, $locations);
									$bgagame->possible['move'][$ship['id']] = $path['possible'];
									if ($ship['fleet'] === 'fleet') $bgagame->acMove($color, $path['location'], [$ship['id']], true);
									else $toMove[] = $shipID;
								}
								if ($toMove) $bgagame->acMove($color, $path['location'], $toMove, true);
								break;
							case 4:
//
								break;
							case 5:
//
								break;
							case 6:
//
								break;
						}
					}
				}
				break;
			default:
				throw new BgaVisibleSystemException('Invalid automas: ' . $color);
		}
	}
	function growthActions(string $color, int $difficulty, int $dice): array
	{
		$wormholes = self::WORMHOLES;
		shuffle($wormholes);
//
		$counters = [];
		switch (Factions::getPlayer($color))
		{
			case FARMERS:
				if (!Ships::getAll($color)) $dice = 6;
				switch ($dice)
				{
					case 1:
						$counters[] = 'research';
						$counters[] = 'Military';
						break;
					case 2:
						$counters[] = 'research';
						$counters[] = 'Spirituality';
						break;
					case 3:
						$counters[] = 'research';
						$counters[] = 'Propulsion';
						break;
					case 4:
						$counters[] = 'research';
						$counters[] = 'Robotics';
						break;
					case 5:
						$counters[] = 'research';
						$counters[] = 'Genetics';
						break;
					case 6:
						$counters[] = 'changeTurnOrderUp';
						$counters[] = 'buildShips';
						Factions::setStatus($color, 'buildShips', array_slice($wormholes, 0, 1));
						break;
				}
				break;
			case SLAVERS:
				switch ($dice)
				{
					case 1:
						$counters[] = 'research';
						$counters[] = 'Military';
						$counters[] = 'buildShips';
						Factions::setStatus($color, 'buildShips', array_merge(...array_fill(0, $difficulty + Factions::ships($color), $wormholes)));
						break;
					case 2:
						$counters[] = 'changeTurnOrderDown';
						$counters[] = 'buildShips';
						Factions::setStatus($color, 'buildShips', array_merge(...array_fill(0, $difficulty + Factions::ships($color), array_slice($wormholes, 0, 2))));
						break;
					case 3:
						$counters[] = 'research';
						$counters[] = 'Propulsion';
						$counters[] = 'buildShips';
						Factions::setStatus($color, 'buildShips', array_merge(...array_fill(0, $difficulty + Factions::ships($color), array_slice($wormholes, 0, 1))));
						break;
					case 4:
						$counters[] = 'research';
						$counters[] = 'Robotics';
						$counters[] = 'buildShips';
						Factions::setStatus($color, 'buildShips', array_merge(...array_fill(0, $difficulty + Factions::ships($color), [self::WORMHOLES[0]])));
						break;
					case 5:
						$counters[] = 'changeTurnOrderDown';
						$counters[] = 'gainStar';
//							$counters[] = 'growPopulation';
						Factions::setStatus($color, 'buildShips', array_merge(...array_fill(0, $difficulty + Factions::ships($color), [self::WORMHOLES[0]])));
						break;
					case 6:
						$counters[] = 'gainStar';
						$counters[] = 'research';
						$technologies = Factions::TECHNOLOGIES;
						if (Factions::getTechnology($color, 'Spirituality') >= 4) unset($technologies['Spirituality']);
						$counters[] = array_rand($technologies);
						break;
				}
				break;
			default:
				throw new BgaVisibleSystemException('Invalid automas: ' . $color);
		}
		return $counters;
	}
	function actions(object $bgagame, string $color): void
	{
		while ($counters = Factions::getStatus($color, 'counters'))
		{
			$gainStar = array_search('gainStar', $counters);
			if ($gainStar !== false)
			{
				unset($counters[$gainStar]);
				Factions::setStatus($color, 'counters', array_values($counters));
				continue;
			}
			$growPopulation = array_search('growPopulation', $counters);
			if ($growPopulation !== false)
			{
				unset($counters[$growPopulation]);
				Factions::setStatus($color, 'counters', array_values($counters));
				continue;
			}
			$research = array_search('research', $counters);
			if ($research !== false)
			{
				$technologies = array_intersect($counters, array_keys(Factions::TECHNOLOGIES));
				$technology = array_shift($technologies);
				$bgagame->acResearch($color, $technology, true);
				continue;
			}
			$buildShips = array_search('buildShips', $counters);
			if ($buildShips !== false)
			{
				$bgagame->acBuildShips($color, Factions::getStatus($color, 'buildShips'), true);
				Factions::setStatus($color, 'buildShips');
				continue;
			}
			throw new BgaVisibleSystemException('Automas Growth Action not implemented');
		}
	}
	function trading(string $color, string $alignment): int
	{
		switch (Factions::getPlayer($color))
		{
			case FARMERS:
				switch ($alignment)
				{
					case 'STO': return 0;
					case 'STS': return 4;
				}
			case SLAVERS:
				switch ($alignment)
				{
					case 'STO': return 2;
					case 'STS': return 4;
				}
			default:
				throw new BgaVisibleSystemException('Invalid automas: ' . $color);
		}
	}
	function paths(array $ship, $dests)
	{
		$founds = [];
//
		$possible = [$ship['location'] => ['MP' => $ship['MP'], 'from' => null, 'distance' => 0]];
//
		$locations = [$ship['location'] => 0];
		while (sizeof($founds) !== sizeof($dests))
		{
			$distance = min($locations);
			$location = array_search($distance, $locations);
			unset($locations[$location]);
//
			if (in_array($location, $dests) && !array_key_exists($location, $founds)) $founds[$location] = $distance;
//
			$distance += 1;
			foreach (Sectors::neighbors($location) as $next_location => $terrain)
			{
				$next_MP = $possible[$location]['MP'] - ($terrain === Sectors::NEBULA ? 2 : 1);
				if ($terrain === Sectors::NEUTRON) $next_MP -= 100;
//				if ($next_MP >= 0)
				{
					if (!array_key_exists($next_location, $possible) || ($possible[$next_location]['distance'] > $distance))
					{
						$possible[$next_location] = ['MP' => $next_MP, 'from' => $location, 'distance' => $distance];
						$locations[$next_location] = $distance;
					}
				}
			}
		}
//
		$nearest = array_keys($founds, min($founds));
		shuffle($nearest);
		$dest = array_pop($nearest);

//
		while ($possible[$dest]['MP'] < 0) $dest = $possible[$dest]['from'];
		return ['location' => $dest, 'possible' => $possible];
	}
}
