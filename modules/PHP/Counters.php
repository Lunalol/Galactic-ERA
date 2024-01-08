<?php

class Counters extends APP_GameClass
{
//
	static function create($color, $type, $location, array $status = []): int
	{
		$json = self::escapeStringForDB(json_encode($status, JSON_FORCE_OBJECT));
		self::DbQuery("INSERT INTO counters (color,type,location,status) VALUES ('$color','$type','$location','$json')");
		return self::DbGetLastId();
	}
	static function getAllDatas(): array
	{
		return self::getCollectionFromDB("SELECT id,color,type,location FROM counters ORDER BY color,type");
	}
	static function destroy(int $id): void
	{
		self::DbQuery("DELETE FROM counters WHERE id = $id");
	}
	static function get(int $id): array
	{
		return self::getNonEmptyObjectFromDB("SELECT * FROM counters WHERE id = $id");
	}
	static function getRelic(int $relic)
	{
		return self::getUniqueValueFromDB("SELECT id FROM counters WHERE status->'$.back' = $relic");
	}
	static function getAtLocation(string $location, string $type = null): array
	{
		if (is_null($type)) return self::getObjectListFromDB("SELECT id FROM counters WHERE location = '$location'", true);
		return self::getObjectListFromDB("SELECT id FROM counters WHERE location = '$location' AND type = '$type'", true);
	}
	static function setStatus(int $id, string $status, $value = null): void
	{
		if (is_null($value)) self::dbQuery("UPDATE counters SET status = JSON_REMOVE(status,'$.$status') WHERE id = $id");
		else self::dbQuery("UPDATE counters SET status = JSON_SET(status,'$.$status','$value') WHERE id = $id");
	}
	static function getStatus(int $id, string $status)
	{
		return self::getUniqueValueFromDB("SELECT JSON_UNQUOTE(status->'$.$status') FROM counters WHERE id = $id");
	}
	static function getAdvancedFleetTactics(string $color): array
	{
		return self::getCollectionFromDB("SELECT location, id FROM counters WHERE color = '$color' AND type IN ('2x', 'DP')", true);
	}
	static function getPopulations(string $color, bool $blocking = false): array
	{
		$populations = self::getCollectionFromDB("SELECT location,COUNT(*) AS population FROM counters WHERE color = '$color' AND type = 'populationDisc' GROUP BY location", true);
//
		$homeStar = Ships::getHomeStarLocation($color);
		if ($homeStar)
		{
			if (array_key_exists($homeStar, $populations)) $populations[$homeStar] += 6;
			else $populations[$homeStar] = 6;
		}
//
		if ($blocking) foreach ($populations as $location => $population) if (self::isBlocked($color, $location)) unset($populations[$location]);
//
		return $populations;
	}
	static function getOwner($location)
	{
		$homeStar = Ships::getAtLocation($location, null, 'homeStar');
		if ($homeStar) return(Ships::get($homeStar[0])['color']);
		$populations = Counters::getAtLocation($location, 'populationDisc');
		if ($populations) return(Counters::get($populations[0])['color']);
		return null;
	}
	static function isBlocked(string $color, string $location, string $willDeclareWarOn = '')
	{
		if (Factions::getTechnology($color, 'Spirituality') >= 5) return false;
		foreach (Factions::atWar($color) as $otherColor) if (Ships::getAtLocation($location, $otherColor)) return true;
		if ($willDeclareWarOn && Ships::getAtLocation($location, $willDeclareWarOn)) return true;
		return false;
	}
	static function reveal(string $color, string $type, int $id)
	{
		self::DbQuery("INSERT INTO revealed VALUES('$color','$type',$id) ON DUPLICATE KEY UPDATE id = id");
		return self::DbGetLastId();
	}
	static function isRevealed(int $id, string $type = null): array
	{
		if (is_null($type)) return self::getObjectListFromDB("SELECT color FROM revealed WHERE type IN ('star','relic') AND id = $id", true);
		return self::getObjectListFromDB("SELECT color FROM revealed WHERE type = '$type' AND id = $id", true);
	}
	static function listRevealed(string $color, string $type = null): array
	{
		if (is_null($type)) return self::getObjectListFromDB("SELECT id FROM revealed WHERE color = '$color' AND type IN ('star','relic')", true);
		return self::getObjectListFromDB("SELECT id FROM revealed WHERE color = '$color' AND type = '$type'", true);
	}
	static function gainStar(string $color, string $location, bool $willDeclareWar = false, bool $bonus = false): array
	{
		if (!$willDeclareWar && self::isBlocked($color, $location, $willDeclareWar)) return [0, 0, 0];
//
		$sizeOfPopulation = 0;
//
		$homeStar = Ships::getAtLocation($location, null, 'homeStar');
		if ($homeStar) $sizeOfPopulation += 6;
//
		$populations = Counters::getAtLocation($location, 'populationDisc');
		if ($populations) $sizeOfPopulation += sizeof($populations);
//
		$orion = false;
		if ($sizeOfPopulation)
		{
			$otherColor = $homeStar ? Ships::get($homeStar[0])['color'] : Counters::get($populations[0])['color'];
			if ($willDeclareWar && self::isBlocked($color, $location, $otherColor)) return [0, 0, 0];
//
// ORION STO: Your population counts double for being conquered
//
			if (Factions::getStarPeople($otherColor) === 'Orion' && Factions::getAlignment($otherColor) === 'STO')
			{
				$orion = true;
				$sizeOfPopulation *= 2;
			}
		}
//
		$ships = 0;
		foreach (Ships::getAtLocation($location, $color) as $ship)
		{
			if (Ships::isShip($color, $ship)) $ships += $bonus ? 2 : 1;
			else
			{
				$fleet = Ships::getStatus($ship, 'fleet');
				$ships += $bonus ? 2 * Ships::getStatus($ship, 'ships') : Ships::getStatus($ship, 'ships');
//
// (B)omb: For every 2 ships in this fleet increase the ship count by 1 for purposes of conquering or liberating a star
//
				if ($fleet === 'B' && !$orion)
				{
					if (Factions::getAdvancedFleetTactics($color, $fleet) === '2x') $ships += Ships::getStatus($ship, 'ships');
					else $ships += intval(0.5 * Ships::getStatus($ship, 'ships'));
				}
			}
		}
		if (!$ships) throw new BgaVisibleSystemException('No ships at location: ' . $location);
//
		$stars = self::getAtLocation($location, 'star');
		if ($stars)
		{
			if (sizeof($stars) > 1) throw new BgaVisibleSystemException('More than one star at location: ' . $location);
//
			switch (Counters::getStatus($stars[0], 'back'))
			{
				case 'UNINHABITED':
					$SHIPS = 1;
					$population = 1;
					$type = COLONIZE;
					break;
				case 'PRIMITIVE':
					switch (Factions::getAlignment($color))
					{
						case 'STO':
							return [0, 0, 0];
						case 'STS':
							$SHIPS = 1;
							$population = 2;
							$type = SUBJUGATE;
							break;
					}
					break;
				case 'ADVANCED':
					switch (Factions::getAlignment($color))
					{
						case 'STO':
							$SHIPS = 1;
							$population = 3;
							$type = ALLY;
							break;
						case 'STS':
//
// PLEJARS STO: May "ally" with advanced neutrals
//
							if (Factions::getStarPeople($color) === 'Plejars' && Factions::getAlignment($color) === 'STS')
							{
								$SHIPS = 1;
								$population = 3;
								$type = ALLY;
								break;
							}
//
							$SHIPS = 3 + 1;
//
// ORION STS: You conquer stars with only 1 ship (this also applies to a star with the “Defense Grid”)
//
							if (Factions::getStarPeople($color) === 'Orion' && Factions::getAlignment($color) === 'STS') $SHIPS = 1;
//
							$population = 1;
							$type = CONQUER;
							break;
					}
					break;
			}
			return [$ships >= $SHIPS ? $type : 0, $SHIPS, $population];
		}
//
		if ($sizeOfPopulation)
		{
			switch (Factions::getAlignment($color))
			{
				case 'STO': // Liberate
					$SHIPS = 1 + $sizeOfPopulation;
//
					$defenseGrid = Counters::getRelic(DEFENSEGRID);
					if ($defenseGrid && Counters::get($defenseGrid)['location'] === $location) $SHIPS += 8;
//
					$population = $sizeOfPopulation;
					$type = LIBERATE;
					break;
				case 'STS': // Conquer
					$SHIPS = 1 + $sizeOfPopulation;
//
					$defenseGrid = Counters::getRelic(DEFENSEGRID);
					if ($defenseGrid && Counters::get($defenseGrid)['location'] === $location) $SHIPS += 8;
//
//
// ORION STS: You conquer stars with only 1 ship (this also applies to a star with the “Defense Grid”)
//
					if (Factions::getStarPeople($color) === 'Orion' && Factions::getAlignment($color) === 'STS') $SHIPS = 1;
//
					$population = 1;
					$type = CONQUERVS;
					break;
			}
			return [$ships >= $SHIPS ? $type : 0, $SHIPS, $population];
		}
//
		return [0, 0, 0];
	}
}
