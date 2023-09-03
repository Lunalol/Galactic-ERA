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
	static function getAtLocation(string $location, string $type = null): array
	{
		if (is_null($type)) return self::getObjectListFromDB("SELECT id FROM counters WHERE location = '$location'", true);
		return self::getObjectListFromDB("SELECT id FROM counters WHERE location = '$location' AND type = '$type'", true);
	}
	static function getStatus(int $id, string $status)
	{
		return self::getUniqueValueFromDB("SELECT JSON_UNQUOTE(status->'$.$status') FROM counters WHERE id = $id");
	}
	static function getAdvancedFleetTactics(string $color): array
	{
		return self::getCollectionFromDB("SELECT location, id FROM counters WHERE color = '$color' AND type IN ('2x', '+3 DP')", true);
	}
	static function getPopulation(string $color): array
	{
		$populations = self::getCollectionFromDB("SELECT location,COUNT(*) AS population FROM counters WHERE color = '$color' AND type = 'populationDisk' GROUP BY location", true);
//
		$homeStar = Ships::getHomeStar($color);
		if ($homeStar)
		{
			if (array_key_exists($homeStar, $populations)) $populations[$homeStar] += 6;
			else $populations[$homeStar] = 6;
		}
//
		return $populations;
	}
	static function reveal(string $color, string $type, int $id)
	{
		self::DbQuery("INSERT INTO revealed VALUES('$color','$type',$id)");
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
	static function gainStar(string $color, string $location): array
	{
		$orion = false;
//
		$populations = Counters::getAtLocation($location, 'populationDisk');
		if ($populations)
		{
			$sizeOfPopulation = sizeof($populations);
			$otherColor = Counters::get($populations[0])['color'];
//
// ORION STO & STS: Your population counts double for being conquered
//
			if (Factions::getStarPeople($otherColor) === 'Orion')
			{
				$orion = true;
				$sizeOfPopulation *= 2;
			}
		}
//
		$ships = 0;
		foreach (Ships::getAtLocation($location, $color) as $ship)
		{
			if (Ships::isShip($color, $ship)) $ships++;
			else
			{
				$fleet = Ships::getStatus($ship, 'fleet');
				$ships += Ships::getStatus($ship, 'ships');
//
// (B)omb: For every 2 ships in this fleet increase the ship count by 1 for purposes of conquering or liberating a star
//
				if ($fleet === 'B' && !$orion)
				{
					if (Factions::getAdvancedFleetTactic($color, $fleet) === '2x') $ships += Ships::getStatus($ship, 'ships');
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
							$SHIPS = INF;
							$population = 0;
							$type = 0;
							break;
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
							$SHIPS = 3 + 1;
//
// ORION STO & STS: You conquer stars with only 1 ship (this also applies to a star with the “Defense Grid”)
//
							if (Factions::getStarPeople($color) === 'Orion') $SHIPS = 1;
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
		if ($populations)
		{
			$sizeOfPopulation = sizeof($populations);
			$otherColor = Counters::get($populations[0])['color'];
//
// ORION STO & STS: Your population counts double for being conquered
//
			if (Factions::getStarPeople($otherColor) === 'Orion') $sizeOfPopulation *= 2;
//
			switch (Factions::getAlignment($color))
			{
				case 'STO': // Liberate
					$SHIPS = 1 + $sizeOfPopulation;
					$population = $sizeOfPopulation;
					$type = LIBERATE;
					break;
				case 'STS': // Conquer
					$SHIPS = 1 + $sizeOfPopulation;
//
// ORION STO & STS: You conquer stars with only 1 ship (this also applies to a star with the “Defense Grid”)
//
					if (Factions::getStarPeople($color) === 'Orion') $SHIPS = 1;
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
