<?php

class Counters extends APP_GameClass
{
//
	static function create($color, $type, $subType, $sector, $hexagon = '+0+0+0', array $status = []): int
	{
		$location = sprintf('%1d:%6s', $sector, $hexagon);
		$json = self::escapeStringForDB(json_encode($status, JSON_FORCE_OBJECT));
		self::DbQuery("INSERT INTO counters (color, type, subType, location, status) VALUES ('$color', '$type', '$subType', '$location', '$json')");
		return self::DbGetLastId();
	}
	static function getAllDatas(): array
	{
		return self::getCollectionFromDB("SELECT id, color, type, subType, location FROM counters ORDER BY color, type, subType");
	}
	static function destroy(int $id): void
	{
		self::DbQuery("DELETE FROM counters WHERE id = $id");
	}
	static function get(string $color, int $id): array
	{
		return self::getNonEmptyObjectFromDB("SELECT * FROM counters WHERE color = '$color' AND id = $id");
	}
	static function getAtLocation(string $location): array
	{
		return self::getObjectListFromDB("SELECT id FROM counters WHERE location = '$location'", true);
	}
}
