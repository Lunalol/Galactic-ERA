<?php

/**
 *
 * @author Lunalol
 */
class Automas extends APP_GameClass
{
	function movement(string $color): void
	{
		$dice = bga_rand(1, 6);
		switch (Factions::getPlayer($color))
		{
			case FARMERS:
				{
					Faction::setStatus($color, 'dice', $dice);
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
					Faction::setStatus($color, 'dice', $dice);
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
		$counters = [];
//
		$dice = bga_rand(1, 6);
		switch (Factions::getPlayer($color))
		{
			case FARMERS:
				{
					Faction::setStatus($color, 'dice', $dice);
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
							break;
					}
				}
				break;
			case SLAVERS:
				{
					Faction::setStatus($color, 'dice', $dice);
					switch ($dice)
					{
						case 1:
							$counters[] = 'research';
							$counters[] = 'Military';
							$counters[] = 'buildShips';
							$counters[] = 'buildShips';
							$counters[] = 'buildShips';
							break;
						case 2:
							$counters[] = 'changeTurnOrderDown';
							$counters[] = 'buildShips';
							$counters[] = 'buildShips';
							break;
						case 3:
							$counters[] = 'research';
							$counters[] = 'Propulsion';
							$counters[] = 'buildShips';
							break;
						case 4:
							$counters[] = 'research';
							$counters[] = 'Robotics';
							$counters[] = 'buildShips';
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
//
				unset($counters[$growPopulation]);
				Factions::setStatus($color, 'counters', array_values($counters));
				continue;
			}
			$buildShips = array_search('buildShips', $counters);
			if ($buildShips !== false)
			{
//
				unset($counters[$buildShips]);
				Factions::setStatus($color, 'counters', array_values($counters));
				continue;
			}
			$spawn = array_search('spawn', $counters);
			if ($spawn !== false)
			{
//
				unset($counters[$spawn]);
				Factions::setStatus($color, 'counters', array_values($counters));
				continue;
			}
		}
//
		Factions::setActivation($color, 'done');
	}
}
