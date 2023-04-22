<?php

/**
 *
 * @author Lunalol
 */
trait gameStateActions
{
	function acStarPeopleChoice(string $color, string $starPeople): void
	{
		$this->checkAction('starPeopleChoice');
//
		$player_id = self::getCurrentPlayerId();
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
		if (!in_array($starPeople, array_keys($this->STARPEOPLES))) throw new BgaVisibleSystemException('Invalid Star People: ' . $starPeople);
//
		Factions::setStatus($color, 'starPeople', [$starPeople]);
//
		$this->gamestate->setPlayerNonMultiactive($player_id, 'next');
	}
	function acAlignmentChoice(string $color, bool $STS): void
	{
		$this->checkAction('alignmentChoice');
//
		$player_id = self::getCurrentPlayerId();
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		Factions::setStatus($color, 'alignment', $STS);
//
		$this->gamestate->setPlayerNonMultiactive($player_id, 'next');
	}
	function acMove(string $color, string $location, array $ships)
	{
		$this->checkAction('move');
//
		$player_id = self::getCurrentPlayerId();
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		foreach ($ships as $ship)
		{
			if (!array_key_exists('move', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . $this->possible);
			if (!array_key_exists($ship, $this->possible['move'])) throw new BgaVisibleSystemException('Invalid ship: ' . $ship);
			if (!array_key_exists($location, $this->possible['move'][$ship])) throw new BgaVisibleSystemException('Invalid location: ' . $location);
		}
//
		$sector = Sectors::get($location[0]);
		$hexagon = substr($location, 2);
		if (array_key_exists($hexagon, $this->SECTORS[$sector]))
		{
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('msg', clienttranslate('${player_name} moves ${N} ship(s) to ${PLANET}'), [
				'player_name' => Players::getName(Factions::getPlayer($color)),
				'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon],
				'N' => sizeof($ships),
				]
			);
//* -------------------------------------------------------------------------------------------------------- */
		}
		else
		{
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('msg', clienttranslate('${player_name} moves ${N} ship(s)'), [
				'player_name' => Players::getName(Factions::getPlayer($color)),
				'N' => sizeof($ships),
				]
			);
//* -------------------------------------------------------------------------------------------------------- */
		}
		foreach ($this->possible['move'][$ships[0]][$location]['path'] as $next_location)
		{
			$this->notifyAllPlayers('moveShips', '', ['ships' => $ships, 'location' => $next_location, 'old' => $location]);
			$location = $next_location;
		}
		$undoID = self::getUniqueValueFromDB("SELECT COALESCE(MAX(undoID), 0) FROM `undo` WHERE color = '$color'") + 1;
//
		foreach ($ships as $ship)
		{
			$json = json_encode(Ships::get($color, $ship));
			self::DbQuery("INSERT INTO `undo` VALUES ($undoID,$ship,'$color','move','$json')");
//
			$MP = $this->possible['move'][$ship][$location]['MP'];
			Ships::setMP($ship, $MP);
			Ships::setActivation($ship, $MP == 0 ? 'done' : 'yes');
			Ships::setLocation($ship, $location);
		}
//
		$this->gamestate->nextState('continue');
	}
	function acUndo($color)
	{
		$this->checkAction('undo');
//
		$player_id = self::getCurrentPlayerId();
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		$undoID = self::getUniqueValueFromDB("SELECT COALESCE(MAX(undoID), 0) FROM `undo` WHERE color = '$color'");
		if ($undoID)
		{
			$toUndo = self::getCollectionFromDB("SELECT id, status FROM `undo` WHERE color = '$color' AND undoID = $undoID AND type = 'move'", true);
			foreach ($toUndo as $ship => $json)
			{
				$status = json_decode($json, JSON_OBJECT_AS_ARRAY);
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('removeShip', '', ['ship' => Ships::get($color, $ship)]);
//* -------------------------------------------------------------------------------------------------------- */
				self::DbQuery("UPDATE ships SET activation = '$status[activation]',fleet = '$status[fleet]',location = '$status[location]', MP = $status[MP] WHERE id = $ship");
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('placeShip', '', ['ship' => Ships::get($color, $ship)]);
//* -------------------------------------------------------------------------------------------------------- */
			}
			self::DbQuery("DELETE FROM `undo` WHERE color = '$color' AND undoID = $undoID");
		}
//
		$this->gamestate->nextState('continue');
	}
	function acPass(string $color): void
	{
		$this->checkAction('pass');
//
		$player_id = self::getCurrentPlayerId();
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		Factions::setActivation($color, 'done');
		self::DbQuery("DELETE FROM `undo` WHERE color = '$color'");
//
		$this->gamestate->nextState('next');
	}
	function acSelectCounters(string $color, array $counters): void
	{
		$this->checkAction('selectCounters');
//
		$player_id = self::getCurrentPlayerId();
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		Factions::setStatus($color, 'counters', $counters);
//
		$this->gamestate->setPlayerNonMultiactive($player_id, 'next');
	}
	function acResearch(string $color, string $technology): void
	{
		$this->checkAction('research');
//
		$player_id = self::getCurrentPlayerId();
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		if (!array_key_exists('counters', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (!in_array('research', $this->possible['counters'])) throw new BgaVisibleSystemException('Invalid action: ' . 'research');
		if (!array_key_exists($technology, array_intersect($this->TECHNOLOGIES, $this->possible['counters']))) throw new BgaVisibleSystemException('Invalid technology: ' . $technology);
//
		$counters = Factions::getStatus($color, 'counters');
		unset($counters[array_search('research', $counters)]);
		unset($counters[array_search($technology, $counters)]);
		Factions::setStatus($color, 'counters', array_values($counters));
//
		$this->gamestate->nextState('continue');
	}
}
