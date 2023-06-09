<?php

class Ships extends APP_GameClass
{
	const FLEETS = ['A', 'B', 'C', 'D', 'E'];
//
	static function create($color, $fleet, $location, array $status = []): int
	{
		$json = self::escapeStringForDB(json_encode($status, JSON_FORCE_OBJECT));
		self::DbQuery("INSERT INTO ships (color,fleet,location,activation,status) VALUES ('$color','$fleet','$location','no','$json')");
		return self::DbGetLastId();
	}
	static function destroy(int $id): void
	{
		self::DbQuery("DELETE FROM ships WHERE id = $id");
	}
	static function get(string $color, int $id): array
	{
		return self::getNonEmptyObjectFromDB("SELECT id,color,fleet,location,activation,MP FROM ships WHERE color = '$color' AND id = $id");
	}
	static function getHomeStar(string $color = null): array
	{
		$sql = "SELECT id,location FROM ships WHERE fleet = 'homeStar'";
		if (!is_null($color)) $sql .= " AND color ='$color'";
		return self::getCollectionFromDB($sql, true);
	}
	static function isShip(string $color, int $id): bool
	{
		return boolval(self::getUniqueValueFromDB("SELECT fleet = 'ship' FROM ships WHERE color = '$color' AND id = $id"));
	}
	static function getFleet(string $color, string $fleet): int
	{
		return self::getUniqueValueFromDB("SELECT id FROM ships WHERE color = '$color' AND status->'$.fleet' = '$fleet'");
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
	static function getConflictLocation(string $color): array
	{
		return self::getObjectListFromDB("SELECT DISTINCT location FROM ships AS attacker"
				. " JOIN ships AS defender USING (location)"
				. " JOIN factions ON attacker.color = factions.color"
				. " WHERE location <> 'stock' AND attacker.color = '$color' AND attacker.color <> defender.color"
				. " AND JSON_CONTAINS(atWar, CAST(defender.color AS json)) ", true);
	}
	static function getConflictFactions(string $color, string $location): array
	{
		return self::getObjectListFromDB("SELECT DISTINCT defender.color FROM ships AS attacker"
				. " JOIN ships AS defender USING (location)"
				. " JOIN factions ON attacker.color = factions.color"
				. " WHERE location = '$location' AND attacker.color = '$color' AND attacker.color <> defender.color"
				. " AND JSON_CONTAINS(atWar, CAST(defender.color AS json)) ", true);
	}
	static function getAllDatas($player_id): array
	{
		$ships = self::getCollectionFromDB("SELECT id,color,fleet,location,activation FROM ships ORDER BY color,fleet");
		foreach ($ships as $id => $ship)
		{
			if ($player_id == Factions::getPlayer($ship['color']))
			{
				$fleet = self::getStatus($id, 'fleet');
				if ($fleet)
				{
					$ships[$id]['fleet'] = self::getStatus($id, 'fleet');
					$ships[$id]['ships'] = self::getStatus($id, 'ships');
				}
			}
//			else $ships[$id]['id'] = 0;
		}
		return /* array_values */($ships);
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
	static function getStatus(int $id, string $status)
	{
		return self::getUniqueValueFromDB("SELECT JSON_UNQUOTE(status->'$.$status') FROM ships WHERE id = $id");
	}
	static function setStatus(int $id, string $status, $value = null): void
	{
		if (is_null($value)) self::dbQuery("UPDATE ships SET status = JSON_REMOVE(status,'$.$status') WHERE id = $id");
		else self::dbQuery("UPDATE ships SET status = JSON_SET(status,'$.$status','$value') WHERE id = $id");
	}
	static function reveal(string $color, string $type, int $id)
	{
		self::DbQuery("INSERT INTO revealed VALUES('$color','$type',$id)");
		return self::DbGetLastId();
	}
	static function movement(array $ship)
	{
		$possible = [$ship['location'] => ['MP' => $ship['MP'], 'from' => null]];
//
		$locations = [$ship['location'] => $ship['MP']];
		while ($locations)
		{
			$MP = max($locations);
			$location = array_search($MP, $locations);
			unset($locations[$location]);
//
			$neighbors = Sectors::neighbors($location);
			foreach ($neighbors as ['location' => $next_location, 'terrain' => $terrain])
			{
				if ($terrain === Sectors::NEUTRON) continue;
				$next_MP = $MP - ($terrain === Sectors::NEBULA ? 2 : 1);
				if ($next_MP >= 0)
				{
					if (!array_key_exists($next_location, $possible) || ($possible[$next_location]['MP'] < $next_MP))
					{
						$possible[$next_location] = ['MP' => $next_MP, 'from' => $location];
						$locations[$next_location] = $next_MP;
					}
				}
			}
		}
		return $possible;
	}
	static function CV(string $color, string $location): int
	{
		$military = Factions::TECHNOLOGIES['Military'][Factions::getTechnology($color, 'Military')];
//
		$CV = 0;
		foreach (self::getAtLocation($location, $color) as $shipID)
		{
			$ship = self::get($color, $shipID);
			switch ($ship['fleet'])
			{
				case 'ship':
					$CV += $military;
					break;
				case 'fleet':
					$CV += $military * self::getStatus($shipID, 'ships');
//
// (A)ssault: Whenever this fleet is involved in combat, add 1 CV per ship in this fleet
//
					if (self::getStatus($shipID, 'fleet') === 'A') $CV += self::getStatus($shipID, 'ships');
//
// (C)ounterassault: Add 2 CV per ship in this fleet if there is an “A” fleet on the opposing side in combat
//
					if (self::getStatus($shipID, 'fleet') === 'C')
					{
						$CV += 2 * self::getStatus($shipID, 'ships');
					}
//
					break;
				case 'homeStar':
					break;
			}
		}
		return $CV;
	}
}
