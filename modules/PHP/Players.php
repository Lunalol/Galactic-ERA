<?php

class Players extends APP_GameClass
{
	static function create(array $players): void
	{
		$values = [];
		foreach ($players as $ID => $player) $values[] = "('$ID','$player[player_color]','$player[player_canal]','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "')";
		self::DbQuery("INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES " . implode(', ', $values));
	}
	static function getAllDatas(): array
	{
		return self::getCollectionFromDb("SELECT player_id id, player_score score FROM player");
	}
	static function getAdmin(): int
	{
		return self::getUniqueValueFromDB("SELECT global_value FROM global WHERE global_id = 5");
	}
	static function getName(int $player_id): string
	{
		return self::getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = $player_id");
	}
}
