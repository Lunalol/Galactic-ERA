<?php

/**
 *
 * @author Lunalol
 */
class DominationCards extends APP_GameClass
{
	static function init()
	{
		$deck = self::getNew("module.common.deck");
		$deck->init("domination");
//
		return $deck;
	}
	static function A(string $color, int $domination): int
	{
		$scoringPhase = false;
		$event = self::getObjectFromDB("SELECT * FROM stack WHERE new_state = 0 ORDER BY id DESC LIMIT 1");
		if ($event && $event['trigger_state'] == 550) $scoringPhase = true;
//
		switch ($domination)
		{
			case ACQUISITION:
// Conquer/liberate 2 player owned stars on the same turn
// Play this card when this happens
				$acquisition = Factions::getStatus($color, 'acquisition') ?? [];
				return (sizeof($acquisition) >= 2) ? 10 : 0;
			case ALIGNMENT:
// Can only be played at the end of the scoring phase
// Have 5 DP and either have more DP (solo variant: tech. levels) than every other player with your alignment or be the only one of your alignment then
				$best = true;
				$DP = Factions::getDP($color);
				$alignement = Factions::getAlignment($color);
				foreach (Factions::list() as $otherColor) if ($color !== $otherColor && $alignement === Factions::getAlignment($otherColor) && Factions::getDP($otherColor) >= $DP) $best = false;
				return ($scoringPhase && Factions::getDP($color) >= 5 && $best) ? 9 : 0;
			case CENTRAL:
// Own 4 stars in the center sector
				$numberOfStars = 0;
				foreach (array_keys(Counters::getPopulations($color, false)) as $location) if ($location[0] === '0') $numberOfStars++;
				return ($numberOfStars >= 4) ? 12 : 0;
			case DEFENSIVE:
// Own all the stars (except neutron stars) in your home star sector (i.e., the sector with your home star)
				$all = true;
				$owned = array_keys(Counters::getPopulations($color));
				foreach (Sectors::SECTORS[Sectors::get(Factions::getHomeStar($color))] as $hexagon => $type)
				{
					if ($type === Sectors::PLANET || $type === Sectors::HOME)
					{
						$rotated = Sectors::rotate($hexagon, -Sectors::getOrientation(Factions::getHomeStar($color)));
						if (!in_array(Factions::getHomeStar($color) . ':' . $rotated, $owned)) $all = false;
					}
				}
				return $all ? 9 : 0;
			case DENSITY:
// Have 3 stars with 5 or more population each
				$numberOfStars = 0;
				foreach (Counters::getPopulations($color, false) as $population) if ($population >= 5) $numberOfStars++;
				return ($numberOfStars >= 3) ? 7 : 0;
			case DIPLOMATIC:
// Have Spirituality level 4 or higher, own the center star of the center sector and be at peace with every player
				return
					(Factions::getTechnology($color, 'Spirituality') >= 4 &&
					array_key_exists('0:+0+0+0', Counters::getPopulations($color, false)) &&
					!Factions::atWar($color)) ? 14 : 0;
			case ECONOMIC:
// Build 10 ships in a single Build Ships growth action
// Any ships built as the direct result of star people special effects (e.g. STS Rogue AI) do not count for fulfilling this
// Play this card when this happens
				return (0) ? 7 : 0;
			case ETHERIC:
// Have a ship each in 4 nebula hexes at the start of your movement
				$numberOfShips = 0;
				foreach (array_unique(array_column(Ships::getAll($color), 'location')) as $location) if (Sectors::terrainFromLocation($location) === Sectors::NEBULA) $numberOfShips++;
				return ($numberOfShips >= 4) ? 8 : 0;
			case EXPLORATORY:
// Have Propulsion level 4 or higher, have a ship and a star each in 4 sectors
				return (0) ? 13 : 0;
			case GENERALSCIENTIFIC:
// Have a total of 16 technology levels
				$levels = 0;
				foreach (array_keys(Factions::TECHNOLOGIES) as $technology) $levels += Factions::getTechnology($color, $technology);
				return ($levels >= 16) ? 9 : 0;
			case MILITARY:
// Have ships totaling 120 in CV (not counting bonuses of any kind)
// Reveal enough ships to prove this
// If you play this card during a battle, all your ships in that battle still count toward the total (even if they would be destroyed).
				return (0) ? 10 : 0;
			case SPATIAL:
// Own 10 stars
				return sizeof(Counters::getPopulations($color, false)) >= 10;
			case SPECIALSCIENTIFIC:
// Have level 6 in 1 technology field and level 5 or higher in another field
				return (0) ? 11 : 0;
			default:
				throw new BgaVisibleSystemException('Invalid Domination Card: ' . $domination);
		}
	}
	static function B(string $color, int $domination): array
	{
		$scoringPhase = false;
		$event = self::getObjectFromDB("SELECT * FROM stack WHERE new_state = 0 ORDER BY id DESC LIMIT 1");
		if ($event && $event['trigger_state'] == 550) $scoringPhase = true;
//
		$scoring = [];
		switch ($domination)
		{
			case ACQUISITION:
// 1 DP per neutral star where only you have a ship
				$numberoOfStars = 0;
				foreach (Sectors::stars() as $location) if (Counters::getAtLocation($location, 'star') && Ships::getAtLocation($location, $color)) $numberoOfStars++;
				$scoring[] = 1 * $numberoOfStars;
// 1 DP per Military level
				$scoring[] = 1 * Factions::getTechnology($color, 'Military');
				break;
			case ALIGNMENT:
// 4 if you did not get any DP for your alignment in the scoring phase of this round
				$scoring[] = ($scoringPhase && Factions::getStatus($color, 'alignment')) ? 0 : 4;
// 1 DP per Spirituality level
				$scoring[] = 1 * Factions::getTechnology($color, 'Spirituality');
				break;
			case CENTRAL:
// 1 DP per population of one of your stars in the center sector
				$locations = array_filter(array_map('intval', Counters::getPopulations($color)), fn($location) => ($location[0] === '0'), ARRAY_FILTER_USE_KEY);
				$scoring[] = $locations ? max($locations) : 0;
				break;
			case DEFENSIVE:
// 4 DP if no other player owns a star in your home sector + 1 DP per 2 Military level
				$defensive = 1;
				$sector = Factions::getHomeStar($color);
				foreach (Sectors::stars() as $location)
				{
					if ($location[0] == $sector)
					{
						$counters = Counters::getAtLocation($location, 'populationDisc');
						if ($counters && Counters::get(array_pop($counters))['color'] !== $color) $defensive = 0;
					}
				}
				$scoring[] = 4 * $defensive + 1 * intdiv(Factions::getTechnology($color, 'Military'), 2);
				break;
			case DENSITY:
// 1 DP per star you own with 4+ population
				$scoring[] = 1 * sizeof(array_filter(Counters::getPopulations($color, false), fn($population) => ($population >= 4)));
				break;
			case DIPLOMATIC:
// 2 DP per other player’s home star where you have a ship (including automa)
				$numberOfHomeStar = 0;
				foreach (Factions::list(true) as $otherColor) if ($otherColor !== $color && Ships::getHomeStarLocation($otherColor) && Ships::getAtLocation(Ships::getHomeStarLocation($otherColor), $color)) $numberOfHomeStar++;
				$scoring[] = 2 * $numberOfHomeStar;
// 1 DP per Spirituality level
				$scoring[] = 1 * Factions::getTechnology($color, 'Spirituality');
				break;
			case ECONOMIC:
// 1 DP per Asteroid system where you have a ship
				$asteroids = [];
				foreach (Ships::getAll($color) as $ship) if (Sectors::terrainFromLocation($ship['location']) === Sectors::ASTEROIDS) $asteroids[] = $ship['location'];
				$scoring[] = 1 * sizeof(array_unique($asteroids));
// 1 DP per Robotics level
				$scoring[] = 1 * Factions::getTechnology($color, 'Robotics');
				break;
			case ETHERIC:
// STO: 1 DP per Spirituality level
				if (Factions::getAlignment($color) === 'STO') $scoring[] = 1 * Factions::getTechnology($color, 'Spirituality');
// STS: 1 DP per Military level
				if (Factions::getAlignment($color) === 'STS') $scoring[] = 1 * Factions::getTechnology($color, 'Military');
				break;
			case EXPLORATORY:
// 1 DP per sectors with a ship of yours
				$numberOfSector = 0;
				$sectors = array_fill_keys(Sectors::getAll(), 0);
				foreach (Ships::getAll($color) as $ship) if ($ship['location'] !== 'stock') $sectors[$ship['location'][0]] |= 1;
				foreach ($sectors as $sector => $result) if ($result) $numberOfSector++;
				$scoring[] = 1 * $numberOfSector;
// 1 DP per Propulsion level
				$scoring[] = 1 * Factions::getTechnology($color, 'Propulsion');
				break;
			case GENERALSCIENTIFIC:
// 2 DP × your lowest technology level
				$min = 6;
				foreach (array_keys(Factions::TECHNOLOGIES) as $technology) $min = min($min, Factions::getTechnology($color, $technology));
				$scoring[] = 2 * $min;
				break;
			case MILITARY:
// 2 DP per sector where you are the only player with a fleet
				$numberOfSector = 0;
				$sectors = array_fill_keys(Sectors::getAll(), [0, 1]);
				foreach (Ships::getAll(null, 'fleet') as $fleet)
				{
					if ($fleet['location'] !== 'stock')
					{
						$sector = $fleet['location'][0];
						if ($fleet['color'] === $color) $sectors[$sector][0] |= 1;
						else $sectors[$sector][1] &= 0;
					}
				}
				foreach ($sectors as $sector => $result) if ($result[0] && $result[1]) $numberOfSector++;
				$scoring[] = 2 * $numberOfSector;
// 1 DP per Military level
				$scoring[] = 1 * Factions::getTechnology($color, 'Military');
				break;
			case SPATIAL:
// 2 DP per 3 stars you own
				$scoring[] = 2 * intdiv(sizeof(Counters::getPopulations($color, false)), 3);
// 1 DP per Propulsion level
				$scoring[] = 1 * Factions::getTechnology($color, 'Propulsion');
				break;
			case SPECIALSCIENTIFIC:
// 1 DP × your highest technology level
				$max = 1;
				foreach (array_keys(Factions::TECHNOLOGIES) as $technology) $max = max($max, Factions::getTechnology($color, $technology));
				$scoring[] = $max;
				break;
			default:
				throw new BgaVisibleSystemException('Invalid Domination Card: ' . $domination);
		}
		return $scoring;
	}
}
