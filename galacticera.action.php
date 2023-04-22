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
	public function move()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$location = self::getArg("location", AT_json, true);
		$ships = self::getArg("ships", AT_json, false);
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
		$counters = self::getArg("counters", AT_json, false);
		$this->game->acSelectCounters($color, $counters);
//
		self::ajaxResponse("");
	}
	public function research()
	{
		self::setAjaxMode();
//
		$color = self::getArg("color", AT_alphanum, true);
		$technology = self::getArg("technology", AT_alphanum, false);
		$this->game->acResearch($color, $technology);
//
		self::ajaxResponse("");
	}
}
