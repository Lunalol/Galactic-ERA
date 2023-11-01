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
	static function get(int $id): array
	{
		return self::getNonEmptyObjectFromDB("SELECT id,color,fleet,location,activation,MP FROM ships WHERE id = $id");
	}
	static function getHomeStar(string $color = null)
	{
		if (is_null($color)) return self::getCollectionFromDB("SELECT id, location FROM ships WHERE fleet = 'homeStar'", true);
		return self::getUniqueValueFromDB("SELECT id FROM ships WHERE color = '$color' AND fleet = 'homeStar'");
	}
	static function getHomeStarLocation(string $color)
	{
		return self::getUniqueValueFromDB("SELECT location FROM ships WHERE color = '$color' AND fleet = 'homeStar'");
	}
	static function isShip(string $color, int $id): bool
	{
		return boolval(self::getUniqueValueFromDB("SELECT fleet = 'ship' FROM ships WHERE color = '$color' AND id = $id"));
	}
	static function getFleet(string $color, string $fleet)
	{
		return self::getUniqueValueFromDB("SELECT id FROM ships WHERE color = '$color' AND status->'$.fleet' = '$fleet'");
	}
	static function getAtLocation(string $location, string $color = null, string $fleet = null): array
	{
		$sql = "SELECT id FROM ships WHERE location = '$location'";
		if (!is_null($color)) $sql .= " AND color ='$color'";
		if (!is_null($fleet)) $sql .= " AND fleet ='$fleet'";
		else $sql .= " AND fleet <> 'homeStar'";
		return self::getObjectListFromDB($sql . " ORDER BY color,fleet", true);
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
				. " AND attacker.fleet <> 'homeStar' AND defender.fleet <> 'homeStar'"
				. " AND JSON_CONTAINS(atWar, CAST(defender.color AS json)) ", true);
	}
	static function getConflictFactions(string $color, string $location): array
	{
		return self::getObjectListFromDB("SELECT DISTINCT defender.color FROM ships AS attacker"
				. " JOIN ships AS defender USING (location)"
				. " JOIN factions ON attacker.color = factions.color"
				. " WHERE location = '$location' AND attacker.color = '$color' AND attacker.color <> defender.color"
				. " AND attacker.fleet <> 'homeStar' AND defender.fleet <> 'homeStar'"
				. " AND JSON_CONTAINS(atWar, CAST(defender.color AS json)) ORDER by `order`", true);
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
//		self::DbQuery("INSERT INTO revealed VALUES('$color','$type',$id)");
//		return self::DbGetLastId();
	}
	static function movement(array $ship)
	{
		$propulsion = Factions::getTechnology($ship['color'], 'Propulsion');
//
// Stargate 1
//
		$ownStars = Counters::getPopulations($ship['color'], true);
//
// Stargate 2
//
		if ($propulsion === 5)
		{
			$stars = [];
// Neutral stars
			foreach (Counters::getAllDatas() as $counter) if ($counter['type'] === 'star') $stars[] = $counter['location'];
// Own stars
			foreach (array_keys(Counters::getPopulations($ship['color'])) as $location) $stars[] = $location;
// Non in war with faction stars
			foreach (Factions::atPeace($ship['color']) as $color) foreach (array_keys(Counters::getPopulations($color)) as $location) $stars[] = $location;
// Neutron stars
			foreach (Sectors::stars(true) as $location) $stars[] = $location;
//
			foreach ($stars as $index => $location) if (Counters::isBlocked($ship['color'], $location)) unset($stars[$index]);
		}
//
//	Super-Stargate
//
		$superStargate = Counters::getRelic(SUPERSTARGATE);
		if ($superStargate && Counters::getStatus($superStargate, 'owner') === $ship['color']) $superStargate = Counters::get($superStargate)['location'];
		$superStargateStars = Counters::getPopulations($ship['color'], $propulsion < 5);
//
		$possible = [$ship['location'] => ['MP' => intval($ship['MP']), 'from' => null]];
//
		$locations = [$ship['location'] => intval($ship['MP'])];
		while ($locations)
		{
			$MP = max($locations);
			$location = array_search($MP, $locations);
			unset($locations[$location]);
//
			$neighbors = Sectors::neighbors($location);
//
// Stargate 1
//
			if ($propulsion === 3 || $propulsion === 4) if (array_key_exists($location, $ownStars) && $ownStars[$location] >= 3) foreach ($ownStars as $next_location => $population) if ($next_location !== $location && $population >= 3) $neighbors[$next_location] = ['location' => $next_location, 'terrain' => Sectors::PLANET];
//
// Stargate 2
//
			if ($propulsion === 5) if (in_array($location, $stars)) foreach ($stars as $next_location) if ($next_location !== $location) $neighbors[$next_location] = ['location' => $next_location, 'terrain' => Sectors::PLANET];
//
//	Super-Stargate
//
			if ($superStargate)
			{
				foreach (array_keys($superStargateStars) as $next_location)
				{
					if ($superStargate === $location && $next_location !== $location) $neighbors[$next_location] = ['location' => $next_location, 'terrain' => Sectors::PLANET];
					if ($superStargate !== $location && $next_location === $location) $neighbors[$superStargate] = ['location' => $superStargate, 'terrain' => Sectors::PLANET];
				}
			}
//
			foreach ($neighbors as $type => ['location' => $next_location, 'terrain' => $terrain])
			{
				if ($terrain === Sectors::NEUTRON && $propulsion < 5) continue;
//
				$next_MP = $MP - ($terrain === Sectors::NEBULA ? 2 : 1);
				if ($type === 'WORMHOLE' && Counters::isBlocked($ship['color'], $next_location)) $next_MP = 0;
//
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
	static function retreatLocations($color, $location)
	{
		$propulsion = Factions::getTechnology($color, 'Propulsion');
//
		$hostiles = [];
		foreach (Factions::atWar($color) as $enemy) $hostiles = array_merge($hostiles, array_column(Ships::getAll($enemy, 'fleet'), 'location'), array_column(Ships::getAll($enemy, 'ship'), 'location'));
//
		$min = INF;
		$distances = [$location => 0];
		$locations = [];
//
		$queue = new SplQueue();
		$queue->enqueue($location);
		while (!$queue->isEmpty())
		{
			$location = $queue->dequeue();
			$distance = $distances[$location] + 1;
//
			$neighbors = Sectors::neighbors($location, false);
			foreach ($neighbors as ['location' => $next_location, 'terrain' => $terrain])
			{
				if (!array_key_exists($next_location, $distances) || $distance < $distances[$next_location])
				{
					$distances[$next_location] = $distance;
					if (!($terrain === Sectors::NEUTRON && $propulsion < 5) && !in_array($next_location, $hostiles))
					{
						$locations[$distance][] = $next_location;
						$min = min($min, $distance);
					}
					if ($distance < $min) $queue->enqueue($next_location);
				}
			}
		}
		return $locations[$min];
	}
	static function CV(string $color, string $location, bool $assault = false): array
	{
		$military = Factions::TECHNOLOGIES['Military'][Factions::getTechnology($color, 'Military')];
//
		$result = ['total' => 0, 'fleets' => [], 'ships' => ['CV' => 0, 'ships' => 0]];
		foreach (self::getAtLocation($location, $color) as $shipID)
		{
			$ship = self::get($shipID);
			switch ($ship['fleet'])
			{
//
				case 'ship':
//
					$CV = $military;
//
					$result['ships']['ships']++;
					$result['ships']['CV'] += $CV;
					$result['total'] += $CV;
					break;
//
				case 'fleet':
//
					$fleet = self::getStatus($shipID, 'fleet');
//
					$CV = $military * self::getStatus($shipID, 'ships');
//
// (A)ssault: Whenever this fleet is involved in combat, add 1 CV per ship in this fleet
//
					if ($fleet === 'A')
					{
						$CV += 1 * self::getStatus($shipID, 'ships');
						if (Factions::getAdvancedFleetTactics($color, $fleet) === '2x') $CV += 1 * self::getStatus($shipID, 'ships');
					}
//
// (C)ounterassault: Add 2 CV per ship in this fleet if there is an “A” fleet on the opposing side in combat
//
					if ($fleet === 'C' && $assault)
					{
						$CV += 2 * self::getStatus($shipID, 'ships');
						if (Factions::getAdvancedFleetTactics($color, $fleet) === '2x') $CV += 2 * self::getStatus($shipID, 'ships');
					}
//
					$result['fleets'][Ships::getStatus($shipID, 'fleet')] = ['CV' => $CV, 'ships' => Ships::getStatus($shipID, 'ships')];
					$result['total'] += $CV;
					break;
			}
		}
//
		return $result;
	}
}
