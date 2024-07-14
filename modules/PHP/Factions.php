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
	const BUILD = [0 + 6, 0 + 6, 2 + 6, 5 + 6, 9 + 6, 14 + 6, 20 + 6, 27 + 6, 35 + 6];
//
// Technology levels
//
	const TECHNOLOGIES = [
		'Military' => [null, 1, 1 /**/, 2, 3 /**/, 6, 10 /**/], // Combat value (CV) of each ship
		'Spirituality' => [null, 0, 1, 2, 3 /**/, 4 /**/, -1 /**/], // Remote view par round
		'Propulsion' => [null, 3, 4, 4 /**/, 5 /**/, 5 /**/, 100], // Ship range
		'Robotics' => [null, 0, +1, +3, +5 /**/, +7 /**/, +10 /**/], // Build ships bonus
		'Genetics' => [null, 0, 1, 2, 3, 4 /**/, 6 /**/], // Grow population bonus
	];
//
// Population requirement for Shipyards
//
	const SHIPYARDS = [null, 4, 4, 4, 3, 2, 1];
//
// Additional growth action cost
//
	const ADDITIONAL = [null, 3, 3, 3, 3, 2, 1];
//
// Teleport population
//
	const TELEPORT = [null, 0, 0, 0, 0, 1, 3];
//
	static $table = null;
	static function create(string $color, int $player_id, int $homeStar): int
	{
		$json = self::$table->escapeStringForDB(json_encode(['A' => null, 'B' => null, 'C' => null, 'D' => null, 'E' => null], JSON_FORCE_OBJECT));
		self::$table->DbQuery("INSERT INTO factions (color,player_id,starPeople,alignment,homeStar,atWar,advancedFleetTactics,status) VALUES ('$color', $player_id, 'None', 'STO', $homeStar, '[]','$json', '{}')");
		return self::$table->DbGetLastId();
	}
	static function list(bool $automas = true): array
	{
		if ($automas) return self::$table->getObjectListFromDB("SELECT color FROM factions ORDER BY `order`", true);
		return self::$table->getObjectListFromDB("SELECT color FROM factions WHERE player_id > 0 ORDER BY `order`", true);
	}
	static function getAllDatas(): array
	{
		return self::$table->getCollectionFromDb("SELECT color,player_id,homeStar,`order`,starPeople,alignment,DP,population,atWar,advancedFleetTactics,Military,Spirituality,Propulsion,Robotics,Genetics,emergencyReserve FROM factions ORDER by `order`");
	}
	static function get(string $color): array
	{
		return self::$table->getNonEmptyObjectFromDB("SELECT color,player_id,homeStar,`order`,starPeople,alignment,DP,population,atWar,advancedFleetTactics,Military,Spirituality,Propulsion,Robotics,Genetics,emergencyReserve FROM factions WHERE color = '$color'");
	}
	static function getNext()
	{
		return self::$table->getUniqueValueFromDB("SELECT color FROM factions WHERE activation = 'no' ORDER BY `order` LIMIT 1");
	}
	static function getActive()
	{
		return self::$table->getUniqueValueFromDB("SELECT color FROM factions WHERE activation = 'yes' LIMIT 1");
	}
	static function setActivation(string $color = 'ALL', string $activation = 'no'): void
	{
		if ($color === 'ALL') self::$table->dbQuery("UPDATE factions SET activation = '$activation'");
		else self::$table->dbQuery("UPDATE factions SET activation = '$activation' WHERE color = '$color'");
	}
	static function getActivation(string $color): string
	{
		return self::$table->getUniqueValueFromDB("SELECT activation FROM factions WHERE color = '$color'");
	}
	static function getPlayer(string $color): int
	{
		return intval(self::$table->getUniqueValueFromDB("SELECT player_id FROM factions WHERE color = '$color'"));
	}
	static function getColor(int $player_id): string
	{
		return self::$table->getUniqueValueFromDB("SELECT color FROM factions WHERE player_id = $player_id");
	}
	static function getAutoma(int $automa = AUTOMA)
	{
		return self::$table->getUniqueValueFromDB("SELECT color FROM factions WHERE player_id = $automa");
	}
	static function getNotAutomas(string $color = ''): string
	{
		if ($color) return self::$table->getUniqueValueFromDB("SELECT color FROM factions WHERE player_id > 0 AND color <> '$color'");
		return self::$table->getUniqueValueFromDB("SELECT color FROM factions WHERE player_id > 0");
	}
	static function getName(string $color)
	{
		$player_id = self::getPlayer($color);
		if ($player_id <= 0) return Automas::getName($color);
		return Players::getName($player_id);
	}
	static function getHomeStar(string $color): int
	{
		return intval(self::$table->getUniqueValueFromDB("SELECT homeStar FROM factions WHERE color = '$color'"));
	}
	static function getAlignment(string $color): string
	{
		return self::$table->getUniqueValueFromDB("SELECT alignment FROM factions WHERE color = '$color'");
	}
	static function switchAlignment(string $color): void
	{
		if (self::getAlignment($color) === 'STO') self::STS($color);
		else self::STO($color);
	}
	static function STO(string $color): void
	{
		self::$table->dbQuery("UPDATE factions SET alignment = 'STO', atWar = '[]' WHERE color = '$color'");
	}
	static function STS(string $color): void
	{
		self::$table->dbQuery("UPDATE factions SET alignment = 'STS', atWar = '[]' WHERE color = '$color'");
	}
	static function getStarPeople(string $color): string
	{
		return self::$table->getUniqueValueFromDB("SELECT starPeople FROM factions WHERE color = '$color'");
	}
	static function setStarPeople(string $color, string $starPeople): void
	{
		self::$table->dbQuery("UPDATE factions SET starPeople = '$starPeople' WHERE color = '$color'");
	}
	static function getByOrder(int $order): string
	{
		return self::$table->getUniqueValueFromDB("SELECT color FROM factions WHERE `order` = $order");
	}
	static function getOrder(string $color): int
	{
		return intval(self::$table->getUniqueValueFromDB("SELECT `order` FROM factions WHERE color = '$color'"));
	}
	static function setOrder(string $color, int $order): void
	{
		self::$table->dbQuery("UPDATE factions SET `order` = $order WHERE color = '$color'");
	}
	static function getDP(string $color): int
	{
		return intval(self::$table->getUniqueValueFromDB("SELECT `DP` FROM factions WHERE color = '$color'"));
	}
	static function gainDP(string $color, int $delta): int
	{
		self::$table->dbQuery("UPDATE factions SET `DP` = `DP` + $delta WHERE color = '$color'");
		return self::getDP($color);
	}
	static function getPopulation(string $color): int
	{
		return intval(self::$table->getUniqueValueFromDB("SELECT `population` FROM factions WHERE color = '$color'"));
	}
	static function gainPopulation(string $color, int $delta): int
	{
		self::$table->dbQuery("UPDATE factions SET `population` = `population` + $delta WHERE color = '$color'");
		return self::getPopulation($color);
	}
	static function getTechnology(string $color, string $technology): int
	{
		return intval(self::$table->getUniqueValueFromDB("SELECT `$technology` FROM factions WHERE color = '$color'"));
	}
	static function gainTechnology(string $color, string $technology): int
	{
		self::$table->dbQuery("UPDATE factions SET `$technology` = `$technology` + 1 WHERE color = '$color'");
		return self::getTechnology($color, $technology);
	}
	static function getEmergencyReserve($color): bool
	{
		return boolval(self::$table->getUniqueValueFromDB("SELECT emergencyReserve FROM factions WHERE color = '$color'", true));
	}
	static function useEmergencyReserve($color)
	{
		self::$table->DbQuery("UPDATE factions SET emergencyReserve = false WHERE color = '$color'");
	}
	static function emergencyReserve(): array
	{
		return self::$table->getCollectionFromDB("SELECT color, player_id FROM factions WHERE status->'$.emergencyReserve'", true);
	}
	static function advancedFleetTactics(): array
	{
		return self::$table->getCollectionFromDB("SELECT color, player_id FROM factions WHERE status->'$.advancedFleetTactics'", true);
	}
	static function getAllAdvancedFleetTactics(string $color): array
	{
		return json_decode(self::$table->getUniqueValueFromDB("SELECT advancedFleetTactics FROM factions WHERE color = '$color'"), JSON_OBJECT_AS_ARRAY);
	}
	static function getAdvancedFleetTactics(string $color, string $fleet)
	{
		return self::$table->getUniqueValueFromDB("SELECT JSON_UNQUOTE(advancedFleetTactics->'$.$fleet') FROM factions WHERE color = '$color'");
	}
	static function setAdvancedFleetTactics(string $color, string $fleet, string $tactics): void
	{
		self::$table->DbQuery("UPDATE factions SET advancedFleetTactics = JSON_SET(advancedFleetTactics, '$.$fleet', '$tactics') WHERE color = '$color'");
	}
	static function getStatus(string $color, string $status)
	{
		return json_decode(self::$table->getUniqueValueFromDB("SELECT JSON_UNQUOTE(status->'$.$status') FROM factions WHERE color = '$color'"), JSON_OBJECT_AS_ARRAY);
	}
	static function setStatus(string $color, string $status, $value = null): void
	{
		if (is_null($value)) self::$table->dbQuery("UPDATE factions SET status = JSON_REMOVE(status, '$.$status') WHERE color = '$color'");
		else
		{
			$json = self::$table->escapeStringForDB(json_encode($value));
			self::$table->dbQuery("UPDATE factions SET status = JSON_SET(status, '$.$status', '$json') WHERE color = '$color'");
		}
	}
	static function clearStatus(string $color): void
	{
		self::$table->dbQuery("UPDATE factions SET status = '{}' WHERE color = '$color'");
	}
	static function ships(string $color): int
	{
		if (Factions::getAutoma(SLAVERS) === $color) $population = 6 + array_sum(Counters::getPopulations($color, true)) + Factions::getDP($color);
		else $population = max(6, array_sum(Counters::getPopulations($color, true)));
//
		$ships = self::TECHNOLOGIES['Robotics'][self::getTechnology($color, 'Robotics')];
//
// COSMIC MAYANS: Asteroid systems do not give you extra ships when building ships
//
		if (Factions::getStarPeople($color) !== 'Mayans') foreach (array_unique(array_column(Ships::getAll($color), 'location')) as $location) if (Sectors::terrainFromLocation($location) === Sectors::ASTEROIDS) $ships++;
//
		foreach (self::BUILD as $step)
		{
			if ($step > $population) break;
			$ships++;
		}
		return $ships;
	}
	static function inContact(string $color): array
	{
		$locations = array_unique(array_merge(array_column(Ships::getAll($color), 'location'), array_keys(Counters::getPopulations($color))));
//
		$factions = [];
		foreach (self::list() as $otherColor)
		{
			if ($otherColor !== $color)
			{
				$inContact = array_diff(array_intersect($locations, array_unique(array_merge(array_column(Ships::getAll($otherColor), 'location'), array_keys(Counters::getPopulations($otherColor))))), ['stock']);
				if ($inContact) $factions[] = $otherColor;
			}
		}
//
		return $factions;
	}
	static function atPeace(string $color): array
	{
		return self::$table->getObjectListFromDB("SELECT color FROM factions WHERE color <> '$color' AND NOT JSON_CONTAINS(atWar, '\"$color\"')", true);
	}
	static function atWar(string $color): array
	{
		return self::$table->getObjectListFromDB("SELECT color FROM factions WHERE color <> '$color' AND JSON_CONTAINS(atWar, '\"$color\"')", true);
	}
	static function declareWar(string $color, string $on): void
	{
		$atWar = array_unique(array_merge(self::atWar($color), [$on]));
		self::$table->dBQuery("UPDATE factions SET atWar = '" . json_encode(array_values($atWar)) . "' WHERE color = '$color'");
	}
	static function declarePeace(string $color, string $on = ''): void
	{
		$atWar = $on ? array_diff(self::atWar($color), [$on]) : [];
		self::$table->dBQuery("UPDATE factions SET atWar = '" . json_encode(array_values($atWar)) . "' WHERE color = '$color'");
	}
}
