<?php

/**
 *
 * @author Lunalol
 */
trait gameStateActions
{
	function acGODMODE(array $god)
	{
		switch ($god['action'])
		{
			case 'toggle':
				if (self::getGameStateValue('GODMODE'))
				{
					self::notifyAllPlayers('GODMODE', 'GOD mode OFF', ['GODMODE' => 0]);
					self::setGameStateValue('GODMODE', 0);
				}
				else
				{
					self::notifyAllPlayers('GODMODE', 'GOD mode ON', ['GODMODE' => 1]);
					self::setGameStateValue('GODMODE', 1);
				}
				break;
			case 'move':
				self::notifyAllPlayers('moveShips', '', ['ships' => [$god['ship']], 'location' => $god['location'], 'old' => Ships::get($god['ship'])['location']]);
				Ships::setLocation($god['ship'], $god['location']);
				break;
			case 'technology':
				self::dbQuery("UPDATE factions SET `$god[technology]` = $god[level] WHERE color = '$god[color]'");
				self::notifyAllPlayers('updateFaction', '', ['faction' => Factions::get($god['color'])]);
				break;
			case 'declareWar':
				self::acDeclareWar($god['color'], $god['on'], true);
				break;
			case 'declarePeace':
				self::acDeclarePeace($god['color'], $god['on'], true);
				break;
		}
//
		self::updateScoring();
	}
	function acNull(string $color): void
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('null');
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		$this->gamestate->setPlayerNonMultiactive($player_id, 'end');
	}
	function acLevelOfDifficulty(int $levelOfDifficulty): void
	{
		$this->checkAction('levelOfDifficulty');
//
		self::setStat($levelOfDifficulty, 'difficulty');
//
		self::setGameStateValue('difficulty', $levelOfDifficulty);
		$slavers = Factions::getAutoma(SLAVERS);
		self::special($slavers, Automas::DIFFICULTY[$levelOfDifficulty]);
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
		self::updateScoring();
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
			if (sizeof(Ships::getAll($color, 'ship')) + $ships > 16) throw new BgaUserException(self::_('No more ship minis'));
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
		self::updateScoring();
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
			$jsonFrom = self::escapeStringForDB(json_encode(array_merge($fromFleet, ['Fleet' => Ships::getStatus($fromID, 'fleet'), 'ships' => Ships::getStatus($fromID, 'ships')])));
			self::DbQuery("INSERT INTO `undo` VALUES ($undoID,$fromID,'$color','move','$jsonFrom')");
			$jsonTo = self::escapeStringForDB(json_encode(array_merge($toFleet, ['Fleet' => Ships::getStatus($toID, 'fleet'), 'ships' => Ships::getStatus($toID, 'ships')])));
			self::DbQuery("INSERT INTO `undo` VALUES ($undoID,$toID,'$color','move','$jsonTo')");
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
			if ($fromFleet['location'] !== $toFleet['location']) throw new BgaUserException(self::_('You can only swap fleets, not ship minis'));
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
		self::updateScoring();
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
		self::updateScoring();
		$this->gamestate->nextState('continue');
	}
	function acDone(string $color): void
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('done');
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		$etheric = Factions::getStatus($color, 'etheric');
		foreach (Ships::getAll($color) as $ship)
		{
			if ($ship['location'] !== 'stock')
			{
				$MP = Factions::TECHNOLOGIES['Propulsion'][Factions::getTechnology($color, 'Propulsion')];
				if (Sectors::terrainFromLocation($ship['location']) === Sectors::NEBULA) $MP += $etheric ? 4 : 2;
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
		self::updateScoring();
		$this->gamestate->nextState('next');
	}
	function acDeclareWar(string $from, string $on, $automa = false)
	{
		$player_id = Factions::getPlayer($from);
//
		if (!$automa)
		{
			$this->checkAction('declareWar');
			if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $from);
			if (!in_array($on, Factions::atPeace($from))) throw new BgaVisibleSystemException('Invalid Declare War on: ' . $on);
//
			if (!self::getGameStateValue('GODMODE'))
			{
//
// GREYS STO: You may not declare war on other players (also not on a automa)
//
				if (Factions::getAlignment($from) === 'STO' && Factions::getStarPeople($from) === 'Greys') throw new BgaUserException(self::_('Greys STO may not declare war'));
//------------------------
// A-section: Diplomatic //
//------------------------
				if (Factions::getStatus($on, 'diplomatic')) throw new BgaUserException(self::_('You can\'t declare war on this player (Diplomatic)'));
//------------------------
// A-section: Diplomatic //
//------------------------
//
// PLEJARS STO: May declare war on STS players during your movement
//
				$possible = false;
				if (Factions::getAlignment($from) === 'STO')
				{
					if (Factions::getStarPeople($from) === 'Plejars' && Factions::getAlignment($on) === 'STS' && in_array($this->gamestate->state()['name'], ['fleets', 'movement'])) $possible = true;
				}
				if (Factions::getAlignment($from) === 'STS')
				{
					if ($this->gamestate->state()['name'] === 'selectCounters')
					{
						$homeStar = Ships::getHomeStarLocation($on);
						foreach (Counters::getPopulations($on, true) as $location => $population) if ($population >= 5 && $location !== $homeStar && Ships::getAtLocation($location, $from)) $possible = true;
					}
					if ($this->gamestate->state()['name'] === 'fleets') $possible = true;
					if ($this->gamestate->state()['name'] === 'movement') $possible = true;
					if ($this->gamestate->state()['name'] === 'researchPlus') $possible = true;
				}
				if (!$possible) throw new BgaUserException(self::_('You can\'t declare war now'));
			}
		}
//
		self::DbQuery("DELETE FROM `undo` WHERE color = '$from'");
		self::DbQuery("DELETE FROM `undo` WHERE color = '$on'");
//
		Factions::declareWar($from, $on);
		Factions::declareWar($on, $from);
//
		if ($this->gamestate->state()['name'] === 'selectCounters')
		{
			$player_id = Factions::getPlayer($on);
			if ($player_id > 0)
			{
				Factions::setStatus($on, 'counters', []);
				self::dbSetPlayerMultiactive($player_id, 1);
				$this->gamestate->updateMultiactiveOrNextState('next');
			}
		}
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', clienttranslate('${player_name1} declares war on ${player_name2}'), ['player_name1' => Factions::getName($from), 'player_name2' => Factions::getName($on)]);
		self::notifyAllPlayers('updateFaction', '', ['faction' => Factions::get($from)]);
		self::notifyAllPlayers('updateFaction', '', ['faction' => Factions::get($on)]);
//* -------------------------------------------------------------------------------------------------------- */
//
// ALLIANCE OF DARKNESS (STS) : STS players lose 3 DP every time they declare war on you
//
		if (Factions::getAlignment($from) === 'STS' && Factions::getStarPeople($on) === 'Alliance' && Factions::getAlignment($on) === 'STS' && $player_id > 0)
		{
			$DP = -3;
			self::gainDP($from, $DP);
			self::incStat($DP, 'DP_SP', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} loses ${DP} DP'), ['DP' => -$DP, 'player_name' => Factions::getName($from), 'faction' => ['color' => $from, 'DP' => Factions::getDP($from)]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
		if (!$automa) $this->gamestate->nextState('continue');
	}
	function acDeclarePeace(string $from, string $on, $automa = false)
	{
		$player_id = Factions::getPlayer($from);
//
		if (!$automa)
		{
			$this->checkAction('declarePeace');
			if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $from);
			if (!in_array($on, Factions::atWar($from))) throw new BgaVisibleSystemException('Invalid Declare War on: ' . $on);
		}
//
		if (!self::getGameStateValue('GODMODE'))
		{
			if (Factions::getPlayer($on) === 0) throw new BgaUserException(self::_('Automa will not accept peace'));
			if (Factions::getPlayer($on) < 0)
			{
				if (Factions::getStatus($on, 'peace')) throw new BgaUserException(self::_('You can make peace only once per round'));
//
// #offboard population : 2 - Slavers never make peace
//
				if (Factions::getPlayer($on) == SLAVERS && Factions::getDP($on) >= 2) throw new BgaUserException(self::_('Slavers’ Offboard Power Effects: Slavers never make peace'));
				Factions::setStatus($on, 'peace', 'once per round');
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('${player_name1} proposes peace to ${player_name2}'), ['player_name1' => Factions::getName($from), 'player_name2' => Factions::getName($on)]);
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
				return self::acAcceptPeace($from, $on, true);
			}
		}
//
		$pending = Factions::getStatus($on, 'peace') ?? [];
		if (!in_array($from, $pending))
		{
			$pending[] = $from;
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyPlayer(Factions::getPlayer($from), 'msg', clienttranslate('You propose peace to ${player_name}'), ['player_name' => Factions::getName($on)]);
			self::notifyPlayer(Factions::getPlayer($on), 'peace', clienttranslate('${player_name} wants to make peace'), ['player_name' => Factions::getName($from), 'from' => $from]);
//* -------------------------------------------------------------------------------------------------------- */
			Factions::setStatus($on, 'peace', $pending);
		}
	}
	function acAcceptPeace(string $on, string $from, $automa = false)
	{
		if (!$automa)
		{
			$pending = Factions::getStatus($on, 'peace') ?? [];
			if (!in_array($from, $pending)) throw new BgaUserException(self::_('Peace agreement not found !'));
			unset($pending[array_search($from, $pending)]);
			if ($pending) Factions::setStatus($on, 'peace', array_values($pending));
			else Factions::setStatus($on, 'peace');
		}
//
		self::DbQuery("DELETE FROM `undo` WHERE color = '$from'");
		self::DbQuery("DELETE FROM `undo` WHERE color = '$on'");
//
		Factions::declarePeace($from, $on);
		Factions::declarePeace($on, $from);
//
		if ($this->gamestate->state()['name'] === 'selectCounters')
		{
			$player_id = Factions::getPlayer($on);
			if ($player_id > 0)
			{
				Factions::setStatus($on, 'counters', []);
				self::dbSetPlayerMultiactive($player_id, 1);
				$this->gamestate->updateMultiactiveOrNextState('next');
			}
		}
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', clienttranslate('${player_name1} accepts peace with ${player_name2}'), ['player_name1' => Factions::getName($from), 'player_name2' => Factions::getName($on)]);
		self::notifyAllPlayers('updateFaction', '', ['faction' => Factions::get($from)]);
		self::notifyAllPlayers('updateFaction', '', ['faction' => Factions::get($on)]);
//* -------------------------------------------------------------------------------------------------------- */
		if (!$automa) $this->gamestate->nextState('continue');
	}
	function acRejectPeace(string $on, string $from, $automa = false)
	{
		if (!$automa)
		{
			$pending = Factions::getStatus($on, 'peace') ?? [];
			if (!in_array($from, $pending)) throw new BgaUserException(self::_('Peace agreement not found !'));
			unset($pending[array_search($from, $pending)]);
			if ($pending) Factions::setStatus($on, 'peace', array_values($pending));
			else Factions::setStatus($on, 'peace');
		}
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyPlayer(Factions::getPlayer($from), 'msg', clienttranslate('${player_name} refuses peace'), ['player_name' => Factions::getName($on)]);
		self::notifyPlayer(Factions::getPlayer($on), 'msg', clienttranslate('You refuse peace with ${player_name}'), ['player_name' => Factions::getName($from)]);
//* -------------------------------------------------------------------------------------------------------- */
	}
	function acRemoteViewing(string $color, bool $ancientPyramids, string $type, string $id)
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('remoteViewing');
		if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
		if (!array_key_exists('view', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
//
		if ($ancientPyramids)
		{
			$relicID = Counters::getRelic(ANCIENTPYRAMIDS);
			if ($relicID)
			{
				if (!Counters::getStatus($relicID, 'available')) throw new BgaVisibleSystemException('Ancient Pyramid already used');
				if (Counters::getStatus($relicID, 'owner') !== $color) throw new BgaVisibleSystemException('Invalid owner');
				Counters::setStatus($relicID, 'available');
			}
			else throw new BgaVisibleSystemException('Invalid relic : Ancient Pyramid');
		}
		else
		{
			if (!$this->possible['view']) throw new BgaVisibleSystemException('No more view');
			Factions::setStatus($color, 'view', $this->possible['view'] - 1);
		}
//
		self::reveal($color, $type, $id, $ancientPyramids, true);
		self::DbQuery("DELETE FROM `undo` WHERE color = '$color'");
//
		$this->gamestate->nextState('continue');
	}
	function acPlanetaryDeathRay(string $color, string $type, int $id, bool $automa = false)
	{
		$player_id = Factions::getPlayer($color);
//
		if (!$automa)
		{
			$this->checkAction('planetaryDeathRay');
			if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
			if (!array_key_exists('planetaryDeathRay', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
			if (!array_key_exists('planetaryDeathRayTargets', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		}
//
		$PlanetaryDeathRay = Counters::getRelic(PLANETARYDEATHRAY);
		if ($PlanetaryDeathRay)
		{
			if (!Counters::getStatus($PlanetaryDeathRay, 'available')) throw new BgaVisibleSystemException('Planetary Death Ray already used');
			if (Counters::getStatus($PlanetaryDeathRay, 'owner') !== $color) throw new BgaVisibleSystemException('Invalid owner');
			Counters::setStatus($PlanetaryDeathRay, 'available');
		}
		else throw new BgaVisibleSystemException('Invalid relic : Planetary Death Ray');
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', clienttranslate('${player_name} uses <B>Planetary Death Ray</B>'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
		switch ($type)
		{
//
			case 'ship':
//
				$ship = Ships::get($id);
				if (!$ship) throw new BgaVisibleSystemException('Invalid ship: ' . $id);
//
				$location = $ship['location'];
				if (!in_array($location, $this->possible['planetaryDeathRayTargets'])) throw new BgaVisibleSystemException('Invalid target: ' . $location);
				if (!in_array($ship['color'], Factions::atWar($color))) throw new BgaUserException(self::_('You must be at war to use Planetary Death Ray'));
//
				$defenseGrid = Counters::getRelic(DEFENSEGRID);
				if ($defenseGrid && Counters::get($defenseGrid)['location'] === $location && Counters::getStatus($defenseGrid, 'owner')) throw new BgaUserException(self::_('Ships are protected by Defense Grid'));
//
				switch ($ship['fleet'])
				{
					case 'ship':
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('removeShip', '', ['ship' => Ships::get($id)]);
//* -------------------------------------------------------------------------------------------------------- */
						Ships::destroy($id);
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('updateFaction', '<div style="color:black;background:#${color};">${LOG}</div>', ['color' => $ship['color'],
							'LOG' => ['log' => clienttranslate('${ships} ship piece(s) destroyed'), 'args' => ['ships' => 1]],
							'faction' => ['color' => $ship['color'], 'ships' => 16 - sizeof(Ships::getAll($ship['color'], 'ship'))]
						]);
//* -------------------------------------------------------------------------------------------------------- */
						break;
//
					case 'fleet':
//
						$Fleet = Ships::getStatus($id, 'fleet');
						Ships::setStatus($id, 'ships', intval(Ships::getStatus($id, 'ships')) - 1);
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('msg', '<div style="color:black;background:#${color};">${LOG}</div>', ['color' => $ship['color'],
							'LOG' => ['log' => clienttranslate('${ships} ship(s) destroyed in a fleet'), 'args' => ['ships' => 1]]]);
//* -------------------------------------------------------------------------------------------------------- */
						if (intval(Ships::getStatus($id, 'ships')) === 0)
						{
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyAllPlayers('removeShip', clienttranslate('A fleet is removed ${GPS}'), ['GPS' => $location, 'ship' => $ship]);
//* -------------------------------------------------------------------------------------------------------- */
							$ship['location'] = 'stock';
							Ships::setLocation($id, $ship['location']);
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyAllPlayers('placeShip', '', ['ship' => $ship]);
//* -------------------------------------------------------------------------------------------------------- */
						}
						if (Factions::getPlayer($ship['color']) > 0) self::notifyPlayer(Factions::getPlayer($ship['color']), 'revealShip', '', ['ship' => ['id' => $id, 'fleet' => $Fleet, 'ships' => Ships::getStatus($id, 'ships')]]);
						break;
				}
//
// WAR Second : All players score 1 DP for every ship of opponents they destroy
//
				if (self::getGameStateValue('galacticStory') == WAR && self::ERA() === 'Second')
				{
					{
//* -------------------------------------------------------------------------------------------------------- */
						if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('All players score 1 DP for every ship of opponents they destroy (also as losers of a battle)')]);
//* -------------------------------------------------------------------------------------------------------- */
						$player_id = Factions::getPlayer($color);
						if ($player_id > 0)
						{
							$DP = 1;
							if ($DP)
							{
								self::gainDP($color, $DP);
								self::incStat($DP, 'DP_GS', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
								self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} scores ${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
							}
						}
					}
				}
//
				break;
//
			case 'disc':
//
				$populationDisc = Counters::get($id);
				if (!$populationDisc) throw new BgaVisibleSystemException('Invalid counter: ' . $id);
				if ($populationDisc['type'] !== 'populationDisc') throw new BgaVisibleSystemException('Invalid counter type: ' . $populationDisc['type']);
//
				$location = $populationDisc['location'];
				if (!in_array($location, $this->possible['planetaryDeathRayTargets'])) throw new BgaVisibleSystemException('Invalid target: ' . $location);
				if (!in_array($populationDisc['color'], Factions::atWar($color))) throw new BgaUserException(self::_('You must be at war to use Planetary Death Ray'));
//
				$defenseGrid = Counters::getRelic(DEFENSEGRID);
				if ($defenseGrid && Counters::get($defenseGrid)['location'] === $location && Counters::getStatus($defenseGrid, 'owner')) throw new BgaUserException(self::_('Populations are protected by Defense Grid'));
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('removeCounter', '', ['counter' => $populationDisc]);
//* -------------------------------------------------------------------------------------------------------- */
				if (Factions::getTechnology($populationDisc['color'], 'Spirituality') < 6) self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $populationDisc['color'], 'population' => Factions::gainPopulation($populationDisc['color'], -1)]]);
//* -------------------------------------------------------------------------------------------------------- */
				Counters::destroy($id);
//
// MIGRATIONS Second : All players score 1 DP for every population of another player they remove from a star
//
				if (self::getGameStateValue('galacticStory') == MIGRATIONS && self::ERA() === 'Second' && $player_id > 0)
				{
					if (intval($location[0]) !== Factions::getHomeStar($color) && in_array($type, [LIBERATE, CONQUERVS]))
					{
//* -------------------------------------------------------------------------------------------------------- */
						if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('All players score 1 DP for every population of another player they remove from a star')]);
//* -------------------------------------------------------------------------------------------------------- */
						$DP = 1;
						self::gainDP($color, $DP);
						self::incStat($DP, 'DP_GS', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} scores ${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
					}
				}
//* -------------------------------------------------------------------------------------------------------- */
				$sector = Sectors::get($location[0]);
				$rotated = Sectors::rotate(substr($location, 2), Sectors::getOrientation($location[0]));
				self::notifyAllPlayers('msg', clienttranslate('${GPS} ${PLANET} loses one <B>population(s)</B>'), ['GPS' => $location,
					'PLANET' => [
						'log' => '<span style = "color:#' . $populationDisc['color'] . ';font-weight:bold;">${PLANET}</span>',
						'i18n' => ['PLANET'], 'args' => ['PLANET' => $this->SECTORS[$sector][$rotated]]
				]]);
//* -------------------------------------------------------------------------------------------------------- */
				self::starsBecomingUninhabited($location);
//
				break;
//
			default:
//
				throw new BgaVisibleSystemException('Invalid planetaryDeathRay: ' . $type);
		}
//
		self::DbQuery("DELETE FROM `undo` WHERE color = '$color'");
//
		self::updateScoring();
		if (!$automa) $this->gamestate->nextState('continue');
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
		if ($player_id > 0)
		{
			$undoID = self::getUniqueValueFromDB("SELECT COALESCE(MAX(undoID), 0) FROM `undo` WHERE color = '$color'") + 1;
			foreach ($ships as $ship)
			{
				$json = self::escapeStringForDB(json_encode(Ships::get($ship)));
				self::DbQuery("INSERT INTO `undo` VALUES ($undoID,$ship,'$color','move','$json')");
			}
		}
//
		$sector = Sectors::get($location[0]);
		$hexagon = substr($location, 2);
		$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($location[0]));
//
		if (array_key_exists($rotated, $this->SECTORS[$sector]))
		{
			if (Ships::isShip($color, $ships[0]))
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('${N} ship(s) move to ${PLANET} ${GPS}'), ['GPS' => $location, 'N' => sizeof($ships), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated]]);
//* -------------------------------------------------------------------------------------------------------- */
			else
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('A fleet moves to ${PLANET} ${GPS}'), ['GPS' => $location, 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
		else
		{
			if (Ships::isShip($color, $ships[0]))
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('${N} ship(s) moves ${GPS}'), ['GPS' => $location, 'N' => sizeof($ships)]);
//* -------------------------------------------------------------------------------------------------------- */
			else
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('A fleet is moving ${GPS}'), ['GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		$locations = [$location];
		while ($from = $this->possible['move'][$ships[0]][$locations[0]]['from']) array_unshift($locations, $from);
//
		foreach ($locations as $next_location)
		{
			if (array_key_exists('wormhole', $this->possible['move'][$ships[0]][$next_location]))
			{
				if (Factions::getTechnology($color, 'Spirituality') < 5)
				{
					$toBlock = [];
					foreach (Factions::atPeace($color) as $otherColor) if (Factions::getAlignment($otherColor) === 'STS' && Ships::getAtLocation($next_location, $otherColor)) $toBlock[] = Factions::getPlayer($otherColor);
					if ($toBlock)
					{
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('msg', clienttranslate('${GPS} ${player_name} tries to use a wormhole'), ['GPS' => $next_location, 'player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
						$this->gamestate->setPlayersMultiactive($toBlock, 'end', true);
						$params = func_get_args();
						Factions::setStatus($color, 'action', ['name' => 'wormhole', 'from' => $location, 'to' => $next_location, 'function' => __FUNCTION__, 'params' => $params]);
						return $this->gamestate->nextState('blockMovement');
					}
				}
			}
			if (array_key_exists('stargate', $this->possible['move'][$ships[0]][$next_location]))
			{
				if (Factions::getTechnology($color, 'Spirituality') < 5)
				{
					$toBlock = [];
					foreach (Factions::atPeace($color) as $otherColor) if (Factions::getAlignment($otherColor) === 'STS' && (Ships::getAtLocation($location, $otherColor) || Ships::getAtLocation($next_location, $otherColor))) $toBlock[] = Factions::getPlayer($otherColor);
					if ($toBlock)
					{
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('msg', clienttranslate('${GPS} ${player_name} tries to use a wormhole'), ['GPS' => $next_location, 'player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
						$this->gamestate->setPlayersMultiactive($toBlock, 'end', true);
						$params = func_get_args();
						Factions::setStatus($color, 'action', ['name' => 'stargate', 'from' => $location, 'to' => $next_location, 'function' => __FUNCTION__, 'params' => $params]);
						return $this->gamestate->nextState('blockMovement');
					}
				}
			}
//
			self::notifyAllPlayers('moveShips', '', ['ships' => $ships, 'location' => $next_location, 'old' => $location]);
			$location = $next_location;
//
			foreach ($ships as $ship)
			{
				$MP = $this->possible['move'][$ship][$location]['MP'];
				Ships::setMP($ship, $MP);
				Ships::setActivation($ship, $MP == 0 ? 'done' : 'yes');
				Ships::setLocation($ship, $location);
			}
//
		}
//
		self::updateScoring();
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
				if ($status['fleet'] === 'fleet')
				{
					foreach (Factions::list(false) as $otherColor)
					{
//* -------------------------------------------------------------------------------------------------------- */
						if ($otherColor === $color) self::notifyPlayer(Factions::getPlayer($otherColor), 'revealShip', '', ['ship' => ['id' => $ship, 'fleet' => Ships::getStatus($ship, 'fleet'), 'ships' => Ships::getStatus($ship, 'ships')]]);
						else if (Ships::getStatus($ship, 'fleet') === 'D') self::notifyPlayer(Factions::getPlayer($otherColor), 'revealShip', '', ['ship' => ['id' => $ship, 'fleet' => 'D', 'ships' => '?']]);
//* -------------------------------------------------------------------------------------------------------- */
					}
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
				if ($status['fleet'] === 'fleet')
				{
					foreach (Factions::list(false) as $otherColor)
					{
//* -------------------------------------------------------------------------------------------------------- */
						if ($otherColor === $color) self::notifyPlayer(Factions::getPlayer($otherColor), 'revealShip', '', ['ship' => ['id' => $ship, 'fleet' => Ships::getStatus($ship, 'fleet'), 'ships' => Ships::getStatus($ship, 'ships')]]);
						else if (Ships::getStatus($ship, 'fleet') === 'D') self::notifyPlayer(Factions::getPlayer($otherColor), 'revealShip', '', ['ship' => ['id' => $ship, 'fleet' => 'D', 'ships' => '?']]);
//* -------------------------------------------------------------------------------------------------------- */
					}
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
		self::updateScoring();
		$this->gamestate->nextState('continue');
	}
	function acPass(string $color, bool $automa = false): void
	{
		$player_id = Factions::getPlayer($color);
//
		if (!$automa)
		{
			$this->checkAction('pass');
			if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
		}
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
		self::updateScoring();
		self::triggerAndNextState('continue');
	}
	function acHomeStarEvacuation(string $color, $location, bool $automa = false)
	{
		$player_id = Factions::getPlayer($color);
//
		if (!$automa)
		{
			$this->checkAction('homeStarEvacuation');
			if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
		}
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
			self::triggerEvent(HOMESTAREVACUATION, $color);
			return self::triggerAndNextState('continue');
		}
//
		if (!array_key_exists($player_id, $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (!array_key_exists('evacuate', $this->possible[$player_id])) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (!in_array($location, $this->possible[$player_id]['evacuate'])) throw new BgaVisibleSystemException('Invalid location: ' . $location);
//
		if (Factions::getTechnology($color, 'Spirituality') < 5)
		{
			$toBlock = [];
			foreach (Factions::list(false) as $otherColor) if (Factions::getAlignment($otherColor) === 'STS' && in_array($otherColor, Factions::atPeace($color)) && Ships::getAtLocation($location, $otherColor)) $toBlock[] = Factions::getPlayer($otherColor);
		}
		if ($toBlock)
		{
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${player_name} tries to evacuate home star ${GPS}'), ['player_name' => Factions::getName($color), 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
			$this->gamestate->setPlayersMultiactive(array_unique($toBlock), 'end', true);
			Factions::setStatus($color, 'action', ['name' => 'homeStarEvacuation', 'locations' => [$location], 'function' => __FUNCTION__, 'params' => func_get_args()]);
			return $this->gamestate->nextState('blockAction');
		}
		return self::acHomeStarEvacuationValidated($color, $location, $automa);
	}
	function acHomeStarEvacuationValidated(string $color, $location, bool $automa = false)
	{
		$player_id = Factions::getPlayer($color);
//
		$sector = Sectors::get($location[0]);
		$hexagon = substr($location, 2);
		$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($location[0]));
//
		if (array_key_exists($rotated, $this->SECTORS[$sector]))
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${player_name} evacuates near ${PLANET} ${GPS}'), ['player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated], 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
		else
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${player_name} evacuates ${GPS}'), ['player_name' => Factions::getName($color), 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
//
		$homeStar = Ships::getHomeStar($color);
		$previousLocation = Ships::getHomeStarLocation($color);
		Ships::setLocation($homeStar, $location);
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('homeStarEvacuation', '', ['homeStar' => $homeStar, 'location' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
		foreach (array_map(['Counters', 'get'], Counters::getAtLocation($location, 'populationDisc')) as $populationDisc)
		{
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('removeCounter', '', ['counter' => $populationDisc]);
//* -------------------------------------------------------------------------------------------------------- */
			if (Factions::getTechnology($populationDisc['color'], 'Spirituality') < 6) self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $populationDisc['color'], 'population' => Factions::gainPopulation($populationDisc['color'], -1)]]);
//* -------------------------------------------------------------------------------------------------------- */
			Counters::destroy($populationDisc['id']);
		}
		if (Factions::getStatus($color, 'evacuate') === 'voluntary')
		{
			$sector = Sectors::get($previousLocation[0]);
			$rotated = Sectors::rotate(substr($previousLocation, 2), Sectors::getOrientation($previousLocation[0]));
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${GPS} ${PLANET} gains ${population} <B>population(s)</B>'), [
				'PLANET' => [
					'log' => '<span style = "color:#' . $color . ';font-weight:bold;">${PLANET}</span>',
					'i18n' => ['PLANET'], 'args' => ['PLANET' => $this->SECTORS[$sector][$rotated]]
				],
				'GPS' => $previousLocation, 'population' => 1]);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'population' => Factions::gainPopulation($color, 1)]]);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('placeCounter', '', ['counter' => Counters::get(Counters::create($color, 'populationDisc', $previousLocation))]);
//* -------------------------------------------------------------------------------------------------------- */
		}
		Factions::setStatus($color, 'evacuate');
//
		self::starsBecomingUninhabited($previousLocation);
//
		if ($player_id > 0 && Factions::getEmergencyReserve($color)) self::triggerEvent(EMERGENCYRESERVE, $color);
//
		self::updateScoring();
		if (!$automa) self::triggerAndNextState('continue');
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
		$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($location[0]));
//
		if (array_key_exists($rotated, $this->SECTORS[$sector]))
		{
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${player_name} starts a battle near ${PLANET} ${GPS}'), ['player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated], 'GPS' => $location]);
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
		if (!$automa && $player_id > 0)
		{
			$this->checkAction('retreat');
			if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
			if ($location && !in_array($location, $this->possible)) throw new BgaVisibleSystemException('Invalid location: ' . $location);
		}
//
		$attacker = Factions::getActive();
		$winner = Factions::getStatus($attacker, 'winner');
//
		if (!$location)
		{
			if ($winner) throw new BgaVisibleSystemException('Retreat is mandatory');
//
			Factions::setStatus($color, 'retreat', 'no');
			return $this->gamestate->nextState('continue');
		}
//
		$combatLocation = Factions::getStatus($attacker, 'combat');
//
		$sector = Sectors::get($location[0]);
		$hexagon = substr($location, 2);
		$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($location[0]));
//
		if ($this->gamestate->state()['name'] === 'retreatE')
		{
			if (array_key_exists($rotated, $this->SECTORS[$sector]))
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('${player_name} evades to ${PLANET} ${GPS}'), ['player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated], 'GPS' => $location]);
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
			if (array_key_exists($rotated, $this->SECTORS[$sector]))
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('${player_name} retreats to ${PLANET} ${GPS}'), ['player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated], 'GPS' => $location]);
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
		foreach (array_diff(array_merge(Counters::getAtLocation($location, 'star'), Counters::getAtLocation($location, 'relic')), Counters::listRevealed($color)) as $counter) self::reveal($color, 'counter', $counter);
//
// RIVALRY Second : Every time players “retreat before combat” they lose 2 DP
//
		if (self::getGameStateValue('galacticStory') == RIVALRY && self::ERA() === 'Second' && $player_id > 0)
		{
			if (!$winner)
			{
//* -------------------------------------------------------------------------------------------------------- */
				if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('Every time players “retreat before combat” they lose 2 DP')]);
//* -------------------------------------------------------------------------------------------------------- */
				$DP = -2;
				self::gainDP($color, $DP);
				self::incStat($DP, 'DP_GS', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} loses ${DP} DP'), ['DP' => -$DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
			}
		}
//
		self::updateScoring();
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
							if (intval(Ships::getStatus($fleetID, 'ships')) < 0) throw new BgaVisibleSystemException("No more ship mini to destroy in $Fleet for $color");
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
							if (Factions::getPlayer($color) > 0) self::notifyPlayer(Factions::getPlayer($color), 'revealShip', '', ['ship' => ['id' => $fleetID, 'fleet' => $Fleet, 'ships' => Ships::getStatus($fleetID, 'ships')]]);
							break;
//
						case 'ships':
//
							$ships = Ships::getAtLocation($location, $color, 'ship');
							if (!$ships) throw new BgaVisibleSystemException("No more ship minis to destroy in $Fleet for $color");
							for ($i = 0; $i < $count; $i++)
							{
								$shipID = array_pop($ships);
								if (!$shipID) throw new BgaVisibleSystemException("No more ship minis to destroy in $Fleet for $color");
//* -------------------------------------------------------------------------------------------------------- */
								self::notifyAllPlayers('removeShip', '', ['ship' => Ships::get($shipID)]);
//* -------------------------------------------------------------------------------------------------------- */
								Ships::destroy($shipID);
							}
//* -------------------------------------------------------------------------------------------------------- */
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyAllPlayers('updateFaction', '<div style="color:black;background:#${color};">${LOG}</div>', ['color' => $color,
								'LOG' => ['log' => clienttranslate('${ships} ship piece(s) destroyed'), 'args' => ['ships' => $count]],
								'faction' => ['color' => $color, 'ships' => 16 - sizeof(Ships::getAll($color, 'ship'))]
							]);
//* -------------------------------------------------------------------------------------------------------- */
							break;
					}
				}
			}
		}
//
// WAR Second : All players score 1 DP for every ship of opponents they destroy (also as losers of a battle)
// Multiple players on a side in a battle each score for all opposing ships destroyed
//
		if (self::getGameStateValue('galacticStory') == WAR && self::ERA() === 'Second')
		{
			{
//* -------------------------------------------------------------------------------------------------------- */
				if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('All players score 1 DP for every ship of opponents they destroy (also as losers of a battle)')]);
//* -------------------------------------------------------------------------------------------------------- */
				$color = $attacker;
				$player_id = Factions::getPlayer($color);
				if ($player_id > 0)
				{
					$DP = 0;
					foreach ($defenders as $defender) $DP += array_sum($toDestroy[$defender]);
					if ($DP)
					{
						self::gainDP($color, $DP);
						self::incStat($DP, 'DP_GS', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} scores ${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
					}
				}
				foreach ($defenders as $color)
				{
					$player_id = Factions::getPlayer($color);
					if ($player_id > 0)
					{
						$DP = array_sum($toDestroy[$attacker]);
						if ($DP)
						{
							self::gainDP($color, $DP);
							self::incStat($DP, 'DP_GS', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} scores ${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
						}
					}
				}
			}
		}
//
		self::updateScoring();
		$this->gamestate->nextState('continue');
	}
	function acSelectCounters(string $color, array $counters): void
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('selectCounters');
		if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		if (!self::getGameStateValue('GODMODE'))
		{
			$oval = $square = 0;
			foreach ($counters as $counter)
			{
				if (in_array($counter, ['research', 'growPopulation', 'gainStar', 'gainStar', 'buildShips', 'switchAlignment'])) $oval++;
				else $square++;
			}
			if ($oval < $this->possible[$player_id]['oval']) throw new BgaVisibleSystemException("Invalid number of oval counters: $oval");
			if ($oval > $this->possible[$player_id]['oval'] + $this->possible[$player_id]['additional']) throw new BgaVisibleSystemException('Invalid number of oval counters: ' . $oval);
			if ($square < 1 || $square > $this->possible[$player_id]['square']) throw new BgaVisibleSystemException("Invalid number of square counters: $square");
		}
//
		if (Factions::getStatus($color, 'central')) array_push($counters, 'gainStar+');
		Factions::setStatus($color, 'counters', array_values($counters));
//
		$this->gamestate->setPlayerNonMultiactive($player_id, 'next');
	}
	function acBlockAction(string $color, bool $blocked)
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('blockAction');
		if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		$blockedColor = Factions::getActive();
		if ($blocked)
		{
			self::acDeclareWar($color, $blockedColor, true);
			self::DbQuery("DELETE FROM `undo` WHERE color = '$blockedColor'");
		}
//
		if ($this->gamestate->setPlayerNonMultiactive($player_id, 'blockAction'))
		{
			$action = Factions::getStatus($blockedColor, 'action');
			Factions::setStatus($blockedColor, 'action');
//
			$blocked = false;
			foreach ($action['locations'] as $location) if (Counters::isBlocked($blockedColor, $location)) $blocked = true;
			if (!$blocked) return call_user_func("self::${action['function']}Validated", ...$action['params']);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', 'Growth action is blocked', []);
//* -------------------------------------------------------------------------------------------------------- */
			self::updateScoring();
			$this->gamestate->nextState('continue');
		}
	}
	function acBlockMovement(string $color, bool $blocked)
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('blockMovement');
		if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		$blockedColor = Factions::getActive();
		if ($blocked)
		{
			self::acDeclareWar($color, $blockedColor, true);
			self::DbQuery("DELETE FROM `undo` WHERE color = '$blockedColor'");
		}
//
		if ($this->gamestate->setPlayerNonMultiactive($player_id, 'blockMovement'))
		{
			$action = Factions::getStatus($blockedColor, 'action');
			Factions::setStatus($blockedColor, 'action');
//
			$location = $action['to'];
			$blocked = Counters::isBlocked(Factions::getActive(), $location);
//* -------------------------------------------------------------------------------------------------------- */
			if ($blocked) self::notifyAllPlayers('msg', 'Movement is blocked', []);
//* -------------------------------------------------------------------------------------------------------- */
			self::argMovement();
//
			if ($action['name'] === 'stargate' && $blocked) $location = $action['from'];
			else
			{
				$ships = $action['params'][2];
				self::notifyAllPlayers('moveShips', '', ['ships' => $ships, 'location' => $location, 'old' => $action['from']]);
//
				foreach ($ships as $ship)
				{
					$MP = $this->possible['move'][$ship][$location]['MP'];
					if (Counters::isBlocked(Factions::getActive(), $location)) $MP = 0;
					Ships::setMP($ship, $MP);
					Ships::setActivation($ship, $MP == 0 ? 'done' : 'yes');
					Ships::setLocation($ship, $location);
				}
			}
//
			self::updateScoring();
			$this->gamestate->nextState('continue');
		}
	}
	function acSwitchAlignment(string $color)
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('switchAlignment');
		if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
		if (!array_key_exists('counters', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
//
// ANCHARA SPECIAL STO & STS: If you have chosen the Switch Alignment growth action counter,
// then on your turn of the growth phase, you may select and execute an additional, unused growth action counter at no cost.
// To do Research, you must have already chosen a technology for your square counter choice
//
		if (Factions::getTechnology($color, 'Spirituality') < 5) self::switchAlignment($color);
//* -------------------------------------------------------------------------------------------------------- */
		else self::notifyAllPlayers('msg', clienttranslate('${player_name} can not switch alignment'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
		$counters = Factions::getStatus($color, 'counters');
		unset($counters[array_search('switchAlignment', $counters)]);
		Factions::setStatus($color, 'counters', array_values($counters));
		Factions::setStatus($color, 'used', array_values(array_merge(Factions::getStatus($color, 'used'), ['switchAlignment'])));
//
		self::updateScoring();
		$this->gamestate->nextState('continue');
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
		$counters = Factions::getStatus($color, 'counters');
		unset($counters[array_search('research', $counters)]);
		if (!in_array('research', Factions::getStatus($color, 'used'))) Factions::setStatus($color, 'used', array_values(array_merge(Factions::getStatus($color, 'used'), ['research'])));
//
		foreach ($technologies as $technology) if ($technology !== 'Robotics') Factions::setStatus($color, 'otherTechnology', $technology);
//
		foreach ($technologies as $technology)
		{
			$level = self::gainTechnology($color, $technology);
//
// GREYS SPECIAL STO & STS: When you research a technology at level 1 you increase it to level 3
//
			if (Factions::getStarPeople($color) === 'Greys' && $level === 2) self::gainTechnology($color, $technology);
//
			unset($counters[array_search($technology, $counters)]);
			if ($level) Factions::setStatus($color, 'used', array_values(array_merge(Factions::getStatus($color, 'used'), [$technology])));
		}
//
		Factions::setStatus($color, 'counters', array_values($counters));
//
		self::updateScoring();
		self::triggerAndNextState('advancedFleetTactics');
	}
	function acResearchPlus($color, $technology, $otherColor, $growthAction)
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('researchPlus');
		if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		$technologies = Factions::getStatus($color, 'researchPlus');
		array_pop($technologies);
		if ($technologies) Factions::setStatus($color, 'researchPlus', $technologies);
		else Factions::setStatus($color, 'researchPlus');
//
		if ($technology)
		{
			if (!array_key_exists($technology, $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
//
			switch ($technology)
			{
				case 'Military':
//
					if (!array_key_exists($otherColor, $this->possible[$technology])) throw new BgaVisibleSystemException("Invalid color : $otherColor");
					if (!in_array($growthAction, $this->possible[$technology][$otherColor])) throw new BgaVisibleSystemException("Invalid growthAction : $growthAction");
					if ($growthAction === 'buildShips' && Factions::getPlayer($otherColor) <= 0) throw new BgaUserException(self::_('You can\'t cancel a spawn ships growth action'));
					if (Factions::getTechnology($otherColor, 'Spirituality') >= 5) throw new BgaUserException(self::_('Spirituality level 5 or 6 are imune to this'));
//
					$counters = Factions::getStatus($otherColor, 'counters');
					unset($counters[array_search($growthAction, $counters)]);
					Factions::setStatus($otherColor, 'counters', array_values($counters));
					Factions::setStatus($otherColor, 'used', array_values(array_merge(Factions::getStatus($otherColor, 'used'), [$growthAction])));
//
					break;
//
				case 'Spirituality':
//
					if (!in_array($growthAction, $this->possible['counters'])) throw new BgaVisibleSystemException("Invalid growthAction : $growthAction");
					Factions::setStatus($color, 'counters', array_values(array_merge(Factions::getStatus($color, 'counters'), [$growthAction])));
//
					break;
//
				default:
					throw new BgaVisibleSystemException("Invalid technology : $technology");
			}
		}
//
		self::updateScoring();
		self::triggerAndNextState('end');
	}
	function acGainStar(string $color, string $location, bool $center = false, bool $automa = false)
	{
		$player_id = Factions::getPlayer($color);
//
		$counter = $center ? 'gainStar+' : 'gainStar';
//
		if (!$automa)
		{
			$this->checkAction('gainStar');
			if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
			if (!array_key_exists('counters', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
			if (!in_array($counter, $this->possible['counters'])) throw new BgaVisibleSystemException('Invalid action: ' . 'gainStar');
			if ($counter === 'gainStar+' && $location[0] !== '0') throw new BgaVisibleSystemException("Invalid location: $location");
			if (!array_key_exists($location, $this->possible['gainStar'])) throw new BgaVisibleSystemException("Invalid location: $location");
		}
//
		$ships = Ships::getAtLocation($location, $color);
		if (!$ships) throw new BgaVisibleSystemException('No ships at location: ' . $location);
//
		[$type, $SHIPS, $population] = Counters::gainStar($color, $location, $automa, $center);
		if (!$type)
		{
			if ($SHIPS > 0) throw new BgaUserException(sprintf(self::_('You need at least %d ships to gain this star'), $SHIPS));
			else throw new BgaUserException(self::_('You can\'t gain this star'));
		}
//
		if ($type === LIBERATE || $type === CONQUERVS)
		{
			$otherColor = Counters::getOwner($location);
			if (!$otherColor) throw new BgaVisibleSystemException('No population to liberate/conquer at location: ' . $location);
			if (!in_array($otherColor, Factions::atWar($color)))
			{
				self::acDeclareWar($color, $this->possible['gainStar'][$location], true);
				return self::acGainStar($color, $location, $center, $automa);
			}
//			if (!in_array ($otherColor, Factions::atWar ($color))) throw new BgaUserException(self::_('You must be at war with star owner'));
		}
//
// An STO player can only declare war on STS players and only to block the subjugation or conquest of a star with “innocent” population
// (i.e., primitive or advanced neutral stars or those of STO players)
// Multiple STO players may use the same opportunity to declare war, even though one would be enough to block it
//
		if (Factions::getTechnology($color, 'Spirituality') < 5)
		{
			$toBlock = [];
			foreach (Factions::list(false) as $otherColor)
			{
				if (in_array($otherColor, Factions::atPeace($color)))
				{
					if (Ships::getAtLocation($location, $otherColor))
					{
						if (Factions::getAlignment($otherColor) === 'STO')
						{
							if ($type === SUBJUGATE || $type === CONQUER) $toBlock[] = Factions::getPlayer($otherColor);
							if ($type === CONQUERVS && Factions::getAlignment($color) === 'STO') $toBlock[] = Factions::getPlayer($otherColor);
						}
						else $toBlock[] = Factions::getPlayer($otherColor);
					}
				}
			}
			if ($toBlock)
			{
				$sector = Sectors::get($location[0]);
				$rotated = Sectors::rotate(substr($location, 2), Sectors::getOrientation($location[0]));
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('${GPS} ${player_name} tries to gain ${PLANET}'), ['GPS' => $location, 'SHIPS' => $SHIPS,
					'player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated]]);
//* -------------------------------------------------------------------------------------------------------- */
				$this->gamestate->setPlayersMultiactive(array_unique($toBlock), 'end', true);
				Factions::setStatus($color, 'action', ['name' => 'gainStar', 'locations' => [$location], 'function' => __FUNCTION__, 'params' => func_get_args()]);
				return $this->gamestate->nextState('blockAction');
			}
		}
		return self::acGainStarValidated($color, $location, $center, $automa);
	}
	function acGainStarValidated(string $color, string $location, bool $center = false, bool $automa = false)
	{
		$player_id = Factions::getPlayer($color);
//
		$counter = $center ? 'gainStar+' : 'gainStar';
//
		[$type, $SHIPS, $population] = Counters::gainStar($color, $location, false, $center);
		if ($type === LIBERATE || $type === CONQUERVS)
		{
			$sizeOfPopulation = 0;
//
			$homeStar = Ships::getAtLocation($location, null, 'homeStar');
			if ($homeStar)
			{
				$sizeOfPopulation += 6;
				$otherColor = Ships::get($homeStar[0])['color'];
				Factions::setStatus($otherColor, 'evacuate', 'involuntary');
				if (Factions::getPlayer($otherColor) === 0)
				{
					self::argHomeStarEvacuation();
					shuffle($this->possible[0]['evacuate']);
					self::acHomeStarEvacuation($otherColor, $this->possible[0]['evacuate'][0], true);
				}
				else self::triggerEvent(HOMESTAREVACUATION, $otherColor);
			}
			$populations = Counters::getAtLocation($location, 'populationDisc');
			if ($populations)
			{
				$sizeOfPopulation += sizeof($populations);
				$otherColor = Counters::get($populations[0])['color'];
			}
//
			Factions::setStatus($color, 'steal', ['from' => [$otherColor], 'levels' => $sizeOfPopulation >= 6 ? 2 : 1]);
			if (Factions::getPlayer($color) > 0) self::triggerEvent(STEALTECHNOLOGY, $color);
			else
			{
				$steal = Factions::getStatus($color, 'steal');
				if ($steal)
				{
					['from' => $from, 'levels' => $levels] = $steal;
					Factions::setStatus($color, 'steal');
//
					$technologies = [];
					foreach ($from as $otherColor)
					{
						foreach (array_keys(Factions::TECHNOLOGIES) as $technology)
						{
							if ($technology === 'Spirituality' && Factions::getTechnology($color, $technology) >= 4) continue; // SLAVERS //
							if (Factions::getTechnology($color, $technology) < Factions::getTechnology($otherColor, $technology)) $technologies[] = $technology;
						}
					}
					if ($technologies)
					{
						$technologies = array_unique($technologies);
						shuffle($technologies);
						$technology = array_pop($technologies);
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('msg', clienttranslate('<B>${TECHNOLOGY} is stealed at ${player_name}</B>'), ['player_name' => Factions::getName($otherColor), 'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology]]);
//* -------------------------------------------------------------------------------------------------------- */
						for ($i = 0; $i < $levels; $i++) self::gainTechnology($color, $technology);
					}
				}
			}
//
			if (Factions::getEmergencyReserve($otherColor))
			{
				$starCount = [];
				foreach (Factions::list() as $_color) $starCount[$_color] = sizeof(Counters::getPopulations($_color));
				if ($starCount[$color] <= min($starCount) - 2) self::triggerEvent(EMERGENCYRESERVE, $otherColor);
			}
//--------------------------
// A-section: Acquisition //
//--------------------------
//
// Conquer/liberate 2 player owned stars on the same turn
// Play this card when this happens
//
			if ($player_id > 0)
			{
				$acquisition = Factions::getStatus($color, 'acquisition') ?? [];
				$acquisition[] = $otherColor;
				Factions::setStatus($color, 'acquisition', $acquisition);
			}
//
//--------------------------
// A-section: Acquisition //
//--------------------------
		}
//
		if ($player_id <= 0) Factions::setStatus($color, 'special', false);
//
		foreach (Counters::getAtLocation($location, 'star') as $star)
		{
			self::reveal('', 'counter', $star);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($star)]);
//* -------------------------------------------------------------------------------------------------------- */
			Counters::destroy($star);
		}
//
		$sector = Sectors::get($location[0]);
		$rotated = Sectors::rotate(substr($location, 2), Sectors::getOrientation($location[0]));
//
		switch ($type)
		{
			case COLONIZE:
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('${GPS} ${player_name} colonizes ${PLANET} with at least ${SHIPS} ship(s)'), ['GPS' => $location, 'SHIPS' => $SHIPS,
					'player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated]]);
//* -------------------------------------------------------------------------------------------------------- */
				break;
			case SUBJUGATE:
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('${GPS} ${player_name} subjugates ${PLANET} with at least ${SHIPS} ship(s)'), ['GPS' => $location, 'SHIPS' => $SHIPS,
					'player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated]]);
//* -------------------------------------------------------------------------------------------------------- */
				break;
			case LIBERATE:
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('${GPS} ${player_name} liberates ${PLANET} with at least ${SHIPS} ship(s)'), ['GPS' => $location, 'SHIPS' => $SHIPS,
					'player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated]]);
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
					self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains ${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
				}
				break;
			case CONQUER:
			case CONQUERVS:
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('${GPS} ${player_name} conquers ${PLANET} with at least ${SHIPS} ship(s)'), ['GPS' => $location, 'SHIPS' => $SHIPS,
					'player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated]]);
//* -------------------------------------------------------------------------------------------------------- */
				break;
			case ALLY:
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('${GPS} ${player_name} allies oneself with ${PLANET}'), ['GPS' => $location,
					'player_name' => Factions::getName($color), 'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated]]);
//* -------------------------------------------------------------------------------------------------------- */
				break;
		}
//
		foreach (array_map(['Counters', 'get'], Counters::getAtLocation($location, 'populationDisc')) as $populationDisc)
		{
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('removeCounter', '', ['counter' => $populationDisc]);
//* -------------------------------------------------------------------------------------------------------- */
			if (Factions::getTechnology($populationDisc['color'], 'Spirituality') < 6) self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $populationDisc['color'], 'population' => Factions::gainPopulation($populationDisc['color'], -1)]]);
//* -------------------------------------------------------------------------------------------------------- */
			Counters::destroy($populationDisc['id']);
		}
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('msg', clienttranslate('${GPS} ${PLANET} gains ${population} <B>population(s)</B>'), [
			'PLANET' => [
				'log' => '<span style = "color:#' . $color . ';font-weight:bold;">${PLANET}</span>',
				'i18n' => ['PLANET'], 'args' => ['PLANET' => $this->SECTORS[$sector][$rotated]]
			],
			'GPS' => $location, 'population' => $population]);
//* -------------------------------------------------------------------------------------------------------- */
		for ($i = 0; $i < $population; $i++)
		{
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'population' => Factions::gainPopulation($color, 1)]]);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('placeCounter', '', ['counter' => Counters::get(Counters::create($color, 'populationDisc', $location))]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		$newShips = 0;
		$relics = Counters::getAtLocation($location, 'relic');
		foreach ($relics as $relic)
		{
			self::reveal('', 'counter', $relic);
//* -------------------------------------------------------------------------------------------------------- */
//				self::notifyAllPlayers('msg', clienttranslate('<B>${RELIC}</B> is found ${GPS}'), [
//					'i18n' => ['RELIC'], 'RELIC' => $this->RELICS[Counters::getStatus($relic, 'back')],
//					'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
			switch (Counters::getStatus($relic, 'back'))
			{
//
				case ANCIENTPYRAMIDS: // Ancient Pyramids
//
					Counters::setStatus($relic, 'owner', $color);
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${GPS} ${player_name} take control of <B>${RELIC}</B>'), ['GPS' => $location,
						'player_name' => Factions::getName($color), 'i18n' => ['RELIC'], 'RELIC' => $this->RELICS[Counters::getStatus($relic, 'back')]]);
//* -------------------------------------------------------------------------------------------------------- */
					break;
//
				case ANCIENTTECHNOLOGYGENETICS: // Ancient Technology: Genetics
//
					self::gainTechnology($color, 'Genetics');
					self::notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
					Counters::destroy($relic);
					break;
//
				case ANCIENTTECHNOLOGYMILITARY: // Ancient Technology: Military
//
					self::gainTechnology($color, 'Military');
					self::notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
					Counters::destroy($relic);
					break;
//
				case ANCIENTTECHNOLOGYPROPULSION: // Ancient Technology: Propulsion
//
					self::gainTechnology($color, 'Propulsion');
					self::notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
					Counters::destroy($relic);
					break;
//
				case ANCIENTTECHNOLOGYROBOTICS: // Ancient Technology: Robotics
//
// YOWIES SPECIAL STO & STS: When you get the Ancient Technology: Robotics relic you get 2 ships at that star instead of a level (use the same restrictions as for the Buried Ships relic
//
					if (Factions::getStarPeople($color) === 'Yowies')
					{
						$newShips = 2;
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${SHIPS} additional ships</B> at ${PLANET}'), [
							'player_name' => Factions::getName($color), 'SHIPS' => $newShips,
							'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated],
						]);
//* -------------------------------------------------------------------------------------------------------- */
					}
					else self::gainTechnology($color, 'Robotics');
					self::notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
					Counters::destroy($relic);
					break;
//
				case ANCIENTTECHNOLOGYSPIRITUALITY: // Ancient Technology: Spirituality
//
					self::gainTechnology($color, 'Spirituality');
					self::notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
					Counters::destroy($relic);
					break;
//
				case BURIEDSHIPS: // Buried Ships
//
					$newShips = 3;
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${player_name} gains <B>${SHIPS} additional ships</B> at ${PLANET}'), [
						'player_name' => Factions::getName($color), 'SHIPS' => $newShips,
						'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated],
					]);
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('removeCounter', '', ['counter' => Counters::get($relic)]);
					Counters::destroy($relic);
					break;
//
				case PLANETARYDEATHRAY: // Planetary Death Ray
//
					Counters::setStatus($relic, 'owner', $color);
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${GPS} ${player_name} take control of <B>${RELIC}</B>'), ['GPS' => $location,
						'player_name' => Factions::getName($color), 'i18n' => ['RELIC'], 'RELIC' => $this->RELICS[Counters::getStatus($relic, 'back')]]);
//* -------------------------------------------------------------------------------------------------------- */
					break;
//
				case DEFENSEGRID: // Defense Grid
//
					Counters::setStatus($relic, 'owner', $color);
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${GPS} ${player_name} take control of <B>${RELIC}</B>'), ['GPS' => $location,
						'player_name' => Factions::getName($color), 'i18n' => ['RELIC'], 'RELIC' => $this->RELICS[Counters::getStatus($relic, 'back')]]);
//* -------------------------------------------------------------------------------------------------------- */
					break;
//
				case SUPERSTARGATE: // Super-Stargate
//
					Counters::setStatus($relic, 'owner', $color);
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${GPS} ${player_name} take control of <B>${RELIC}</B>'), ['GPS' => $location,
						'player_name' => Factions::getName($color), 'i18n' => ['RELIC'], 'RELIC' => $this->RELICS[Counters::getStatus($relic, 'back')]]);
//* -------------------------------------------------------------------------------------------------------- */
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
				if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('All players score 2 DP for every star outside of their home star sector that they take from another player')]);
//* -------------------------------------------------------------------------------------------------------- */
				$DP = 2;
				self::gainDP($color, $DP);
				self::incStat($DP, 'DP_GS', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} scores ${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
			}
		}
//
// MIGRATIONS Second : All players score 1 DP for every population of another player they remove from a star
//
		if (self::getGameStateValue('galacticStory') == MIGRATIONS && self::ERA() === 'Second' && $player_id > 0)
		{
			if (in_array($type, [LIBERATE, CONQUERVS]))
			{
//* -------------------------------------------------------------------------------------------------------- */
				if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('All players score 1 DP for every population of another player they remove from a star')]);
//* -------------------------------------------------------------------------------------------------------- */
				$DP = 1 * $sizeOfPopulation;
				self::gainDP($color, $DP);
				self::incStat($DP, 'DP_GS', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} scores ${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
			}
		}
//
// RIVALRY First: All players score 1 DP for every Gain Star action they do in this era
//
		if (self::getGameStateValue('galacticStory') == RIVALRY && self::ERA() === 'First' && $player_id > 0)
		{
//* -------------------------------------------------------------------------------------------------------- */
			if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('All players score 1 DP for every Gain Star action they do in this era')]);
//* -------------------------------------------------------------------------------------------------------- */
			$DP = 1;
			self::gainDP($color, $DP);
			self::incStat($DP, 'DP_GS', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} scores ${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
// WAR Second : All players score 1 DP for every star they take from another player
//
		if (self::getGameStateValue('galacticStory') == WAR && self::ERA() === 'Second' && $player_id > 0)
		{
			if (in_array($type, [LIBERATE, CONQUERVS]))
			{
//* -------------------------------------------------------------------------------------------------------- */
				if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('All players score 1 DP for every star they take from another player')]);
//* -------------------------------------------------------------------------------------------------------- */
				$DP = 1;
				self::gainDP($color, $DP);
				self::incStat($DP, 'DP_GS', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} scores ${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
			}
		}
//
		$counters = Factions::getStatus($color, 'counters');
		if (array_search($counter, $counters) !== false) unset($counters[array_search($counter, $counters)]);
		Factions::setStatus($color, 'counters', array_values($counters));
		Factions::setStatus($color, 'used', array_values(array_merge(Factions::getStatus($color, 'used'), [$counter])));
//
		self::updateScoring();
//
		if (Factions::getPopulation($color) > 39) self::triggerEvent(REMOVEPOPULATION, $color);
//
		if ($newShips)
		{
			if (!$automa)
			{
				Factions::setStatus($color, 'buriedShips', ['location' => $location, 'ships' => $newShips]);
				return self::triggerAndNextState('buriedShips');
			}
			else return self::acBuildShips($color, Automas::BuildShips($color, [$location => 3]), true, true);
		}
//
		self::triggerAndNextState('advancedFleetTactics');
	}
	function acGrowPopulation(string $color, array $locations, array $locationsBonus, bool $bonus = false, bool $automa = false)
	{
		$player_id = Factions::getPlayer($color);
//
		$ancientPyramids = Counters::getRelic(ANCIENTPYRAMIDS);
		if ($ancientPyramids && Counters::getStatus($ancientPyramids, 'owner') === $color) $ancientPyramids = intval(Counters::getStatus($ancientPyramids, 'available'));
//
		$counter = $bonus ? 'growPopulation+' : 'growPopulation';
//
		if (!$automa)
		{
			$this->checkAction('growPopulation');
			if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
			if (!array_key_exists('counters', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
			if (!in_array($counter, $this->possible['counters'])) throw new BgaVisibleSystemException("Invalid action: $counter");
//
			$bonusPopulation = 0;
			foreach ($locationsBonus as $location) if (!array_key_exists('ancientPyramids', $this->possible) || $this->possible['ancientPyramids'] !== $location) $bonusPopulation++;
			if ($bonusPopulation > $this->possible['bonusPopulation'] + ($bonus ? 2 : 0)) throw new BgaVisibleSystemException('Invalid bonus population: ' . sizeof($locationsBonus));
		}
//
		foreach ($locations as $location) if (!array_key_exists($location, $this->possible['growPopulation'])) throw new BgaVisibleSystemException('Invalid location: ' . $location);
		foreach ($locationsBonus as $location) if (!array_key_exists($location, $this->possible['growPopulation'])) throw new BgaVisibleSystemException('Invalid location: ' . $location);
//
		if (Factions::getTechnology($color, 'Spirituality') < 5)
		{
			$toBlock = [];
			foreach (Factions::list(false) as $otherColor)
			{
				if (Factions::getAlignment($otherColor) === 'STS' && in_array($otherColor, Factions::atPeace($color)))
				{
					foreach ($locations as $location) if (Ships::getAtLocation($location, $otherColor)) $toBlock[] = Factions::getPlayer($otherColor);
					foreach ($locationsBonus as $location) if (Ships::getAtLocation($location, $otherColor)) $toBlock[] = Factions::getPlayer($otherColor);
				}
			}
			if ($toBlock)
			{
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('${player_name} tries to growth population'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
				$this->gamestate->setPlayersMultiactive(array_unique($toBlock), 'end', true);
				Factions::setStatus($color, 'action', ['name' => 'growPopulation', 'locations' => array_unique(array_merge($locations, $locationsBonus)), 'function' => __FUNCTION__, 'params' => func_get_args()]);
				return $this->gamestate->nextState('blockAction');
			}
		}
		return self::acGrowPopulationValidated($color, $locations, $locationsBonus, $bonus, $automa);
	}
	function acGrowPopulationValidated(string $color, array $locations, array $locationsBonus, bool $bonus = false, bool $automa = false): void
	{
		$player_id = Factions::getPlayer($color);
//
		$counter = $bonus ? 'growPopulation+' : 'growPopulation';
//
		foreach ($locations as $location)
		{
			$sector = Sectors::get($location[0]);
			$rotated = Sectors::rotate(substr($location, 2), Sectors::getOrientation($location[0]));
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('placeCounter', clienttranslate('${GPS} ${PLANET} gains a <B>population</B>'), [
				'PLANET' => [
					'log' => '<span style = "color:#' . $color . ';font-weight:bold;">${PLANET}</span>',
					'i18n' => ['PLANET'], 'args' => ['PLANET' => $this->SECTORS[$sector][$rotated]]
				], 'GPS' => $location, 'counter' => Counters::get(Counters::create($color, 'populationDisc', $location))]);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'population' => Factions::gainPopulation($color, 1)]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		foreach ($locationsBonus as $location)
		{
			$sector = Sectors::get($location[0]);
			$rotated = Sectors::rotate(substr($location, 2), Sectors::getOrientation($location[0]));
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('placeCounter', clienttranslate('${GPS} ${PLANET} gains a <B>population</B>'), [
				'PLANET' => [
					'log' => '<span style = "color:#' . $color . ';font-weight:bold;">${PLANET}</span>',
					'i18n' => ['PLANET'], 'args' => ['PLANET' => $this->SECTORS[$sector][$rotated]]
				], 'GPS' => $location, 'counter' => Counters::get(Counters::create($color, 'populationDisc', $location))]);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'population' => Factions::gainPopulation($color, 1)]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		$counters = Factions::getStatus($color, 'counters');
		unset($counters[array_search($counter, $counters)]);
		Factions::setStatus($color, 'counters', array_values($counters));
		Factions::setStatus($color, 'used', array_values(array_merge(Factions::getStatus($color, 'used'), ['growPopulation'])));
//
// Scoring
//
		$galacticStory = self::getGameStateValue('galacticStory');
		$era = [1 => 'First', 2 => 'First', 3 => 'Second', 4 => 'Second', 5 => 'Second', 6 => 'Second', 7 => 'Third', 8 => 'Third'][self::getGameStateValue('round')];
//
// MIGRATIONS First: All players score 3 DP for every Grow Population action they do in this era
// Only Grow Population actions that generated at least one additional population are counted
//
		if ($era === 'First' && $galacticStory == MIGRATIONS && (sizeof($locations) + sizeof($locationsBonus) > 0) && $player_id > 0)
		{
//* -------------------------------------------------------------------------------------------------------- */
			if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('All players score 3 DP for every Grow Population action they do in this era')]);
//* -------------------------------------------------------------------------------------------------------- */
			$DP = 3;
			self::gainDP($color, $DP);
			self::incStat($DP, 'DP_GS', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} scores ${DP} DP'), ['DP' => 3,
				'player_name' => Factions::getName($color),
				'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		self::updateScoring();
//
		if (Factions::getPopulation($color) > 39) self::triggerEvent(REMOVEPOPULATION, $color);
//
		self::triggerAndNextState('continue');
	}
	function acRemovePopulation(string $color, array $locations): void
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('removePopulation');
		if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		foreach ($locations as $location)
		{
			if (!in_array($location, $this->possible['populations'])) throw new BgaVisibleSystemException('Invalid location: ' . $location);
//
			$populationDiscs = Counters::getAtLocation($location, 'populationDisc');
			if (!$populationDiscs) throw new BgaVisibleSystemException('Invalid location: ' . $location);
//
			$populationDisc = Counters::get(array_pop($populationDiscs));
			if ($populationDisc['color'] !== $color) throw new BgaVisibleSystemException('Invalid counter color: ' . $populationDisc['color']);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('removeCounter', '', ['counter' => $populationDisc]);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $populationDisc['color'], 'population' => Factions::gainPopulation($populationDisc['color'], -1)]]);
//* -------------------------------------------------------------------------------------------------------- */
			Counters::destroy($populationDisc['id']);
//
			$sector = Sectors::get($location[0]);
			$rotated = Sectors::rotate(substr($location, 2), Sectors::getOrientation($location[0]));
			self::notifyAllPlayers('msg', clienttranslate('${GPS} ${PLANET} loses one <B>population(s)</B>'), ['GPS' => $location,
				'PLANET' => [
					'log' => '<span style = "color:#' . $populationDisc['color'] . ';font-weight:bold;">${PLANET}</span>',
					'i18n' => ['PLANET'], 'args' => ['PLANET' => $this->SECTORS[$sector][$rotated]]
			]]);
//* -------------------------------------------------------------------------------------------------------- */
			self::starsBecomingUninhabited($location);
		}
//
		self::updateScoring();
		$this->gamestate->nextState('end');
	}
	function acTeleportPopulation(string $color, array $from, array $to): void
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('teleportPopulation');
		if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
		if (!array_key_exists('teleportPopulation', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
		if (sizeof($from) > $this->possible['teleportPopulation']) throw new BgaVisibleSystemException('Invalid from: ' . json_encode($from));
		if (sizeof($to) > $this->possible['teleportPopulation']) throw new BgaVisibleSystemException('Invalid to: ' . json_encode($to));
		if (sizeof($from) !== sizeof($to)) throw new BgaVisibleSystemException('Sizeof(from) <> Sizeof(to');
//
		foreach ($from as $location)
		{
			if (!in_array($location, $this->possible['populations'])) throw new BgaVisibleSystemException('Invalid location: ' . $location);
			$counters = Counters::getAtLocation($location, 'populationDisc');
			if (!$counters) throw new BgaVisibleSystemException('No more population discs at ' . $location);
			$counter = Counters::get(array_pop($counters));
			if ($counter['color'] !== $color) throw new BgaVisibleSystemException('Invalid color : ' . $counter['color']);
			Counters::destroy($counter['id']);
//
			$sector = Sectors::get($location[0]);
			$rotated = Sectors::rotate(substr($location, 2), Sectors::getOrientation($location[0]));
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('removeCounter', clienttranslate('${GPS} ${PLANET} teleports a <B>population</B>'), [
				'PLANET' => [
					'log' => '<span style = "color:#' . $color . ';font-weight:bold;">${PLANET}</span>',
					'i18n' => ['PLANET'], 'args' => ['PLANET' => $this->SECTORS[$sector][$rotated]]
				], 'GPS' => $location, 'counter' => $counter]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//
		foreach ($to as $location)
		{
			if (!in_array($location, $this->possible['populations'])) throw new BgaVisibleSystemException('Invalid location: ' . $location);
//
			$sector = Sectors::get($location[0]);
			$rotated = Sectors::rotate(substr($location, 2), Sectors::getOrientation($location[0]));
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('placeCounter', clienttranslate('${GPS} ${PLANET} gains a <B>population</B>'), [
				'PLANET' => [
					'log' => '<span style = "color:#' . $color . ';font-weight:bold;">${PLANET}</span>',
					'i18n' => ['PLANET'], 'args' => ['PLANET' => $this->SECTORS[$sector][$rotated]]
				], 'GPS' => $location, 'counter' => Counters::get(Counters::create($color, 'populationDisc', $location))]);
		}
//
		foreach (array_unique($from) as $location) self::starsBecomingUninhabited($location);
//
		Factions::setStatus($color, 'used', array_values(array_merge(Factions::getStatus($color, 'used'), ['teleportPopulation'])));
//
		self::updateScoring();
		$this->gamestate->nextState('continue');
	}
	function acBuildShips(string $color, array $buildShips, bool $automa = false, bool $buriedShips = false)
	{
		$player_id = Factions::getPlayer($color);
//
		if (!$automa)
		{
			$this->checkAction('buildShips');
			if ($player_id != self::getCurrentPlayerId()) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
			if (!array_key_exists('stars', $this->possible)) throw new BgaVisibleSystemException('Invalid possible: ' . json_encode($this->possible));
			if ($this->gamestate->state()['name'] == !'buriedShips' && !in_array('buildShips', $this->possible['counters'])) throw new BgaVisibleSystemException('Invalid action: ' . 'buildShips');
		}
//
		foreach ($buildShips['fleets'] as $Fleet => $location)
		{
			$fleet = Ships::get(Ships::getFleet($color, $Fleet));
			if ($fleet['location'] !== 'stock') throw new BgaVisibleSystemException("Invalid fleet location: $fleet[location] !== 'stock'");
		}
//
		$remainingShips = 16 - sizeof(Ships::getAll($color, 'ship'));
		foreach (array_count_values($buildShips['ships']) as $location => $ships) if (!in_array($location, Ships::FLEETS)) $remainingShips -= $ships;
		if ($remainingShips < 0) throw new BgaUserException(self::_('No more ship minis'));
//
		if ($this->gamestate->state()['name'] !== 'emergencyReserve')
		{
			if (Factions::getTechnology($color, 'Spirituality') < 5 && $player_id > 0)
			{
				$toBlock = [];
				foreach (Factions::list(false) as $otherColor)
				{
					if (Factions::getAlignment($otherColor) === 'STS' && in_array($otherColor, Factions::atPeace($color)))
					{
						foreach (array_keys(Counters::getPopulations($color)) as $location) if (Ships::getAtLocation($location, $otherColor)) $toBlock[] = Factions::getPlayer($otherColor);
					}
				}
				if ($toBlock)
				{
//* -------------------------------------------------------------------------------------------------------- */
					self::notifyAllPlayers('msg', clienttranslate('${player_name} tries to build ships'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
					$this->gamestate->setPlayersMultiactive(array_unique($toBlock), 'end', true);
					Factions::setStatus($color, 'action', ['name' => 'buildShips', 'locations' => array_keys(Counters::getPopulations($color)), 'function' => __FUNCTION__, 'params' => func_get_args()]);
					return $this->gamestate->nextState('blockAction');
				}
			}
		}
		return self::acBuildShipsValidated($color, $buildShips, $automa, $buriedShips);
	}
	function acBuildShipsValidated(string $color, array $buildShips, bool $automa = false, bool $buriedShips = false): void
	{
		$player_id = Factions::getPlayer($color);
//
		foreach ($buildShips['fleets'] as $Fleet => $location)
		{
			$fleetID = Ships::getFleet($color, $Fleet);
			$fleet = Ships::get($fleetID);
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
				$sector = Sectors::get($location[0]);
				$rotated = Sectors::rotate(substr($location, 2), Sectors::getOrientation($location[0]));
//
				if ($automa)
				{
//* -------------------------------------------------------------------------------------------------------- */
					if (!$buriedShips) self::notifyAllPlayers('msg', clienttranslate('${player_name} spawns ${ships} <B>additional ship(s)</B> ${GPS}'), [
							'player_name' => Factions::getName($color), 'ships' => $ships, 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
				}
				else
				{
					if ($location === Ships::getHomeStarLocation($color))
					{
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('msg', clienttranslate('${player_name} builds ${ships} <B>additional ship(s)</B> at Home Star ${GPS}'), [
							'player_name' => Factions::getName($color), 'ships' => $ships, 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
					}
					else
					{
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('msg', clienttranslate('${player_name} builds ${ships} <B>additional ship(s)</B> at ${PLANET} ${GPS}'), [
							'player_name' => Factions::getName($color), 'ships' => $ships,
							'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated], 'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
					}
				}
				$remainingShips -= $ships;
				for ($i = 0; $i < $ships; $i++) self::notifyAllPlayers('placeShip', '', ['ship' => Ships::get(Ships::create($color, 'ship', $location))]);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'ships' => $remainingShips]]);
//* -------------------------------------------------------------------------------------------------------- */
			}
		}
//
		if ($this->gamestate->state()['name'] !== 'buriedShips' && !$buriedShips)
		{
			$counters = Factions::getStatus($color, 'counters');
			unset($counters[array_search('buildShips', $counters)]);
			Factions::setStatus($color, 'counters', array_values($counters));
			Factions::setStatus($color, 'used', array_values(array_merge(Factions::getStatus($color, 'used'), ['buildShips'])));
//------------------------
// A-section: Economic //
//------------------------
			if (sizeof($buildShips) >= 10) Factions::setStatus($color, 'economic', true);
//------------------------
// A-section: Economic //
//------------------------
//
// Scoring
//
			$galacticStory = self::getGameStateValue('galacticStory');
			$era = [1 => 'First', 2 => 'First', 3 => 'Second', 4 => 'Second', 5 => 'Second', 6 => 'Second', 7 => 'Third', 8 => 'Third'][self::getGameStateValue('round')];
//
// WAR First: All players score 2 DP for every Build Ships action they do in this era
//
			if ($era === 'First' && $galacticStory == WAR && $player_id > 0)
			{
//* -------------------------------------------------------------------------------------------------------- */
				if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('All players score 2 DP for every Build Ships action they do in this era')]);
//* -------------------------------------------------------------------------------------------------------- */
				$DP = 2;
				self::gainDP($color, $DP);
				self::incStat($DP, 'DP_GS', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('updateFaction', clienttranslate('Galactic Story: ${player_name} gains ${DP} DP '), ['DP' => 2,
					'player_name' => Factions::getName($color),
					'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
			}
		}
//
		self::updateScoring();
//
		$this->gamestate->nextState('continue');
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
//
			$players = [];
			foreach (Factions::list(false) as $color) if (Factions::getActivation($color) !== 'done') $players[] = Factions::getPlayer($color);
//
			if ($this->gamestate->setPlayersMultiactive($players, 'next', true)) return;
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
		self::updateScoring();
		$this->gamestate->nextState('continue');
	}
	function acDomination(string $color, int $id, string $section)
	{
		$player_id = Factions::getPlayer($color);
//
		$this->gamestate->checkPossibleAction('domination');
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		$dominationCard = $this->domination->getCard($id);
		if (!$dominationCard) throw new BgaVisibleSystemException('Invalid card : ' . $id);
		$domination = $dominationCard['type'];
//
		switch ($section)
		{
//
			case 'A':
//
				$section = 'A';
				if ($this->domination->countCardInLocation($section, $player_id)) throw new BgaVisibleSystemException('A-Section already played');
				if (!DominationCards::A($color, $domination, $this->gamestate->state()['name'])) throw new BgaUserException(self::_('Play this card when primary condition happens'));
//
				$this->domination->moveCard($id, $section, $color);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('playDomination', clienttranslate('${player_name} plays <B>${DOMINATION}</B>'), [
					'player_name' => Factions::getName($color), 'i18n' => ['DOMINATION'], 'DOMINATION' => $this->DOMINATIONCARDS[$domination],
					'card' => $dominationCard, 'section' => $section
				]);
//* -------------------------------------------------------------------------------------------------------- */
				switch ($domination)
				{
					case ACQUISITION:
						$DP = 10;
//* -------------------------------------------------------------------------------------------------------- */
						if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('Get an additional technology level from one of the players you took a star from this turn')]);
//* -------------------------------------------------------------------------------------------------------- */
						Factions::setStatus($color, 'steal', ['from' => Factions::getStatus($color, 'acquisition'), 'levels' => 1]);
						self::triggerEvent(STEALTECHNOLOGY, $color);
						break;
					case ALIGNMENT:
						$DP = 9 + 2 * self::getGameStateValue('alignment');
//* -------------------------------------------------------------------------------------------------------- */
						if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('Get an additional 2 DP for every Switch Alignment growth action counter played this round (including your own)')]);
//* -------------------------------------------------------------------------------------------------------- */
						break;
					case CENTRAL:
						$DP = 12;
//* -------------------------------------------------------------------------------------------------------- */
						if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('On your turn in this growth phase, you get a free Gain Star action, which you can use in the center sector only<BR>Your ships count double for this action (apply before calculating Fleet “B” bonus)')]);
//* -------------------------------------------------------------------------------------------------------- */
						if (Factions::getStatus($color, 'counters')) Factions::setStatus($color, 'counters', array_merge(Factions::getStatus($color, 'counters'), ['gainStar+']));
						Factions::setStatus($color, 'central', true);
						break;
					case DEFENSIVE:
						$DP = 9;
//* -------------------------------------------------------------------------------------------------------- */
						if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('Add 20 CV to your side in the current battle if it is in your home star sector<BR>You may play this card even after ships have been revealed')]);
//* -------------------------------------------------------------------------------------------------------- */
						break;
					case DENSITY:
						$DP = 7;
//* -------------------------------------------------------------------------------------------------------- */
						if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('Add 1 population disc to each of your stars with 5+ population (regardless of any limits or blocking)')]);
//* -------------------------------------------------------------------------------------------------------- */
						break;
					case DIPLOMATIC:
						$DP = 14;
//* -------------------------------------------------------------------------------------------------------- */
						if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('No players may declare war on you for the rest of this round')]);
//* -------------------------------------------------------------------------------------------------------- */
						Factions::setStatus($color, 'diplomatic', true);
						break;
					case ETHERIC:
						$DP = 8;
//* -------------------------------------------------------------------------------------------------------- */
						if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('All of your ships starting their move in a nebula hex now get +4 range (instead of +2)')]);
//* -------------------------------------------------------------------------------------------------------- */
						Factions::setStatus($color, 'etheric', true);
						break;
					case EXPLORATORY:
						$DP = 13;
//* -------------------------------------------------------------------------------------------------------- */
						if (DEBUG) self::notifyAllPlayers('msg', '<span class="ERA-info">${log}</span>', ['i18n' => ['log'], 'log' => clienttranslate('You may inspect the unplayed domination cards of another player<BR>In a game with 5+ players, you may even do this with 2 players')]);
//* -------------------------------------------------------------------------------------------------------- */
						Factions::setStatus($color, 'domination', $domination);
						Factions::setStatus($color, 'exploratory', sizeof(Factions::list()) >= 5 ? 2 : 1);
						self::triggerEvent(ONETIMEEFFECT, $color);
						break;
					default:
						throw new BgaVisibleSystemException('A-Section NOT implemented');
				}
//
				self::gainDP($color, $DP);
				self::incStat($DP, 'DP_DC_A', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains ${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
				break;
//
			case 'B':
//
				$section = 'B';
				if ($this->domination->countCardInLocation($section, $color)) $section = 'A';
				if ($this->domination->countCardInLocation($section, $color)) throw new BgaVisibleSystemException('All sections already played');
				$this->domination->moveCard($id, $section, $color);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('playDomination', clienttranslate('${player_name} plays <B>${DOMINATION}</B>'), [
					'player_name' => Factions::getName($color), 'i18n' => ['DOMINATION'], 'DOMINATION' => $this->DOMINATIONCARDS[$domination],
					'card' => $dominationCard, 'section' => $section
				]);
//* -------------------------------------------------------------------------------------------------------- */
				$DP = max(DominationCards::B($color, $domination));
//				if (!$DP) throw new BgaUserException(self::_('Useless scoring'));
				self::gainDP($color, $DP);
				self::incStat($DP, 'DP_DC_B', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains ${DP} DP'), ['DP' => $DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
				break;
//
			default:
				throw new BgaVisibleSystemException("Invalid section: $section");
		}
//
		$cards = $this->domination->countCardInLocation('hand', $color) + $this->domination->countCardInLocation('A', $color) + $this->domination->countCardInLocation('B', $color);
		if ($cards < 2)
		{
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${player_name} draw a new card'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
			$this->domination->pickCard('deck', $color);
		}
		$cards++;
//
		$faction = ['color' => $color, 'domination' => []];
		foreach ($this->domination->getPlayerHand($color) as $domination) $faction['domination'][$domination['id']] = $domination['type'];
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyPlayer($player_id, 'updateFaction', '', ['faction' => $faction]);
//* -------------------------------------------------------------------------------------------------------- */
		foreach ($this->domination->getPlayerHand($color) as $domination) $faction['domination'][$domination['id']] = 'back';
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('updateFaction', '', ['player_id' => $player_id, 'faction' => $faction]);
//* -------------------------------------------------------------------------------------------------------- */
//
//		if ($this->gamestate->state()['name'] === 'domination' && $this->domination->countCardInLocation('A', $color) + $this->domination->countCardInLocation('B', $color) === 2) $this->gamestate->setPlayerNonMultiactive($player_id, 'end');
//
		self::updateScoring();
		self::triggerAndNextState('continue');
	}
	function acDominationCardExchange(string $color, int $id)
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('dominationCardExchange');
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		if ($id)
		{
			Factions::setStatus($color, 'exchange', 'done');
//
			$dominationCard = $this->domination->getCard($id);
			if (!$dominationCard) throw new BgaVisibleSystemException('Invalid card : ' . $id);
//
			$this->domination->playCard($id);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('msg', clienttranslate('${player_name} draw a new card'), ['player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
			$this->domination->pickCard('deck', $color);
		}
//
		$faction = ['color' => $color, 'domination' => []];
		foreach ($this->domination->getPlayerHand($color) as $domination) $faction['domination'][$domination['id']] = $domination['type'];
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyPlayer($player_id, 'updateFaction', '', ['faction' => $faction]);
//* -------------------------------------------------------------------------------------------------------- */
		foreach ($this->domination->getPlayerHand($color) as $domination) $faction['domination'][$domination['id']] = 'back';
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('updateFaction', '', ['player_id' => $player_id, 'faction' => $faction]);
//* -------------------------------------------------------------------------------------------------------- */
		if ($this->gamestate->state()['name'] === 'researchPlus') return $this->gamestate->nextState('continue');
//
		Factions::setActivation($color, 'done');
//
		self::updateScoring();
		$this->gamestate->nextState('nextPlayer');
	}
	function acOneTimeEffect(string $color, array $json)
	{
		$player_id = Factions::getPlayer($color);
//
		$this->checkAction('oneTimeEffect');
		if ($player_id != Factions::getPlayer($color)) throw new BgaVisibleSystemException('Invalid Faction: ' . $color);
//
		$domination = Factions::getStatus($color, 'domination');
//
		switch ($domination)
		{
//
			case EXPLORATORY:
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('msg', clienttranslate('${player_name} inspects unplayed domination cards of ${player_name1}'), ['player_name' => Factions::getName($color), 'player_name1' => Factions::getName($json['color'])]);
//* -------------------------------------------------------------------------------------------------------- */
				$dominationCards = $this->domination->getPlayerHand($json['color']);
//* -------------------------------------------------------------------------------------------------------- */
				foreach ($dominationCards as $dominationCard) self::notifyPlayer($player_id, 'msg', clienttranslate('<B>${DOMINATION}</B>'), ['i18n' => ['DOMINATION'], 'DOMINATION' => $this->DOMINATIONCARDS[$dominationCard['type']]]);
//* -------------------------------------------------------------------------------------------------------- */
				$count = Factions::getStatus($color, 'exploratory') - 1;
				if ($count > 0)
				{
					Factions::setStatus($color, 'exploratory', $count);
					return $this->gamestate->nextState('continue');
				}
				Factions::setStatus($color, 'exploratory');
//
				break;
//
			default:
//
				throw new BgaVisibleSystemException("Invalid oneTimeEffect: $domination");
//
		}
//
		return $this->gamestate->nextState('end');
	}
}
