<?php

/**
 *
 * @author Lunalol
 */
trait gameUtils
{
	function reveal(string $color, string $location, int $counter)
	{
		Counters::reveal($color, 'star', $counter);
//
		$sector = Sectors::get($location[0]);
		$hexagon = substr($location, 2);
//
		switch (Counters::getStatus($counter, 'back'))
		{
			case 'UNINHABITED':
				if ($color)
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyPlayer(Factions::getPlayer($color), 'flipCounter', clienttranslate('${PLANET} is <B>uninhabited</B>'), [
						'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon],
						'counter' => ['id' => $counter, 'type' => Counters::getStatus($counter, 'back')]
						]
					);
//* -------------------------------------------------------------------------------------------------------- */
				else
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('flipCounter', clienttranslate('${PLANET} is <B>uninhabited</B>'), [
						'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon],
						'counter' => ['id' => $counter, 'type' => Counters::getStatus($counter, 'back')]
						]
					);
//* -------------------------------------------------------------------------------------------------------- */
				break;
			case 'PRIMITIVE':
				if ($color)
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyPlayer(Factions::getPlayer($color), 'flipCounter', clienttranslate('${PLANET} has a <B>primitive</B> civilization'), [
						'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon],
						'counter' => ['id' => $counter, 'type' => Counters::getStatus($counter, 'back')]
						]
					);
//* -------------------------------------------------------------------------------------------------------- */
				else $this->notifyAllPlayers('flipCounter', clienttranslate('${PLANET} has a <B>primitive</B> civilization'), [
//* -------------------------------------------------------------------------------------------------------- */
						'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon],
						'counter' => ['id' => $counter, 'type' => Counters::getStatus($counter, 'back')]
						]
					);
//* -------------------------------------------------------------------------------------------------------- */
				break;
			case 'ADVANCED':
				if ($color)
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyPlayer(Factions::getPlayer($color), 'flipCounter', clienttranslate('${PLANET} has an <B>advanced</B> civilization'), [
						'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon],
						'counter' => ['id' => $counter, 'type' => Counters::getStatus($counter, 'back')]
						]
					);
//* -------------------------------------------------------------------------------------------------------- */
				else
//* -------------------------------------------------------------------------------------------------------- */
					$this->notifyAllPlayers('flipCounter', clienttranslate('${PLANET} has an <B>advanced</B> civilization'), [
						'i18n' => ['PLANET'], 'PLANET' => $this->SECTORS[$sector][$hexagon],
						'counter' => ['id' => $counter, 'type' => Counters::getStatus($counter, 'back')]
						]
					);
//* -------------------------------------------------------------------------------------------------------- */
				break;
		}
	}
}
