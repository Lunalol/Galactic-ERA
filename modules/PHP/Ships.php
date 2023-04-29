<?php

class Ships extends APP_GameClass
{
	static function create($color, $fleet, $location): int
	{
		self::DbQuery("INSERT INTO ships (color,fleet,location) VALUES ('$color','$fleet','$location')");
		return self::DbGetLastId();
	}
	static function destroy(int $id): void
	{
		self::DbQuery("DELETE FROM ships WHERE id = $id");
	}
	static function get(string $color, int $id): array
	{
		return self::getNonEmptyObjectFromDB("SELECT * FROM ships WHERE color = '$color' AND id = $id");
	}
	static function getHomeStar(string $color = null): array
	{
		$sql = "SELECT id, location FROM ships WHERE fleet = 'homeStar'";
		if (!is_null($color)) $sql .= " AND color ='$color'";
		return self::getCollectionFromDB($sql, true);
	}
	static function getAtLocation(string $location, string $color = null, string $fleet = null): array
	{
		$sql = "SELECT id FROM ships WHERE location = '$location'";
		if (!is_null($color)) $sql .= " AND color ='$color'";
		if (!is_null($fleet)) $sql .= " AND fleet ='$fleet'";
		return self::getObjectListFromDB($sql, true);
	}
	static function getAll(string $color = null, string $fleet = null): array
	{
		$sql = "SELECT * FROM ships WHERE true";
		if (!is_null($color)) $sql .= " AND color ='$color'";
		if (!is_null($fleet)) $sql .= " AND fleet ='$fleet'";
		return self::getCollectionFromDB($sql . " ORDER BY color,fleet");
	}
	static function getAllDatas(): array
	{
		return self::getCollectionFromDB("SELECT id,color,fleet,location FROM ships ORDER BY color,fleet");
	}
	static function setLocation(int $id, string $location): void
	{
		self::dbQuery("UPDATE ships SET location = '$location' WHERE id = $id");
	}
	static function setActivation(int $id = null, string $activation = 'no'): void
	{
		if (is_null($id)) self::dbQuery("UPDATE ships SET activation = '$activation'");
		else self::dbQuery("UPDATE ships SET activation = '$activation' WHERE id = $id");
	}
	static function getActivation(int $id): string
	{
		return self::getUniqueValueFromDB("SELECT activation FROM ships WHERE id = $id");
	}
	static function setMP(int $id, int $MP): void
	{
		self::dbQuery("UPDATE ships SET MP = $MP WHERE id = $id");
	}
	static function movement(array $ship)
	{
		$location = $ship['location'];
		$possible = [$location => ['MP' => $ship['MP'], 'path' => [$location]]];
//
		$queue = new SplQueue();
		$queue->enqueue($location);
		while (!$queue->isEmpty())
		{
			$location = $queue->dequeue();
			$MP = $possible[$location]['MP'];
//
			foreach (Sectors::neighbors($location) as $next_location => $terrain)
			{
				if ($terrain === Sectors::NEUTRON) continue;
				$next_MP = $MP - ($terrain === Sectors::NEBULA ? 2 : 1);
				if ($next_MP >= 0)
				{
					if (!array_key_exists($next_location, $possible) || ($possible[$next_location]['MP'] < $next_MP))
					{
						$possible[$next_location] = ['MP' => $next_MP, 'path' => array_merge($possible[$location]['path'], [$next_location])];
						$queue->enqueue($next_location);
					}
				}
			}
		}
		array_shift($possible);
		return $possible;
	}
}
