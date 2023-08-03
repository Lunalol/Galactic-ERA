<?php

class Factions extends APP_GameClass
{
//
// Population track
//
	const POPULATION = [6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 34, 36, 38, 40, 42, 44, 46, 48, 50, 52, 54, 57, 60
	];
//
// Build ships
//
	const BUILD = [0, 0, 2, 5, 9, 14, 20, 27, 35];
//
// Technology levels
//
	const TECHNOLOGIES = [
		'Military' => [null, 1, 1 /**/, 2, 3 /**/, 6, 10 /**/], // Combat value (CV) of each ship
		'Spirituality' => [null, 0, 1, 2, 3 /**/, 4 /**/, INF /**/], // Remote view par round
		'Propulsion' => [null, 3, 4, 4 /**/, 5 /**/, 5 /**/, INF], // Ship range
		'Robotics' => [null, 0, +1, +3, +5 /**/, +7 /**/, +10 /**/], // Build ships bonus
		'Genetics' => [null, 0, 1, 2, 3, 4 /**/, 6 /**/], // Grow population bonus
	];
//
// Population requirement for Shipyards;
//
	const SHIPYARDS = [null, 4, 4, 4, 3, 2, 1];
//
	static function create(string $color, int $player_id, int $homeStar): int
	{
		self::DbQuery("INSERT INTO factions (color,player_id,starPeople,alignment,homeStar,atWar,status) VALUES ('$color', $player_id, 'None', 'STO', $homeStar, '[]', '{}')");
		return self::DbGetLastId();
	}
	static function list(): array
	{
		return self::getObjectListFromDB("SELECT color FROM factions ORDER BY `order`", true);
	}
	static function getAllDatas(): array
	{
		return self::getCollectionFromDb("SELECT color,player_id,homeStar,`order`,starPeople,alignment,DP,population,atWar,Military,Spirituality,Propulsion,Robotics,Genetics FROM factions ORDER by `order`");
	}
	static function get(string $color): array
	{
		return self::getNonEmptyObjectFromDB("SELECT color,player_id,homeStar,`order`,starPeople,alignment,DP,population,atWar,Military,Spirituality,Propulsion,Robotics,Genetics FROM factions WHERE color = '$color'");
	}
	static function getNext()
	{
		return self::getUniqueValueFromDB("SELECT color FROM factions WHERE activation = 'no' ORDER BY `order` LIMIT 1");
	}
	static function getActive()
	{
		return self::getUniqueValueFromDB("SELECT color FROM factions WHERE activation = 'yes'");
	}
	static function setActivation(string $color = 'ALL', string $activation = 'no'): void
	{
		if ($color === 'ALL') self::dbQuery("UPDATE factions SET activation = '$activation'");
		else self::dbQuery("UPDATE factions SET activation = '$activation' WHERE color = '$color'");
	}
	static function getActivation(string $color): string
	{
		return self::getUniqueValueFromDB("SELECT activation FROM factions WHERE color = '$color'");
	}
	static function getPlayer(string $color): int
	{
		return intval(self::getUniqueValueFromDB("SELECT player_id FROM factions WHERE color = '$color'"));
	}
	static function getNotAutomas(): string
	{
		return self::getUniqueValueFromDB("SELECT color FROM factions WHERE player_id > 0");
	}
	static function getName(string $color)
	{
		$player_id = self::getPlayer($color);
		if ($player_id < 0) return Automas::getName($color);
		return Players::getName($player_id);
	}
	static function getHomeStar(string $color): int
	{
		return intval(self::getUniqueValueFromDB("SELECT homeStar FROM factions WHERE color = '$color'"));
	}
	static function getAlignment(string $color): string
	{
		return self::getUniqueValueFromDB("SELECT alignment FROM factions WHERE color = '$color'");
	}
	static function switchAlignment(string $color): void
	{
		if (self::getAlignment($color) === 'STO') self::STS($color);
		else self::STO($color);
	}
	static function STO(string $color): void
	{
		self::dbQuery("UPDATE factions SET alignment = 'STO', atWar = '[]' WHERE color = '$color'");
	}
	static function STS(string $color): void
	{
		self::dbQuery("UPDATE factions SET alignment = 'STS', atWar = '[]' WHERE color = '$color'");
	}
	static function getStarPeople(string $color): string
	{
		return self::getUniqueValueFromDB("SELECT starPeople FROM factions WHERE color = '$color'");
	}
	static function setStarPeople(string $color, string $starPeople): void
	{
		self::dbQuery("UPDATE factions SET starPeople = '$starPeople' WHERE color = '$color'");
	}
	static function getByOrder(int $order): string
	{
		return self::getUniqueValueFromDB("SELECT color FROM factions WHERE `order` = $order");
	}
	static function getOrder(string $color): int
	{
		return intval(self::getUniqueValueFromDB("SELECT `order` FROM factions WHERE color = '$color'"));
	}
	static function setOrder(string $color, int $order): void
	{
		self::dbQuery("UPDATE factions SET `order` = $order WHERE color = '$color'");
	}
	static function getDP(string $color): int
	{
		return intval(self::getUniqueValueFromDB("SELECT `DP` FROM factions WHERE color = '$color'"));
	}
	static function gainDP(string $color, int $delta): int
	{
		self::dbQuery("UPDATE factions SET `DP` = `DP` + $delta WHERE color = '$color'");
		return self::getDP($color);
	}
	static function getPopulation(string $color): int
	{
		return intval(self::getUniqueValueFromDB("SELECT `population` FROM factions WHERE color = '$color'"));
	}
	static function gainPopulation(string $color, int $delta): int
	{
		self::dbQuery("UPDATE factions SET `population` = `population` + $delta WHERE color = '$color'");
		return self::getPopulation($color);
	}
	static function getTechnology(string $color, string $technology): int
	{
		return intval(self::getUniqueValueFromDB("SELECT `$technology` FROM factions WHERE color = '$color'"));
	}
	static function setTechnology(string $color, string $technology, int $level): void
	{
		self::dbQuery("UPDATE factions SET `$technology` = $level WHERE color = '$color'");
	}
	static function gainTechnology(string $color, string $technology): int
	{
		self::dbQuery("UPDATE factions SET `$technology` = `$technology` + 1 WHERE color = '$color'");
		return self::getTechnology($color, $technology);
	}
	static function getStatus(string $color, string $status)
	{
		return json_decode(self::getUniqueValueFromDB("SELECT JSON_UNQUOTE(status->'$.$status') FROM factions WHERE color = '$color'"), JSON_OBJECT_AS_ARRAY);
	}
	static function setStatus(string $color, string $status, $value = null): void
	{
		if (is_null($value)) self::dbQuery("UPDATE factions SET status = JSON_REMOVE(status, '$.$status') WHERE color = '$color'");
		else
		{
			$json = self::escapeStringForDB(json_encode($value));
			self::dbQuery("UPDATE factions SET status = JSON_SET(status, '$.$status', '$json') WHERE color = '$color'");
		}
	}
	static function ships(string $color): int
	{
		$ships = self::TECHNOLOGIES['Robotics'][self::getTechnology($color, 'Robotics')];
		foreach (array_unique(array_column(Ships::getAll($color), 'location')) as $location) if (Sectors::terrainFromLocation($location) === Sectors::ASTEROIDS) $ships++;
		foreach (self::BUILD as $population)
		{
			if ($population > self::getPopulation($color)) break;
			$ships++;
		}
		return $ships;
	}
	static function inContact(string $color): array
	{
		$locations = array_unique(array_merge(array_column(Ships::getAll($color), 'location'), array_keys(Counters::getPopulation($color))));
//
		$factions = [];
		foreach (self::list() as $otherColor) if ($otherColor != $color && array_intersect($locations, array_unique(array_merge(array_column(Ships::getAll($otherColor), 'location'), array_keys(Counters::getPopulation($otherColor)))))) $factions[] = $otherColor;
		return $factions;
	}
	static function atPeace(string $color): array
	{
		return self::getObjectListFromDB("SELECT color FROM factions WHERE color <> '$color' AND NOT JSON_CONTAINS(atWar, '\"$color\"')", true);
	}
	static function atWar(string $color): array
	{
		return self::getObjectListFromDB("SELECT color FROM factions WHERE color <> '$color' AND JSON_CONTAINS(atWar, '\"$color\"')", true);
	}
	static function declareWar(string $color, string $on): void
	{
		$atWar = array_merge(self::atWar($color), [$on]);
		self::dBQuery("UPDATE factions SET atWar = '" . json_encode($atWar) . "' WHERE color = '$color'");
	}
	static function declarePeace(string $color, string $on): void
	{
		$atWar = array_diff(self::atWar($color), [$on]);
		self::dBQuery("UPDATE factions SET atWar = '" . json_encode($atWar) . "' WHERE color = '$color'");
	}
}
