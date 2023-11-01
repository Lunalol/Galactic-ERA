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
	static function A(string $color, int $domination): boolean
	{
		switch ($domination)
		{
			case ACQUISITION:
// Conquer/liberate 2 playerowned stars on the same turn
// Play this card when this happens
				return false;
			case ALIGNMENT:
// Can only be played at the end of the scoring phase
// Have 5 DP and either have more DP (solo variant: tech. levels) than every other player with your alignment or be the only one of your alignment then
				return false;
			case CENTRAL:
// Own 4 stars in the center sector
				return false;
			case DEFENSIVE:
// Own all the stars (except neutron stars) in your home star sector (i.e., the sector with your home star)
				return false;
			case DENSITY:
// Have 3 stars with 5 or more population each
				return false;
			case DIPLOMATIC:
// Have Spirituality level 4 or higher, own the center star of the center sector and be at peace with every player
				return false;
			case ECONOMIC:
// Build 10 ships in a single Build Ships growth action
// Any ships built as the direct result of star people special effects (e.g. STS Rogue AI) do not count for fulfilling this
// Play this card when this happens
				return false;
			case ETHERIC:
// Have a ship each in 4 nebula hexes at the start of your movement
				return false;
			case EXPLORATORY:
// Have Propulsion level 4 or higher, have a ship and a star each in 4 sectors
				return false;
			case GENERALSCIENTIFIC:
// Have a total of 16 technology levels
				return false;
			case MILITARY:
// Have ships totaling 120 in CV (not counting bonuses of any kind)
// Reveal enough ships to prove this
// If you play this card during a battle, all your ships in that battle still count toward the total (even if they would be destroyed).
				return false;
			case SPATIAL:
// Own 10 stars
				return false;
			case SPECIALSCIENTIFIC:
// Have level 6 in 1 technology field and level 5 or higher in another field
				return false;
			default:
				throw new BgaVisibleSystemException('Invalid Domination Card: ' . $domination);
		}
	}
	static function B(string $color, int $domination)
	{
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
//
				$scoring[] = 0;
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
					if ($location[0] === $sector)
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
// 2 DP per other player’s home star where you have a ship (including puppet)
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
