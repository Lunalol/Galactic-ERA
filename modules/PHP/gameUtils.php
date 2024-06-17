<?php

/**
 *
 * @author Lunalol
 */
trait gameUtils
{
	function ERA()
	{
		return [1 => 'First', 2 => 'First', 3 => 'Second', 4 => 'Second', 5 => 'Second', 6 => 'Second', 7 => 'Third', 8 => 'Third'][self::getGameStateValue('round')];
	}
	function updateScoring()
	{
		$scoring = [];
//
		foreach (Factions::list(false) as $color)
		{
			if ($this->domination->countCardInLocation('A', $color) == 0)
			{
				foreach (array_keys($this->DOMINATIONCARDS) as $domination)
				{
					$scoring[$color][$domination]['A'] = DominationCards::A($color, $domination, self::getGameStateValue('galacticGoal') == PERSONALGROWTH ? 2 : 1);
					$scoring[$color][$domination]['effect'] = $scoring[$color][$domination]['A'] && DominationCards::effect($color, $domination, $this->gamestate->state()['name']);
				}
			}
			else
			{
				foreach (array_keys($this->DOMINATIONCARDS) as $domination)
				{
					$scoring[$color][$domination]['A'] = 0;
					$scoring[$color][$domination]['effect'] = false;
				}
			}
		}
//
		foreach (Factions::list(false) as $color) foreach (array_keys($this->DOMINATIONCARDS) as $domination) $scoring[$color][$domination]['B'] = DominationCards::B($color, $domination, self::getGameStateValue('galacticGoal') == PERSONALGROWTH ? 2 : 1);
//
		self::notifyAllPlayers('updateScoring', '', ['scoring' => $scoring]);
	}
	function switchAlignment($color)
	{
		$player_id = Factions::getPlayer($color);
//
		Factions::switchAlignment($color);
//------------------------
// A-section: Alignment //
//------------------------
		self::incGameStateValue('alignment', 1);
//------------------------
// A-section: Alignment //
//------------------------
		foreach (Factions::atWar($color) as $otherColor)
		{
			Factions::declarePeace($otherColor, $color);
			Factions::declarePeace($color, $otherColor);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', '', ['faction' => Factions::get($otherColor)]);
//* -------------------------------------------------------------------------------------------------------- */
		}
//* -------------------------------------------------------------------------------------------------------- */
		self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} switches alignment (<B>${ALIGNMENT}</B>)'), [
			'player_name' => Factions::getName($color), 'i18n' => ['ALIGNMENT'], 'ALIGNMENT' => Factions::getAlignment($color),
			'faction' => Factions::get($color)]);
//* -------------------------------------------------------------------------------------------------------- */
//
// ALLIANCE OF LIGHT (STO) / DARKNESS (STS) : Lose 2 DP every time you switch alignment (also due to reaching Spirituality level 5/6)
//
		if (Factions::getStarPeople($color) === 'Alliance' && $player_id > 0)
		{
			$DP = -2;
			self::gainDP($color, $DP);
			self::incStat($DP, 'DP_SP', $player_id);
//* -------------------------------------------------------------------------------------------------------- */
			self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} loses ${DP} DP'), ['DP' => -$DP, 'player_name' => Factions::getName($color), 'faction' => ['color' => $color, 'DP' => Factions::getDP($color)]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
	}
	function gainDP(string $color, int $delta): int
	{
		$player_id = Factions::getPlayer($color);
		if ($player_id > 0)
		{
			$DP = self::dbGetScore($player_id);
			$FINAL = self::dbIncScore($player_id, $delta);
//
			while ($DP != $FINAL)
			{
				if ($delta > 0) $DP++;
				else $DP--;
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('updateFaction', '', ['faction' => ['color' => $color, 'DP' => $DP]]);
//* -------------------------------------------------------------------------------------------------------- */
			}
		}
		return Factions::gainDP($color, $delta);
	}
	function gainTechnology(string $color, string $technology, bool $research = false): int
	{
		if (Factions::getTechnology($color, $technology) === 6)
		{
//
// When automas research a technology they already have at level 6, this has no effect instead
//
			if (Factions::getPlayer($color) > 0)
			{
				Factions::setStatus($color, 'researchPlus', array_merge(Factions::getStatus($color, 'researchPlus') ?? [], [$technology]));
				self::triggerEvent(RESEARCHPLUS, $color);
//* -------------------------------------------------------------------------------------------------------- */
//				self::notifyAllPlayers('msg', clienttranslate('${player_name} gains a <B>${TECHNOLOGY}+ effect</B>'), ['player_name' => Factions::getName($color), 'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology]]);
//* -------------------------------------------------------------------------------------------------------- */
			}
			return 0;
		}
//
		$level = Factions::gainTechnology($color, $technology);
//
// YOWIES SPECIAL STO & STS: You may not have Robotics higher than level 1
//
//		if (Factions::getStarPeople($color) === 'Yowies' && $technology === 'Robotics' && $level > 1) throw new BgaUserException(self::_('Yowies may not have Robotics higher than level 1'));
//* -------------------------------------------------------------------------------------------------------- */
		if ($research) self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} researches <B>${TECHNOLOGY} (${LEVEL})</B>'), [
				'player_name' => Factions::getName($color),
				'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology], 'LEVEL' => $level,
				'faction' => ['color' => $color, $technology => $level]
			]);
		else self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} gains <B>${TECHNOLOGY} (${LEVEL})</B>'), [
				'player_name' => Factions::getName($color),
				'i18n' => ['TECHNOLOGY'], 'TECHNOLOGY' => $this->TECHNOLOGIES[$technology], 'LEVEL' => $level,
				'faction' => ['color' => $color, $technology => $level]
			]);
//* -------------------------------------------------------------------------------------------------------- */
//
// Spirituality : At levels 5 and 6 you automatically switch to STO (no growth action needed for that) and may not switch back to STS again.*
// This happens only when the level is reached (so not during the “Switch Alignment” step).
//
		if ($technology === 'Spirituality' && $level >= 5 && Factions::getAlignment($color) === 'STS') self::switchAlignment($color);
//
// Advanced fleet tactics
//
		if ($technology === 'Military' && in_array($level, [2, 4, 6]))
		{
			if (Factions::getPlayer($color) <= 0)
			{
				for ($i = 0; $i < ($level === 6 ? 3 : 1); $i++)
				{
					$Fleet = Automas::advancedFleetTactics($color);
					if ($Fleet)
					{
						Factions::setAdvancedFleetTactics($color, $Fleet, '2x');
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyAllPlayers('updateFaction', '${player_name} gets <B>${tactics}</B> on <B>${FLEET}</B> fleet', [
							'player_name' => Factions::getName($color), 'tactics' => '2x', 'FLEET' => $Fleet,
							'faction' => Factions::get($color)]);
//* -------------------------------------------------------------------------------------------------------- */
					}
				}
			}
			else Factions::setStatus($color, 'advancedFleetTactics', $level === 6 ? 3 : 1);
		}
//
		return $level;
	}
	function reveal(string $color, string $type, string $id, bool $ancientPyramids = false, bool $verbose = false)
	{
		switch ($type)
		{
//
			case 'dominationCard':
//
				$dominationCard = $this->domination->getCardOnTop('deck');
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyPlayer(Factions::getPlayer($color), 'msg', clienttranslate('Top of deck: <B>${DOMINATION}</B>'), ['i18n' => ['DOMINATION'], 'DOMINATION' => $this->DOMINATIONCARDS[$dominationCard['type']]]);
//* -------------------------------------------------------------------------------------------------------- */
				if ($verbose) self::notifyAllPlayers('msg', clienttranslate('${player_name} is remote viewing the domination card deck'), ['player_id' => Factions::getPlayer($color), 'player_name' => Factions::getName($color)]);
//* -------------------------------------------------------------------------------------------------------- */
				break;
//
			case 'fleet':
//
				$ship = Ships::get($id);
				if (!$ship) throw new BgaVisibleSystemException("Invalid ship: $id");
				if ($ship['fleet'] !== 'fleet') throw new BgaVisibleSystemException('Not a fleet');
//
				if (!$ancientPyramids && Factions::getTechnology($color, 'spirituality') <= Factions::getTechnology($ship['color'], 'spirituality')) throw new BgaUserException(self::_('You must have a higher spirituality level than the owner of the fleet'));
//
				foreach (Factions::list(false) as $otherColor)
				{
					if ($otherColor === $color)
					{
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyPlayer(Factions::getPlayer($color), 'msg', '<div class="ERA-removeViewing" style="background:#${color};color:black;"><span class="fa fa-eye fa-spin"></span>&nbsp${LOG} ${GPS}</div>', [
							'color' => $ship['color'], 'GPS' => $ship['location'],
							'LOG' => [
								'log' => clienttranslate('<B>${fleet}</B> fleet with ${ships} ship(s)'),
								'args' => ['fleet' => Ships::getStatus($id, 'fleet'), 'ships' => Ships::getStatus($id, 'ships')]
							]
						]);
//* -------------------------------------------------------------------------------------------------------- */
					}
					else if ($otherColor === $ship['color'])
					{
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyPlayer(Factions::getPlayer($otherColor), 'msg', '<div class="ERA-removeViewing" style="background:#${color};color:black;"><span class="fa fa-eye fa-spin"></span>&nbsp${LOG} ${GPS}</div>', [
							'color' => $ship['color'], 'GPS' => $ship['location'],
							'LOG' => [
								'log' => clienttranslate('<B>${fleet}</B> fleet is remote viewed by ${player_name}'),
								'args' => ['fleet' => Ships::getStatus($id, 'fleet'), 'player_name' => Factions::getName($color)]
							]
						]);
//* -------------------------------------------------------------------------------------------------------- */
					}
					else
					{
//* -------------------------------------------------------------------------------------------------------- */
						self::notifyPlayer(Factions::getPlayer($otherColor), 'msg', '<div class="ERA-removeViewing" style="background:#${color};color:black;"><span class="fa fa-eye fa-spin"></span>&nbsp${LOG} ${GPS}</div>', [
							'color' => $ship['color'], 'GPS' => $ship['location'],
							'LOG' => [
								'log' => clienttranslate('${owner} is remote viewing a fleet from ${player_name}'),
								'args' => ['owner' => Factions::getName($ship['color']), 'player_name' => Factions::getName($color)]
							]
						]);
//* -------------------------------------------------------------------------------------------------------- */
					}
				}
				break;
//
			case 'counter':
//
				if (in_array($color, Counters::isRevealed($id))) return;
//
				$location = Counters::get($id)['location'];
				$sector = Sectors::get($location[0]);
				$hexagon = substr($location, 2);
				$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($location[0]));
//
				switch (Counters::getStatus($id, 'back'))
				{
					case 'UNINHABITED':
						if ($color)
						{
							Counters::reveal($color, 'star', $id);
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyPlayer(Factions::getPlayer($color), 'flipCounter', clienttranslate('${GPS} ${PLANET} is <B>uninhabited</B>'), [
								'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated],
								'GPS' => $location, 'counter' => ['id' => $id, 'type' => Counters::getStatus($id, 'back')]]);
//* -------------------------------------------------------------------------------------------------------- */
							if ($verbose) self::notifyAllPlayers('msg', clienttranslate('${player_name} is remote viewing ${PLANET} ${GPS}'), ['player_id' => Factions::getPlayer($color),
									'player_name' => Factions::getName($color),
									'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated],
									'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
						}
						else
						{
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyAllPlayers('flipCounter', clienttranslate('${GPS} ${PLANET} is <B>uninhabited</B>'), [
								'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated],
								'GPS' => $location, 'counter' => ['id' => $id, 'type' => Counters::getStatus($id, 'back')]]);
//* -------------------------------------------------------------------------------------------------------- */
						}
						break;
					case 'PRIMITIVE':
						if ($color)
						{
							Counters::reveal($color, 'star', $id);
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyPlayer(Factions::getPlayer($color), 'flipCounter', clienttranslate('${GPS} ${PLANET} has a <B>primitive</B> civilization'), [
								'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated],
								'GPS' => $location, 'counter' => ['id' => $id, 'type' => Counters::getStatus($id, 'back')]]);
//* -------------------------------------------------------------------------------------------------------- */
							if ($verbose) self::notifyAllPlayers('msg', clienttranslate('${player_name} is remote viewing ${PLANET} ${GPS}'), ['player_id' => Factions::getPlayer($color),
									'player_name' => Factions::getName($color),
									'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated],
									'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
						}
						else
						{
							self::notifyAllPlayers('flipCounter', clienttranslate('${GPS} ${PLANET} has a <B>primitive</B> civilization'), [
//* -------------------------------------------------------------------------------------------------------- */
								'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated],
								'GPS' => $location, 'counter' => ['id' => $id, 'type' => Counters::getStatus($id, 'back')]]);
//* -------------------------------------------------------------------------------------------------------- */
						}

						break;
					case 'ADVANCED':
						if ($color)
						{
							Counters::reveal($color, 'star', $id);
//* -------------------------------------------------------------------------------------------------------- */
							self::notifyPlayer(Factions::getPlayer($color), 'flipCounter', clienttranslate('${GPS} ${PLANET} has an <B>advanced</B> civilization'), [
								'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated],
								'GPS' => $location, 'counter' => ['id' => $id, 'type' => Counters::getStatus($id, 'back')]]);
//* -------------------------------------------------------------------------------------------------------- */
							if ($verbose) self::notifyAllPlayers('msg', clienttranslate('${player_name} is remote viewing ${PLANET} ${GPS}'), ['player_id' => Factions::getPlayer($color),
									'player_name' => Factions::getName($color),
									'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated],
									'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
						}
						else
						{
//* -------------------------------------------------------------------------------------------------------- */10
							self::notifyAllPlayers('flipCounter', clienttranslate('${GPS} ${PLANET} has an <B>advanced</B> civilization'), [
								'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated],
								'GPS' => $location, 'counter' => ['id' => $id, 'type' => Counters::getStatus($id, 'back')]]);
//* -------------------------------------------------------------------------------------------------------- */
						}
						break;
					default:
						if ($color)
						{
							Counters::reveal($color, 'relic', $id);
//* -------------------------------------------------------------------------------------------------------- */10
							self::notifyPlayer(Factions::getPlayer($color), 'flipCounter', clienttranslate('<B>${RELIC}</B> is revealed at ${PLANET} ${GPS}'), [
								'i18n' => ['PLANET', 'RELIC'], 'PLANET' => $this->SECTORS[$sector][$rotated], 'RELIC' => $this->RELICS[Counters::getStatus($id, 'back')],
								'GPS' => $location, 'counter' => ['id' => $id, 'type' => Counters::getStatus($id, 'back')]]);
//* -------------------------------------------------------------------------------------------------------- */
							if ($verbose) self::notifyAllPlayers('msg', clienttranslate('${player_name} is remote viewing a relic at ${PLANET} ${GPS}'), ['player_id' => Factions::getPlayer($color),
									'player_name' => Factions::getName($color),
									'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$rotated],
									'GPS' => $location]);
//* -------------------------------------------------------------------------------------------------------- */
						}
						else
						{
							foreach (Factions::list(false) as $otherColor) Counters::reveal($otherColor, 'relic', $id);
//* -------------------------------------------------------------------------------------------------------- */10
							self::notifyAllPlayers('flipCounter', clienttranslate('<B>${RELIC}</B> is revealed at ${PLANET} ${GPS}'), [
								'i18n' => ['PLANET', 'RELIC'], 'PLANET' => $this->SECTORS[$sector][$rotated], 'RELIC' => $this->RELICS[Counters::getStatus($id, 'back')],
								'GPS' => $location, 'counter' => ['id' => $id, 'type' => Counters::getStatus($id, 'back')]]);
//* -------------------------------------------------------------------------------------------------------- */
						}
				}
				break;
		}
	}
	function starsBecomingUninhabited($location)
	{
		if (array_search($location, Ships::getHomeStar()) !== false) return;
//
		if (!Counters::getAtLocation($location, 'populationDisc'))
		{
			$sector = Sectors::get($location[0]);
			$hexagon = substr($location, 2);
			$rotated = Sectors::rotate($hexagon, Sectors::getOrientation($location[0]));
			if (array_key_exists($rotated, $this->SECTORS[$sector]))
			{
				$star = Counters::create('neutral', 'star', $location, ['back' => 'UNINHABITED']);
				self::notifyAllPlayers('placeCounter', '', ['counter' => Counters::get($star)]);
				self::reveal('', 'counter', $star);
				foreach (Factions::list(false) as $otherColor) Counters::reveal($otherColor, 'star', $star);
//
				foreach (Counters::getAtLocation($location, 'relic') as $relic) Counters::setStatus($relic, 'owner', 'uninhabited');
			}
		}
	}
	function special($color, $N)
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
				$DP = -5;
				self::gainDP(Factions::getNotAutomas(), $DP);
				self::incStat($DP, 'DP_LOST', Factions::getPlayer(Factions::getNotAutomas()));
//* -------------------------------------------------------------------------------------------------------- */
				self::notifyAllPlayers('updateFaction', clienttranslate('${player_name} loses ${DP} DP'), ['DP' => -$DP,
					'player_name' => Factions::getName(Factions::getNotAutomas()),
					'faction' => ['color' => Factions::getNotAutomas(), 'DP' => Factions::getDP(Factions::getNotAutomas())]]);
//* -------------------------------------------------------------------------------------------------------- */
			}
		}
	}
}
