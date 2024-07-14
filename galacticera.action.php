<?php

class action_galacticera extends APP_GameAction
{
	public function __default()
	{
		if (self::isArg('notifwindow'))
		{
			$this->view = "common_notifwindow";
			$this->viewArgs['table'] = self::getArg("table", AT_posint, true);
		}
		else
		{
			$this->view = "galacticera_galacticera";
			self::trace("Complete reinitialization of board game");
		}
	}
	public function GODMODE()
	{
		self::setAjaxMode();
		$god = self::getArg("god", AT_json, false);
		$this->game->acGODMODE($god);
		self::ajaxResponse();
	}
	public function getGame()
	{
		self::setAjaxMode();
		self::ajaxResponseWithResult($this->game->getGame());
	}
	public function continue()
	{
		self::setAjaxMode();
//
		$this->game->acContinue();
//
		self::ajaxResponse("");
	}
	public function null()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$skip = self::getArg("skip", AT_bool, true);
		$this->game->acNull($color, $skip);
//
		self::ajaxResponse("");
	}
	public function levelOfDifficulty()
	{
		self::setAjaxMode();
//
		$levelOfDifficulty = intval(self::getArg("levelOfDifficulty", AT_int, true));
		$this->game->acLevelOfDifficulty($levelOfDifficulty,);
//
		self::ajaxResponse("");
	}
	public function starPeopleChoice()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$starPeople = self::getArg("starPeople", AT_alphanum, true);
		$this->game->acStarPeopleChoice($color, $starPeople);
//
		self::ajaxResponse("");
	}
	public function alignmentChoice()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$alignment = self::getArg("alignment", AT_bool, true);
		$this->game->acAlignmentChoice($color, $alignment);
//
		self::ajaxResponse("");
	}
	public function individualChoice()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$technology = self::getArg("technology", AT_alphanum, true);
		$this->game->acIndividualChoice($color, $technology);
//
		self::ajaxResponse("");
	}
	public function advancedFleetTactics()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$fleet = self::getArg("fleet", AT_alphanum, true);
		$tactics = self::getArg("tactics", AT_alphanum, true);
		$this->game->acAdvancedFleetTactics($color, $fleet, $tactics);
//
		self::ajaxResponse("");
	}
	public function shipsToFleet()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$fleet = self::getArg("fleet", AT_alphanum, true);
		$ships = self::getArg("ships", AT_json, true);
		$this->game->acShipsToFleet($color, $fleet, $ships);
//
		self::ajaxResponse("");
	}
	public function fleetToFleet()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$from = self::getArg("from", AT_alphanum, true);
		$to = self::getArg("to", AT_alphanum, true);
		$ships = self::getArg("ships", AT_int, true);
		$this->game->acFleetToFleet($color, $from, $to, $ships);
//
		self::ajaxResponse("");
	}
	public function fleetToShips()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$fleet = self::getArg("fleet", AT_alphanum, true);
		$ships = self::getArg("ships", AT_int, true);
		$this->game->acFleetToShips($color, $fleet, $ships);
//
		self::ajaxResponse("");
	}
	public function swapFleets()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$fleets = self::getArg("fleets", AT_json, true);
		$this->game->acSwapFleets($color, $fleets);
//
		self::ajaxResponse("");
	}
	public function done()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$this->game->acDone($color);
//
		self::ajaxResponse("");
	}
	public function declareWar()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$on = self::getArg("on", AT_alphanum, true);
		$this->game->acDeclareWar($color, $on);
//
		self::ajaxResponse("");
	}
	public function declarePeace()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$on = self::getArg("on", AT_alphanum, true);
		$this->game->acDeclarePeace($color, $on);
//
		self::ajaxResponse("");
	}
	public function acceptPeace()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$from = self::getArg("from", AT_alphanum, true);
		$this->game->acAcceptPeace($color, $from);
//
		self::ajaxResponse("");
	}
	public function rejectPeace()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$from = self::getArg("from", AT_alphanum, true);
		$this->game->acRejectPeace($color, $from);
//
		self::ajaxResponse("");
	}
	public function remoteViewing()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$ancientPyramids = self::getArg("ancientPyramids", AT_bool, true);
		$type = self::getArg("type", AT_alphanum, true);
		$id = self::getArg("id", AT_alphanum, true);
		$this->game->acRemoteViewing($color, $ancientPyramids, $type, $id);
//
		self::ajaxResponse("");
	}
	public function planetaryDeathRay()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$type = self::getArg("type", AT_alphanum, true);
		$id = self::getArg("id", AT_int, true);
		$this->game->acPlanetaryDeathRay($color, $type, $id);
//
		self::ajaxResponse("");
	}
	public function scout()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$ships = self::getArg("ships", AT_json, true);
		$this->game->acScout($color, $ships);
//
		self::ajaxResponse("");
	}
	public function move()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$location = self::getArg("location", AT_json, true);
		$ships = self::getArg("ships", AT_json, true);
		$this->game->acMove($color, $location, $ships);
//
		self::ajaxResponse("");
	}
	public function undo()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$this->game->acUndo($color);
//
		self::ajaxResponse("");
	}
	public function pass()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$this->game->acPass($color);
//
		self::ajaxResponse("");
	}
	public function stealTechnology()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$technology = self::getArg("technology", AT_alphanum, true);
		$this->game->acStealTechnology($color, $technology);
//
		self::ajaxResponse("");
	}
	public function homeStarEvacuation()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$location = self::getArg("location", AT_json, false);
		$this->game->acHomeStarEvacuation($color, $location);
//
		self::ajaxResponse("");
	}
	public function combatChoice()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$location = self::getArg("location", AT_json, true);
		$this->game->acCombatChoice($color, $location);
//
		self::ajaxResponse("");
	}
	public function retreat()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$location = self::getArg("location", AT_json, true);
		$this->game->acRetreat($color, $location);
//
		self::ajaxResponse("");
	}
	public function battleLoss()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$ships = self::getArg("ships", AT_json, true);
		$this->game->acBattleLoss($color, $ships);
//
		self::ajaxResponse("");
	}
	public function Anchara()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$counter = self::getArg("counter", AT_alphanum, true);
		$this->game->acAnchara($color, $counter);
//
		self::ajaxResponse("");
	}
	public function selectCounters()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$counters = self::getArg("counters", AT_json, true);
		$this->game->acSelectCounters($color, $counters);
//
		self::ajaxResponse("");
	}
	public function blockAction()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$blocked = self::getArg("blocked", AT_bool, true);
		$this->game->acBlockAction($color, $blocked);
//
		self::ajaxResponse("");
	}
	public function blockMovement()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$blocked = self::getArg("blocked", AT_bool, true);
		$this->game->acBlockMovement($color, $blocked);
//
		self::ajaxResponse("");
	}
	public function switchAlignment()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$this->game->acSwitchAlignment($color);
//
		self::ajaxResponse("");
	}
	public function research()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$technologies = self::getArg("technologies", AT_json, true);
		$this->game->acResearch($color, $technologies);
//
		self::ajaxResponse("");
	}
	public function researchPlus()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$technology = self::getArg("technology", AT_alphanum, true);
		$otherColor = self::getArg("otherColor", AT_alphanum, false);
		$growthAction = self::getArg("growthAction", AT_alphanum, false);
		$otherTechnology = self::getArg("otherTechnology", AT_alphanum, false);
		$this->game->acResearchPlus($color, $technology, $otherColor, $growthAction, $otherTechnology);
//
		self::ajaxResponse("");
	}
	public function gainStar()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$location = self::getArg("location", AT_json, true);
		$center = self::getArg("center", AT_json, true);
		$this->game->acGainStar($color, $location, $center);
//
		self::ajaxResponse("");
	}
	public function growPopulation()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$locations = self::getArg("locations", AT_json, true);
		$locationsBonus = self::getArg("locationsBonus", AT_json, true);
		$bonus = self::getArg("bonus", AT_json, true);
		$this->game->acGrowPopulation($color, $locations, $locationsBonus, $bonus);
//
		self::ajaxResponse("");
	}
	public function removePopulation()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$locations = self::getArg("locations", AT_json, true);
		$this->game->acRemovePopulation($color, $locations);
//
		self::ajaxResponse("");
	}
	public function teleportPopulation()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$from = self::getArg("from", AT_json, true);
		$to = self::getArg("to", AT_json, true);
		$this->game->acTeleportPopulation($color, $from, $to);
//
		self::ajaxResponse("");
	}
	public function buildShips()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$buildShips = self::getArg("buildShips", AT_json, true);
		$this->game->acBuildShips($color, $buildShips);
//
		self::ajaxResponse("");
	}
	public function trade()
	{
		self::setAjaxMode();
//
		$from = self::getArg("from", AT_alphanum, true);
		$to = self::getArg("to", AT_alphanum, true);
		$technology = self::getArg("technology", AT_alphanum, true);
		$toTeach = self::getArg("toTeach", AT_alphanum, false);
		$this->game->acTrade($from, $to, $technology, $toTeach);
//
		self::ajaxResponse("");
	}
	public function dominationCardExchange()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$id = self::getArg("id", AT_int, true);
		$this->game->acDominationCardExchange($color, $id);
//
		self::ajaxResponse("");
	}
	public function domination()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$id = self::getArg("id", AT_int, true);
		$section = self::getArg("section", AT_alphanum, true);
		$effect = self::getArg("effect", AT_bool, true);
		$this->game->acDomination($color, $id, $section, $effect);
//
		self::ajaxResponse("");
	}
	public function oneTimeEffect()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$json = self::getArg("json", AT_json, true);
		$this->game->acOneTimeEffect($color, $json);
//
		self::ajaxResponse("");
	}
}
