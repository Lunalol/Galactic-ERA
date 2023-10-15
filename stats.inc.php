<?php
/**
 *
 * @author Lunalol - PERRIN Jean-Luc
 *
 */
$stats_type = [
	"table" =>
	[
		"difficulty" => ["id" => 11, "name" => totranslate("Solo variant difficulty level"), "type" => "int"],
//
	],
	"player" =>
	[
		"DP" => ["id" => 100, "name" => totranslate("Destiny Points (DP)"), "type" => "int"],
//
		"DP_GS" => ["id" => 101, "name" => totranslate("DP: Galactic Story"), "type" => "int"],
		"DP_GG" => ["id" => 102, "name" => totranslate("DP: Galactic Goal"), "type" => "int"],
		"DP_AFT" => ["id" => 103, "name" => totranslate("DP: Advanced Fleet Tactics"), "type" => "int"],
		"DP_DC_A" => ["id" => 104, "name" => totranslate("DP: Domination card (A)"), "type" => "int"],
		"DP_DC_B" => ["id" => 105, "name" => totranslate("DP: Domination card (B)"), "type" => "int"],
		"DP_POP" => ["id" => 106, "name" => totranslate("DP: Population Bonus"), "type" => "int"],
		"DP_MAJ" => ["id" => 107, "name" => totranslate("DP: Sector Majority"), "type" => "int"],
		"DP_SP" => ["id" => 108, "name" => totranslate("DP: Star People"), "type" => "int"],
//
		"easy" => ["id" => 201, "name" => totranslate('Best Solo score (easy)'), "type" => "int"],
		"standard" => ["id" => 202, "name" => totranslate('Best Solo score (standard)'), "type" => "int"],
		"hard" => ["id" => 203, "name" => totranslate('Best Solo score (hard)'), "type" => "int"],
		"insane" => ["id" => 204, "name" => totranslate('Best Solo score (insane)'), "type" => "int"],
	],
//
	"value_labels" => [
		11 => [
			0 => totranslate("Easy"),
			1 => totranslate("Standard"),
			2 => totranslate("Hard"),
			3 => totranslate("Insane"),
		],
	]
];
