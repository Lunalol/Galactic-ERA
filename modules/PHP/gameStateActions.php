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
	function acScout(string $color, array $ships)
	{
		$this->checkAction('scout');
//
		$player_id = self::getCurrentPlayerId();
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		if (!array_key_exists('scout', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . $this->possible);
		foreach ($ships as $ship) if (!array_key_exists($ship, $this->possible['scout'])) throw new BgaVisibleSystemException('Invalid ship: ' . $ship);
//
		foreach ($ships as $ship) Ships::setActivation($ship, 'done');
//
		if ($ships)
		{
			$ship = Ships::get($color, $ships[0]);
			$counters = Counters::getAtLocation($ship['location'], 'star');
			foreach ($counters as $counter) self::reveal($color, $ship['location'], $counter);
		}
//
		$this->gamestate->nextState('continue');
	}
	function acMove(string $color, string $location, array $ships)
	{
		$this->checkAction('move');
//
		$player_id = self::getCurrentPlayerId();
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		if (!array_key_exists('move', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . $this->possible);
		foreach ($ships as $ship)
		{
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
		$revealed = Counters::listRevealed($color);
		foreach (Ships::getAll($color) as $ship)
		{
			$counters = array_diff(Counters::getAtLocation($ship['location'], 'star'), $revealed);
			foreach ($counters as $counter) self::reveal($color, $ship['location'], $counter);
		}
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
		if (Factions::getTechnology($color, $technology) === 6) throw new BgaVisibleSystemException('Reseach+ Effect not implemented');
		$level = Factions::gainTechnology($color, $technology);
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains <B>${TECHNOLOGY} level ${LEVEL}</B>'), [
			'player_name' => Players::getName(Factions::getPlayer($color)),
			'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology], 'LEVEL' => $level,
			'faction' => ['color' => $color, $technology => $level]
		]);
//* -------------------------------------------------------------------------------------------------------- */
		$counters = Factions::getStatus($color, 'counters');
		unset($counters[array_search('research', $counters)]);
		unset($counters[array_search($technology, $counters)]);
		Factions::setStatus($color, 'counters', array_values($counters));
//
		$this->gamestate->nextState('continue');
	}
	function acGainStar(string $color, string $location): void
	{
		$this->checkAction('gainStar');
//
		$player_id = self::getCurrentPlayerId();
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		if (!array_key_exists('counters', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (!in_array('gainStar', $this->possible['counters'])) throw new BgaVisibleSystemException('Invalid action: ' . 'gainStar');
//
		$ships = Ships::getAtLocation($location, $color);
		if (!$ships) throw new BgaVisibleSystemException('No ships at location: ' . $location);
//
		$relics = Counters::getAtLocation($location, 'relic');
		if ($relics) throw new BgaVisibleSystemException('Relics not implemented');
		$stars = Counters::getAtLocation($location, 'star');
		foreach ($stars as $star)
		{
			$alignment = Factions::getAlignment($color);
			switch (Counters::getStatus($star, 'back'))
			{
				case 'UNINHABITED':
					{
						$SHIPS = 1;
						$population = 1;
					}
					break;
				case 'PRIMITIVE':
					{
						switch ($alignment)
						{
							case 'STO':
								throw new BgaUserException(self::_('STO players cannot take this star'));
								break;
							case 'STS':
								$SHIPS = 1;
								$population = 2;
								break;
						}
					}
					break;
				case 'ADVANCED':
					{
						switch ($alignment)
						{
							case 'STO':
								$SHIPS = 1;
								$population = 3;
								break;
							case 'STS':
								$SHIPS = 4;
								$population = 1;
								break;
						}
					}
					break;
			}
			if (sizeof($ships) < $SHIPS) throw new BgaUserException(self::_('Not enough ships'));
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('removeCounter', clienttranslate('${player_name} gain ${PLANET}'), [
				'player_name' => Players::getName(Factions::getPlayer($color)),
				'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)],
				'counter' => Counters::get($star),
				]
			);
//* -------------------------------------------------------------------------------------------------------- */
			for ($i = 0; $i < $population; $i++)
			{
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'population' => Factions::gainPopulation($color, 1)]]);
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('placeCounter', clienttranslate('${player_name} gains a <B>population</B>'), [
					'player_name' => Players::getName(Factions::getPlayer($color)),
					'counter' => Counters::get(Counters::create($color, 'populationDisk', $location))
				]);
//* -------------------------------------------------------------------------------------------------------- */
			}
			Counters::destroy($star);
		}
//
		$counters = Factions::getStatus($color, 'counters');
		unset($counters[array_search('gainStar', $counters)]);
		Factions::setStatus($color, 'counters', array_values($counters));
//
		$this->gamestate->nextState('continue');
	}
	function acGrowPopulation(string $color, array $locations): void
	{
		$this->checkAction('growPopulation');
//
		$player_id = self::getCurrentPlayerId();
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		if (!array_key_exists('counters', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (!in_array('growPopulation', $this->possible['counters'])) throw new BgaVisibleSystemException('Invalid action: ' . 'gainStar');
//
		foreach ($locations as $location)
		{
			if (!array_key_exists($location, $this->possible['growPopulation'])) throw new BgaVisibleSystemException('Invalid location: ' . $location);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'population' => Factions::gainPopulation($color, 1)]]);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('placeCounter', clienttranslate('${player_name} gains a <B>population</B>'), [
				'player_name' => Players::getName(Factions::getPlayer($color)),
				'counter' => Counters::get(Counters::create($color, 'populationDisk', $location))
			]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		$counters = Factions::getStatus($color, 'counters');
		unset($counters[array_search('growPopulation', $counters)]);
		Factions::setStatus($color, 'counters', array_values($counters));
//
		if (self::argBonusPopulation()['bonus'] > 0) $this->gamestate->nextState('bonusPopulation');
		else $this->gamestate->nextState('continue');
	}
	function acBonusPopulation(string $color, array $locations): void
	{
		$this->checkAction('bonusPopulation');
//
		$player_id = self::getCurrentPlayerId();
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		if (!array_key_exists('bonusPopulation', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
//
		foreach ($locations as $location)
		{
			if (!array_key_exists($location, $this->possible['bonusPopulation'])) throw new BgaVisibleSystemException('Invalid location: ' . $location);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'population' => Factions::gainPopulation($color, 1)]]);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('placeCounter', clienttranslate('${player_name} gains a <B>population</B>'), [
				'player_name' => Players::getName(Factions::getPlayer($color)),
				'counter' => Counters::get(Counters::create($color, 'populationDisk', $location))
			]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		$this->gamestate->nextState('continue');
	}
	function acBuildShips(string $color, array $locations): void
	{
		$this->checkAction('buildShips');
//
		$player_id = self::getCurrentPlayerId();
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		if (!array_key_exists('buildShips', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
//
		foreach ($locations as $location)
		{
			if (!in_array($location, $this->possible['buildShips'])) throw new BgaVisibleSystemException('Invalid location: ' . $location);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'ships' => 16 - sizeof(Ships::getAll($color, 'ship'))]]);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B>'), [
				'player_name' => Players::getName(Factions::getPlayer($color)),
				'ship' => Ships::get($color, Ships::create($color, 'ship', $location))
			]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		$counters = Factions::getStatus($color, 'counters');
		unset($counters[array_search('buildShips', $counters)]);
		Factions::setStatus($color, 'counters', array_values($counters));
//
		$this->gamestate->nextState('continue');
	}
}
