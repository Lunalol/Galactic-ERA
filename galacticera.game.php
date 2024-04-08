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
require_once('modules/PHP/DominationCards.php');
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

	function __construct()
	{
		parent::__construct();
//
		$this->GLOBALLABELS = [
			'game' => GAME, 'difficulty' => DIFFICULTY,
			'galacticStory' => GALACTICSTORY, 'galacticGoal' => GALACTICGOAL,
			'round' => ROUND, 'alignment' => SWITCHALIGNMENT,
			'GODMODE' => GODMODE
		];
//
		self::initGameStateLabels($this->GLOBALLABELS);
//
// Initialize domination deck
//
		$this->domination = DominationCards::init();
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
		if ($options[GAME] != MANUAL)
		{
//
// Randomly draw a galactic story tile and place it alongside the turn track in the long rectangle labeled “Galactic Story”.
//
			while (($galacticStory = array_rand($this->STORIES)) == NONE);
			self::setGameStateInitialValue('galacticStory', $galacticStory);
//
// Randomly draw a galactic goal tile and place it on the spot of the same size below the turn track.
// Introductory Game: Leave out the galactic goal for an introductory game.
//
			if ($options[GAME] == INTRODUCTORY) $galacticGoal = NONE;
			else while (($galacticGoal = array_rand($this->GOALS)) == NONE);
			self::setGameStateInitialValue('galacticGoal', $galacticGoal);
		}
//
		$this->initStatistics();
//
		$this->activeNextPlayer();
	}
	protected function initStatistics()
	{
		if (self::getPlayersNumber() === 1) self::initStat('table', 'difficulty', 0);
//
		self::initStat('player', 'DP', 0);
		self::initStat('player', 'DP_GS', 0);
		self::initStat('player', 'DP_GG', 0);
		self::initStat('player', 'DP_POP', 0);
		self::initStat('player', 'DP_MAJ', 0);
		self::initStat('player', 'DP_SP', 0);
		self::initStat('player', 'DP_AFT', 0);
		self::initStat('player', 'DP_DC_A', 0);
		self::initStat('player', 'DP_DC_B', 0);
		self::initStat('player', 'DP_LOST', 0);
//
// Legacy
//
		foreach (array_keys(self::loadPlayersBasicInfos()) as $player_id)
		{
			$datas = self::retrieveLegacyData($player_id, LEGACYDATA);
			$legacy = $datas ? json_decode($datas[LEGACYDATA]) : [0 => '', 1 => '', 2 => '', 3 => ''];
//
			if ($legacy[0] !== '') self::initStat('player', 'easy', $legacy[0], $player_id);
			if ($legacy[1] !== '') self::initStat('player', 'standard', $legacy[1], $player_id);
			if ($legacy[2] !== '') self::initStat('player', 'hard', $legacy[2], $player_id);
			if ($legacy[3] !== '') self::initStat('player', 'insane', $legacy[3], $player_id);
		}
	}
	protected function getAllDatas()
	{
		$player_id = intval(self::getCurrentPlayerId());
//
		$result = ['GODMODE' => self::getGameStateValue('GODMODE')];
//
		$result['players'] = Players::getAllDatas();
		$result['factions'] = Factions::getAllDatas();
		$result['galacticStory'] = $this->STORIES[self::getGameStateValue('galacticStory')];
		$result['galacticGoal'] = $this->GOALS[self::getGameStateValue('galacticGoal')];
		$result['technologies'] = Factions::TECHNOLOGIES;
		$result['round'] = intval(self::getGameStateValue('round'));
		$result['A'] = $this->domination->getCardsInLocation('A');
		$result['B'] = $this->domination->getCardsInLocation('B');
//
		foreach (Factions::list() as $color)
		{
			if (Factions::getPlayer($color) > 0)
			{
				if ($this->domination->countCardInLocation('A', $color) == 0) foreach (array_keys($this->DOMINATIONCARDS) as $domination) $result['factions'][$color]['scoring'][$domination]['A'] = DominationCards::A($color, $domination, self::getGameStateValue('galacticGoal') == PERSONALGROWTH ? 2 : 1, $this->gamestate->state()['name']);
				else foreach (array_keys($this->DOMINATIONCARDS) as $domination) $result['factions'][$color]['scoring'][$domination]['A'] = 0;
//
				foreach (array_keys($this->DOMINATIONCARDS) as $domination) $result['factions'][$color]['scoring'][$domination]['B'] = DominationCards::B($color, $domination, self::getGameStateValue('galacticGoal') == PERSONALGROWTH ? 2 : 1);
			}
//
			if ($player_id === Factions::getPlayer($color))
			{
				foreach (Counters::listRevealed($color, 'star') as $counter) $result['factions'][$color]['revealed']['stars'][$counter] = Counters::getStatus($counter, 'back');
				foreach (Counters::listRevealed($color, 'relic') as $counter) $result['factions'][$color]['revealed']['relics'][$counter] = Counters::getStatus($counter, 'back');
				foreach (Ships::getAll(null, 'fleet') as $fleet)
				{
					if ($fleet['color'] === $color) $result['factions'][$color]['revealed']['fleets'][$fleet['id']] = ['fleet' => Ships::getStatus($fleet['id'], 'fleet'), 'ships' => Ships::getStatus($fleet['id'], 'ships')];
					else if (Ships::getStatus($fleet['id'], 'fleet') === 'D') $result['factions'][$color]['revealed']['fleets'][$fleet['id']] = ['fleet' => 'D', 'ships' => '?'];
				}
				$result['peace'] = Factions::getStatus($color, 'peace');
			}
//
			foreach ($this->domination->getPlayerHand($color) as $domination) $result['factions'][$color]['domination'][$domination['id']] = ($player_id === Factions::getPlayer($color)) ? $domination['type'] : 'back';
			$result['factions'][$color]['ships'] = 16 - sizeof(Ships::getAll($color, 'ship'));
		}
//
		$result['sectors'] = Sectors::getAllDatas();
		foreach ($result['sectors'] as $position => $sector)
		{
			$locations = [];
			foreach (array_keys(Sectors::SHAPES[$sector['sector']]) as $location) $locations[$location] = Sectors::rotate($location, -$sector['orientation']);
			$result['sectors'][$position]['shape'] = array_combine($locations, Sectors::SHAPES[$sector['sector']]);
		}
//
		$result['ships'] = Ships::getAllDatas($player_id);
		$result['counters'] = Counters::getAllDatas();
//
		return $result;
	}
	/**
	 * Changes values of multiactivity in db, does not sent notifications.
	 * To send notifications after use updateMultiactiveOrNextState
	 * @param number $player_id, player id <=0 or null - means ALL
	 * @param number $value - 1 multiactive, 0 non multiactive
	 */
	function dbSetPlayerMultiactive($player_id = -1, $value = 1)
	{
		$value = $value ? 1 : 0;
		$sql = "UPDATE player SET player_is_multiactive = '$value' WHERE player_zombie = 0 and player_eliminated = 0";
		if ($player_id > 0) $sql .= " AND player_id = $player_id";
		self::DbQuery($sql);
	}
	function dbGetScore(int $player_id): int
	{
		return intval(self::getUniqueValueFromDB("SELECT player_score FROM player WHERE player_id=$player_id"));
	}
	function dbSetScore(int $player_id, int $score, int $score_aux = 0): void
	{
		self::DbQuery("UPDATE player SET player_score=$score, player_score_aux=$score_aux WHERE player_id = $player_id");
		self::notifyAllPlayers('updateScore', '', ['player_id' => $player_id, 'score' => $score]);
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
		if ($from_version <= '2308311759' && in_array($this->table_id, []))
		{

		}
	}
	function PJL()
	{
		$my_player_id = 2317749;
		$tomodify = ['player' => ['player_id'], 'factions' => ['player_id'], 'global' => ['global_value'], 'stats' => ['stats_player_id']];
		$players_id = [];
		foreach (self::getObjectListFromDB("SELECT * from player") as $player) $players_id[] = $player['player_id'];
		for ($i = 0;
			$i < sizeof($players_id);
			$i++) foreach ($tomodify as $table => $fields) foreach ($fields as $field) $this->DbQuery(sprintf("UPDATE `%s` SET `%s`=%d WHERE `%s`=%d", $table, $field, $my_player_id + $i, $field, $players_id [$i]));
		$this->notifyAllPlayers('loadGame', 'Refreshing interface', ['id' => -1, 'n' => 0]);
	}
	function triggerEvent(int $new_state, string $new_active_faction)
	{
		self::DbQuery("INSERT INTO stack (new_state, new_active_faction) VALUES ($new_state, '$new_active_faction')");
	}
	function triggerAndNextState(string $nextState)
	{
		$event = $this->getObjectFromDB("SELECT * FROM stack WHERE new_state <> 0 ORDER BY id LIMIT 1");
		if ($event)
		{
			$state = $this->gamestate->state_id();
			$old_state = $nextState ? $this->gamestate->state()['transitions'][$nextState] : 0;
			$old_active_faction = Factions::getActive() ?? 0;
			self::DbQuery("UPDATE stack SET trigger_state = $state, old_state = $old_state, old_active_faction = '$old_active_faction' WHERE id = $event[id]");
//
			return $this->gamestate->jumpToState(PUSH_EVENT);
		}
		if ($nextState) $this->gamestate->nextState($nextState);
	}
	function stPushEvent()
	{
		$event = $this->getObjectFromDB("SELECT * FROM stack WHERE new_state <> 0 ORDER BY id LIMIT 1");
		if ($event)
		{
			self::DbQuery("UPDATE stack SET new_state = 0 WHERE id = $event[id]");
//
			if ($event['new_active_faction'] !== 'neutral') $this->gamestate->changeActivePlayer(Factions::getPlayer($event['new_active_faction']));
			return $this->gamestate->jumpToState($event['new_state']);
		}
	}
	function stPopEvent()
	{
		$event = $this->getObjectFromDB("SELECT * FROM stack WHERE new_state = 0 ORDER BY id DESC LIMIT 1");
		if ($event)
		{
			$this->DbQuery("DELETE FROM stack WHERE id = $event[id]");
//
			if ($event['old_active_faction'] > 0) $this->gamestate->changeActivePlayer(Factions::getPlayer($event['old_active_faction']));
			if ($event['old_state'] && $this->gamestate->states[$event['old_state']]['type'] === 'multipleactiveplayer') $this->gamestate->setAllPlayersMultiactive('next');
			if ($event['old_state']) return $this->gamestate->jumpToState($event['old_state']);
		}
	}
}
