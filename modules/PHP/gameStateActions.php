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
	function acIndividualChoice(string $color, string $technology): void
	{
		$this->checkAction('individualChoice');
//
		$player_id = self::getCurrentPlayerId();
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		if (!array_key_exists('counters', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (!array_key_exists($technology, array_intersect($this->TECHNOLOGIES, $this->possible['counters']))) throw new BgaVisibleSystemException('Invalid technology: ' . $technology);
//
		$level = 2;
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains <B>${TECHNOLOGY} level ${LEVEL}</B>'), [
			'player_name' => Players::getName(Factions::getPlayer($color)),
			'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology], 'LEVEL' => $level,
			'faction' => ['color' => $color, $technology => $level]
		]);
//* -------------------------------------------------------------------------------------------------------- */
		Factions::setActivation($color, 'done');
//
		$this->gamestate->nextState('nextPlayer');
	}
	function acCreateFleet(string $color, string $fleet, array $ships)
	{
		$this->checkAction('createFleet');
//
		$player_id = self::getCurrentPlayerId();
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		if (!array_key_exists('fleets', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (!array_key_exists($fleet, $this->possible['fleets'])) throw new BgaVisibleSystemException('Invalid Fleet: ' . $fleet);
//
		if ($ships)
		{
			$ship = Ships::get($color, $ships[0]);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('placeShip', clienttranslate('${player_name} moves ${N} ship(s) to fleet ${GPS}'), [
				'player_name' => Players::getName(Factions::getPlayer($color)),
				'ship' => Ships::get($color, Ships::create($color, 'fleet', $ship['location'], ['fleet' => $fleet, 'ships' => sizeof($ships)])),
				'GPS' => $ship['location'],
				'N' => sizeof($ships)
				]
			);
//* -------------------------------------------------------------------------------------------------------- */
			foreach ($ships as $ship)
			{
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('removeShip', '', ['ship' => Ships::get($color, $ship)]);
//* -------------------------------------------------------------------------------------------------------- */
				Ships::destroy($ship);
			}
		}
//
		$this->gamestate->nextState('continue');
	}
	function acDone(string $color): void
	{
		$this->checkAction('done');
//
		$player_id = self::getCurrentPlayerId();
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		foreach (Ships::getAll($color) as $ship)
		{
			$MP = Factions::TECHNOLOGIES['Propulsion'][Factions::getTechnology($color, 'Propulsion')];
			if (Sectors::terrainFromLocation($ship['location']) === Sectors::NEBULA) $MP += 2;
			if (!is_null(Ships::getStatus($ship['id'], 'fleet'))) $MP += 1;
			Ships::setMP($ship['id'], $ship['fleet'] === 'homeStar' ? 0 : $MP);
		}
//
		$this->gamestate->nextState('next');
	}
	function acRemoteViewing(string $color, int $counter)
	{
		$this->checkAction('removeViewing');
//
		$player_id = self::getCurrentPlayerId();
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
		if (!array_key_exists('view', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (!$this->possible['view']) throw new BgaVisibleSystemException('No move view: ' . $this->possible['view']);
//
		self::reveal($color, Counters::get($counter)['location'], $counter);
		Factions::setStatus($color, 'view', $this->possible['view'] - 1);
//
		self::DbQuery("DELETE FROM `undo` WHERE color = '$color'");
//
		$this->gamestate->nextState('continue');
	}
	function acScout(string $color, array $ships)
	{
		$this->checkAction('scout');
//
		$player_id = self::getCurrentPlayerId();
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		if (!array_key_exists('scout', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		foreach ($ships as $ship) if (!array_key_exists($ship, $this->possible['scout'])) throw new BgaVisibleSystemException('Invalid ship: ' . $ship);
//
		foreach ($ships as $ship) Ships::setActivation($ship, 'done');
//
		if ($ships)
		{
			$ship = Ships::get($color, $ships[0]);
			foreach (array_diff(array_merge(Counters::getAtLocation($ship['location'], 'star'), Counters::getAtLocation($ship['location'], 'relic')), Counters::listRevealed($color)) as $counter) self::reveal($color, $ship['location'], $counter);
//
			self::DbQuery("DELETE FROM `undo` WHERE color = '$color'");
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
		if (!array_key_exists('move', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
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
			$this->notifyAllPlayers('msg', clienttranslate('${player_name} moves ${N} ship(s) to ${PLANET} ${GPS}'), [
				'player_name' => Players::getName(Factions::getPlayer($color)),
				'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon],
				'GPS' => $location, 'N' => sizeof($ships)]);
//* -------------------------------------------------------------------------------------------------------- */
		}
		else
		{
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('msg', clienttranslate('${player_name} moves ${N} ship(s) ${GPS}'), [
				'player_name' => Players::getName(Factions::getPlayer($color)),
				'GPS' => $location, 'N' => sizeof($ships)]);
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
			$json = self::escapeStringForDB(json_encode(Ships::get($color, $ship)));
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
//
		self::DbQuery("DELETE FROM `undo` WHERE color = '$color'");
		foreach (Ships::getAll($color) as $ship) foreach (array_diff(array_merge(Counters::getAtLocation($ship['location'], 'star'), Counters::getAtLocation($ship['location'], 'relic')), Counters::listRevealed($color)) as $counter) self::reveal($color, $ship['location'], $counter);
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
		Factions::setStatus($color, 'counters', array_values($counters));
//
		$this->gamestate->setPlayerNonMultiactive($player_id, 'next');
	}
	function acResearch(string $color, string $technology, bool $automa = false): void
	{
		if (!$automa)
		{
			$this->checkAction('research');
//
			$player_id = self::getCurrentPlayerId();
			if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
			if (!array_key_exists('counters', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
			if (!in_array('research', $this->possible['counters'])) throw new BgaVisibleSystemException('Invalid action: ' . 'research');
			if (!array_key_exists($technology, array_intersect($this->TECHNOLOGIES, $this->possible['counters']))) throw new BgaVisibleSystemException('Invalid technology: ' . $technology);
		}
//
		if (Factions::getTechnology($color, $technology) === 6) throw new BgaVisibleSystemException('Reseach+ Effect not implemented');
		$level = Factions::gainTechnology($color, $technology);
//
// GREYS SPECIAL STO & STS: When you research a technology at level 1 you increase it to level 3.
//
		if (Factions::getStarPeople($color) === 'Greys' && Factions::getTechnology($color, $technology) === 1)
		{
			$level = Factions::gainTechnology($color, $technology);
		}
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
		Factions::setStatus($color, 'used', array_values(array_merge(Factions::getStatus($color, 'used'), ['research', $technology])));
//
		if (!$automa) $this->gamestate->nextState('continue');
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
			$this->notifyAllPlayers('removeCounter', clienttranslate('${player_name} gains ${PLANET} ${GPS}'), [
				'player_name' => Players::getName(Factions::getPlayer($color)),
				'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)],
				'GPS' => $location, 'counter' => Counters::get($star)]);
//* -------------------------------------------------------------------------------------------------------- */
			for ($i = 0; $i < $population; $i++)
			{
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'population' => Factions::gainPopulation($color, 1)]]);
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('placeCounter', clienttranslate('${PLANET} gains a <B>population</B> ${GPS}'), [
					'PLANET' => [
						'log' => '<span style = "color:#' . $color . ';font-weight:bold;">${PLANET}</span>',
						'i18n' => ['PLANET'], 'args' => ['PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)]]
					],
					'GPS' => $location, 'counter' => Counters::get(Counters::create($color, 'populationDisk', $location))]);
//* -------------------------------------------------------------------------------------------------------- */
			}
			Counters::destroy($star);
		}
//
		$relics = Counters::getAtLocation($location, 'relic');
		foreach ($relics as $relic)
		{
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('msg', clienttranslate('<B>${RELIC}</B> is found ${GPS}'), [
				'i18n' => ['RELIC'], 'RELIC' => $this->RELICS[Counters::getStatus($relic, 'back')],
				'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
			switch (Counters::getStatus($relic, 'back'))
			{
				case 0: // Ancient Pyramids
					throw new BgaVisibleSystemException('Relic not implemented');
					break;
				case 1: // Ancient Technology: Genetics
					$technology = 'Genetics';
					if (Factions::getTechnology($color, $technology) === 6) throw new BgaVisibleSystemException('Reseach+ Effect not implemented');
					$level = Factions::gainTechnology($color, $technology);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains <B>${TECHNOLOGY} level ${LEVEL}</B>'), [
						'player_name' => Players::getName(Factions::getPlayer($color)),
						'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology], 'LEVEL' => $level,
						'faction' => ['color' => $color, $technology => $level]
					]);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
					Counters::destroy($relic);
					break;
				case 2: // Ancient Technology: Military
					$technology = 'Military';
					if (Factions::getTechnology($color, $technology) === 6) throw new BgaVisibleSystemException('Reseach+ Effect not implemented');
					$level = Factions::gainTechnology($color, $technology);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains <B>${TECHNOLOGY} level ${LEVEL}</B>'), [
						'player_name' => Players::getName(Factions::getPlayer($color)),
						'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology], 'LEVEL' => $level,
						'faction' => ['color' => $color, $technology => $level]
					]);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
					Counters::destroy($relic);
					break;
				case 3: // Ancient Technology: Propulsion
					$technology = 'Propulsion';
					if (Factions::getTechnology($color, $technology) === 6) throw new BgaVisibleSystemException('Reseach+ Effect not implemented');
					$level = Factions::gainTechnology($color, $technology);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains <B>${TECHNOLOGY} level ${LEVEL}</B>'), [
						'player_name' => Players::getName(Factions::getPlayer($color)),
						'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology], 'LEVEL' => $level,
						'faction' => ['color' => $color, $technology => $level]
					]);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
					Counters::destroy($relic);
					break;
				case 4: // Ancient Technology: Robotics
					$technology = 'Robotics';
					if (Factions::getTechnology($color, $technology) === 6) throw new BgaVisibleSystemException('Reseach+ Effect not implemented');
					$level = Factions::gainTechnology($color, $technology);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains <B>${TECHNOLOGY} level ${LEVEL}</B>'), [
						'player_name' => Players::getName(Factions::getPlayer($color)),
						'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology], 'LEVEL' => $level,
						'faction' => ['color' => $color, $technology => $level]
					]);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
					Counters::destroy($relic);
					break;
				case 5: // Ancient Technology: Spirituality
					$technology = 'Spirituality';
					if (Factions::getTechnology($color, $technology) === 6) throw new BgaVisibleSystemException('Reseach+ Effect not implemented');
					$level = Factions::gainTechnology($color, $technology);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains <B>${TECHNOLOGY} level ${LEVEL}</B>'), [
						'player_name' => Players::getName(Factions::getPlayer($color)),
						'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology], 'LEVEL' => $level,
						'faction' => ['color' => $color, $technology => $level]
					]);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
					Counters::destroy($relic);
					break;
				case 6: // Buried Ships
					for ($i = 0; $i < 3; $i++)
					{
//* -------------------------------------------------------------------------------------------------------- */
						$this->notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B> at ${PLANET}'), [
							'player_name' => Players::getName(Factions::getPlayer($color)),
							'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)],
							'ship' => Ships::get($color, Ships::create($color, 'ship', $location))
						]);
//* -------------------------------------------------------------------------------------------------------- */
						$this->notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'ships' => 16 - sizeof(Ships::getAll($color, 'ship'))]]);
//* -------------------------------------------------------------------------------------------------------- */
					}
					$this->notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
					Counters::destroy($relic);
					break;
				case 7: // Planetary Death Ray
					throw new BgaVisibleSystemException('Relic not implemented');
					break;
				case 8: // Defense Grid
					throw new BgaVisibleSystemException('Relic not implemented');
					break;
				case 9: // Super-Stargate
					throw new BgaVisibleSystemException('Relic not implemented');
					break;
			}
		}
//
		$counters = Factions::getStatus($color, 'counters');
		unset($counters[array_search('gainStar', $counters)]);
		Factions::setStatus($color, 'counters', array_values($counters));
		Factions::setStatus($color, 'used', array_values(array_merge(Factions::getStatus($color, 'used'), ['gainStar'])));
//
// Scoring
//
		$galacticStory = self::getGameStateValue('galacticStory');
		$era = [1 => 'First', 2 => 'First', 3 => 'Second', 4 => 'Second', 5 => 'Second', 6 => 'Second', 7 => 'Third', 8 => 'Third'][self::getGameStateValue('round')];
//
// RIVALRY First: All players score 1 DP for every Gain Star action they do in this era.
//
		if ($era === 'First' && $galacticStory === RIVALRY)
		{
			Factions::gainDP($color, 1);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', _('${player_name} gains ${DP} DP(s)'), ['DP' => 1,
				'player_name' => Players::getName(Factions::getPlayer($color)),
				'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		$this->gamestate->nextState('continue');
	}
	function acGrowPopulation(string $color, array $locations, array $locationsBonus): void
	{
		$this->checkAction('growPopulation');
//
		$player_id = self::getCurrentPlayerId();
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		if (!array_key_exists('counters', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (!in_array('growPopulation', $this->possible['counters'])) throw new BgaVisibleSystemException('Invalid action: ' . 'growPopulation');
		if (sizeof($locationsBonus) > $this->possible['bonusPopulation']) throw new BgaVisibleSystemException('Invalid bonus population: ' . sizeof($locationsBonus));
//
		foreach ($locations as $location)
		{
			if (!array_key_exists($location, $this->possible['growPopulation'])) throw new BgaVisibleSystemException('Invalid location: ' . $location);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('placeCounter', clienttranslate('${PLANET} gains a <B>population</B> ${GPS}'), [
				'PLANET' => [
					'log' => '<span style = "color:#' . $color . ';font-weight:bold;">${PLANET}</span>',
					'i18n' => ['PLANET'], 'args' => ['PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)]]
				], 'GPS' => $location, 'counter' => Counters::get(Counters::create($color, 'populationDisk', $location))]);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'population' => Factions::gainPopulation($color, 1)]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
		foreach ($locationsBonus as $location)
		{
			if (!array_key_exists($location, $this->possible['growPopulation'])) throw new BgaVisibleSystemException('Invalid location: ' . $location);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('placeCounter', clienttranslate('${PLANET} gains a <B>population</B> ${GPS}'), [
				'PLANET' => [
					'log' => '<span style = "color:#' . $color . ';font-weight:bold;">${PLANET}</span>',
					'i18n' => ['PLANET'], 'args' => ['PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)]]
				], 'GPS' => $location, 'counter' => Counters::get(Counters::create($color, 'populationDisk', $location))]);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'population' => Factions::gainPopulation($color, 1)]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		$counters = Factions::getStatus($color, 'counters');
		unset($counters[array_search('growPopulation', $counters)]);
		Factions::setStatus($color, 'counters', array_values($counters));
		Factions::setStatus($color, 'used', array_values(array_merge(Factions::getStatus($color, 'used'), ['growPopulation'])));
//
// Scoring
//
		$galacticStory = self::getGameStateValue('galacticStory');
		$era = [1 => 'First', 2 => 'First', 3 => 'Second', 4 => 'Second', 5 => 'Second', 6 => 'Second', 7 => 'Third', 8 => 'Third'][self::getGameStateValue('round')];
//
// MIGRATIONS First: All players score 3 DP for every Grow Population action they do in this era.
// Only Grow Population actions that generated at least one additional population are counted.
//
		if ($era === 'First' && $galacticStory === MIGRATIONS && (sizeof($locations) + sizeof($locationsBonus) > 0))
		{
			Factions::gainDP($color, 3);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', _('${player_name} gains ${DP} DP(s)'), ['DP' => 3,
				'player_name' => Players::getName(Factions::getPlayer($color)),
				'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
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
		if (!in_array('buildShips', $this->possible['counters'])) throw new BgaVisibleSystemException('Invalid action: ' . 'buildShips');
//
		foreach ($locations as $location)
		{
			if (!in_array($location, $this->possible['buildShips'])) throw new BgaVisibleSystemException('Invalid location: ' . $location);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('placeShip', clienttranslate('${player_name} gains an <B>additional ship</B> at ${PLANET} ${GPS}'), [
				'player_name' => Players::getName(Factions::getPlayer($color)),
				'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)],
				'GPS' => $location, 'ship' => Ships::get($color, Ships::create($color, 'ship', $location))]);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'ships' => 16 - sizeof(Ships::getAll($color, 'ship'))]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		$counters = Factions::getStatus($color, 'counters');
		unset($counters[array_search('buildShips', $counters)]);
		Factions::setStatus($color, 'counters', array_values($counters));
		Factions::setStatus($color, 'used', array_values(array_merge(Factions::getStatus($color, 'used'), ['buildShips'])));
//
// Scoring
//
		$galacticStory = self::getGameStateValue('galacticStory');
		$era = [1 => 'First', 2 => 'First', 3 => 'Second', 4 => 'Second', 5 => 'Second', 6 => 'Second', 7 => 'Third', 8 => 'Third'][self::getGameStateValue('round')];
//
// WARS First: All players score 2 DP for every Build Ships action they do in this era.
//
		if ($era === 'First' && $galacticStory === WARS)
		{
			Factions::gainDP($color, 2);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', _('${player_name} gains ${DP} DP(s) '), ['DP' => 2,
				'player_name' => Players::getName(Factions::getPlayer($color)),
				'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		$this->gamestate->nextState('continue');
	}
	function acTrade(string $from, string $to, string $technology)
	{
		$this->checkAction('trade');
//
		$player_id = self::getCurrentPlayerId();
		if ($player_id != Factions::getPlayer($from)) throw new BgaVisibleSystemException('Invalid Faction: ' . $from);
//
		$fromStatus = Factions::getStatus($from, 'trade');
//
		if ($technology === 'accept')
		{
			if (!array_key_exists($to, $fromStatus)) throw new BgaUserException(self::_('You must choose what you are getting'));
			$toStatus = Factions::getStatus($to, 'trade');
			if (!array_key_exists($from, $toStatus)) throw new BgaUserException(self::_('Other player must choose what you are teaching'));
//
			foreach ([$from => $fromStatus[$to], $to => $toStatus[$from]] as $color => $technology)
			{
				if (Factions::getTechnology($color, $technology) === 6) throw new BgaVisibleSystemException('Reseach+ Effect not implemented');
				$level = Factions::gainTechnology($color, $technology);
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains <B>${TECHNOLOGY} level ${LEVEL}</B>'), [
					'player_name' => Players::getName(Factions::getPlayer($color)),
					'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology], 'LEVEL' => $level,
					'faction' => ['color' => $color, $technology => $level]
				]);
//* -------------------------------------------------------------------------------------------------------- */
			}
			$this->gamestate->setPlayerNonMultiactive(Factions::getPlayer($from), 'next');
			$this->gamestate->setPlayerNonMultiactive(Factions::getPlayer($to), 'next');
			if (sizeof($this->gamestate->getActivePlayerList()) < 2) $this->gamestate->setAllPlayersNonMultiactive('next');
			else $this->gamestate->nextState('continue');
			return;
		}
		if (!$to)
		{
			$this->gamestate->setPlayerNonMultiactive($player_id, 'next');
			if (sizeof($this->gamestate->getActivePlayerList()) < 2) $this->gamestate->setAllPlayersNonMultiactive('next');
			else $this->gamestate->nextState('continue');
			return;
		}
		if ($technology === 'reset') unset($fromStatus[$to]);
		else if (array_key_exists($to, $fromStatus))
		{
			$old = $fromStatus[$to];
			unset($fromStatus[$to]);
			if ($technology !== $old) $fromStatus[$to] = $technology;
		}
		else $fromStatus[$to] = $technology;
//
		Factions::setStatus($from, 'trade', $fromStatus);
//
		$this->gamestate->nextState('continue');
	}
}
