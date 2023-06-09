<?php
//
require_once(APP_GAMEMODULE_PATH . 'module/table/table.game.php');
require_once('modules/PHP/constants.inc.php');
require_once('modules/PHP/hexagons.php');
require_once('modules/PHP/Players.php');
require_once('modules/PHP/Factions.php');
require_once('modules/PHP/Sectors.php');
require_once('modules/PHP/Counters.php');
require_once('modules/PHP/Ships.php');
require_once('modules/PHP/Automas.php');
require_once('modules/PHP/gameStates.php');
require_once('modules/PHP/gameStateArguments.php');
require_once('modules/PHP/gameStateActions.php');
require_once('modules/PHP/gameUtils.php');

class GalacticEra extends Table
{
	use gameStates;
	use gameStateArguments;
	use gameStateActions;
	use gameUtils;
//
	function __construct()
	{
		parent::__construct();
//
		$this->GLOBALLABELS = [
			'game' => GAME,
			'difficulty' => DIFFICULTY,
			'round' => ROUND,
			'galacticStory' => GALACTICSTORY,
			'galacticGoal' => GALACTICGOAL,
		];
//
		self::initGameStateLabels($this->GLOBALLABELS);
//
// Initialize domination deck
//
		$this->domination = self::getNew("module.common.deck");
		$this->domination->init("domination");
	}
	protected function getGameName()
	{
		return "galacticera";
	}
	protected function setupNewGame($players, $options = [])
	{
		$gameinfos = self::getGameinfos();
//
		$default_colors = $gameinfos['player_colors'];
		foreach (array_keys($players) as $player_id) $players[$player_id]['player_color'] = array_shift($default_colors);
//
		Players::create($players);
//
		self::reattributeColorsBasedOnPreferences($players, $gameinfos['player_colors']);
		self::reloadPlayersBasicInfos();
//
		$this->initStatistics();
	}
	protected function initStatistics()
	{

	}
	protected function getAllDatas()
	{
		$player_id = self::getCurrentPlayerId();
//
		$result = [];
//
		$result['players'] = Players::getAllDatas();
		$result['factions'] = Factions::getAllDatas();
		$result['galacticStory'] = $this->STORIES[self::getGameStateValue('galacticStory')];
		$result['galacticGoal'] = $this->GOALS[self::getGameStateValue('galacticGoal')];
		$result['round'] = intval(self::getGameStateValue('round'));
//
		foreach (Factions::list() as $color)
		{
			if ($player_id === Factions::getPlayer($color))
			{
				foreach (Counters::listRevealed($color, 'star') as $counter) $result['factions'][$color]['revealed']['stars'][$counter] = Counters::getStatus($counter, 'back');
				foreach (Counters::listRevealed($color, 'relic') as $counter) $result['factions'][$color]['revealed']['relics'][$counter] = Counters::getStatus($counter, 'back');
				foreach (Counters::listRevealed($color, 'fleet') as $fleet) $result['factions'][$color]['revealed']['fleets'][$fleet] = Ships::getStatus($fleet, 'fleet');
			}
//
			foreach ($this->domination->getPlayerHand($color) as $domination) $result['factions'][$color]['domination'][] = ($player_id === Factions::getPlayer($color)) ? $this->DOMINATIONCARDS[$domination['type']] : 'back';
			$result['factions'][$color]['ships'] = 16 - sizeof(Ships::getAll($color, 'ship'));
		}
//
		$result['sectors'] = Sectors::getAllDatas();
		foreach ($result['sectors'] as $location => $sector)
		{
			$result['sectors'][$location]['shape'] = Sectors::SHAPES[$sector['sector']];
			$result['sectors'][$location]['description'] = $this->SECTORS[$sector['sector']];
		}
//
		$result['ships'] = Ships::getAllDatas($player_id);
		$result['counters'] = Counters::getAllDatas();
//
		return $result;
	}
	function dbGetScore(int $player_id): int
	{
		return intval(self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id='$player_id'"));
	}
	function dbSetScore(int $player_id, int $score = 0): void
	{
		$this->DbQuery("UPDATE player SET player_score = $score WHERE player_id = $player_id");
	}
	function dbIncScore(int $player_id, int $inc): int
	{
		if ($player_id <= 0) return 0;
//
		$score = self::dbGetScore($player_id);
		if ($inc !== 0)
		{
			$score += $inc;
			$this->dbSetScore($player_id, $score);
		}
		return $score;
	}
	function getGameProgression(): int
	{
		return (8 - self::getGameStateValue('round')) * (100 / 8);
	}
	function zombieTurn($state, $player_id)
	{
		if ($state['type'] === "activeplayer")
		{
			switch ($state['name'])
			{
				default:
					return $this->gamestate->nextState("zombiePass");
			}
		}
		if ($state['type'] === "multipleactiveplayer") return $this->gamestate->setPlayerNonMultiactive($player_id, '');
		throw new feException("Zombie mode not supported at this game state: " . $state['name']);
	}
	function upgradeTableDb($from_version)
	{

	}
}
