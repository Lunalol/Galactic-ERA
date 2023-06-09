<?php

/**
 *
 * @author Lunalol
 */
trait gameUtils
{
	function reveal(string $color, string $location, int $counter)
	{
		if (Counters::isRevealed($color, $counter)) return;
//
		$sector = Sectors::get($location[0]);
		$hexagon = substr($location, 2);
//
		switch (Counters::getStatus($counter, 'back'))
		{
			case 'UNINHABITED':
				if ($color)
				{
					Counters::reveal($color, 'star', $counter);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyPlayer(Factions::getPlayer($color), 'flipCounter', clienttranslate('${GPS} ${PLANET} is <B>uninhabited</B>'), [
						'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon],
						'GPS' => $location, 'counter' => ['id' => $counter, 'type' => Counters::getStatus($counter, 'back')]]);
//* -------------------------------------------------------------------------------------------------------- */
				}
				else
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('flipCounter', clienttranslate('${GPS} ${PLANET} is <B>uninhabited</B>'), [
						'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon],
						'GPS' => $location, 'counter' => ['id' => $counter, 'type' => Counters::getStatus($counter, 'back')]]);
//* -------------------------------------------------------------------------------------------------------- */
				break;
			case 'PRIMITIVE':
				if ($color)
				{
					Counters::reveal($color, 'star', $counter);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyPlayer(Factions::getPlayer($color), 'flipCounter', clienttranslate('${GPS} ${PLANET} has a <B>primitive</B> civilization'), [
						'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon],
						'GPS' => $location, 'counter' => ['id' => $counter, 'type' => Counters::getStatus($counter, 'back')]]);
//* -------------------------------------------------------------------------------------------------------- */
				}
				else $this->notifyAllPlayers('flipCounter', clienttranslate('${GPS} ${PLANET} has a <B>primitive</B> civilization'), [
//* -------------------------------------------------------------------------------------------------------- */
						'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon],
						'GPS' => $location, 'counter' => ['id' => $counter, 'type' => Counters::getStatus($counter, 'back')]]);
//* -------------------------------------------------------------------------------------------------------- */
				break;
			case 'ADVANCED':
				if ($color)
				{
					Counters::reveal($color, 'star', $counter);
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyPlayer(Factions::getPlayer($color), 'flipCounter', clienttranslate('${GPS} ${PLANET} has an <B>advanced</B> civilization'), [
						'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon],
						'GPS' => $location, 'counter' => ['id' => $counter, 'type' => Counters::getStatus($counter, 'back')]]);
//* -------------------------------------------------------------------------------------------------------- */
				}
				else
//* -------------------------------------------------------------------------------------------------------- */10
					$this->notifyAllPlayers('flipCounter', clienttranslate('${GPS} ${PLANET} has an <B>advanced</B> civilization'), [
						'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon],
						'GPS' => $location, 'counter' => ['id' => $counter, 'type' => Counters::getStatus($counter, 'back')]]);
//* -------------------------------------------------------------------------------------------------------- */
				break;
			default:
				if ($color)
				{
					Counters::reveal($color, 'relic', $counter);
//* -------------------------------------------------------------------------------------------------------- */10
					$this->notifyPlayer(Factions::getPlayer($color), 'flipCounter', clienttranslate('<B>${RELIC}</B> is revealed at ${PLANET} ${GPS}'), [
						'i18n' => ['PLANET', 'RELIC'], 'PLANET' => $this->SECTORS[$sector][$hexagon], 'RELIC' => $this->RELICS[Counters::getStatus($counter, 'back')],
						'GPS' => $location, 'counter' => ['id' => $counter, 'type' => Counters::getStatus($counter, 'back')]]);
//* -------------------------------------------------------------------------------------------------------- */
				}
				else
//* -------------------------------------------------------------------------------------------------------- */10
					$this->notifyAllPlayers('flipCounter', clienttranslate('<B>${RELIC}</B> is revealed at ${PLANET} ${GPS}'), [
						'i18n' => ['PLANET', 'RELIC'], 'PLANET' => $this->SECTORS[$sector][$hexagon], 'RELIC' => $this->RELICS[Counters::getStatus($counter, 'back')],
						'GPS' => $location, 'counter' => ['id' => $counter, 'type' => Counters::getStatus($counter, 'back')]]);
//* -------------------------------------------------------------------------------------------------------- */
		}
	}
}
