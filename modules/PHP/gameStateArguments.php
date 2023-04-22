<?php

/**
 *
 * @author Lunalol
 */
trait gameStateArguments
{
	function argStarPeople()
	{
		$private = [];
		foreach (Factions::list() as $color)
		{
			$player_id = Factions::getPlayer($color);
			$private[$player_id]['color'] = $color;
			$private[$player_id]['starPeople'] = Factions::getStatus($color, 'starPeople');
			$private[$player_id]['alignment'] = false;
		}
//
		return ['_private' => $private];
	}
	function argAlignment()
	{
		$private = [];
		foreach (Factions::list() as $color)
		{
			$player_id = Factions::getPlayer($color);
			$private[$player_id]['color'] = $color;
			$private[$player_id]['starPeople'] = [Factions::getStarPeople($color)];
			$private[$player_id]['alignment'] = Factions::getStatus($color, 'alignment');
		}
//
		return ['_private' => $private];
	}
	function argMovement()
	{
		$color = Factions::getActive();
		$player_id = Factions::getPlayer($color);
//
		$this->possible = [];
		foreach (Ships::getAll($color) as $ship)
		{
			if ($ship['fleet'] === 'ship') $this->possible['move'][$ship['id']] = Ships::movement($ship);
//
			$counter = Counters::getAtLocation($ship['location']);
			if ($counter) $this->possible['scout'][$ship['id']] = $counter;
		}
//
		return ['_private' => [$player_id => $this->possible], 'active' => $color];
	}
	function argSelectCounters()
	{
		$private = [];
		foreach (Factions::list() as $color)
		{
			$player_id = Factions::getPlayer($color);
			$private[$player_id]['color'] = $color;
			$private[$player_id]['counters'] = Factions::getStatus($color, 'counters');
			$private[$player_id]['N'] = 2;
		}
//
		return ['_private' => $private];
	}
	function argResolveGrowthActions()
	{
		$private = [];
		foreach (Factions::list() as $color)
		{
			$player_id = Factions::getPlayer($color);
			$private[$player_id]['color'] = $color;
			$private[$player_id]['counters'] = Factions::getStatus($color, 'counters');
		}
//
		$color = Factions::getActive();
		$player_id = Factions::getPlayer($color);
		$this->possible = $private[$player_id];
//
		return ['_private' => $private, 'active' => $color];
	}
}
