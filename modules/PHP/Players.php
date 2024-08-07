<?php

class Players extends APP_GameClass
{
	static $table = null;
	static function create(array $players): void
	{
		$values = [];
		foreach ($players as $ID => $player) $values[] = "('$ID','$player[player_color]','$player[player_canal]','" . addslashes($player['player_name']) . "','" . addslashes($player['player_avatar']) . "')";
		self::$table->DbQuery("INSERT INTO player (player_id, player_color, player_canal, player_name, player_avatar) VALUES " . implode(', ', $values));
	}
	static function getAllDatas(): array
	{
		return self::$table->getCollectionFromDb("SELECT player_id id, player_score score, skipDM FROM player");
	}
	static function getAdmin(): int
	{
		return intval(self::$table->getUniqueValueFromDB("SELECT global_value FROM global WHERE global_id = 5"));
	}
	static function getName(int $player_id): string
	{
		return self::$table->getUniqueValueFromDB("SELECT player_name FROM player WHERE player_id = $player_id");
	}
	static function getSkipDM(int $player_id): bool
	{
		return boolval(self::$table->getUniqueValueFromDB("SELECT skipDM FROM player WHERE player_id = $player_id"));
	}
	static function setSkipDM(int $player_id, int $skipDM): void
	{
		self::$table->DbQuery("UPDATE player SET skipDM = $skipDM WHERE player_id = $player_id");
	}
}
