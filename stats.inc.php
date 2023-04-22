<?php
$stats_type = array(
	// Statistics global to table
	"table" => array(
		"turns_number" => array("id" => 10,
			"name" => totranslate("Number of turns"),
			"type" => "int"),
	/*
	  Examples:


	  "table_teststat1" => array(   "id"=> 10,
	  "name" => totranslate("table test stat 1"),
	  "type" => "int" ),

	  "table_teststat2" => array(   "id"=> 11,
	  "name" => totranslate("table test stat 2"),
	  "type" => "float" )
	 */
	),
	// Statistics existing for each player
	"player" => array(
		"turns_number" => array("id" => 10,
			"name" => totranslate("Number of turns"),
			"type" => "int"),
	/*
	  Examples:


	  "player_teststat1" => array(   "id"=> 10,
	  "name" => totranslate("player test stat 1"),
	  "type" => "int" ),

	  "player_teststat2" => array(   "id"=> 11,
	  "name" => totranslate("player test stat 2"),
	  "type" => "float" )

	 */
	)
);
