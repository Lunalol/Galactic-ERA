<?php

class Boards extends APP_GameClass
{
	const DELTAS = [
		0 => ['q' => 0, 'r' => 0, 's' => 0],
		1 => ['q' => -4, 'r' => +9, 's' => -5],
	];
//
	static function create(int $tile, int $location)
	{
		self::DbQuery("INSERT INTO board (tile, location) VALUES ($tile, $location)");
		return self::DbGetLastId();
	}
	static function neighbors($hex)
	{
		$neighbors = [];
		for ($i = 0; $i < 6; $i++)
		{
			$neighbor = hex_neighbor($hex, $i);
			if (hex_length($neighbor) > 4)
			{
				foreach (Boards::DELTAS as $delta)
				{
					$next = hex_sub($neighbor, $delta);
					if (hex_length($next) <= 4) $neighbors[] = $next;
				}
			}
			else $neighbors[] = $neighbor;
		}
		return $neighbors;
	}
}
