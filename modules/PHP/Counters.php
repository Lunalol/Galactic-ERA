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
	static function getPopulation(string $color): array
	{
		$populations = self::getCollectionFromDB("SELECT location,COUNT(*) AS population FROM counters WHERE color = '$color' AND type = 'populationDisk' GROUP BY location", true);
		foreach (Ships::getHomeStar($color) as $location)
		{
			if (array_key_exists($location, $populations)) $populations[$location] += 6;
			else $populations[$location] = 6;
		}
		return $populations;
	}
	static function reveal(string $color, string $type, int $id)
	{
		self::DbQuery("INSERT INTO revealed VALUES('$color','$type',$id)");
		return self::DbGetLastId();
	}
	static function isRevealed(string $color, int $id, string $type = null)
	{
		if (is_null($type)) return boolval(self::getUniqueValueFromDB("SELECT EXISTS (SELECT * FROM revealed WHERE color = '$color' AND type IN ('star','relic') AND id = $id)"));
		return boolval(self::getUniqueValueFromDB("SELECT EXISTS (SELECT * FROM revealed WHERE color = '$color' AND type = '$type' AND id = $id)"));
	}
	static function listRevealed(string $color, string $type = null): array
	{
		if (is_null($type)) return self::getObjectListFromDB("SELECT id FROM revealed WHERE color = '$color' AND type IN ('star','relic')", true);
		return self::getObjectListFromDB("SELECT id FROM revealed WHERE color = '$color' AND type = '$type'", true);
	}
	static function gainStar(string $color, string $location): array
	{
		$alignment = Factions::getAlignment($color);
//
		$ships = Ships::getAtLocation($location, $color);
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
					break;
				case 'PRIMITIVE':
					switch ($alignment)
					{
						case 'STO':
							$SHIPS = INF;
							$population = 0;
							break;
						case 'STS':
							$SHIPS = 1;
							$population = 2;
							break;
					}
					break;
				case 'ADVANCED':
					switch ($alignment)
					{
						case 'STO':
							$SHIPS = 1;
							$population = 3;
							break;
						case 'STS':
							$SHIPS = 3 + 1;
							$population = 1;
							break;
					}
					break;
			}
		}
		else
		{
			switch ($alignment)
			{
				case 'STO': // Liberate
					$SHIPS = 1 + sizeof(Counters::getAtLocation($location, 'populationDisk'));
					$population = sizeof(Counters::getAtLocation($location, 'populationDisk'));
					break;
				case 'STS': // Conquer
					$SHIPS = 1 + sizeof(Counters::getAtLocation($location, 'populationDisk'));
					$population = 1;
					break;
			}
		}
//
		return [sizeof($ships) >= $SHIPS, $population];
	}
}
