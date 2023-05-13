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
	public function createFleet()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$fleet = self::getArg("fleet", AT_alphanum, true);
		$ships = self::getArg("ships", AT_json, true);
		$this->game->acCreateFleet($color, $fleet, $ships);
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
	public function removeViewing()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$counter = self::getArg("counter", AT_int, true);
		$this->game->acRemoteViewing($color, $counter);
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
	public function research()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$technology = self::getArg("technology", AT_alphanum, true);
		$this->game->acResearch($color, $technology);
//
		self::ajaxResponse("");
	}
	public function gainStar()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$location = self::getArg("location", AT_json, true);
		$this->game->acGainStar($color, $location);
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
		$this->game->acGrowPopulation($color, $locations, $locationsBonus);
//
		self::ajaxResponse("");
	}
	public function buildShips()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$locations = self::getArg("locations", AT_json, true);
		$this->game->acBuildShips($color, $locations);
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
		$this->game->acTrade($from, $to, $technology);
//
		self::ajaxResponse("");
	}
}
