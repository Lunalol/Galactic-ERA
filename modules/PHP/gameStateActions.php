<?php

/**
 *
 * @author Lunalol
 */
trait gameStateActions
{
	function acStarPeopleChoice(string $color, string $starPeople): void
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('starPeopleChoice');
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
		if (!in_array($starPeople, array_keys($this->STARPEOPLES))) throw new BgaVisibleSystemException('Invalid Star People: ' . $starPeople);
//
		Factions::setStatus($color, 'starPeople', [$starPeople]);
//
		$this->gamestate->setPlayerNonMultiactive($player_id, 'next');
	}
	function acAlignmentChoice(string $color, bool $STS): void
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('alignmentChoice');
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		Factions::setStatus($color, 'alignment', $STS);
//
		$this->gamestate->setPlayerNonMultiactive($player_id, 'next');
	}
	function acIndividualChoice(string $color, string $technology): void
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('individualChoice');
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
		if (!array_key_exists('counters', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (!array_key_exists($technology, array_intersect($this->TECHNOLOGIES, $this->possible['counters']))) throw new BgaVisibleSystemException('Invalid technology: ' . $technology);
//
		$level = 2;
		Factions::setTechnology($color, $technology, $level);
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains <B>${TECHNOLOGY} (${LEVEL})</B>'), [
			'player_name' => Factions::getName($color),
			'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology], 'LEVEL' => $level,
			'faction' => ['color' => $color, $technology => $level]
		]);
//* -------------------------------------------------------------------------------------------------------- */
		Factions::setActivation($color, 'done');
//
		$this->gamestate->nextState('nextPlayer');
	}
	function acShipsToFleet(string $color, string $Fleet, array $ships)
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('shipsToFleet');
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
		if (!array_key_exists('fleets', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (!array_key_exists($Fleet, $this->possible['fleets'])) throw new BgaVisibleSystemException('Invalid Fleet: ' . $Fleet);
//
		if ($ships)
		{
			$fleetID = Ships::getFleet($color, $Fleet);
			Ships::setStatus($fleetID, 'ships', intval(Ships::getStatus($fleetID, 'ships')) + sizeof($ships));
//
			$location = Ships::get($color, $ships[0])['location'];
			$fleet = Ships::get($color, $fleetID);
			if ($fleet['location'] === 'stock')
			{
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('removeShip', '', ['ship' => Ships::get($color, $fleetID)]);
//* -------------------------------------------------------------------------------------------------------- */
				$fleet['location'] = $location;
				Ships::setLocation($fleetID, $fleet['location']);
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('placeShip', clienttranslate('A new fleet is created ${GPS}'), ['GPS' => $location, 'ship' => $fleet]);
			}
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('msg', clienttranslate('${N} ship(s) join fleet ${GPS}'), ['GPS' => $location, 'N' => sizeof($ships)]);
//* -------------------------------------------------------------------------------------------------------- */
			foreach ($ships as $shipID)
			{
				$ship = Ships::get($color, $shipID);
				if (!$ship) throw new BgaVisibleSystemException('Invalid ship: ' . $shipID);
				if ($ship['location'] !== $location) throw new BgaVisibleSystemException('Invalid location: ' . $location);
				if ($ship['fleet'] !== 'ship') throw new BgaVisibleSystemException('Not a ship');
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('removeShip', '', ['ship' => $ship]);
//* -------------------------------------------------------------------------------------------------------- */
				Ships::destroy($shipID);
			}
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyPlayer($player_id, 'revealShip', '', ['ship' => ['id' => $fleetID, 'fleet' => $Fleet, 'ships' => Ships::getStatus($fleetID, 'ships')]]);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'ships' => 16 - sizeof(Ships::getAll($color, 'ship'))]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		$this->gamestate->nextState('continue');
	}
	function acFleetToShips(string $color, string $Fleet, int $ships)
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('fleetToShips');
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
		if (!array_key_exists('fleets', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (!array_key_exists($Fleet, $this->possible['fleets'])) throw new BgaVisibleSystemException('Invalid Fleet: ' . $Fleet);
//
		if ($ships)
		{

			$fleetID = Ships::getFleet($color, $Fleet);
			$fleet = Ships::get($color, $fleetID);
			$location = $fleet['location'];
//
			if (intval(Ships::getStatus($fleetID, 'ships')) < $ships) throw new BgaVisibleSystemException('Not enough ships: ' . $ships);
//
			Ships::setStatus($fleetID, 'ships', intval(Ships::getStatus($fleetID, 'ships')) - $ships);
//
			if (intval(Ships::getStatus($fleetID, 'ships')) === 0)
			{
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('removeShip', clienttranslate('A fleet is removed ${GPS}'), ['GPS' => $location, 'ship' => $fleet]);
//* -------------------------------------------------------------------------------------------------------- */
				$fleet['location'] = 'stock';
				Ships::setLocation($fleetID, $fleet['location']);
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('placeShip', '', ['ship' => $fleet]);
//* -------------------------------------------------------------------------------------------------------- */
			}
//
			for ($i = 0; $i < $ships; $i++) $this->notifyAllPlayers('placeShip', '', ['ship' => Ships::get($color, Ships::create($color, 'ship', $location))]);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('msg', clienttranslate('${N} ship(s) leave fleet ${GPS}'), ['GPS' => $location, 'N' => $ships]);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyPlayer($player_id, 'revealShip', '', ['ship' => ['id' => $fleetID, 'fleet' => $Fleet, 'ships' => Ships::getStatus($fleetID, 'ships')]]);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'ships' => 16 - sizeof(Ships::getAll($color, 'ship'))]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		$this->gamestate->nextState('continue');
	}
	function acFleetToFleet(string $color, string $from, string $to, int $ships)
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('fleetToFleet');
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
		if (!array_key_exists('fleets', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (!array_key_exists($from, $this->possible['fleets'])) throw new BgaVisibleSystemException('Invalid Fleet: ' . $from);
		if (!array_key_exists($to, $this->possible['fleets'])) throw new BgaVisibleSystemException('Invalid Fleet: ' . $to);
//
		if ($ships)
		{
			$fromID = Ships::getFleet($color, $from);
			$fromFleet = Ships::get($color, $fromID);
			$toID = Ships::getFleet($color, $to);
			$toFleet = Ships::get($color, $toID);
//
			if (intval(Ships::getStatus($fromID, 'ships')) < $ships) throw new BgaVisibleSystemException('Not enough ships: ' . $ships);
//
			$location = $fromFleet['location'];
//
			if ($toFleet['location'] === 'stock')
			{
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('removeShip', '', ['ship' => $toFleet]);
//* -------------------------------------------------------------------------------------------------------- */
				$toFleet['location'] = $location;
				Ships::setLocation($toID, $toFleet['location']);
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('placeShip', clienttranslate('A new fleet is created ${GPS}'), ['GPS' => $location, 'ship' => $toFleet]);
//* -------------------------------------------------------------------------------------------------------- */
			}
//* -------------------------------------------------------------------------------------------------------- */
			if ($fromFleet['location'] !== $toFleet['location']) throw new BgaVisibleSystemException('Invalid location: ' . $fromFleet['location'] . ' <> ' . $toFleet['location']);
//
			Ships::setStatus($fromID, 'ships', intval(Ships::getStatus($fromID, 'ships')) - $ships);
			Ships::setStatus($toID, 'ships', intval(Ships::getStatus($toID, 'ships')) + $ships);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('msg', clienttranslate('Some ship(s) swap fleet ${GPS}'), ['GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
			if (intval(Ships::getStatus($fromID, 'ships')) === 0)
			{
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('removeShip', clienttranslate('A fleet is removed ${GPS}'), ['GPS' => $location, 'ship' => $fromFleet]);
//* -------------------------------------------------------------------------------------------------------- */
				$fromFleet['location'] = 'stock';
				Ships::setLocation($fromID, $fromFleet['location']);
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('placeShip', '', ['ship' => $fromFleet]);
//* -------------------------------------------------------------------------------------------------------- */
			}
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyPlayer($player_id, 'revealShip', '', ['ship' => ['id' => $fromID, 'fleet' => $from, 'ships' => Ships::getStatus($fromID, 'ships')]]);
			$this->notifyPlayer($player_id, 'revealShip', '', ['ship' => ['id' => $toID, 'fleet' => $to, 'ships' => Ships::getStatus($toID, 'ships')]]);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'ships' => 16 - sizeof(Ships::getAll($color, 'ship'))]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		$this->gamestate->nextState('continue');
	}
	function acSwapFleets(string $color, array $fleets)
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('swapFleets');
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
		if (!array_key_exists('fleets', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (sizeof($fleets) !== 2) throw new BgaVisibleSystemException('Invalid fleets: ' . json_encode($fleets));
		if (!array_key_exists($fleets[0], $this->possible['fleets'])) throw new BgaVisibleSystemException('Invalid Fleet: ' . $fleets[0]);
		if (!array_key_exists($fleets[1], $this->possible['fleets'])) throw new BgaVisibleSystemException('Invalid Fleet: ' . $fleets[1]);
//
		$first = Ships::getFleet($color, $fleets[0]);
		$second = Ships::getFleet($color, $fleets[1]);
		$this->possible['stars'][] = 'stock';
//
		if (!in_array(Ships::get($color, $first)['location'], $this->possible['stars'])) throw new BgaVisibleSystemException('Invalid location: ' . Ships::get($color, $first)['location']);
		if (!in_array(Ships::get($color, $second)['location'], $this->possible['stars'])) throw new BgaVisibleSystemException('Invalid location: ' . Ships::get($color, $second)['location']);
//
		Ships::setStatus($first, 'fleet', $fleets[1]);
		Ships::setStatus($second, 'fleet', $fleets[0]);
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyPlayer($player_id, 'revealShip', '', ['ship' => ['id' => $first, 'fleet' => $fleets[1], 'ships' => Ships::getStatus($first, 'ships')]]);
		$this->notifyPlayer($player_id, 'revealShip', '', ['ship' => ['id' => $second, 'fleet' => $fleets[0], 'ships' => Ships::getStatus($second, 'ships')]]);
//* -------------------------------------------------------------------------------------------------------- */
		$this->gamestate->nextState('continue');
	}
	function acDone(string $color): void
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('done');
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		foreach (Ships::getAll($color) as $ship)
		{
			if ($ship['location'] !== 'stock')
			{
				$MP = Factions::TECHNOLOGIES['Propulsion'][Factions::getTechnology($color, 'Propulsion')];
				if (Sectors::terrainFromLocation($ship['location']) === Sectors::NEBULA) $MP += 2;
				if (Ships::getStatus($ship['id'], 'fleet') === 'D') $MP += 1;
				Ships::setMP($ship['id'], $ship['fleet'] === 'homeStar' ? 0 : $MP);
			}
		}
//
		self::DbQuery("DELETE FROM `undo` WHERE color = '$color'");
		foreach (Ships::getAll($color) as $ship) foreach (array_diff(array_merge(Counters::getAtLocation($ship['location'], 'star'), Counters::getAtLocation($ship['location'], 'relic')), Counters::listRevealed($color)) as $counter) self::reveal($color, $ship['location'], $counter);
//
		$undoID = self::getUniqueValueFromDB("SELECT COALESCE(MAX(undoID), 0) FROM `undo` WHERE color = '$color'") + 1;
		self::DbQuery("INSERT INTO `undo` VALUES ($undoID,0,'$color','done','[]')");
//
		$this->gamestate->nextState('next');
	}
	function acDeclareWar(string $color, string $on, $automa = false)
	{
		$player_id = Factions::getPlayer($color);
//
		if (!$automa)
		{
			$this->checkAction('declareWar');
			if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
			if (!in_array($on, Factions::atPeace($color))) throw new BgaVisibleSystemException('Invalid Declare War on: ' . $on);
		}
//
		if (Factions::getAlignment($color) === 'STO') throw new BgaUserException(self::_('You can\'t declare war now'));
//
		Factions::declareWar($color, $on);
		Factions::declareWar($on, $color);
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('msg', clienttranslate('${player_name1} declares war on ${player_name2}'), ['player_name1' => Factions::getName($color), 'player_name2' => Factions::getName($on)]);
		$this->notifyAllPlayers('updateFaction', '', ['faction' => Factions::get($color)]);
		$this->notifyAllPlayers('updateFaction', '', ['faction' => Factions::get($on)]);
//* -------------------------------------------------------------------------------------------------------- */
		if (!$automa) $this->gamestate->nextState('continue');
	}
	function acRemoteViewing(string $color, int $counter)
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('remoteViewing');
		if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
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
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('scout');
		if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
		if (!array_key_exists('scout', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		foreach ($ships as $ship) if (!array_key_exists($ship, $this->possible['scout'])) throw new BgaVisibleSystemException('Invalid ship: ' . $ship);
//
		if ($ships)
		{
			foreach ($ships as $ship) Ships::setActivation($ship, 'done');
//
			$ship = Ships::get($color, $ships[0]);
			foreach (array_diff(array_merge(Counters::getAtLocation($ship['location'], 'star'), Counters::getAtLocation($ship['location'], 'relic')), Counters::listRevealed($color)) as $counter) self::reveal($color, $ship['location'], $counter);
//
			self::DbQuery("DELETE FROM `undo` WHERE color = '$color'");
		}
//
		$this->gamestate->nextState('continue');
	}
	function acMove(string $color, string $location, array $ships, bool $automa = false)
	{
		$player_id = Factions::getPlayer($color);
//
		if (!$automa)
		{
			$this->checkAction('move');
			if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
			if (!array_key_exists('move', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
			if (!$ships) throw new BgaVisibleSystemException('Empty ship list');
			foreach ($ships as $ship)
			{
				if (!array_key_exists($ship, $this->possible['move'])) throw new BgaVisibleSystemException('Invalid ship: ' . $ship);
				if (!array_key_exists($location, $this->possible['move'][$ship])) throw new BgaVisibleSystemException('Invalid location: ' . $location);
			}
		}
//
		$sector = Sectors::get($location[0]);
		$hexagon = substr($location, 2);
		if (array_key_exists($hexagon, $this->SECTORS[$sector]))
		{
			if (Ships::isShip($color, $ships[0]))
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('msg', clienttranslate('${N} ship(s) move to ${PLANET} ${GPS}'), ['GPS' => $location, 'N' => sizeof($ships), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon]]);
//				$this->notifyAllPlayers('msg', clienttranslate('${player_name} moves ${N} ship(s) to ${PLANET} ${GPS}'), ['GPS' => $location, 'N' => sizeof($ships),
//					'player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon]]);
//* -------------------------------------------------------------------------------------------------------- */
			else
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('msg', clienttranslate('A fleet moves to ${PLANET} ${GPS}'), ['GPS' => $location, 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon]]);
//				$this->notifyAllPlayers('msg', clienttranslate('${player_name} moves a fleet to ${PLANET} ${GPS}'), ['GPS' => $location,
//					'player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
		else
		{
			if (Ships::isShip($color, $ships[0]))
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('msg', clienttranslate('${N} ship(s) moves ${GPS}'), ['GPS' => $location, 'N' => sizeof($ships)]);
//				$this->notifyAllPlayers('msg', clienttranslate('${player_name} moves ${N} ship(s) ${GPS}'), ['player_name' => Factions::getName($color), 'GPS' => $location, 'N' => sizeof($ships)]);
//* -------------------------------------------------------------------------------------------------------- */
			else
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('msg', clienttranslate('A fleet is moving ${GPS}'), ['GPS' => $location]);
//				$this->notifyAllPlayers('msg', clienttranslate('${player_name} moves a fleet ${GPS}'), ['player_name' => Factions::getName($color), 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		$locations = [$location];
		while ($from = $this->possible['move'][$ships[0]][$locations[0]]['from']) array_unshift($locations, $from);
//
		foreach ($locations as $next_location)
		{
			$this->notifyAllPlayers('moveShips', '', ['ships' => $ships, 'location' => $next_location, 'old' => $location]);
			$location = $next_location;
		}
//
		$undoID = self::getUniqueValueFromDB("SELECT COALESCE(MAX(undoID), 0) FROM `undo` WHERE color = '$color'") + 1;
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
		if (!$automa) $this->gamestate->nextState('continue');
	}
	function acUndo($color)
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('undo');
		if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		$undoID = self::getUniqueValueFromDB("SELECT COALESCE(MAX(undoID), 0) FROM `undo` WHERE color = '$color' AND type = 'move'");
		if ($undoID)
		{
			$toUndo = self::getCollectionFromDB("SELECT id, status FROM `undo` WHERE color = '$color' AND undoID = $undoID AND type = 'move'", true);
			foreach ($toUndo as $ship => $json)
			{
				$status = json_decode($json, JSON_OBJECT_AS_ARRAY);
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('removeShip', '${player_name} cancels last move', ['player_name' => Factions::getName($color), 'ship' => Ships::get($color, $ship)]);
//* -------------------------------------------------------------------------------------------------------- */
				self::DbQuery("UPDATE ships SET activation = '$status[activation]',fleet = '$status[fleet]',location = '$status[location]', MP = $status[MP] WHERE id = $ship");
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('placeShip', '', ['ship' => Ships::get($color, $ship)]);
//* -------------------------------------------------------------------------------------------------------- */
			}
			self::DbQuery("DELETE FROM `undo` WHERE color = '$color' AND undoID = $undoID");
		}
		else
		{
			$undoID = self::getUniqueValueFromDB("SELECT COALESCE(MAX(undoID), 0) FROM `undo` WHERE color = '$color' AND type = 'done'");
			if ($undoID)
			{
				self::DbQuery("DELETE FROM `undo` WHERE color = '$color' AND undoID = $undoID");
				return $this->gamestate->nextState('undo');
			}
		}
//
		$this->gamestate->nextState('continue');
	}
	function acPass(string $color): void
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('pass');
		if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		Factions::setActivation($color, 'done');
//
		$this->gamestate->nextState('next');
	}
	function acCombatChoice(string $color, string $location, bool $automa = false)
	{
		$player_id = Factions::getPlayer($color);
//
		if (!$automa)
		{
			$this->checkAction('combatChoice');
			if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
			if (!in_array($location, $this->possible)) throw new BgaVisibleSystemException('Invalid $ocation: ' . $location);
		}
//
		$sector = Sectors::get($location[0]);
		$hexagon = substr($location, 2);
		if (array_key_exists($hexagon, $this->SECTORS[$sector]))
		{
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('msg', clienttranslate('${player_name} starts a battle near ${PLANET} ${GPS}'), ['player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon], 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
		}
		else
		{
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('msg', clienttranslate('${player_name} starts a battle ${GPS}'), ['player_name' => Factions::getName($color), 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		Factions::setStatus($color, 'combat', $location);
		foreach (Ships::getConflictFactions($color, $location) as $defender) Factions::getStatus($defender, 'retreat');
//
		$this->gamestate->nextState('engage');
	}
	function acRetreat(string $color, string $location, bool $automa = false)
	{
		$player_id = Factions::getPlayer($color);
//
		if (!$automa)
		{
			$this->checkAction('retreat');
			if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
			if ($location && !in_array($location, $this->possible)) throw new BgaVisibleSystemException('Invalid location: ' . $location);
		}
//
		if (!$location)
		{
			Factions::setStatus($color, 'retreat', 'no');
			return $this->gamestate->nextState('continue');
		}
//
		$combatLocation = Factions::getStatus(Factions::getActive(), 'combat');
//
		$sector = Sectors::get($location[0]);
		$hexagon = substr($location, 2);
		if (array_key_exists($hexagon, $this->SECTORS[$sector]))
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('msg', clienttranslate('${player_name} retreats to ${PLANET} ${GPS}'), ['player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon], 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
		else
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('msg', clienttranslate('${player_name} retreats ${GPS}'), ['player_name' => Factions::getName($color), 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
		$ships = Ships::getAtLocation($combatLocation, $color);
		foreach ($ships as $ship) Ships::setLocation($ship, $location);
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('moveShips', '', ['ships' => $ships, 'location' => $location, 'old' => $combatLocation]);
//* -------------------------------------------------------------------------------------------------------- */
		$this->gamestate->nextState('continue');
	}
	function acSelectCounters(string $color, array $counters): void
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('selectCounters');
		if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		$oval = $square = 0;
		foreach ($counters as $counter)
		{
			if (in_array($counter, ['research', 'growPopulation', 'gainStar', 'gainStar', 'buildShips', 'switchAlignment'])) $oval++;
			else $square++;
		}
		if ($oval < $this->possible[$player_id]['N']) throw new BgaVisibleSystemException("Invalid number of oval counters: $oval < 2");
		if ($square < 1) throw new BgaVisibleSystemException("Invalid number of square counters: $square < 1");
		if ($oval > $this->possible[$player_id]['N'] + $this->possible[$player_id]['additional']) throw new BgaVisibleSystemException('Invalid number of oval counters: ' . $oval);
//
		Factions::setStatus($color, 'counters', array_values($counters));
//
		$this->gamestate->setPlayerNonMultiactive($player_id, 'next');
	}
	function acResearch(string $color, string $technology, bool $automa = false): void
	{
		$player_id = Factions::getPlayer($color);
//
		if (!$automa)
		{
			$this->checkAction('research');
			if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
			if (!array_key_exists('counters', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
			if (!in_array('research', $this->possible['counters'])) throw new BgaVisibleSystemException('Invalid action: ' . 'research');
			if (!array_key_exists($technology, array_intersect($this->TECHNOLOGIES, $this->possible['counters']))) throw new BgaVisibleSystemException('Invalid technology: ' . $technology);
		}
//
		if (Factions::getTechnology($color, $technology) === 6)
		{
//
// When automas research a technology they already have at level 6, this has no effect instead
//
			if ($player_id < 0) return;
			throw new BgaVisibleSystemException('Reseach+ Effect not implemented');
		}
		$level = Factions::gainTechnology($color, $technology);
//
// GREYS SPECIAL STO & STS: When you research a technology at level 1 you increase it to level 3
//
		if (Factions::getStarPeople($color) === 'Greys' && Factions::getTechnology($color, $technology) === 2) $level = Factions::gainTechnology($color, $technology);
//
// YOWIES SPECIAL STO & STS: You may not have Robotics higher than level 1
//
		if (Factions::getStarPeople($color) === 'Yowies' && $technology === 'Robotics' && $level > 1) throw new BgaUserException(self::_('May not have Robotics higher than level 1'));
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains <B>${TECHNOLOGY} (${LEVEL})</B>'), [
			'player_name' => Factions::getName($color),
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
	function acGainStar(string $color, string $location, bool $automa = false): void
	{
		$player_id = Factions::getPlayer($color);
//
		if (!$automa)
		{
			$this->checkAction('gainStar');
			if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
			if (!array_key_exists('counters', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
			if (!in_array('gainStar', $this->possible['counters'])) throw new BgaVisibleSystemException('Invalid action: ' . 'gainStar');
		}
//
		$ships = Ships::getAtLocation($location, $color);
		if (!$ships) throw new BgaVisibleSystemException('No ships at location: ' . $location);
//
		[$can, $population] = Counters::gainStar($color, $location);
		if (!$can) throw new BgaUserException(self::_('You can\'t gain this star'));
		foreach (Counters::getAtLocation($location, 'star') as $star)
		{
			self::reveal('', $location, $star);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($star)]);
//* -------------------------------------------------------------------------------------------------------- */
			Counters::destroy($star);
		}
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains ${PLANET} ${GPS}'), ['GPS' => $location,
			'player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)]]);
//* -------------------------------------------------------------------------------------------------------- */
		foreach (array_map(['Counters', 'get'], Counters::getAtLocation($location, 'populationDisk')) as $populationDisk)
		{
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('removeCounter', '', ['counter' => $populationDisk]);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'population' => Factions::gainPopulation($populationDisk['color'], -1)]]);
//* -------------------------------------------------------------------------------------------------------- */
			Counters::destroy($populationDisk['id']);
		}
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('msg', clienttranslate('${GPS} ${PLANET} gains ${population} <B>population(s)</B>'), [
			'PLANET' => [
				'log' => '<span style = "color:#' . $color . ';font-weight:bold;">${PLANET}</span>',
				'i18n' => ['PLANET'], 'args' => ['PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)]]
			],
			'GPS' => $location, 'population' => $population]);
//* -------------------------------------------------------------------------------------------------------- */
		for ($i = 0; $i < $population; $i++)
		{
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'population' => Factions::gainPopulation($color, 1)]]);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('placeCounter', '', ['counter' => Counters::get(Counters::create($color, 'populationDisk', $location))]);
//* -------------------------------------------------------------------------------------------------------- */
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
					$this->notifyAllPlayers('msg', 'Relic not implemented', []);
					$this->notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
					Counters::destroy($relic);
					break;
				case 1: // Ancient Technology: Genetics
					$technology = 'Genetics';
					if (Factions::getTechnology($color, $technology) === 6) throw new BgaVisibleSystemException('Reseach+ Effect not implemented');
					$level = Factions::gainTechnology($color, $technology);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains <B>${TECHNOLOGY} (${LEVEL})</B>'), [
						'player_name' => Factions::getName($color),
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
					$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains <B>${TECHNOLOGY} (${LEVEL})</B>'), [
						'player_name' => Factions::getName($color),
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
					$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains <B>${TECHNOLOGY} (${LEVEL})</B>'), [
						'player_name' => Factions::getName($color),
						'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology], 'LEVEL' => $level,
						'faction' => ['color' => $color, $technology => $level]
					]);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
					Counters::destroy($relic);
					break;
				case 4: // Ancient Technology: Robotics
					self::acResearch($color, 'Robotics', true);
					$this->notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
					Counters::destroy($relic);
					break;
				case 5: // Ancient Technology: Spirituality
					$technology = 'Spirituality';
					if (Factions::getTechnology($color, $technology) === 6) throw new BgaVisibleSystemException('Reseach+ Effect not implemented');
					$level = Factions::gainTechnology($color, $technology);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains <B>${TECHNOLOGY} (${LEVEL})</B>'), [
						'player_name' => Factions::getName($color),
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
							'player_name' => Factions::getName($color),
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
					$this->notifyAllPlayers('msg', 'Relic not implemented', []);
					$this->notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
					Counters::destroy($relic);
					break;
				case 8: // Defense Grid
					$this->notifyAllPlayers('msg', 'Relic not implemented', []);
					$this->notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
					Counters::destroy($relic);
					break;
				case 9: // Super-Stargate
					$this->notifyAllPlayers('msg', 'Relic not implemented', []);
					$this->notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
					Counters::destroy($relic);
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
		if ($era === 'First' && $galacticStory == RIVALRY && $player_id > 0)
		{
			Factions::gainDP($color, 1);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', _('${player_name} gains ${DP} DP(s)'), ['DP' => 1,
				'player_name' => Factions::getName($color),
				'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		if (!$automa) $this->gamestate->nextState('continue');
	}
	function acSpecial($color, $N)
	{
		Factions::gainPopulation($color, $N);
		$DP = Factions::gainDP($color, $N);
//* -------------------------------------------------------------------------------------------------------- */
		$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} removes ${N} population disc(s) (add to offboard power track)'), [
			'player_name' => Factions::getName($color), 'faction' => Factions::get($color), 'N' => $N]);
//* -------------------------------------------------------------------------------------------------------- */
		if ($DP >= 5)
		{
			Factions::gainDP(Factions::getNotAutomas(), -5);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', _('${player_name} loses ${DP} DP(s)'), ['DP' => 5,
				'player_name' => Factions::getName(Factions::getNotAutomas()),
				'faction' => ['color' => Factions::getNotAutomas(), 'DP' => Factions::getDP(Factions::getNotAutomas())]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
		$counters = Factions::getStatus($color, 'counters');
		if ($counters)
		{
			unset($counters[array_search('gainStar', $counters)]);
			Factions::setStatus($color, 'counters', array_values($counters));
			Factions::setStatus($color, 'used', array_values(array_merge(Factions::getStatus($color, 'used'), ['gainStar'])));
		}
	}
	function acGrowPopulation(string $color, array $locations, array $locationsBonus, bool $automa = false): void
	{
		$player_id = Factions::getPlayer($color);
//
		if (!$automa)
		{
			$this->checkAction('growPopulation');
			if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
			if (!array_key_exists('counters', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
			if (!in_array('growPopulation', $this->possible['counters'])) throw new BgaVisibleSystemException('Invalid action: ' . 'growPopulation');
			if (sizeof($locationsBonus) > $this->possible['bonusPopulation']) throw new BgaVisibleSystemException('Invalid bonus population: ' . sizeof($locationsBonus));
		}
//
		foreach ($locations as $location)
		{
			if (!array_key_exists($location, $this->possible['growPopulation'])) throw new BgaVisibleSystemException('Invalid location: ' . $location);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('placeCounter', clienttranslate('${GPS} ${PLANET} gains a <B>population</B>'), [
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
			$this->notifyAllPlayers('placeCounter', clienttranslate('${GPS} ${PLANET} gains a <B>population</B>'), [
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
		if ($era === 'First' && $galacticStory == MIGRATIONS && (sizeof($locations) + sizeof($locationsBonus) > 0) && $player_id > 0)
		{
			Factions::gainDP($color, 3);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', _('${player_name} gains ${DP} DP(s)'), ['DP' => 3,
				'player_name' => Factions::getName($color),
				'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		if (!$automa) $this->gamestate->nextState('continue');
	}
	function acBuildShips(string $color, array $locations, bool $automa = false): void
	{
		$player_id = Factions::getPlayer($color);
//
		if (!$automa)
		{
			$this->checkAction('buildShips');
			if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
			if (!array_key_exists('buildShips', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
			if (!in_array('buildShips', $this->possible['counters'])) throw new BgaVisibleSystemException('Invalid action: ' . 'buildShips');
			foreach ($locations as $location) if (!in_array($location, $this->possible['buildShips'])) throw new BgaVisibleSystemException('Invalid location: ' . $location);
		}
//
		foreach (array_count_values($locations) as $location => $ships)
		{
			if ($automa)
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('msg', clienttranslate('${player_name} spawns ${ships} <B>additional ship(s)</B> ${GPS}'), [
					'player_name' => Factions::getName($color), 'ships' => $ships, 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
			else
//* -------------------------------------------------------------------------------------------------------- */
				$this->notifyAllPlayers('msg', clienttranslate('${player_name} gains ${ships} <B>additional ship(s)</B> at ${PLANET} ${GPS}'), [
					'player_name' => Factions::getName($color), 'ships' => $ships,
					'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)], 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
			for ($i = 0; $i < $ships; $i++) $this->notifyAllPlayers('placeShip', '', ['ship' => Ships::get($color, Ships::create($color, 'ship', $location))]);
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
		if ($era === 'First' && $galacticStory == WARS && $player_id > 0)
		{
			Factions::gainDP($color, 2);
//* -------------------------------------------------------------------------------------------------------- */
			$this->notifyAllPlayers('updateFaction', _('${player_name} gains ${DP} DP(s) '), ['DP' => 2,
				'player_name' => Factions::getName($color),
				'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		if (!$automa) $this->gamestate->nextState('continue');
	}
	function acTrade(string $from, string $to, string $technology)
	{
		$player_id = Factions::getPlayer($from);
//
		$this->checkAction('trade');
		if ($player_id != Factions::getPlayer($from)) throw new BgaVisibleSystemException('Invalid Faction: ' . $from);
//
		if (!$to)
		{
			Factions::setActivation($from, 'done');
			$this->gamestate->setPlayerNonMultiactive($player_id, 'next');
			if (sizeof($this->gamestate->getActivePlayerList()) < 2) $this->gamestate->setAllPlayersNonMultiactive('next');
			else $this->gamestate->nextState('continue');
			return;
		}
//
		$automas = Factions::getPlayer($to) < 0;
		if ($automas && $technology === 'accept') $technology = 'confirm';
//
		$fromStatus = Factions::getStatus($from, 'trade');
		switch ($technology)
		{
			case 'accept':
				{
					if (!array_key_exists($to, $fromStatus)) throw new BgaUserException(self::_('You must choose what you are getting'));
					$toStatus = Factions::getStatus($to, 'trade');
					if (!array_key_exists($from, $toStatus)) throw new BgaUserException(self::_('Other player must choose what you are teaching'));
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyPlayer(Factions::getPlayer($from), 'msg', clienttranslate('Waiting from confirmation of ${player_name}'), [
						'player_name' => Factions::getName($to)]);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyPlayer(Factions::getPlayer($to), 'trade', '', [
						'from' => $from, 'to' => $to]);
//* -------------------------------------------------------------------------------------------------------- */
					$toStatus[$from]['pending'] = true;
					Factions::setStatus($to, 'trade', $toStatus);
				}
				break;
			case 'confirm':
				{
					if (!array_key_exists($to, $fromStatus)) throw new BgaUserException(self::_('You must choose what you are getting'));
					$toStatus = Factions::getStatus($to, 'trade');
					if (!array_key_exists($from, $toStatus)) throw new BgaUserException(self::_('Other player must choose what you are teaching'));
//
					foreach ([$from => $fromStatus[$to]['technology'], $to => $toStatus[$from]['technology']] as $color => $technology)
					{
						if (Factions::getTechnology($color, $technology) === 6) throw new BgaVisibleSystemException('Reseach+ Effect not implemented');
						$level = Factions::gainTechnology($color, $technology);
//
// YOWIES SPECIAL STO & STS: You may not have Robotics higher than level 1
//
						if (Factions::getStarPeople($color) === 'Yowies' && $technology === 'Robotics' && $level > 1) throw new BgaUserException(self::_('May not have Robotics higher than level 1'));
//* -------------------------------------------------------------------------------------------------------- */
						$this->notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains <B>${TECHNOLOGY} (${LEVEL})</B>'), [
							'player_name' => Factions::getName($color),
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
				break;
			case 'refuse':
				{
					if (array_key_exists($to, $fromStatus)) unset($fromStatus[$to]);
					if (!$automas)
					{
						$toStatus = Factions::getStatus($to, 'trade');
						if (array_key_exists($from, $toStatus))
						{
//* -------------------------------------------------------------------------------------------------------- */
							$this->notifyPlayer(Factions::getPlayer($to), 'msg', clienttranslate('${player_name} refuses your trade'), [
								'player_name' => Factions::getName($from)]);
//* -------------------------------------------------------------------------------------------------------- */
							unset($toStatus[$from]);
							Factions::setStatus($to, 'trade', $toStatus);
						}
					}
				}
				break;
			default:
				{
					if (array_key_exists($to, $fromStatus))
					{
						$old = $fromStatus[$to]['technology'];
						unset($fromStatus[$to]);
						if ($technology !== $old) $fromStatus[$to] = ['technology' => $technology, 'pending' => false];
					}
					else $fromStatus[$to] = ['technology' => $technology, 'pending' => false];
				}
		}
		Factions::setStatus($from, 'trade', $fromStatus);
//
		$this->gamestate->nextState('continue');
	}
}
