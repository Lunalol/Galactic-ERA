<?php

/**
 *
 * @author Lunalol
 */
class Automas extends APP_GameClass
{
	const WORMHOLES = ['0:-2+4-2', '1:-4+2+2', '1:+2+2-4'];
//
	function startBonus(string $color): array
	{
		$dice = bga_rand(1, 6);
		switch (Factions::getPlayer($color))
		{
			case FARMERS:
				{
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
				}
				break;
			case SLAVERS:
				{
					switch ($dice)
					{
						case 1: return ['Military' => 3];
						case 2: return ['Spirituality' => 2, 'Military' => 2];
						case 3: return ['Propulsion' => 2, 'Military' => 2];
						case 4: return ['Robotics' => 2, 'Military' => 2];
						case 5: return ['Genetics' => 2, 'Military' => 2];
						case 6: return ['offboard' => 2];
					}
				}
				break;
		}
	}
	function movement(string $color): void
	{
		$dice = bga_rand(1, 6);
		switch (Factions::getPlayer($color))
		{
			case FARMERS:
				{
					switch ($dice)
					{
						case 1:
							break;
						case 2:
							break;
						case 3:
							break;
						case 4:
							break;
						case 5:
							break;
						case 6:
							break;
					}
				}
				break;
			case SLAVERS:
				{
					switch ($dice)
					{
						case 1:
							break;
						case 2:
							break;
						case 3:
							break;
						case 4:
							break;
						case 5:
							break;
						case 6:
							break;
					}
				}
				break;
		}
//
		Factions::setActivation($color, 'done');
	}
	function growthActions(string $color): array
	{
		$wormholes = self::WORMHOLES;
		shuffle($wormholes);
//
		$counters = [];
//
		$dice = bga_rand(1, 6);
		switch (Factions::getPlayer($color))
		{
			case FARMERS:
				{
					if (!Ships::getAll($color)) $dice = 6;
//
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
				}
				break;
			case SLAVERS:
				{
					switch ($dice)
					{
						case 1:
							$counters[] = 'research';
							$counters[] = 'Military';
							$counters[] = 'buildShips';
							Factions::setStatus($color, 'buildShips', array_merge(...array_fill(0, Factions::ships($color), $wormholes)));
							break;
						case 2:
							$counters[] = 'changeTurnOrderDown';
							$counters[] = 'buildShips';
							Factions::setStatus($color, 'buildShips', array_merge(...array_fill(0, Factions::ships($color), array_slice($wormholes, 0, 2))));
							break;
						case 3:
							$counters[] = 'research';
							$counters[] = 'Propulsion';
							$counters[] = 'buildShips';
							Factions::setStatus($color, 'buildShips', array_merge(...array_fill(0, Factions::ships($color), array_slice($wormholes, 0, 1))));
							break;
						case 4:
							$counters[] = 'research';
							$counters[] = 'Robotics';
							$counters[] = 'buildShips';
							Factions::setStatus($color, 'buildShips', array_merge(...array_fill(0, Factions::ships($color), [self::WORMHOLES[0]])));
							break;
						case 5:
							$counters[] = 'changeTurnOrderDown';
							$counters[] = 'gainStar';
							$counters[] = 'growPopulation';
							break;
						case 6:
							$counters[] = 'gainStar';
							$counters[] = 'research';
							$counters[] = array_rand(Factions::TECHNOLOGIES);
							break;
					}
				}
				break;
		}
//
		return $counters;
	}
	function actions(object $bgagame, string $color): void
	{
		while ($counters = Factions::getStatus($color, 'counters'))
		{
			$research = array_search('research', $counters);
			if ($research !== false)
			{
				$technologies = array_intersect($counters, array_keys(Factions::TECHNOLOGIES));
				$technology = array_shift($technologies);
				$bgagame->acResearch($color, $technology, true);
				continue;
			}
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
			$buildShips = array_search('buildShips', $counters);
			if ($buildShips !== false)
			{
				$bgagame->acBuildShips($color, Factions::getStatus($color, 'buildShips'), true);
				Factions::setStatus($color, 'buildShips');
				continue;
			}
			throw new BgaVisibleSystemException('Automas Growth Action not implemented');
		}
//
		Factions::setActivation($color, 'done');
	}
}
