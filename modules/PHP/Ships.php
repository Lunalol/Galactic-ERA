<?php

class Ships extends APP_GameClass
{
	static function create($color, $fleet, $sector, $hexagon = '+0+0+0'): int
	{
		$location = sprintf('%1d:%6s', $sector, $hexagon);
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
	static function getAll(string $color = null): array
	{
		if (is_null($color)) return self::getCollectionFromDB("SELECT * FROM ships ORDER BY color,fleet");
		return self::getCollectionFromDB("SELECT * FROM ships WHERE color = '$color' ORDER BY fleet");
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
	static function getActivation(string $color): string
	{
		return self::getUniqueValueFromDB("SELECT activation FROM ships WHERE id = id = $id");
	}
	static function setMP(int $id, int $MP): void
	{
		self::dbQuery("UPDATE ships SET MP = $MP WHERE id = $id");
	}
	static function movement(array $ship)
	{
		$sectors = Sectors::getAllDatas();
//
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
