<?php

/**
 *
 * @author Lunalol
 */
trait gameStateActions
{
	function acLevelOfDifficulty(int $levelOfDifficulty): void
	{
		$this->checkAction('levelOfDifficulty');
//
		self::setGameStateValue('difficulty', $levelOfDifficulty);
		$slavers = Factions::getAutoma(SLAVERS);
		if ($slavers) self::acSpecial($slavers, Automas::DIFFICULTY[$levelOfDifficulty]);
//
		$this->gamestate->nextState('next');
	}
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
	function acIndividualChoice(string $color, string $technology)
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('individualChoice');
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
		if (!array_key_exists('counters', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (!array_key_exists($technology, array_intersect($this->TECHNOLOGIES, $this->possible['counters']))) throw new BgaVisibleSystemException('Invalid technology: ' . $technology);
//
		self::gainTechnology($color, $technology);
//
		Factions::setActivation($color, 'done');
		$this->gamestate->nextState('nextPlayer');
	}
	function acAdvancedFleetTactics(string $color, string $Fleet, string $tactics)
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('advancedFleetTactics');
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
		if (!array_key_exists($player_id, $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (!array_key_exists('fleets', $this->possible[$player_id])) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (!array_key_exists($Fleet, $this->possible[$player_id]['fleets'])) throw new BgaVisibleSystemException('Invalid Fleet: ' . $Fleet);
		if (Factions::getAdvancedFleetTactics($color, $Fleet) !== 'null') throw new BgaVisibleSystemException('Invalid Fleet: ' . $Fleet);
//
		Factions::setAdvancedFleetTactics($color, $Fleet, $tactics);
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} gets <B>${tactics}</B> on <B>${FLEET}</B> fleet'), [
			'player_name' => Factions::getName($color), 'tactics' => $tactics, 'FLEET' => $Fleet,
			'faction' => Factions::get($color)]);
//* -------------------------------------------------------------------------------------------------------- */
		$advancedFleetTactics = Factions::getStatus($color, 'advancedFleetTactics') - 1;
		if ($advancedFleetTactics)
		{
			Factions::setStatus($color, 'advancedFleetTactics', $advancedFleetTactics);
			$this->gamestate->nextState('continue');
		}
		else
		{
			Factions::setStatus($color, 'advancedFleetTactics');
			$this->gamestate->setPlayerNonMultiactive($player_id, 'next');
		}
	}
//	function acBuriedShips(string $color, string $Fleet, int $ships)
//	{
//		$player_id = Factions::getPlayer($color);
////
//		$this->checkAction('buriedShips');
//		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//		if (!array_key_exists('fleets', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
//		if ($Fleet && !array_key_exists($Fleet, $this->possible['fleets'])) throw new BgaVisibleSystemException('Invalid Fleet: ' . $Fleet);
//		if (sizeof(Ships::getAll($color, 'ship')) + $ships > 16) throw new BgaUserException(self::_('No more ships'));
////
//		if ($ships)
//		{
//			['location' => $location, 'ships' => $buriedShips] = Factions::getStatus($color, 'buriedShips');
////
//			$buriedShips -= $ships;
//			if ($buriedShips < 0) throw new BgaVisibleSystemException("Invalid ships: $ships > $buriedShips");
////
//			Factions::setStatus($color, 'buriedShips', ['location' => $location, 'ships' => $buriedShips]);
////
//			if ($Fleet)
//			{
//				$fleetID = Ships::getFleet($color, $Fleet);
//				$fleet = Ships::get($fleetID);
////
//				if ($fleet['location'] === 'stock')
//				{
////* -------------------------------------------------------------------------------------------------------- */
//					self::notifyAllPlayers('removeShip', '', ['ship' => Ships::get($fleetID)]);
////* -------------------------------------------------------------------------------------------------------- */
//					$fleet['location'] = $location;
//					Ships::setLocation($fleetID, $fleet['location']);
////* -------------------------------------------------------------------------------------------------------- */
//					self::notifyAllPlayers('placeShip', clienttranslate('A new fleet is created ${GPS}'), ['GPS' => $location, 'ship' => $fleet]);
////* -------------------------------------------------------------------------------------------------------- */
//				}
//				Ships::setStatus($fleetID, 'ships', intval(Ships::getStatus($fleetID, 'ships')) + $ships);
////* -------------------------------------------------------------------------------------------------------- */
//				self::notifyAllPlayers('msg', clienttranslate('${N} ship(s) join fleet ${GPS}'), ['GPS' => $location, 'N' => $ships]);
////* -------------------------------------------------------------------------------------------------------- */
////* -------------------------------------------------------------------------------------------------------- */
//				self::notifyAllPlayers('revealShip', '', ['player_id' => $player_id, 'player_id' => $player_id, 'ship' => ['id' => $fleetID, 'fleet' => $Fleet === 'D' ? 'D' : 'fleet', 'ships' => '?']]);
//				self::notifyPlayer($player_id, 'revealShip', '', ['ship' => ['id' => $fleetID, 'fleet' => $Fleet, 'ships' => Ships::getStatus($fleetID, 'ships')]]);
//			}
//			else
//			{
////* -------------------------------------------------------------------------------------------------------- */
//				self::notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${SHIPS} additional ships</B> at ${PLANET}'), [
//					'player_name' => Factions::getName($color), 'SHIPS' => $ships,
//					'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)],
//				]);
////* -------------------------------------------------------------------------------------------------------- */
//				for ($i = 0; $i < $ships; $i++)
////* -------------------------------------------------------------------------------------------------------- */
//					self::notifyAllPlayers('placeShip', '', ['ship' => Ships::get(Ships::create($color, 'ship', $location))]);
////* -------------------------------------------------------------------------------------------------------- */
//			}
////* -------------------------------------------------------------------------------------------------------- */
//			self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'ships' => 16 - sizeof(Ships::getAll($color, 'ship'))]]);
////* -------------------------------------------------------------------------------------------------------- */
//		}
////
//		$this->gamestate->nextState('continue');
//	}
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
			$fleet = Ships::get($fleetID);
//
// UNDO
//
			$undoID = self::getUniqueValueFromDB("SELECT COALESCE(MAX(undoID), 0) FROM `undo` WHERE color = '$color'") + 1;
			$json = self::escapeStringForDB(json_encode(array_merge($fleet, ['Fleet' => Ships::getStatus($fleetID, 'fleet'), 'ships' => Ships::getStatus($fleetID, 'ships')])));
			self::DbQuery("INSERT INTO `undo` VALUES ($undoID,$fleetID,'$color','move','$json')");
//
			$location = Ships::get($ships[0])['location'];
			if ($fleet['location'] === 'stock')
			{
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('removeShip', '', ['ship' => Ships::get($fleetID)]);
//* -------------------------------------------------------------------------------------------------------- */
				$fleet['location'] = $location;
				Ships::setLocation($fleetID, $fleet['location']);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('placeShip', clienttranslate('A new fleet is created ${GPS}'), ['GPS' => $location, 'ship' => $fleet]);
//* -------------------------------------------------------------------------------------------------------- */
			}
			Ships::setStatus($fleetID, 'ships', intval(Ships::getStatus($fleetID, 'ships')) + sizeof($ships));
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${N} ship(s) join fleet ${GPS}'), ['GPS' => $location, 'N' => sizeof($ships)]);
//* -------------------------------------------------------------------------------------------------------- */
			$MP = $fleet['MP'];
			foreach ($ships as $shipID)
			{
				$ship = Ships::get($shipID);
//
// UNDO
//
				$json = self::escapeStringForDB(json_encode($ship));
				self::DbQuery("INSERT INTO `undo` VALUES ($undoID,$shipID,'$color','create','$json')");
//
				if ($ship['activation'] !== 'no') $MP = min($ship['MP'], $MP);
				if (!$ship) throw new BgaVisibleSystemException('Invalid ship: ' . $shipID);
				if ($ship['location'] !== $location) throw new BgaVisibleSystemException('Invalid location: ' . $location);
				if ($ship['fleet'] !== 'ship') throw new BgaVisibleSystemException('Not a ship');
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('removeShip', '', ['ship' => $ship]);
//* -------------------------------------------------------------------------------------------------------- */
				Ships::destroy($shipID);
			}
			Ships::setMP($fleetID, $MP);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('revealShip', '', ['player_id' => $player_id, 'ship' => ['id' => $fleetID, 'fleet' => $Fleet === 'D' ? 'D' : 'fleet', 'ships' => '?']]);
			self::notifyPlayer($player_id, 'revealShip', '', ['ship' => ['id' => $fleetID, 'fleet' => $Fleet, 'ships' => Ships::getStatus($fleetID, 'ships')]]);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'ships' => 16 - sizeof(Ships::getAll($color, 'ship'))]]);
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
			$fleet = Ships::get($fleetID);
			$location = $fleet['location'];
//
			$MP = $fleet['MP'];
			if ($Fleet === 'D')
			{
				$MP--;
				if (Factions::getAdvancedFleetTactics($color, 'D') === '2x') $MP--;
			}
			if ($MP < 0 && $this->gamestate->state()['name'] === 'movement') throw new BgaUserException(self::_('(D)art: Ships that have already used this advantage may not leave this fleet, in this turn.'));
//
			if (intval(Ships::getStatus($fleetID, 'ships')) < $ships) throw new BgaVisibleSystemException('Not enough ships: ' . $ships);
			if (sizeof(Ships::getAll($color, 'ship')) + $ships > 16) throw new BgaUserException(self::_('No more ships'));
//
// UNDO
//
			$undoID = self::getUniqueValueFromDB("SELECT COALESCE(MAX(undoID), 0) FROM `undo` WHERE color = '$color'") + 1;
			$json = self::escapeStringForDB(json_encode(array_merge($fleet, ['Fleet' => Ships::getStatus($fleetID, 'fleet'), 'ships' => Ships::getStatus($fleetID, 'ships')])));
			self::DbQuery("INSERT INTO `undo` VALUES ($undoID,$fleetID,'$color','move','$json')");
//
			Ships::setStatus($fleetID, 'ships', intval(Ships::getStatus($fleetID, 'ships')) - $ships);
			if (intval(Ships::getStatus($fleetID, 'ships')) === 0)
			{
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('removeShip', clienttranslate('A fleet is removed ${GPS}'), ['GPS' => $location, 'ship' => $fleet]);
//* -------------------------------------------------------------------------------------------------------- */
				$fleet['location'] = 'stock';
				Ships::setLocation($fleetID, $fleet['location']);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('placeShip', '', ['ship' => $fleet]);
//* -------------------------------------------------------------------------------------------------------- */
			}
//
			for ($i = 0; $i < $ships; $i++)
			{
				$shipID = Ships::create($color, 'ship', $location);
//
// UNDO
//
				self::DbQuery("INSERT INTO `undo` VALUES ($undoID,$shipID,'$color','destroy','[]')");
//
				self::notifyAllPlayers('placeShip', '', ['ship' => Ships::get($shipID)]);
				Ships::setMP($shipID, $MP);
				Ships::setActivation($shipID, $MP == 0 ? 'done' : 'yes');
			}
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${N} ship(s) leave fleet ${GPS}'), ['GPS' => $location, 'N' => $ships]);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyPlayer($player_id, 'revealShip', '', ['ship' => ['id' => $fleetID, 'fleet' => $Fleet, 'ships' => Ships::getStatus($fleetID, 'ships')]]);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'ships' => 16 - sizeof(Ships::getAll($color, 'ship'))]]);
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
			$fromFleet = Ships::get($fromID);
//
			$toID = Ships::getFleet($color, $to);
			$toFleet = Ships::get($toID);
//
			$fromFleetMP = $fromFleet['MP'];
			if ($from === 'D')
			{
				$fromFleetMP--;
				if (Factions::getAdvancedFleetTactics($color, 'D') === '2x') $fromFleetMP--;
			}
			if ($fromFleetMP < 0 && $this->gamestate->state()['name'] === 'movement') throw new BgaUserException(self::_('(D)art: Ships that have already used this advantage may not leave this fleet, in this turn.'));
//
			$toFleetMP = $toFleet['MP'];
			if ($to === 'D')
			{
				$toFleetMP--;
				if (Factions::getAdvancedFleetTactics($color, 'D') === '2x') $toFleetMP--;
			}
			if ($toFleetMP < 0 && $this->gamestate->state()['name'] === 'movement') throw new BgaUserException(self::_('(D)art: Ships that have already used this advantage may not leave this fleet, in this turn.'));
//
			if (intval(Ships::getStatus($fromID, 'ships')) < $ships) throw new BgaVisibleSystemException('Not enough ships: ' . $ships);
//
// UNDO
//
			$undoID = self::getUniqueValueFromDB("SELECT COALESCE(MAX(undoID), 0) FROM `undo` WHERE color = '$color'") + 1;
			$json = self::escapeStringForDB(json_encode(array_merge($fromFleet, ['Fleet' => Ships::getStatus($fromID, 'fleet'), 'ships' => Ships::getStatus($fromID, 'ships')])));
			self::DbQuery("INSERT INTO `undo` VALUES ($undoID,$fromID,'$color','move','$json')");
			$json = self::escapeStringForDB(json_encode(array_merge($toFleet, ['Fleet' => Ships::getStatus($toID, 'fleet'), 'ships' => Ships::getStatus($toID, 'ships')])));
			self::DbQuery("INSERT INTO `undo` VALUES ($undoID,$toID,'$color','move','$json')");
//
			$location = $fromFleet['location'];
			if ($toFleet['location'] === 'stock')
			{
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('removeShip', '', ['ship' => $toFleet]);
//* -------------------------------------------------------------------------------------------------------- */
				$toFleet['location'] = $location;
				Ships::setLocation($toID, $toFleet['location']);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('placeShip', clienttranslate('A new fleet is created ${GPS}'), ['GPS' => $location, 'ship' => $toFleet]);
//* -------------------------------------------------------------------------------------------------------- */
			}
//* -------------------------------------------------------------------------------------------------------- */
			if ($fromFleet['location'] !== $toFleet['location']) throw new BgaVisibleSystemException('Invalid location: ' . $fromFleet['location'] . ' <> ' . $toFleet['location']);
//
			Ships::setStatus($fromID, 'ships', intval(Ships::getStatus($fromID, 'ships')) - $ships);
			Ships::setMP($fromID, min($fromFleetMP, $toFleetMP));
			Ships::setStatus($toID, 'ships', intval(Ships::getStatus($toID, 'ships')) + $ships);
			Ships::setMP($toID, min($fromFleetMP, $toFleetMP));
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('Some ship(s) swap fleet ${GPS}'), ['GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
			if (intval(Ships::getStatus($fromID, 'ships')) === 0)
			{
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('removeShip', clienttranslate('A fleet is removed ${GPS}'), ['GPS' => $location, 'ship' => $fromFleet]);
//* -------------------------------------------------------------------------------------------------------- */
				$fromFleet['location'] = 'stock';
				Ships::setLocation($fromID, $fromFleet['location']);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('placeShip', '', ['ship' => $fromFleet]);
//* -------------------------------------------------------------------------------------------------------- */
			}
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyPlayer($player_id, 'revealShip', '', ['ship' => ['id' => $fromID, 'fleet' => $from, 'ships' => Ships::getStatus($fromID, 'ships')]]);
			self::notifyPlayer($player_id, 'revealShip', '', ['ship' => ['id' => $toID, 'fleet' => $to, 'ships' => Ships::getStatus($toID, 'ships')]]);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'ships' => 16 - sizeof(Ships::getAll($color, 'ship'))]]);
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
		if ($first !== $second)
		{
			$this->possible['stars'][] = 'stock';
			if (Ships::get($first)['location'] !== Ships::get($second)['location'])
			{
				if (!in_array(Ships::get($first)['location'], $this->possible['stars'])) throw new BgaVisibleSystemException('Invalid location: ' . Ships::get($first)['location']);
				if (!in_array(Ships::get($second)['location'], $this->possible['stars'])) throw new BgaVisibleSystemException('Invalid location: ' . Ships::get($second)['location']);
			}
//
			Ships::setStatus($first, 'fleet', $fleets[1]);
			Ships::setStatus($second, 'fleet', $fleets[0]);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('revealShip', '', ['player_id' => $player_id, 'ship' => ['id' => $first, 'fleet' => $fleets[1] === 'D' ? 'D' : 'fleet', 'ships' => '?']]);
			self::notifyPlayer($player_id, 'revealShip', '', ['ship' => ['id' => $first, 'fleet' => $fleets[1], 'ships' => Ships::getStatus($first, 'ships')]]);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('revealShip', '', ['player_id' => $player_id, 'ship' => ['id' => $second, 'fleet' => $fleets[0] === 'D' ? 'D' : 'fleet', 'ships' => '?']]);
			self::notifyPlayer($player_id, 'revealShip', '', ['ship' => ['id' => $second, 'fleet' => $fleets[0], 'ships' => Ships::getStatus($second, 'ships')]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
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
				if (Ships::getStatus($ship['id'], 'fleet') === 'D')
				{
					$MP++;
					if (Factions::getAdvancedFleetTactics($color, 'D') === '2x') $MP++;
				}

				Ships::setMP($ship['id'], $ship['fleet'] === 'homeStar' ? 0 : $MP);
			}
		}
//
		self::DbQuery("DELETE FROM `undo` WHERE color = '$color'");
		foreach (Ships::getAll($color) as $ship) foreach (array_diff(array_merge(Counters::getAtLocation($ship['location'], 'star'), Counters::getAtLocation($ship['location'], 'relic')), Counters::listRevealed($color)) as $counter) self::reveal($color, 'counter', $counter);
//
		if ($this->gamestate->state()['name'] === 'fleets')
		{
			$undoID = self::getUniqueValueFromDB("SELECT COALESCE(MAX(undoID), 0) FROM `undo` WHERE color = '$color'") + 1;
			self::DbQuery("INSERT INTO `undo` VALUES ($undoID,0,'$color','done','[]')");
		}
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
// PLEJARS STS: May declare war on STS players during your movement
//
		if (Factions::getAlignment($color) === 'STO')
		{
			if (Factions::getStarPeople($color) === 'Plejars' && Factions::getAlignment($on) === 'STS' && in_array($this->gamestate->state()['name'], ['fleets', 'movement'])) ;
			else throw new BgaUserException(self::_('You can\'t declare war now'));
		}
//
		Factions::declareWar($color, $on);
		Factions::declareWar($on, $color);
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', clienttranslate('${player_name1} declares war on ${player_name2}'), ['player_name1' => Factions::getName($color), 'player_name2' => Factions::getName($on)]);
		self::notifyAllPlayers('updateFaction', '', ['faction' => Factions::get($color)]);
		self::notifyAllPlayers('updateFaction', '', ['faction' => Factions::get($on)]);
//* -------------------------------------------------------------------------------------------------------- */
		if (!$automa) $this->gamestate->nextState('continue');
	}
	function acDeclarePeace(string $color, string $on, $automa = false)
	{
		$player_id = Factions::getPlayer($color);
//
		if (!$automa)
		{
			$this->checkAction('declarePeace');
			if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
			if (!in_array($on, Factions::atWar($color))) throw new BgaVisibleSystemException('Invalid Declare War on: ' . $on);
		}
//
		if (Factions::getPlayer($on) <= 0)
		{
			if (Factions::getStatus($on, 'peace')) throw new BgaUserException(self::_('You can make peace only once per round'));
//
// #offboard population : 2 - Slavers never make peace
//
			if (Factions::getPlayer($on) == SLAVERS && Factions::getDP($on) >= 2) throw new BgaUserException(self::_('Slavers’ Offboard Power Effects: Slavers never make peace'));
			Factions::setStatus($on, 'peace', 'once per round');
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${player_name1} proposes peace to ${player_name2}'), ['player_name1' => Factions::getName($color), 'player_name2' => Factions::getName($on)]);
//* -------------------------------------------------------------------------------------------------------- */
			$dice = bga_rand(1, 6);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${player_name} rolls ${DICE}'), [
				'player_name' => Factions::getName($on), 'DICE' => $dice]);
//* -------------------------------------------------------------------------------------------------------- */
			if ($dice > Automas::makingPeace($on))
			{
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('${player_name} reject peace'), ['player_name' => Factions::getName($on)]);
//* -------------------------------------------------------------------------------------------------------- */
				return;
			}
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${player_name} accept peace'), ['player_name' => Factions::getName($on)]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		Factions::declarePeace($color, $on);
		Factions::declarePeace($on, $color);
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', clienttranslate('${player_name1} is at peace with ${player_name2}'), ['player_name1' => Factions::getName($color), 'player_name2' => Factions::getName($on)]);
		self::notifyAllPlayers('updateFaction', '', ['faction' => Factions::get($color)]);
		self::notifyAllPlayers('updateFaction', '', ['faction' => Factions::get($on)]);
//* -------------------------------------------------------------------------------------------------------- */
		if (!$automa) $this->gamestate->nextState('continue');
	}
	function acRemoteViewing(string $color, string $type, string $id)
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('remoteViewing');
		if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
		if (!array_key_exists('view', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (!$this->possible['view']) throw new BgaVisibleSystemException('No move view: ' . $this->possible['view']);
//
		self::reveal($color, $type, $id);
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
			$ship = Ships::get($ships[0]);
			foreach (array_diff(array_merge(Counters::getAtLocation($ship['location'], 'star'), Counters::getAtLocation($ship['location'], 'relic')), Counters::listRevealed($color)) as $counter) self::reveal($color, 'counter', $counter);
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
				self::notifyAllPlayers('msg', clienttranslate('${N} ship(s) move to ${PLANET} ${GPS}'), ['GPS' => $location, 'N' => sizeof($ships), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon]]);
//				self::notifyAllPlayers('msg', clienttranslate('${player_name} moves ${N} ship(s) to ${PLANET} ${GPS}'), ['GPS' => $location, 'N' => sizeof($ships),
//					'player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon]]);
//* -------------------------------------------------------------------------------------------------------- */
			else
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('A fleet moves to ${PLANET} ${GPS}'), ['GPS' => $location, 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon]]);
//				self::notifyAllPlayers('msg', clienttranslate('${player_name} moves a fleet to ${PLANET} ${GPS}'), ['GPS' => $location,
//					'player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
		else
		{
			if (Ships::isShip($color, $ships[0]))
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('${N} ship(s) moves ${GPS}'), ['GPS' => $location, 'N' => sizeof($ships)]);
//				self::notifyAllPlayers('msg', clienttranslate('${player_name} moves ${N} ship(s) ${GPS}'), ['player_name' => Factions::getName($color), 'GPS' => $location, 'N' => sizeof($ships)]);
//* -------------------------------------------------------------------------------------------------------- */
			else
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('A fleet is moving ${GPS}'), ['GPS' => $location]);
//				self::notifyAllPlayers('msg', clienttranslate('${player_name} moves a fleet ${GPS}'), ['player_name' => Factions::getName($color), 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		$locations = [$location];
		while ($from = $this->possible['move'][$ships[0]][$locations[0]]['from']) array_unshift($locations, $from);
//
		foreach ($locations as $next_location)
		{
			self::notifyAllPlayers('moveShips', '', ['ships' => $ships, 'location' => $next_location, 'old' => $location]);
			$location = $next_location;
		}
//
		if ($player_id > 0) $undoID = self::getUniqueValueFromDB("SELECT COALESCE(MAX(undoID), 0) FROM `undo` WHERE color = '$color'") + 1;
		foreach ($ships as $ship)
		{
			if ($player_id > 0)
			{
				$json = self::escapeStringForDB(json_encode(Ships::get($ship)));
				self::DbQuery("INSERT INTO `undo` VALUES ($undoID,$ship,'$color','move','$json')");
			}
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
			foreach (self::getCollectionFromDB("SELECT id, status FROM `undo` WHERE color = '$color' AND undoID = $undoID AND type = 'move'", true) as $ship => $json)
			{
				$status = json_decode($json, JSON_OBJECT_AS_ARRAY);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('removeShip', clienttranslate('${player_name} cancels last move'), ['player_name' => Factions::getName($color), 'ship' => Ships::get($ship)]);
//* -------------------------------------------------------------------------------------------------------- */
				self::DbQuery("UPDATE ships SET activation = '$status[activation]',fleet = '$status[fleet]',location = '$status[location]', MP = $status[MP] WHERE id = $ship");
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('placeShip', '', ['ship' => Ships::get($ship)]);
//* -------------------------------------------------------------------------------------------------------- */
				if (array_key_exists('Fleet', $status)) Ships::setStatus($ship, 'fleet', $status['Fleet']);
				if (array_key_exists('ships', $status)) Ships::setStatus($ship, 'ships', $status['ships']);
//
				foreach (Counters::isRevealed($ship, 'fleet') as $otherColor)
				{
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyPlayer(Factions::getPlayer($otherColor), 'revealShip', '', ['ship' => ['id' => $ship, 'fleet' => Ships::getStatus($ship, 'fleet'), 'ships' => Ships::getStatus($ship, 'ships')]]);
//* -------------------------------------------------------------------------------------------------------- */
				}
			}
			foreach (self::getCollectionFromDB("SELECT id, status FROM `undo` WHERE color = '$color' AND undoID = $undoID AND type = 'create'", true) as $ship => $json)
			{
				$status = json_decode($json, JSON_OBJECT_AS_ARRAY);
//
				self::DbQuery("INSERT ships SET id = $ship,color = '$status[color]', activation = '$status[activation]',fleet = '$status[fleet]',location = '$status[location]', MP = $status[MP]");
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('placeShip', '', ['ship' => Ships::get($ship)]);
//* -------------------------------------------------------------------------------------------------------- */
				if (array_key_exists('Fleet', $status)) Ships::setStatus($ship, 'fleet', $status['Fleet']);
				if (array_key_exists('ships', $status)) Ships::setStatus($ship, 'ships', $status['ships']);
//
				foreach (Counters::isRevealed($ship, 'fleet') as $otherColor)
				{
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyPlayer(Factions::getPlayer($otherColor), 'revealShip', '', ['ship' => ['id' => $ship, 'fleet' => Ships::getStatus($ship, 'fleet'), 'ships' => Ships::getStatus($ship, 'ships')]]);
//* -------------------------------------------------------------------------------------------------------- */
				}
			}
			foreach (self::getCollectionFromDB("SELECT id, status FROM `undo` WHERE color = '$color' AND undoID = $undoID AND type = 'destroy'", true) as $ship => $json)
			{
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('removeShip', '', ['ship' => Ships::get($ship)]);
//* -------------------------------------------------------------------------------------------------------- */
				Ships::destroy($ship);
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
		self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'ships' => 16 - sizeof(Ships::getAll($color, 'ship'))]]);

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
	function acStealTechnology(string $color, string $technology)
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('stealTechnology');
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
		if (!array_key_exists('counters', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if ($technology && !array_key_exists($technology, array_intersect($this->TECHNOLOGIES, $this->possible['counters']))) throw new BgaVisibleSystemException('Invalid technology: ' . $technology);
//
		['from' => $from, 'levels' => $levels] = Factions::getStatus($color, 'steal');
		Factions::setStatus($color, 'steal');
//
		if (!$technology) return $this->gamestate->nextState('continue');
//
		for ($i = 0; $i < $levels; $i++) self::gainTechnology($color, $technology);
//
		$players = array_values(Factions::advancedFleetTactics());
		if ($this->gamestate->setPlayersMultiactive($players, 'continue', true)) return;
		$this->gamestate->nextState('advancedFleetTactics');
	}
	function acHomeStarEvacuation(string $color, $location)
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('homeStarEvacuation');
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		if (!$location)
		{
			if (Factions::getStatus($color, 'evacuate') === 'voluntary')
			{
				Factions::setStatus($color, 'evacuate');
				return $this->gamestate->nextState('continue');
			}
//
			if (Factions::getStatus($color, 'evacuate')) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
			Factions::setStatus($color, 'evacuate', 'voluntary');
			return $this->gamestate->nextState('homeStarEvacuation');
		}
//
		if (!array_key_exists($player_id, $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (!array_key_exists('evacuate', $this->possible[$player_id])) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (!in_array($location, $this->possible[$player_id]['evacuate'])) throw new BgaVisibleSystemException('Invalid $ocation: ' . $location);
//
		$sector = Sectors::get($location[0]);
		$hexagon = substr($location, 2);
		if (array_key_exists($hexagon, $this->SECTORS[$sector]))
		{
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${player_name} evacuates near ${PLANET} ${GPS}'), ['player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon], 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
		}
		else
		{
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${player_name} evacuates ${GPS}'), ['player_name' => Factions::getName($color), 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		$homeStar = Ships::getHomeStar($color);
		$previousLocation = Ships::getHomeStarLocation($color);
		Ships::setLocation($homeStar, $location);
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('homeStarEvacuation', '', ['homeStar' => $homeStar, 'location' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
		foreach (array_map(['Counters', 'get'], Counters::getAtLocation($location, 'populationDisk')) as $populationDisk)
		{
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('removeCounter', '', ['counter' => $populationDisk]);
//* -------------------------------------------------------------------------------------------------------- */
			if (Factions::getTechnology($populationDisk['color'], 'Spirituality') < 6) self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $populationDisk['color'], 'population' => Factions::gainPopulation($populationDisk['color'], -1)]]);
//* -------------------------------------------------------------------------------------------------------- */
			Counters::destroy($populationDisk['id']);
		}
		if (Factions::getStatus($color, 'evacuate') === 'voluntary')
		{
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${GPS} ${PLANET} gains ${population} <B>population(s)</B>'), [
				'PLANET' => [
					'log' => '<span style = "color:#' . $color . ';font-weight:bold;">${PLANET}</span>',
					'i18n' => ['PLANET'], 'args' => ['PLANET' => $this->SECTORS[Sectors::get($previousLocation[0])][substr($previousLocation, 2)]]
				],
				'GPS' => $previousLocation, 'population' => 1]);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'population' => Factions::gainPopulation($color, 1)]]);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('placeCounter', '', ['counter' => Counters::get(Counters::create($color, 'populationDisk', $previousLocation))]);
//* -------------------------------------------------------------------------------------------------------- */
		}
		Factions::setStatus($color, 'evacuate');
//
		self::starsBecomingUninhabited($previousLocation);
//
		$this->gamestate->nextState('continue');
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
			self::notifyAllPlayers('msg', clienttranslate('${player_name} starts a battle near ${PLANET} ${GPS}'), ['player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon], 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
		}
		else
		{
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${player_name} starts a battle ${GPS}'), ['player_name' => Factions::getName($color), 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		Factions::setStatus($color, 'combat', $location);
		Factions::setStatus($color, 'winner');
		foreach (Ships::getConflictFactions($color, $location) as $defender)
		{
			Factions::getStatus($defender, 'retreat');
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${player_name} engages ${player_name1}'), [
				'player_name' => Factions::getName($color), 'player_name1' => Factions::getName($defender),
			]);
//* -------------------------------------------------------------------------------------------------------- */
		}
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
			if (Factions::getStatus(Factions::getActive(), 'winner')) throw new BgaVisibleSystemException('Retreat is mandatory');
//
			Factions::setStatus($color, 'retreat', 'no');
			return $this->gamestate->nextState('continue');
		}
//
		$combatLocation = Factions::getStatus(Factions::getActive(), 'combat');
//
		$sector = Sectors::get($location[0]);
		$hexagon = substr($location, 2);
//
		$retreatE = $this->gamestate->state()['name'] === 'retreatE';
		if ($retreatE)
		{
			if (array_key_exists($hexagon, $this->SECTORS[$sector]))
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('${player_name} evades to ${PLANET} ${GPS}'), ['player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon], 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
			else
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('${player_name} evades ${GPS}'), ['player_name' => Factions::getName($color), 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */ = Ships::getAtLocation($combatLocation, $color);
			$fleet = Ships::getFleet($color, 'E');
			Ships::setLocation($fleet, $location);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('moveShips', '', ['ships' => [$fleet], 'location' => $location, 'old' => $combatLocation]);
//* -------------------------------------------------------------------------------------------------------- */
		}
		else
		{
			if (array_key_exists($hexagon, $this->SECTORS[$sector]))
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('${player_name} retreats to ${PLANET} ${GPS}'), ['player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon], 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
			else
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('${player_name} retreats ${GPS}'), ['player_name' => Factions::getName($color), 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
			$ships = Ships::getAtLocation($combatLocation, $color);
			foreach ($ships as $ship) Ships::setLocation($ship, $location);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('moveShips', '', ['ships' => $ships, 'location' => $location, 'old' => $combatLocation]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		$this->gamestate->nextState('continue');
	}
	function acBattleLoss(string $color, array $ships, bool $automa = false)
	{
		$player_id = Factions::getPlayer($color);
//
		if (!$automa)
		{
			$this->checkAction('battleLoss');
			if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
		}
//
		$attacker = Factions::getActive();
		$location = Factions::getStatus($attacker, 'combat');
		$defenders = Ships::getConflictFactions($attacker, $location);
//
		$toDestroy = [$attacker => ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'ships' => 0]];
		foreach ($defenders as $defender) $toDestroy[$defender] = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'E' => 0, 'ships' => 0];
//
		foreach ($ships as $side => $_ships) foreach ($_ships as [$color, $Fleet]) $toDestroy[$color][$Fleet]++;
//
		foreach (array_merge([$attacker], $defenders) as $color)
		{
			foreach ($toDestroy[$color] as $Fleet => $count)
			{
				if ($count)
				{
					switch ($Fleet)
					{
//
						case 'A':
						case 'B':
						case 'C':
						case 'D':
						case 'E':
//
							$fleetID = Ships::getFleet($color, $Fleet);
							$fleet = Ships::get($fleetID);
							if (!$fleet) throw new BgaVisibleSystemException("Invalid fleet: $fleetID");
							if ($fleet['location'] !== $location) throw new BgaVisibleSystemException("Invalid location: $fleet[location]");
//
							Ships::setStatus($fleetID, 'ships', intval(Ships::getStatus($fleetID, 'ships')) - $count);
							if (intval(Ships::getStatus($fleetID, 'ships')) < 0) throw new BgaVisibleSystemException("No more ships to destroy in $Fleet for $color");
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyAllPlayers('msg', '<div style="color:black;background:#${color};">${LOG}</div>', ['color' => $color,
								'LOG' => ['log' => clienttranslate('${ships} ship(s) destroyed in ${FLEET} fleet'), 'args' => ['ships' => $count, 'FLEET' => $Fleet]],
							]);
//* -------------------------------------------------------------------------------------------------------- */
							if (intval(Ships::getStatus($fleetID, 'ships')) === 0)
							{
//* -------------------------------------------------------------------------------------------------------- */
								self::notifyAllPlayers('removeShip', clienttranslate('Fleet ${FLEET} is removed ${GPS}'), ['GPS' => $location, 'FLEET' => $Fleet, 'ship' => $fleet]);
//* -------------------------------------------------------------------------------------------------------- */
								$fleet['location'] = 'stock';
								Ships::setLocation($fleetID, $fleet['location']);
//* -------------------------------------------------------------------------------------------------------- */
								self::notifyAllPlayers('placeShip', '', ['ship' => $fleet]);
//* -------------------------------------------------------------------------------------------------------- */
							}
							if ($player_id > 0) self::notifyPlayer($player_id, 'revealShip', '', ['ship' => ['id' => $fleetID, 'fleet' => $Fleet, 'ships' => Ships::getStatus($fleetID, 'ships')]]);
							break;
//
						case 'ships':
//
							$ships = Ships::getAtLocation($location, $color, 'ship');
							if (!$ships) throw new BgaVisibleSystemException("No more ships to destroy in $Fleet for $color");
							for ($i = 0; $i < $count; $i++)
							{
								$shipID = array_pop($ships);
								if (!$shipID) throw new BgaVisibleSystemException("No more ships to destroy in $Fleet for $color");
//* -------------------------------------------------------------------------------------------------------- */
								self::notifyAllPlayers('removeShip', '', ['ship' => Ships::get($shipID)]);
//* -------------------------------------------------------------------------------------------------------- */
								Ships::destroy($shipID);
							}
//* -------------------------------------------------------------------------------------------------------- */
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyAllPlayers('updateFaction', '<div style="color:black;background:#${color};">${LOG}</div>', ['color' => $color,
								'LOG' => ['log' => clienttranslate('${ships} single ship(s) destroyed'), 'args' => ['ships' => $count]],
								'faction' => ['color' => $color, 'ships' => 16 - sizeof(Ships::getAll($color, 'ship'))]
							]);
//* -------------------------------------------------------------------------------------------------------- */
							break;
					}
				}
			}
		}
//
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
		if ($oval < $this->possible[$player_id]['oval']) throw new BgaVisibleSystemException("Invalid number of oval counters: $oval");
		if ($oval > $this->possible[$player_id]['oval'] + $this->possible[$player_id]['additional']) throw new BgaVisibleSystemException('Invalid number of oval counters: ' . $oval);
		if ($square < 1 || $square > $this->possible[$player_id]['square']) throw new BgaVisibleSystemException("Invalid number of square counters: $square");
//
		Factions::setStatus($color, 'counters', array_values($counters));
//
		$this->gamestate->setPlayerNonMultiactive($player_id, 'next');
	}
	function acResearch(string $color, array $technologies, bool $automa = false)
	{
		$player_id = Factions::getPlayer($color);
//
		if (!$automa)
		{
			$this->checkAction('research');
			if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
			if (!array_key_exists('counters', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
			if (!in_array('research', $this->possible['counters'])) throw new BgaVisibleSystemException('Invalid action: ' . 'research');
//			if (sizeof($technologies) > 1 && Factions::getTechnology($color, 'Robotics') < 5) throw new BgaVisibleSystemException('Too much research tokens');
			foreach ($technologies as $technology) if (!array_key_exists($technology, array_intersect($this->TECHNOLOGIES, $this->possible['counters']))) throw new BgaVisibleSystemException('Invalid technology: ' . $technology);
		}
//
		foreach ($technologies as $technology)
		{
			$level = self::gainTechnology($color, $technology);
//
// GREYS SPECIAL STO & STS: When you research a technology at level 1 you increase it to level 3
//
			if (Factions::getStarPeople($color) === 'Greys' && $level === 2) self::gainTechnology($color, $technology);
//
		}
//
		$counters = Factions::getStatus($color, 'counters');
		unset($counters[array_search('research', $counters)]);
		foreach ($technologies as $technology) unset($counters[array_search($technology, $counters)]);
		Factions::setStatus($color, 'counters', array_values($counters));
		foreach ($technologies as $technology) Factions::setStatus($color, 'used', array_values(array_merge(Factions::getStatus($color, 'used'), ['research', $technology])));
//
		if (!$automa)
		{
			$players = array_values(Factions::advancedFleetTactics());
			if ($this->gamestate->setPlayersMultiactive($players, 'continue', true)) return;
			$this->gamestate->nextState('advancedFleetTactics');
		}
	}
	function acGainStar(string $color, array $locations, bool $automa = false): void
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
		foreach ($locations as $location)
		{
			$ships = Ships::getAtLocation($location, $color);
			if (!$ships) throw new BgaVisibleSystemException('No ships at location: ' . $location);
//
			[$type, $SHIPS, $population] = Counters::gainStar($color, $location);
			if (!$type) throw new BgaUserException(self::_('You can\'t gain this star'));
//
			if ($type === LIBERATE || $type === CONQUERVS)
			{
				$sizeOfPopulation = 0;
				$homeStar = Ships::getAtLocation($location, null, 'homeStar');
				if ($homeStar)
				{
					$sizeOfPopulation += 6;
					$otherColor = Ships::get($homeStar[0])['color'];
					Factions::setStatus($otherColor, 'evacuate', 'involuntary');
				}
				else
				{
					$populations = Counters::getAtLocation($location, 'populationDisk');
					$sizeOfPopulation += sizeof($populations);
					if ($populations) $otherColor = Counters::get($populations[0])['color'];
					else throw new BgaVisibleSystemException('No population to liberate/conquer at location: ' . $location);
				}
				if (!in_array($otherColor, Factions::atWar($color))) throw new BgaUserException(self::_('You must be at war with star owner'));
				Factions::setStatus($color, 'steal', ['from' => $otherColor, 'levels' => $sizeOfPopulation >= 6 ? 2 : 1]);
			}
//
			foreach (Counters::getAtLocation($location, 'star') as $star)
			{
				self::reveal('', 'counter', $star);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($star)]);
//* -------------------------------------------------------------------------------------------------------- */
				Counters::destroy($star);
			}
			switch ($type)
			{
				case COLONIZE:
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${GPS} ${player_name} colonizes ${PLANET} with at least ${SHIPS} ship(s)'), ['GPS' => $location, 'SHIPS' => $SHIPS,
						'player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)]]);
//* -------------------------------------------------------------------------------------------------------- */
					break;
				case SUBJUGATE:
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${GPS} ${player_name} subjugates ${PLANET} with at least ${SHIPS} ship(s)'), ['GPS' => $location, 'SHIPS' => $SHIPS,
						'player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)]]);
//* -------------------------------------------------------------------------------------------------------- */
					break;
				case LIBERATE:
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${GPS} ${player_name} liberates ${PLANET} with at least ${SHIPS} ship(s)'), ['GPS' => $location, 'SHIPS' => $SHIPS,
						'player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)]]);
//* -------------------------------------------------------------------------------------------------------- */
//
// PLEJARS STS: You get 2 DP every time you liberate a star (in addition to other DP gained for this)
//
					if (Factions::getStarPeople($color) === 'Plejars' && Factions::getAlignment($color) === 'STO')
					{
						$DP = 2;
						self::gainDP($color, $DP);
						self::incStat($DP, 'DP_SP', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains ${DP} DP(s)'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
					}
					break;
				case CONQUER:
				case CONQUERVS:
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${GPS} ${player_name} conquers ${PLANET} with at least ${SHIPS} ship(s)'), ['GPS' => $location, 'SHIPS' => $SHIPS,
						'player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)]]);
//* -------------------------------------------------------------------------------------------------------- */
					break;
				case ALLY:
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${GPS} ${player_name} allies oneself with ${PLANET}'), ['GPS' => $location,
						'player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)]]);
//* -------------------------------------------------------------------------------------------------------- */
					break;
			}
//
			foreach (array_map(['Counters', 'get'], Counters::getAtLocation($location, 'populationDisk')) as $populationDisk)
			{
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('removeCounter', '', ['counter' => $populationDisk]);
//* -------------------------------------------------------------------------------------------------------- */
				if (Factions::getTechnology($populationDisk['color'], 'Spirituality') < 6) self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $populationDisk['color'], 'population' => Factions::gainPopulation($populationDisk['color'], -1)]]);
//* -------------------------------------------------------------------------------------------------------- */
				Counters::destroy($populationDisk['id']);
			}
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${GPS} ${PLANET} gains ${population} <B>population(s)</B>'), [
				'PLANET' => [
					'log' => '<span style = "color:#' . $color . ';font-weight:bold;">${PLANET}</span>',
					'i18n' => ['PLANET'], 'args' => ['PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)]]
				],
				'GPS' => $location, 'population' => $population]);
//* -------------------------------------------------------------------------------------------------------- */
			for ($i = 0; $i < $population; $i++)
			{
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'population' => Factions::gainPopulation($color, 1)]]);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('placeCounter', '', ['counter' => Counters::get(Counters::create($color, 'populationDisk', $location))]);
//* -------------------------------------------------------------------------------------------------------- */
			}
//
			$newShips = 0;
			$relics = Counters::getAtLocation($location, 'relic');
			foreach ($relics as $relic)
			{
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('<B>${RELIC}</B> is found ${GPS}'), [
					'i18n' => ['RELIC'], 'RELIC' => $this->RELICS[Counters::getStatus($relic, 'back')],
					'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
				switch (Counters::getStatus($relic, 'back'))
				{
//
					case 0: // Ancient Pyramids
//
						self::notifyAllPlayers('msg', 'Relic not implemented', []);
						self::notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
						Counters::destroy($relic);
						break;
//
					case 1: // Ancient Technology: Genetics
//
						self::gainTechnology($color, 'Genetics');
						self::notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
						Counters::destroy($relic);
						break;
//
					case 2: // Ancient Technology: Military
//
						self::gainTechnology($color, 'Military');
						self::notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
						Counters::destroy($relic);
						break;
//
					case 3: // Ancient Technology: Propulsion
//
						self::gainTechnology($color, 'Propulsion');
						self::notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
						Counters::destroy($relic);
						break;
//
					case 4: // Ancient Technology: Robotics
//
// YOWIES SPECIAL STO & STS: When you get the Ancient Technology: Robotics relic you get 2 ships at that star instead of a level (use the same restrictions as for the Buried Ships relic
//
						if (Factions::getStarPeople($color) === 'Yowies')
						{
							$newShips = 2;
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${SHIPS} additional ships</B> at ${PLANET}'), [
								'player_name' => Factions::getName($color), 'SHIPS' => $newShips,
								'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)],
							]);
//* -------------------------------------------------------------------------------------------------------- */
						}
						else self::gainTechnology($color, 'Robotics');
						self::notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
						Counters::destroy($relic);
						break;
//
					case 5: // Ancient Technology: Spirituality
//
						self::gainTechnology($color, 'Spirituality');
						self::notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
						Counters::destroy($relic);
						break;
//
					case 6: // Buried Ships
//
						$newShips = 3;
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${SHIPS} additional ships</B> at ${PLANET}'), [
							'player_name' => Factions::getName($color), 'SHIPS' => $newShips,
							'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)],
						]);
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
						Counters::destroy($relic);
						break;
//
					case 7: // Planetary Death Ray
//
						self::notifyAllPlayers('msg', 'Relic not implemented', []);
						self::notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
						Counters::destroy($relic);
						break;
//
					case 8: // Defense Grid
//
						self::notifyAllPlayers('msg', 'Relic not implemented', []);
						self::notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
						Counters::destroy($relic);
						break;
//
					case 9: // Super-Stargate
//
						self::notifyAllPlayers('msg', 'Relic not implemented', []);
						self::notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
						Counters::destroy($relic);
						break;
				}
			}
//
// Scoring
//
// JOURNEYS Second : All players score 2 DP for every star outside of their home star sector that they take from another player
//
			if (self::getGameStateValue('galacticStory') == JOURNEYS && self::ERA() === 'Second' && $player_id > 0)
			{
				if (intval($location[0]) !== Factions::getHomeStar($color) && in_array($type, [LIBERATE, CONQUERVS]))
				{
//* -------------------------------------------------------------------------------------------------------- */
					if (DEBUG) self::notifyAllPlayers('msg', clienttranslate('All players score 2 DP for every star outside of their home star sector that they take from another player'), []);
//* -------------------------------------------------------------------------------------------------------- */
					$DP = 2;
					self::gainDP($color, $DP);
					self::incStat($DP, 'DP_GS', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} gains ${DP} DP(s)'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
				}
			}
//
// RIVALRY First: All players score 1 DP for every Gain Star action they do in this era.
//
			if (self::getGameStateValue('galacticStory') == RIVALRY && self::ERA() === 'First' && $player_id > 0)
			{
//* -------------------------------------------------------------------------------------------------------- */
				if (DEBUG) self::notifyAllPlayers('msg', clienttranslate('All players score 2 DP for every star outside of their home star sector that they take from another player'), []);
//* -------------------------------------------------------------------------------------------------------- */
				$DP = 1;
				self::gainDP($color, $DP);
				self::incStat($DP, 'DP_GS', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} gains ${DP} DP(s)'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
			}
//
		}
//
		$counters = Factions::getStatus($color, 'counters');
		unset($counters[array_search('gainStar', $counters)]);
		Factions::setStatus($color, 'counters', array_values($counters));
		Factions::setStatus($color, 'used', array_values(array_merge(Factions::getStatus($color, 'used'), ['gainStar'])));
//
		if (!$automa)
		{
			if (Factions::getStatus($color, 'steal'))
			{
				$this->gamestate->nextState('stealTechnology');
				return;
			}
			if ($newShips)
			{
				Factions::setStatus($color, 'buriedShips', ['location' => $location, 'ships' => $newShips]);
				$this->gamestate->nextState('buriedShips');
				return;
			}
			$players = array_values(Factions::advancedFleetTactics());
			if ($this->gamestate->setPlayersMultiactive($players, 'continue', true)) return;
			$this->gamestate->nextState('advancedFleetTactics');
		}
//
		if ($newShips)
		{
			self::notifyAllPlayers('msg', 'Buried ships with AUTOMAS not implemented', []);
		}
	}
	function acSpecial($color, $N)
	{
		if ($N > 0)
		{
			Factions::gainPopulation($color, $N);
			$DP = self::gainDP($color, $N);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} removes ${N} population disc(s) (add to offboard power track)'), [
				'player_name' => Factions::getName($color), 'faction' => Factions::get($color), 'N' => $N]);
//* -------------------------------------------------------------------------------------------------------- */
			if ($DP >= 5)
			{
//
// #offboard population : 5+ - You immediately lose 5 DP for each new offboard population
//
				self::gainDP(Factions::getNotAutomas(), -5);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} loses ${DP} DP(s)'), ['DP' => 5,
					'player_name' => Factions::getName(Factions::getNotAutomas()),
					'faction' => ['color' => Factions::getNotAutomas(), 'DP' => Factions::getDP(Factions::getNotAutomas())]]);
//* -------------------------------------------------------------------------------------------------------- */
			}
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
			self::notifyAllPlayers('placeCounter', clienttranslate('${GPS} ${PLANET} gains a <B>population</B>'), [
				'PLANET' => [
					'log' => '<span style = "color:#' . $color . ';font-weight:bold;">${PLANET}</span>',
					'i18n' => ['PLANET'], 'args' => ['PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)]]
				], 'GPS' => $location, 'counter' => Counters::get(Counters::create($color, 'populationDisk', $location))]);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'population' => Factions::gainPopulation($color, 1)]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
		foreach ($locationsBonus as $location)
		{
			if (!array_key_exists($location, $this->possible['growPopulation'])) throw new BgaVisibleSystemException('Invalid location: ' . $location);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('placeCounter', clienttranslate('${GPS} ${PLANET} gains a <B>population</B>'), [
				'PLANET' => [
					'log' => '<span style = "color:#' . $color . ';font-weight:bold;">${PLANET}</span>',
					'i18n' => ['PLANET'], 'args' => ['PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)]]
				], 'GPS' => $location, 'counter' => Counters::get(Counters::create($color, 'populationDisk', $location))]);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'population' => Factions::gainPopulation($color, 1)]]);
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
			self::gainDP($color, 3);
			self::incStat($DP, 'DP_GS', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains ${DP} DP(s)'), ['DP' => 3,
				'player_name' => Factions::getName($color),
				'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		if (!$automa) $this->gamestate->nextState('continue');
	}
	function acBuildShips(string $color, array $buildShips, bool $automa = false): void
	{
		$player_id = Factions::getPlayer($color);
//
		if (!$automa)
		{
			$this->checkAction('buildShips');
			if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
			if (!array_key_exists('stars', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
			if ($this->gamestate->state()['name'] == !'buriedShips' && !in_array('buildShips', $this->possible['counters'])) throw new BgaVisibleSystemException('Invalid action: ' . 'buildShips');
//			foreach ($locations as $location) if (!in_array($location, $this->possible['buildShips'])) throw new BgaVisibleSystemException('Invalid location: ' . $location);
		}
//
		foreach ($buildShips['fleets'] as $Fleet => $location)
		{
			$fleetID = Ships::getFleet($color, $Fleet);
			$fleet = Ships::get($fleetID);
//
			if ($fleet['location'] !== 'stock') throw new BgaVisibleSystemException("Invalid fleet location: $fleet[location] !== 'stock'");
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('removeShip', '', ['ship' => Ships::get($fleetID)]);
//* -------------------------------------------------------------------------------------------------------- */
			$fleet['location'] = $location;
			Ships::setLocation($fleetID, $fleet['location']);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('placeShip', clienttranslate('A new fleet is created ${GPS}'), ['GPS' => $location, 'ship' => $fleet]);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('revealShip', '', ['player_id' => $player_id, 'ship' => ['id' => $fleetID, 'fleet' => $Fleet === 'D' ? 'D' : 'fleet', 'ships' => '?']]);
			if ($player_id > 0) self::notifyPlayer($player_id, 'revealShip', '', ['ship' => ['id' => $fleetID, 'fleet' => $Fleet, 'ships' => Ships::getStatus($fleetID, 'ships')]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		$remainingShips = 16 - sizeof(Ships::getAll($color, 'ship'));
//
		foreach (array_count_values($buildShips['ships']) as $location => $ships)
		{
			if (in_array($location, Ships::FLEETS))
			{
				$Fleet = $location;
//
				$fleetID = Ships::getFleet($color, $Fleet);
				$fleet = Ships::get($fleetID);
//
				Ships::setStatus($fleetID, 'ships', intval(Ships::getStatus($fleetID, 'ships')) + $ships);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('${ships} ship(s) join fleet ${GPS}'), ['GPS' => $fleet['location'], 'ships' => $ships]);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('revealShip', '', ['player_id' => $player_id, 'ship' => ['id' => $fleetID, 'fleet' => $Fleet === 'D' ? 'D' : 'fleet', 'ships' => '?']]);
				if ($player_id > 0) self::notifyPlayer($player_id, 'revealShip', '', ['ship' => ['id' => $fleetID, 'fleet' => $Fleet, 'ships' => Ships::getStatus($fleetID, 'ships')]]);
//* -------------------------------------------------------------------------------------------------------- */
			}
			else
			{
				if ($automa)
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${player_name} spawns ${ships} <B>additional ship(s)</B> ${GPS}'), [
						'player_name' => Factions::getName($color), 'ships' => $ships, 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
				else
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${player_name} builds ${ships} <B>additional ship(s)</B> at ${PLANET} ${GPS}'), [
						'player_name' => Factions::getName($color), 'ships' => $ships,
						'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[Sectors::get($location[0])][substr($location, 2)], 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
				$remainingShips -= $ships;
				if ($remainingShips < 0) throw new BgaUserException(self::_('No more ships'));
				for ($i = 0; $i < $ships; $i++) self::notifyAllPlayers('placeShip', '', ['ship' => Ships::get(Ships::create($color, 'ship', $location))]);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'ships' => $remainingShips]]);
//* -------------------------------------------------------------------------------------------------------- */
			}
		}
//
		if ($this->gamestate->state()['name'] !== 'buriedShips')
		{
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
// WAR First: All players score 2 DP for every Build Ships action they do in this era.
//
			if ($era === 'First' && $galacticStory == WAR && $player_id > 0)
			{
				self::gainDP($color, 2);
				self::incStat($DP, 'DP_GS', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains ${DP} DP(s) '), ['DP' => 2,
					'player_name' => Factions::getName($color),
					'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
			}
		}
//
		if (!$automa) $this->gamestate->nextState('continue');
	}
	function acTrade(string $from, string $to, string $technology, $toTeach = null)
	{
		$player_id = Factions::getPlayer($from);
//
		$this->gamestate->checkPossibleAction('trade');
		if ($player_id != Factions::getPlayer($from)) throw new BgaVisibleSystemException('Invalid Faction: ' . $from);
		if (Factions::getActivation($from) === 'done') throw new BgaVisibleSystemException('Invalid Faction: ' . $from);
//
		if (!$to)
		{
			Factions::setActivation($from, 'done');
			if ($this->gamestate->setPlayerNonMultiactive($player_id, 'next')) return;
			return $this->gamestate->nextState('continue');
		}
//
		$automas = Factions::getPlayer($to) <= 0;
//
		$fromStatus = Factions::getStatus($from, 'trade');
		switch ($technology)
		{
			case 'confirm':
				{
					if (!array_key_exists($to, $fromStatus)) throw new BgaUserException(self::_('You must choose what you are getting'));
					$toStatus = Factions::getStatus($to, 'trade');
					if (!array_key_exists($from, $toStatus)) throw new BgaUserException(self::_('Other player must choose what you are teaching'));
//
					foreach ([$from => $fromStatus[$to]['technology'], $to => $toStatus[$from]['technology']] as $color => $technology) self::gainTechnology($color, $technology);
//
					Factions::setActivation($from, 'done');
					Factions::setActivation($to, 'done');
//
					if ($player_id > 0 && $this->gamestate->setPlayerNonMultiactive($player_id, 'next')) return;
					if ($this->gamestate->setPlayerNonMultiactive(Factions::getPlayer($to), 'next')) return;
					return $this->gamestate->nextState('continue');
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
							self::notifyPlayer(Factions::getPlayer($to), 'msg', clienttranslate('${player_name} refuses your trade'), [
								'player_name' => Factions::getName($from)]);
//* -------------------------------------------------------------------------------------------------------- */
							unset($toStatus[$from]);
							Factions::setStatus($to, 'trade', $toStatus);
//
							$otherPlayer = Factions::getPlayer($to);
							if ($otherPlayer) self::dbSetPlayerMultiactive($otherPlayer, 1);
							self::dbSetPlayerMultiactive($player_id, 1);
							$this->gamestate->updateMultiactiveOrNextState('next');
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
						if ($technology !== $old)
						{
							$fromStatus[$to] = ['technology' => $technology, 'pending' => false];
							self::dbSetPlayerMultiactive($player_id, 0);
						}
						else self::dbSetPlayerMultiactive($player_id, 1);
					}
					else
					{
						$fromStatus[$to] = ['technology' => $technology, 'pending' => false];
						self::dbSetPlayerMultiactive($player_id, 0);
//
						$toStatus = Factions::getStatus($to, 'trade');
						if ($automas)
						{
							if (!array_key_exists($from, $toStatus))
							{
								$toStatus[$from] = ['technology' => $toTeach, 'pending' => false];
								Factions::setStatus($to, 'trade', $toStatus);
							}
							self::dbSetPlayerMultiactive($player_id, 1);
							Factions::setStatus($from, 'trade', $fromStatus);
							return self::acTrade($to, $from, 'confirm');
						}
//
						if (array_key_exists($from, $toStatus))
						{
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyPlayer(Factions::getPlayer($from), 'msg', clienttranslate('Waiting from confirmation of ${player_name}'), [
								'player_name' => Factions::getName($to)]);
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyPlayer(Factions::getPlayer($to), 'trade', '', ['from' => $from, 'to' => $to]);
//* -------------------------------------------------------------------------------------------------------- */
							$toStatus[$from]['pending'] = true;
							Factions::setStatus($to, 'trade', $toStatus);
//
							self::dbSetPlayerMultiactive(Factions::getPlayer($to), 1);
						}
					}
//
					$this->gamestate->updateMultiactiveOrNextState('next');
				}
		}
		Factions::setStatus($from, 'trade', $fromStatus);
//
		$this->gamestate->nextState('continue');
	}
}
