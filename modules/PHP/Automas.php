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
	static function getName(string $color): array
	{
		switch (Factions::getPlayer($color))
		{
			case AUTOMA:
				return [
					'log' => '<span style="color:#' . $color . ';font-weight:bold;">${NAME}</span>',
					'args' => ['NAME' => clienttranslate('Automa'), 'i18n' => ['NAME']]];
			case FARMERS:
				return [
					'log' => '<span style="color:#' . $color . ';font-weight:bold;">${NAME}</span>',
					'args' => ['NAME' => clienttranslate('Farmers automa'), 'i18n' => ['NAME']]];
			case SLAVERS:
				return [
					'log' => '<span style="color:#' . $color . ';font-weight:bold;">${NAME}</span>',
					'args' => ['NAME' => clienttranslate('Slavers automa'), 'i18n' => ['NAME']]];
			default:
				throw new BgaVisibleSystemException('Invalid automas: ' . $color);
		}
	}
	static function startBonus(string $color, int $dice): array
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
	static function advancedFleetTactics(string $color)
	{
		switch (Factions::getPlayer($color))
		{
			case FARMERS:
				return null;
			case AUTOMA:
			case SLAVERS:
				return array_rand(array_filter(Factions::getAllAdvancedFleetTactics($color), fn($tactics) => is_null($tactics)));
			default:
				throw new BgaVisibleSystemException('Invalid automas: ' . $color);
		}
	}
	static function makingPeace(string $color): int
	{
		switch (Factions::getPlayer($color))
		{
			case FARMERS:
				return 4;
			case SLAVERS:
				return 2;
			default:
				throw new BgaVisibleSystemException('Invalid automas: ' . $color);
		}
	}
	static function movement(object $bgagame, string $color, int $dice): void
	{
		foreach (Ships::getAll($color) as $ship)
		{
			$MP = Factions::TECHNOLOGIES['Propulsion'][Factions::getTechnology($color, 'Propulsion')];
			if (Sectors::terrainFromLocation($ship['location']) === Sectors::NEBULA) $MP += 2;
			if (Ships::getStatus($ship['id'], 'fleet') === 'D')
			{
				$MP += 1;
				if (Factions::getAdvancedFleetTactics($color, 'D') === '2x') $MP += 1;
			}
			Ships::setMP($ship['id'], $MP);
		}
//
		switch (Factions::getPlayer($color))
		{
			case FARMERS:
//
				foreach (Ships::getAll($color) as $ship)
				{
					$location = $ship['location'];
//
					switch ($dice)
					{
//
						case 1:
//
// Each ship moves to (or as close as possible to) the nearest one of your stars
//
							$locations = array_keys(Counters::getPopulations(Factions::getNotAutomas()));
//
							$path = self::paths($location, $ship['MP'], $locations);
							if (!$path) throw new BgaVisibleSystemException('No movement path found for Farmers');
							if (DEBUG >= 2)
							{
								$bgagame->notifyAllPlayers('msg', '<HR>', []);
								$bgagame->notifyAllPlayers('msg', '<B>Each ship moves to (or as close as possible to) the nearest one of your stars</B>', []);
								foreach ($path['debug']['founds'] as $possible => $range)
								{
									$sector = Sectors::get($possible[0]);
									$hexagon = substr($possible, 2);
									$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($possible[0]));
									if (array_key_exists($rotated, $bgagame->SECTORS[$sector])) $bgagame->notifyAllPlayers('msg', '${PLANET} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'i18n' => ['PLANET'], 'PLANET' => $bgagame->SECTORS[$sector][$rotated]]);
									else $bgagame->notifyAllPlayers('msg', '${location} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'location' => $possible]);
								}
								$bgagame->notifyAllPlayers('msg', '<HR>', []);
							}
//
							$bgagame->possible['move'][$ship['id']] = $path['possible'];
							$bgagame->acMove($color, $path['location'], [$ship['id']], true);
							break;
//
						case 2:
//
// Each ship moves to (or as close as possible to) the nearest star (other than the one it may be at already)
//
							$locations = [];
							foreach (Sectors::getAll() as $sector)
							{
								foreach (array_keys($bgagame->SECTORS[Sectors::get($sector)]) as $hexagon)
								{
									$rotated = Sectors::rotate($hexagon, -Sectors::getOrientation($sector));
									$star = "$sector:$rotated";
									if ($location !== $star) $locations[] = $star;
								}
							}
//
							$path = self::paths($location, $ship['MP'], $locations);
							if (!$path) throw new BgaVisibleSystemException('No movement path found for Farmers');
							if (DEBUG >= 2)
							{
								$bgagame->notifyAllPlayers('msg', '<HR>', []);
								$bgagame->notifyAllPlayers('msg', '<B>Each ship moves to (or as close as possible to) the nearest star (other than the one it may be at already)</B>', []);
								foreach ($path['debug']['founds'] as $possible => $range)
								{
									$sector = Sectors::get($possible[0]);
									$hexagon = substr($possible, 2);
									$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($possible[0]));
									if (array_key_exists($rotated, $bgagame->SECTORS[$sector])) $bgagame->notifyAllPlayers('msg', '${PLANET} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'i18n' => ['PLANET'], 'PLANET' => $bgagame->SECTORS[$sector][$rotated]]);
									else $bgagame->notifyAllPlayers('msg', '${location} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'location' => $possible]);
								}
								$bgagame->notifyAllPlayers('msg', '<HR>', []);
							}
//
							$bgagame->possible['move'][$ship['id']] = $path['possible'];
							$bgagame->acMove($color, $path['location'], [$ship['id']], true);
							break;
//
						case 3:
//
// Each ship moves as close as possible to the center hex of its sector
//
							$locations = [$location[0] . ':+0+0+0'];
//
							$path = self::paths($location, $ship['MP'], $locations);
							if (!$path) throw new BgaVisibleSystemException('No movement path found for Farmers');
							if (DEBUG >= 2)
							{
								$bgagame->notifyAllPlayers('msg', '<HR>', []);
								$bgagame->notifyAllPlayers('msg', '<B>Each ship moves as close as possible to the center hex of its sector</B>', []);
								foreach ($path['debug']['founds'] as $possible => $range)
								{
									$sector = Sectors::get($possible[0]);
									$hexagon = substr($possible, 2);
									$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($possible[0]));
									if (array_key_exists($rotated, $bgagame->SECTORS[$sector])) $bgagame->notifyAllPlayers('msg', '${PLANET} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'i18n' => ['PLANET'], 'PLANET' => $bgagame->SECTORS[$sector][$rotated]]);
									else $bgagame->notifyAllPlayers('msg', '${location} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'location' => $possible]);
								}
								$bgagame->notifyAllPlayers('msg', '<HR>', []);
							}
//
							$bgagame->possible['move'][$ship['id']] = $path['possible'];
							$bgagame->acMove($color, $path['location'], [$ship['id']], true);
							break;
//
						case 4:
//
// Each ship moves to any star within range
// If there is no star within range then it moves as close as possible to the nearest one
//
							$locations = [];
							foreach (Sectors::getAll() as $sector) foreach (array_keys($bgagame->SECTORS[Sectors::get($sector)]) as $hexagon)
								{
									$rotated = Sectors::rotate($hexagon, -Sectors::getOrientation($sector));
									$locations[] = "$sector:$rotated";
								}
//
							$path = self::paths($location, $ship['MP'], $locations, true);
							if (!$path) $path = self::paths($location, $ship['MP'], $locations);
							if (!$path) throw new BgaVisibleSystemException('No movement path found for Farmers');
							if (DEBUG >= 2)
							{
								$bgagame->notifyAllPlayers('msg', '<HR>', []);
								$bgagame->notifyAllPlayers('msg', '<B>Each ship moves to any star within range. If there is no star within range then it moves as close as possible to the nearest one</B>', []);
								foreach ($path['debug']['founds'] as $possible => $range)
								{
									$sector = Sectors::get($possible[0]);
									$hexagon = substr($possible, 2);
									$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($possible[0]));
									if (array_key_exists($rotated, $bgagame->SECTORS[$sector])) $bgagame->notifyAllPlayers('msg', '${PLANET} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'i18n' => ['PLANET'], 'PLANET' => $bgagame->SECTORS[$sector][$rotated]]);
									else $bgagame->notifyAllPlayers('msg', '${location} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'location' => $possible]);
								}
								$bgagame->notifyAllPlayers('msg', '<HR>', []);
							}
//
							$bgagame->possible['move'][$ship['id']] = $path['possible'];
							$bgagame->acMove($color, $path['location'], [$ship['id']], true);
							break;
//
						case 5:
//
// Each ship moves its full range in a random direction
//
							$neighbors = Sectors::neighbors($location, false);
							$direction = array_rand($neighbors);
							while (array_key_exists($direction, $neighbors))
							{
								$next_location = $neighbors[$direction]['location'];
								$neighbors = Sectors::neighbors($next_location, false);
							}
//
							$path = self::paths($location, $ship['MP'], [$next_location], false, $direction);
							if (!$path) throw new BgaVisibleSystemException('No movement path found for Farmers');
							if (DEBUG >= 2)
							{
								$bgagame->notifyAllPlayers('msg', '<HR>', []);
								$bgagame->notifyAllPlayers('msg', '<B>Each ship moves its full range in a random direction</B>', []);
								foreach ($path['debug']['founds'] as $possible => $range)
								{
									$sector = Sectors::get($possible[0]);
									$hexagon = substr($possible, 2);
									$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($possible[0]));
									if (array_key_exists($rotated, $bgagame->SECTORS[$sector])) $bgagame->notifyAllPlayers('msg', '${PLANET} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'i18n' => ['PLANET'], 'PLANET' => $bgagame->SECTORS[$sector][$rotated]]);
									else $bgagame->notifyAllPlayers('msg', '${location} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'location' => $possible]);
								}
								$bgagame->notifyAllPlayers('msg', '<HR>', []);
							}
//
							$bgagame->possible['move'][$ship['id']] = $path['possible'];
							$bgagame->acMove($color, $path['location'], [$ship['id']], true);
							break;
//
						case 6:
//
// No movement (unless in a hex with hostile ships, then the ship moves to the nearest hex without these)
//
							if (in_array($location, Ships::getConflictLocation($color)))
							{
								$locations = Ships::retreatLocations($color, $location);
								if ($locations)
								{
									$path = self::paths($location, $ship['MP'], $locations);
									if (!$path) throw new BgaVisi0leSystemException('No movement path found for Farmers');
									if (DEBUG >= 2)
									{
										$bgagame->notifyAllPlayers('msg', '<HR>', []);
										$bgagame->notifyAllPlayers('msg', '<B>Each ship moves to the nearest hex without hostile ships</B>', []);
										foreach ($path['debug']['founds'] as $possible => $range)
										{
											$sector = Sectors::get($possible[0]);
											$hexagon = substr($possible, 2);
											$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($possible[0]));
											if (array_key_exists($rotated, $bgagame->SECTORS[$sector])) $bgagame->notifyAllPlayers('msg', '${PLANET} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'i18n' => ['PLANET'], 'PLANET' => $bgagame->SECTORS[$sector][$rotated]]);
											else $bgagame->notifyAllPlayers('msg', '${location} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'location' => $possible]);
										}
										$bgagame->notifyAllPlayers('msg', '<HR>', []);
									}
//
									$bgagame->possible['move'][$ship['id']] = $path['possible'];
									$bgagame->acMove($color, $path['location'], [$ship['id']], true);
								}
							}
							else
							{
								if (DEBUG >= 2)
								{
									$bgagame->notifyAllPlayers('msg', '<HR>', []);
									$bgagame->notifyAllPlayers('msg', '<B>No movement</B>', []);
									$bgagame->notifyAllPlayers('msg', '<HR>', []);
								}
							}
							break;
					}
				}
				break;
			case SLAVERS:
				{
//
// At the start of their turn, the Slavers transfer all their ships into a single fleet for every hex where they already have a fleet
//
					foreach (array_unique(array_column(Ships::getAll($color, 'fleet'), 'location')) as $location)
					{
						if ($location !== 'stock')
						{
							$fleets = Ships::getAtLocation($location, $color, 'fleet');
							shuffle($fleets);
							$fleetID = array_pop($fleets);
//
							$ships = Ships::getAtLocation($location, $color, 'ship');
							if ($ships)
							{
								Ships::setStatus($fleetID, 'ships', Ships::getStatus($fleetID, 'ships') + sizeof($ships));
//* -------------------------------------------------------------------------------------------------------- */
								$bgagame->notifyAllPlayers('msg', clienttranslate('${N} ship(s) join fleet ${GPS}'), ['GPS' => $location, 'N' => sizeof($ships)]);
//* -------------------------------------------------------------------------------------------------------- */
								foreach ($ships as $shipID)
								{
//* -------------------------------------------------------------------------------------------------------- */
									$bgagame->notifyAllPlayers('removeShip', '', ['ship' => Ships::get($shipID)]);
//* -------------------------------------------------------------------------------------------------------- */
									Ships::destroy($shipID);
								}
//* -------------------------------------------------------------------------------------------------------- */
								$bgagame->notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'ships' => 16 - sizeof(Ships::getAll($color, 'ship'))]]);
//* -------------------------------------------------------------------------------------------------------- */
							}
							foreach ($fleets as $otherFleetID)
							{
								$otherFleet = Ships::get($otherFleetID);
								Ships::setStatus($fleetID, 'ships', Ships::getStatus($fleetID, 'ships') + Ships::getStatus($otherFleetID, 'ships'));
//* -------------------------------------------------------------------------------------------------------- */
								$bgagame->notifyAllPlayers('removeShip', clienttranslate('A fleet is removed ${GPS}'), ['GPS' => $location, 'ship' => $otherFleet]);
//* -------------------------------------------------------------------------------------------------------- */
								$otherFleet['location'] = 'stock';
								Ships::setLocation($otherFleetID, $otherFleet['location']);
//* -------------------------------------------------------------------------------------------------------- */
								$bgagame->notifyAllPlayers('placeShip', '', ['ship' => $otherFleet]);
//* -------------------------------------------------------------------------------------------------------- */
							}
						}
					}
//
					$PlanetaryDeathRay = Counters::getRelic(PLANETARYDEATHRAY);
					if ($PlanetaryDeathRay && Counters::getStatus($PlanetaryDeathRay, 'owner') === $color && Counters::getStatus($PlanetaryDeathRay, 'available'))
					{
						$atWar = Factions::atWar($color);
						$defenseGrid = Counters::getRelic(DEFENSEGRID);
						$bgagame->possible['planetaryDeathRayTargets'] = Sectors::range(Counters::get($PlanetaryDeathRay)['location'], 5);
//
						$targets = [];
						foreach ($bgagame->possible['planetaryDeathRayTargets'] as $location)
						{
							if ($defenseGrid && Counters::get($defenseGrid)['location'] === $location && Counters::getStatus($defenseGrid, 'owner')) continue;
							foreach (Counters::getAtLocation($location, 'populationDisc') as $disc) if (in_array(Counters::get($disc)['color'], $atWar)) $targets[] = $disc;
						}
						if ($targets)
						{
							shuffle($targets);
							$bgagame->acPlanetaryDeathRay($color, 'disc', array_pop($targets), true);
						}
						else
						{
							$targets = [];
							foreach ($bgagame->possible['planetaryDeathRayTargets'] as $location)
							{
								foreach (Ships::getAtLocation($location) as $ship) if (in_array(Ships::get($ship)['color'], $atWar)) $targets[] = $ship;
							}
							if ($targets)
							{
								shuffle($targets);
								$bgagame->acPlanetaryDeathRay($color, 'ship', array_pop($targets), true);
							}
						}
					}
//
					$shipList = [];
					foreach (array_unique(array_column(Ships::getAll($color), 'location')) as $location) if ($location !== 'stock') $shipList[$location] = Ships::getAtLocation($location, $color);
					foreach ($shipList as $location => $ships)
					{
						switch ($dice)
						{
//
							case 1:
//
// If at peace with you, they first declare war on you
//
								foreach (Factions::atPeace($color) as $otherColor) $bgagame->acDeclareWar($color, $otherColor, true);
//
// All ships then move to (or as close as possible to) the nearest one of your stars
//
								$locations = array_keys(Counters::getPopulations(Factions::getNotAutomas()));
//
								$MPs = [];
								foreach ($ships as $shipID) $MPs[] = Ships::get($shipID)['MP'];
								$path = self::paths($location, min($MPs), $locations);
								if (!$path) throw new BgaVisibleSystemException('No movement path found for Slavers');
								if (DEBUG >= 2)
								{
									$bgagame->notifyAllPlayers('msg', '<HR>', []);
									$bgagame->notifyAllPlayers('msg', '<B>All ships then move to (or as close as possible to) the nearest one of your stars</B>', []);
									foreach ($path['debug']['founds'] as $possible => $range)
									{
										$sector = Sectors::get($possible[0]);
										$hexagon = substr($possible, 2);
										$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($possible[0]));
										if (array_key_exists($rotated, $bgagame->SECTORS[$sector])) $bgagame->notifyAllPlayers('msg', '${PLANET} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'i18n' => ['PLANET'], 'PLANET' => $bgagame->SECTORS[$sector][$rotated]]);
										else $bgagame->notifyAllPlayers('msg', '${location} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'location' => $possible]);
									}
									$bgagame->notifyAllPlayers('msg', '<HR>', []);
								}
//
								$toMove = [];
								foreach ($ships as $shipID)
								{
									$ship = Ships::get($shipID);
									$bgagame->possible['move'][$ship['id']] = $path['possible'];
									if ($ship['fleet'] === 'fleet') $bgagame->acMove($color, $path['location'], [$shipID], true);
									else $toMove[] = $shipID;
								}
								if ($toMove) $bgagame->acMove($color, $path['location'], $toMove, true);
								break;
//
							case 2:
//
// If at peace with you, they first declare war on you
//
								foreach (Factions::atPeace($color) as $otherColor) $bgagame->acDeclareWar($color, $otherColor, true);
//
// All ships then move to the hex with the most hostile ships within range
// If they have no hostile ships within range, they move as close as possible to the nearest hostile ships (no movement if no hostile ships anywhere)
//
								$hostiles = [];
								foreach (Factions::atWar($color) as $enemy)
								{
									foreach (array_count_values(array_column(Ships::getAll($enemy, 'fleet'), 'location')) as $hostile => $count)
									{
										if ($hostile !== 'stock')
										{
											if (!array_key_exists($hostile, $hostiles)) $hostiles[$hostile] = 0;
											$hostiles[$hostile] += $count;
										}
									}
									foreach (array_count_values(array_column(Ships::getAll($enemy, 'ship'), 'location')) as $hostile => $count)
									{
										if ($hostile !== 'stock')
										{
											if (!array_key_exists($hostile, $hostiles)) $hostiles[$hostile] = 0;
											$hostiles[$hostile] += $count;
										}
									}
								}
								if ($hostiles)
								{
									$MPs = [];
									foreach ($ships as $shipID) $MPs[] = Ships::get($shipID)['MP'];
									$founds = [];
									foreach ($hostiles as $hostile => $count) if (!is_null(self::paths($location, min($MPs), [$hostile], true))) $founds[$hostile] = -$count;
									if ($founds)
									{
										if (DEBUG >= 2)
										{
											$bgagame->notifyAllPlayers('msg', '<HR>', []);
											$bgagame->notifyAllPlayers('msg', '<B>All ships then move to the hex with the most hostile ships within range</B>', []);
											foreach ($founds as $possible => $count)
											{
												$sector = Sectors::get($possible[0]);
												$hexagon = substr($possible, 2);
												$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($possible[0]));
												if (array_key_exists($rotated, $bgagame->SECTORS[$sector])) $bgagame->notifyAllPlayers('msg', '${PLANET} with ${count} hostile(s) ${GPS}', ['GPS' => $possible, 'count' => -$count, 'i18n' => ['PLANET'], 'PLANET' => $bgagame->SECTORS[$sector][$rotated]]);
												else $bgagame->notifyAllPlayers('msg', '${location} with ${count} hostile(s) ${GPS}', ['GPS' => $possible, 'count' => -$count, 'location' => $possible]);
											}
											$bgagame->notifyAllPlayers('msg', '<HR>', []);
										}
									}
									else
									{
										foreach ($hostiles as $hostile => $count)
										{
											$path = self::paths($location, min($MPs), [$hostile]);
											$founds[$hostile] = $path['possible'][$hostile]['distance'];
										}
										if (DEBUG >= 2)
										{
											$bgagame->notifyAllPlayers('msg', '<HR>', []);
											$bgagame->notifyAllPlayers('msg', '<B>If they have no hostile ships within range, they move as close as possible to the nearest hostile ships</B>', []);
											foreach ($founds as $possible => $range)
											{
												$sector = Sectors::get($possible[0]);
												$hexagon = substr($possible, 2);
												$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($possible[0]));
												if (array_key_exists($rotated, $bgagame->SECTORS[$sector])) $bgagame->notifyAllPlayers('msg', '${PLANET} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'i18n' => ['PLANET'], 'PLANET' => $bgagame->SECTORS[$sector][$rotated]]);
												else $bgagame->notifyAllPlayers('msg', '${location} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'location' => $possible]);
											}
											$bgagame->notifyAllPlayers('msg', '<HR>', []);
										}
									}
//
									$selected = array_keys($founds, min($founds));
									shuffle($selected);
//
									$path = self::paths($location, min($MPs), [array_pop($selected)]);
//
									$toMove = [];
									foreach ($ships as $shipID)
									{
										$ship = Ships::get($shipID);
										$bgagame->possible['move'][$ship['id']] = $path['possible'];
										if ($ship['fleet'] === 'fleet') $bgagame->acMove($color, $path['location'], [$shipID], true);
										else $toMove[] = $shipID;
									}
									if ($toMove) $bgagame->acMove($color, $path['location'], $toMove, true);
								}
								else
								{
									if (DEBUG >= 2)
									{
										$bgagame->notifyAllPlayers('msg', '<HR>', []);
										$bgagame->notifyAllPlayers('msg', '<B>No movement</B>', []);
										$bgagame->notifyAllPlayers('msg', '<HR>', []);
									}
								}
								break;
//
							case 3:
//
// All ships move as close as possible to the center hex of their sector
//
								$locations = [$location[0] . ':+0+0+0'];
//
								$MPs = [];
								foreach ($ships as $shipID) $MPs[] = Ships::get($shipID)['MP'];
								$path = self::paths($location, min($MPs), $locations);
								if (!$path) throw new BgaVisibleSystemException('No movement path found for Slavers');
								if (DEBUG >= 2)
								{
									$bgagame->notifyAllPlayers('msg', '<HR>', []);
									$bgagame->notifyAllPlayers('msg', '<B>All ships move as close as possible to the center hex of their sectors</B>', []);
									foreach ($path['debug']['founds'] as $possible => $range)
									{
										$sector = Sectors::get($possible[0]);
										$hexagon = substr($possible, 2);
										$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($possible[0]));
										if (array_key_exists($rotated, $bgagame->SECTORS[$sector])) $bgagame->notifyAllPlayers('msg', '${PLANET} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'i18n' => ['PLANET'], 'PLANET' => $bgagame->SECTORS[$sector][$rotated]]);
										else $bgagame->notifyAllPlayers('msg', '${location} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'location' => $possible]);
									}
									$bgagame->notifyAllPlayers('msg', '<HR>', []);
								}
//
								$toMove = [];
								foreach ($ships as $shipID)
								{
									$ship = Ships::get($shipID);
									$bgagame->possible['move'][$ship['id']] = $path['possible'];
									if ($ship['fleet'] === 'fleet') $bgagame->acMove($color, $path['location'], [$shipID], true);
									else $toMove[] = $shipID;
								}
								if ($toMove) $bgagame->acMove($color, $path['location'], $toMove, true);
								break;
//
							case 4:
//
// All ships move to any star within range other than their own
// If there are none, then they move as close as possible to the nearest one.
//
								$locations = [];
								foreach (Sectors::getAll() as $sector) foreach (array_keys($bgagame->SECTORS[Sectors::get($sector)]) as $hexagon)
									{
										$rotated = Sectors::rotate($hexagon, -Sectors::getOrientation($sector));
										$locations[] = "$sector:$rotated";
									}
								$locations = array_diff($locations, array_keys(Counters::getPopulations($color)));
//
								$MPs = [];
								foreach ($ships as $shipID) $MPs[] = Ships::get($shipID)['MP'];
								$path = self::paths($location, min($MPs), $locations, true);
								if (!$path) $path = self::paths($location, $ship['MP'], $locations);
								if (!$path) throw new BgaVisibleSystemException('No movement path found for Slavers');
								if (DEBUG >= 2)
								{
									$bgagame->notifyAllPlayers('msg', '<HR>', []);
									$bgagame->notifyAllPlayers('msg', '<B>All ships move to any star within range other than their own If there are none, then they move as close as possible to the nearest one</B>', []);
									foreach ($path['debug']['founds'] as $possible => $range)
									{
										$sector = Sectors::get($possible[0]);
										$hexagon = substr($possible, 2);
										$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($possible[0]));
										if (array_key_exists($rotated, $bgagame->SECTORS[$sector])) $bgagame->notifyAllPlayers('msg', '${PLANET} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'i18n' => ['PLANET'], 'PLANET' => $bgagame->SECTORS[$sector][$rotated]]);
										else $bgagame->notifyAllPlayers('msg', '${location} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'location' => $possible]);
									}
									$bgagame->notifyAllPlayers('msg', '<HR>', []);
								}
//
								$toMove = [];
								foreach ($ships as $shipID)
								{
									$ship = Ships::get($shipID);
									$bgagame->possible['move'][$ship['id']] = $path['possible'];
									if ($ship['fleet'] === 'fleet') $bgagame->acMove($color, $path['location'], [$shipID], true);
									else $toMove[] = $shipID;
								}
								if ($toMove) $bgagame->acMove($color, $path['location'], $toMove, true);
								break;
//
							case 5:
//
// All ships move to any neutral star within range
// If there are none then they move their full range in a random direction
//
								$locations = [];
								foreach (Sectors::getAll() as $sector) foreach (array_keys($bgagame->SECTORS[Sectors::get($sector)]) as $hexagon)
									{
										$rotated = Sectors::rotate($hexagon, -Sectors::getOrientation($sector));
										if (!Counters::getAtLocation("$sector:$rotated", 'populationDisc')) $locations[] = "$sector:$rotated";
									}
//
								$MPs = [];
								foreach ($ships as $shipID) $MPs[] = Ships::get($shipID)['MP'];
								$path = self::paths($location, min($MPs), $locations, true);
								if (!$path)
								{
									$neighbors = Sectors::neighbors($location, false);
									$direction = array_rand($neighbors);
									while (array_key_exists($direction, $neighbors))
									{
										$next_location = $neighbors[$direction]['location'];
										$neighbors = Sectors::neighbors($next_location, false);
									}
									$path = self::paths($location, min($MPs), [$next_location], false, $direction);
								}
								if (!$path) throw new BgaVisibleSystemException('No movement path found for Slavers');
								if (DEBUG >= 2)
								{
									$bgagame->notifyAllPlayers('msg', '<HR>', []);
									$bgagame->notifyAllPlayers('msg', '<B>All ships move to any neutral star within range. If there are none then they move their full range in a random direction.</B>', []);
									foreach ($path['debug']['founds'] as $possible => $range)
									{
										$sector = Sectors::get($possible[0]);
										$hexagon = substr($possible, 2);
										$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($possible[0]));
										if (array_key_exists($rotated, $bgagame->SECTORS[$sector])) $bgagame->notifyAllPlayers('msg', '${PLANET} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'i18n' => ['PLANET'], 'PLANET' => $bgagame->SECTORS[$sector][$rotated]]);
										else $bgagame->notifyAllPlayers('msg', '${location} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'location' => $possible]);
									}
									$bgagame->notifyAllPlayers('msg', '<HR>', []);
								}
//
								$toMove = [];
								foreach ($ships as $shipID)
								{
									$ship = Ships::get($shipID);
									$bgagame->possible['move'][$ship['id']] = $path['possible'];
									if ($ship['fleet'] === 'fleet') $bgagame->acMove($color, $path['location'], [$shipID], true);
									else $toMove[] = $shipID;
								}
								if ($toMove) $bgagame->acMove($color, $path['location'], $toMove, true);
								break;
//
							case 6:
//
// All ships move to any neutral star within range
// If there are none, then they do not move
//
								$locations = [];
								foreach (Sectors::getAll() as $sector) foreach (array_keys($bgagame->SECTORS[Sectors::get($sector)]) as $hexagon)
									{
										$rotated = Sectors::rotate($hexagon, -Sectors::getOrientation($sector));
										if (!Counters::getAtLocation("$sector:$rotated", 'populationDisc')) $locations[] = "$sector:$rotated";
									}
//
								$MPs = [];
								foreach ($ships as $shipID) $MPs[] = Ships::get($shipID)['MP'];
								$path = self::paths($location, min($MPs), $locations, true);
								if ($path)
								{
									if (DEBUG >= 2)
									{
										$bgagame->notifyAllPlayers('msg', '<HR>', []);
										$bgagame->notifyAllPlayers('msg', '<B>All ships move to any neutral star within range. If there are none, then they do not move</B>', []);
										foreach ($path['debug']['founds'] as $possible => $range)
										{
											$sector = Sectors::get($possible[0]);
											$hexagon = substr($possible, 2);
											$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($possible[0]));
											if (array_key_exists($rotated, $bgagame->SECTORS[$sector])) $bgagame->notifyAllPlayers('msg', '${PLANET} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'i18n' => ['PLANET'], 'PLANET' => $bgagame->SECTORS[$sector][$rotated]]);
											else $bgagame->notifyAllPlayers('msg', '${location} at range ${range} ${GPS}', ['GPS' => $possible, 'range' => $range, 'location' => $possible]);
										}
										$bgagame->notifyAllPlayers('msg', '<HR>', []);
									}
//
									$toMove = [];
									foreach ($ships as $shipID)
									{
										$ship = Ships::get($shipID);
										$bgagame->possible['move'][$ship['id']] = $path['possible'];
										if ($ship['fleet'] === 'fleet') $bgagame->acMove($color, $path['location'], [$shipID], true);
										else $toMove[] = $shipID;
									}
									if ($toMove) $bgagame->acMove($color, $path['location'], $toMove, true);
								}
								else
								{
									if (DEBUG >= 2)
									{
										$bgagame->notifyAllPlayers('msg', '<HR>', []);
										$bgagame->notifyAllPlayers('msg', '<B>No movement</B>', []);
										$bgagame->notifyAllPlayers('msg', '<HR>', []);
									}
								}
								break;
						}
					}
				}
				break;
			default:
				throw new BgaVisibleSystemException('Invalid automas: ' . $color);
		}
	}
	static function retreat(string $color): int
	{
		switch (Factions::getPlayer($color))
		{
			case FARMERS:
				return 0;
			case SLAVERS:
				return 4;
			default:
				throw new BgaVisibleSystemException('Invalid automas: ' . $color);
		}
	}
	static function battleLoss(string $attacker, array $defenders, bool $totalVictory)
	{
		$location = Factions::getStatus($attacker, 'combat');
		$winner = Factions::getStatus($attacker, 'winner');
//
		$toDestroy = ['winner' => [], 'losers' => []];
		if (Factions::getPlayer($winner) === SLAVERS)
		{
//
			if (!Factions::getStatus($attacker, 'military'))
			{
				foreach (Ships::getAtLocation($location, $attacker, 'fleet') as $shipID) for ($i = 0; $i < intval(Ships::getStatus($shipID, 'ships')); $i++) $toDestroy[$attacker === $winner ? 'winner' : 'losers'][] = [$attacker, Ships::getStatus($shipID, 'fleet')];
				for ($i = 0; $i < sizeof(Ships::getAtLocation($location, $attacker, 'ship')); $i++) $toDestroy[$attacker === $winner ? 'winner' : 'losers'][] = [$attacker, 'ships'];
			}
//
			foreach ($defenders as $defender)
			{
				if (!Factions::getStatus($defender, 'military'))
				{
					foreach (Ships::getAtLocation($location, $defender, 'fleet') as $shipID) for ($i = 0; $i < intval(Ships::getStatus($shipID, 'ships')); $i++) $toDestroy[$attacker !== $winner ? 'winner' : 'losers'][] = [$defender, Ships::getStatus($shipID, 'fleet')];
					for ($i = 0; $i < sizeof(Ships::getAtLocation($location, $defender, 'ship')); $i++) $toDestroy[$attacker !== $winner ? 'winner' : 'losers'][] = [$defender, 'ships'];
				}
			}
//
			shuffle($toDestroy['winner']);
			if ($totalVictory) $toDestroy['winner'] = [];
			else $toDestroy['winner'] = array_slice($toDestroy['winner'], 0, ceil(sizeof($toDestroy['losers']) / 2));
		}
//
		return $toDestroy;
	}
	static function growthActions(string $color, int $difficulty, int $dice): array
	{
		$wormholes = self::WORMHOLES;
		shuffle($wormholes);
//
		$counters = [];
		switch (Factions::getPlayer($color))
		{
			case AUTOMA:
				break;
			case FARMERS:
				if (!Ships::getAll($color)) $dice = 6;
				switch ($dice)
				{
					case 1:
						$counters[] = 'research';
						$counters[] = 'Military';
						if (Factions::getTechnology($color, 'robotics') >= 5) self::randomTechnology($color, $counters);
						break;
					case 2:
						$counters[] = 'research';
						$counters[] = 'Spirituality';
						if (Factions::getTechnology($color, 'robotics') >= 5) self::randomTechnology($color, $counters);
						break;
					case 3:
						$counters[] = 'research';
						$counters[] = 'Propulsion';
						if (Factions::getTechnology($color, 'robotics') >= 5) self::randomTechnology($color, $counters);
						break;
					case 4:
						$counters[] = 'research';
						$counters[] = 'Robotics';
						if (Factions::getTechnology($color, 'robotics') >= 5) self::randomTechnology($color, $counters);
						break;
					case 5:
						$counters[] = 'research';
						$counters[] = 'Genetics';
						if (Factions::getTechnology($color, 'robotics') >= 5) self::randomTechnology($color, $counters);
						break;
					case 6:
						$counters[] = 'changeTurnOrderUp';
						$counters[] = 'buildShips';
						Factions::setStatus($color, 'buildShips', array_combine(array_slice($wormholes, 0, 1), [1]));
						break;
				}
				break;
			case SLAVERS:
				$ships = $difficulty + Factions::ships($color);
				switch ($dice)
				{
					case 1:
// Research Military
						$counters[] = 'research';
						$counters[] = 'Military';
						if (Factions::getTechnology($color, 'robotics') >= 5) self::randomTechnology($color, $counters);
// Spawn ships at all 3 wormholes
						$counters[] = 'buildShips';
						Factions::setStatus($color, 'buildShips', array_combine($wormholes, [$ships, $ships, $ships]));
						break;
					case 2:
// Change turn order: down
						$counters[] = 'changeTurnOrderDown';
// Gain a star owned by you (declaring war on you if needed), otherwise gain 2 neutral stars (**)
						$counters[] = 'gainStar';
						Factions::setStatus($color, 'gainStar', 'player');
//						Factions::setStatus($color, 'special', true);
// Spawn ships at 2 wormholes
						$counters[] = 'buildShips';
						Factions::setStatus($color, 'buildShips', array_combine(array_slice($wormholes, 0, 2), [$ships, $ships]));
						break;
					case 3:
// Research Propulsion
						$counters[] = 'research';
						$counters[] = 'Propulsion';
						if (Factions::getTechnology($color, 'robotics') >= 5) self::randomTechnology($color, $counters);
// Spawn ships at 1 wormhole
						$counters[] = 'buildShips';
						Factions::setStatus($color, 'buildShips', array_combine(array_slice($wormholes, 0, 1), [$ships]));
						break;
					case 4:
// Research Robotics
						$counters[] = 'research';
						$counters[] = 'Robotics';
						if (Factions::getTechnology($color, 'robotics') >= 5) self::randomTechnology($color, $counters);
// Spawn ships at the center sector wormhole
						$counters[] = 'buildShips';
						Factions::setStatus($color, 'buildShips', array_combine([self::WORMHOLES[0]], [$ships]));
						break;
					case 5:
// Change turn order: down
						$counters[] = 'changeTurnOrderDown';
// Gain a star(**)
						$counters[] = 'gainStar';
						Factions::setStatus($color, 'gainStar', 'any');
//						Factions::setStatus($color, 'special', true);
// Grow population (if they cannot grow any population, then they spawn ships at the center sector wormhole instead)
						$counters[] = 'growPopulation';
//						$counters[] = 'buildShips';
						Factions::setStatus($color, 'growPopulation', array_combine([self::WORMHOLES[0]], [$ships]));
						break;
					case 6:
// Gain a neutral star (otherwise one of yours)(**)
						$counters[] = 'gainStar';
						Factions::setStatus($color, 'gainStar', 'neutral');
//						Factions::setStatus($color, 'special', true);
// Research a randomly selected technology (determine which one immediately and use a technology counter to mark as reminder)
						$counters[] = 'research';
						self::randomTechnology($color, $counters);
						break;
				}
				break;
			default:
				throw new BgaVisibleSystemException('Invalid automas: ' . $color);
		}
//
		return $counters;
	}
	static function randomTechnology(string $color, array &$counters)
	{
		$technologies = array_diff_key(Factions::TECHNOLOGIES, $counters);
		if (Factions::getPlayer($color) === SLAVERS && Factions::getTechnology($color, 'Spirituality') >= 4) unset($technologies['Spirituality']);
		foreach (array_keys($technologies) as $technology) if (Factions::getTechnology($color, $technology) === 6) unset($technologies[$technology]);
		if ($technologies) $counters[] = array_rand($technologies);
	}
	static function actions(object $bgagame, string $color)
	{
		$counters = Factions::getStatus($color, 'counters');
		$counter = array_shift($counters);
//
		switch ($counter)
		{
//
			case 'gainStar':
//
				Factions::setStatus($color, 'special', true);
//
				$shipLocations = array_unique(array_column(Ships::getAll($color), 'location'));
				$stars = array_keys(Counters::getPopulations(Factions::getNotAutomas()));
//
				switch (Factions::getStatus($color, 'gainStar'))
				{
//
					case 'player':
//
// Gain a star owned by you (declaring war on you if needed), otherwise gain 2 neutral stars (**)
//
						$locations = [];
						foreach (array_intersect($shipLocations, $stars) as $location) if (Counters::gainStar($color, $location, true)[0]) $locations[] = $location;
						if ($locations)
						{
							shuffle($locations);
							if (DEBUG >= 2)
							{
								$bgagame->notifyAllPlayers('msg', '<HR>', []);
								$bgagame->notifyAllPlayers('msg', '<B>Gain a star owned by you (declaring war on you if needed), otherwise gain 2 neutral stars (**)</B>', []);
								foreach ($locations as $location)
								{
									$sector = Sectors::get($location[0]);
									$hexagon = substr($location, 2);
									$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($location[0]));
									$bgagame->notifyAllPlayers('msg', '${PLANET} ${GPS}', ['GPS' => $location, 'i18n' => ['PLANET'], 'PLANET' => $bgagame->SECTORS[$sector][$rotated]]);
								}
								$bgagame->notifyAllPlayers('msg', '<HR>', []);
							}
							$locations = array_slice($locations, 0, 1);
						}
						else
						{
							Factions::setStatus($color, 'gainStar', 'neutral2');
							Factions::setStatus($color, 'counters', ['gainStar', 'gainStar', ...$counters]);
//
							return $bgagame->gamestate->nextState('continue');
						}
//
						break;
//
					case 'neutral2':
//
// Gain a neutral star (**)
//
						$locations = [];
						foreach ($shipLocations as $location) if (Counters::getAtLocation($location, 'star') && Counters::gainStar($color, $location)[0]) $locations[] = $location;
						shuffle($locations);
						if (DEBUG >= 2)
						{
							$bgagame->notifyAllPlayers('msg', '<HR>', []);
							$bgagame->notifyAllPlayers('msg', '<B>Gain a star owned by you (declaring war on you if needed), otherwise gain 2 neutral stars (**)</B>', []);
							foreach ($locations as $location)
							{
								$sector = Sectors::get($location[0]);
								$hexagon = substr($location, 2);
								$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($location[0]));
								$bgagame->notifyAllPlayers('msg', '${PLANET} ${GPS}', ['GPS' => $location, 'i18n' => ['PLANET'], 'PLANET' => $bgagame->SECTORS[$sector][$rotated]]);
							}
							$bgagame->notifyAllPlayers('msg', '<HR>', []);
						}
						$locations = array_slice($locations, 0, 1);
//
						break;
//
					case 'any':
//
// Gain a star(**)
//
						$locations = [];
						foreach (array_intersect($shipLocations, $stars) as $location) if (Counters::gainStar($color, $location, true)[0]) $locations[] = $location;
						foreach ($shipLocations as $location) if (Counters::getAtLocation($location, 'star') && Counters::gainStar($color, $location)[0]) $locations[] = $location;
						shuffle($locations);
						if (DEBUG >= 2)
						{
							$bgagame->notifyAllPlayers('msg', '<HR>', []);
							$bgagame->notifyAllPlayers('msg', '<B>Gain a star(**)</B>', []);
							foreach ($locations as $location)
							{
								$sector = Sectors::get($location[0]);
								$hexagon = substr($location, 2);
								$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($location[0]));
								$bgagame->notifyAllPlayers('msg', '${PLANET} ${GPS}', ['GPS' => $location, 'i18n' => ['PLANET'], 'PLANET' => $bgagame->SECTORS[$sector][$rotated]]);
							}
							$bgagame->notifyAllPlayers('msg', '<HR>', []);
						}
						$locations = array_slice($locations, 0, 1);
//
						break;
//
					case 'neutral':
//
// Gain a neutral star (otherwise one of yours)(**)
//
						$locations = [];
						foreach ($shipLocations as $location) if (Counters::getAtLocation($location, 'star') && Counters::gainStar($color, $location)[0]) $locations[] = $location;
						if (!$locations) foreach (array_intersect($shipLocations, $stars) as $location) if (Counters::gainStar($color, $location, true)[0]) $locations[] = $location;
						shuffle($locations);
						if (DEBUG >= 2)
						{
							$bgagame->notifyAllPlayers('msg', '<HR>', []);
							$bgagame->notifyAllPlayers('msg', '<B>Gain a neutral star (otherwise one of yours)(**)</B>', []);
							foreach ($locations as $location)
							{
								$sector = Sectors::get($location[0]);
								$hexagon = substr($location, 2);
								$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($location[0]));
								$bgagame->notifyAllPlayers('msg', '${PLANET} ${GPS}', ['GPS' => $location, 'i18n' => ['PLANET'], 'PLANET' => $bgagame->SECTORS[$sector][$rotated]]);
							}
							$bgagame->notifyAllPlayers('msg', '<HR>', []);
						}
						$locations = array_slice($locations, 0, 1);
//
						break;
//
					default:
//
						throw new BgaVisibleSystemException('Not implemented: gainStar ' . Factions::getStatus($color, 'gainStar'));
//
				}
//
				if ($locations)
				{
					$location = array_pop($locations);
					if (in_array($location, $stars)) foreach (Factions::atPeace($color) as $otherColor) $bgagame->acDeclareWar($color, $otherColor, true);
					return $bgagame->acGainStar($color, $location, false, true);
				}
//
				Factions::setStatus($color, 'counters', $counters);
				return $bgagame->gamestate->nextState('continue');
//
			case 'growPopulation':
//
				$locations = [];
//
				$bgagame->possible = ['growPopulation' => []];
				foreach (Counters::getPopulations($color, true) as $location => $population)
				{
					$bgagame->possible['growPopulation'][$location] = ['population' => intval($population), 'growthLimit' => Sectors::nearest($location, $color)];
					if ($bgagame->possible['growPopulation'][$location]['population'] < $bgagame->possible['growPopulation'][$location]['growthLimit']) $locations[] = $location;
				}
				$bgagame->possible['bonusPopulation'] = Factions::TECHNOLOGIES['Genetics'][Factions::getTechnology($color, 'Genetics')];
//
				$locationsBonus = array_keys($bgagame->possible['growPopulation']);
				shuffle($locationsBonus);
				$locationsBonus = array_slice($locationsBonus, 0, $bgagame->possible['bonusPopulation']);
//
				if (!$locations && !$locationsBonus)
				{
//
// If they cannot grow any population, then they spawn ships at the center sector wormhole instead
//
					$counters[] = 'buildShips';
					Factions::setStatus($color, 'counters', $counters);
					return $bgagame->acBuildShips($color, self::BuildShips($color, Factions::getStatus($color, 'growPopulation')), true);
				}
				else return $bgagame->acGrowPopulation($color, $locations, $locationsBonus, false, true);
//
			case 'research':
//
				return $bgagame->acResearch($color, array_intersect($counters, array_keys(Factions::TECHNOLOGIES)), true);
//
			case 'buildShips':
//
				return $bgagame->acBuildShips($color, self::BuildShips($color, Factions::getStatus($color, 'buildShips')), true);
//
			case 'Military':
			case 'Spirituality':
			case 'Propulsion':
			case 'Robotics':
			case 'Genetics':
//
				Factions::setStatus($color, 'counters', $counters);
				return $bgagame->gamestate->nextState('continue');
//
			default:
//
				throw new BgaVisibleSystemException("Invalid action $counter");
		}
	}
	static function buildShips(string $color, array $locations): array
	{
		$shipsUsed = sizeof(Ships::getAll($color, 'ship'));
		$stocks = Ships::getAtLocation('stock', $color, 'fleet');
		shuffle($stocks);
//
		$toBuild = ['fleets' => [], 'ships' => []];
		foreach ($locations as $location => $ships)
		{
			if ($shipsUsed + $ships > 16)
			{
				$fleets = Ships::getAtLocation($location, $color, 'fleet');
				if (!$fleets && $stocks) $fleets = [array_pop($stocks)];
				if ($fleets)
				{
					shuffle($fleets);
					$fleet = Ships::get(array_pop($fleets));
//
					$Fleet = Ships::getStatus($fleet['id'], 'fleet');
					if ($fleet['location'] === 'stock') $toBuild['fleets'][$Fleet] = $location;
//
					for ($i = 0; $i < $ships; $i++) $toBuild['ships'][] = $Fleet;
				}
				else
				{
//					$ships = 16 - $shipsUsed;
//					$shipsUsed += $ships;
//					for ($i = 0; $i < $ships; $i++) $toBuild['ships'][] = $location;
				}
			}
			else
			{
				$shipsUsed += $ships;
				for ($i = 0; $i < $ships; $i++) $toBuild['ships'][] = $location;
			}
		}
		return $toBuild;
	}
	static function trading(string $color, string $alignment): int
	{
		switch (Factions::getPlayer($color))
		{
			case AUTOMA:
				return 0;
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
	static function paths(string $location, int $MP, array $dests, bool $inRange = false, $direction = null)
	{
		$founds = [];
//
		$possible = [$location => ['MP' => $MP, 'from' => null, 'distance' => 0]];
//
		$locations = [$location => 0];
		while (sizeof($founds) !== sizeof($dests) && $locations)
		{
			$distance = min($locations);
			$location = array_search($distance, $locations);
			unset($locations[$location]);
//
			if (in_array($location, $dests) && !array_key_exists($location, $founds)) $founds[$location] = $distance;
//
			$distance += 1;
			$neighbors = Sectors::neighbors($location, false);
			if (!is_null($direction) && array_key_exists($direction, $neighbors)) $neighbors = [$direction => $neighbors[$direction]];
			else shuffle($neighbors);
			foreach ($neighbors as ['location' => $next_location, 'terrain' => $terrain])
			{
				$next_MP = $possible[$location]['MP'] - ($terrain === Sectors::NEBULA ? 2 : 1);
				if ($terrain === Sectors::NEUTRON) $next_MP -= 100;
//
				if (!$inRange || $next_MP >= 0)
				{
					if (!array_key_exists($next_location, $possible) || ($possible[$next_location]['MP'] < $next_MP) || ($possible[$next_location]['distance'] > $distance))
					{
						$possible[$next_location] = ['MP' => $next_MP, 'from' => $location, 'distance' => $distance];
						$locations[$next_location] = $distance;
					}
				}
			}
		}
//
		if ($inRange) $selected = array_keys($founds);
		else $selected = array_keys($founds, min($founds));
//
		if (!$selected) return NULL;
//
		shuffle($selected);
		$dest = array_pop($selected);
//
		while ($possible[$dest]['MP'] < 0) $dest = $possible[$dest]['from'];
		return ['location' => $dest, 'possible' => $possible, 'debug' => ['selected' => $selected, 'founds' => $founds]];
	}
}
