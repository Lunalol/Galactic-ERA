<?php
define('STUDIO', strpos($_SERVER['HTTP_HOST'], "studio.boardgamearena.com") !== false);
define('FAST_START', 0);
define('DEBUG', 1);
//
define('PUSH_EVENT', 998);
define('POP_EVENT', 999);
define('HOMESTAREVACUATION', 1000);
define('EMERGENCYRESERVE', 1050);
define('DECLAREWAR', 1100);
define('STEALTECHNOLOGY', 1200);
define('REMOVEPOPULATION', 1300);
define('RESEARCHPLUS', 1500);
define('DOMINATION', 2000);
//
// Game options
//
define('GAME', 100);
define('GALACTICSTORY', 101);
define('GALACTICGOAL', 102);
define('DIFFICULTY', 103);
//
define('INTRODUCTORY', 0);
define('STANDARD', 1);
define('MANUAL', 2);
//
// Game Preferences
//
define('SPEED', 100);
define('SLOW', 0);
define('NORMAL', 1);
define('FAST', 2);
//
define('CONFIRM', 101);
define('ALWAYS', 0);
define('MOBILE', 1);
define('NEVER', 2);
//
// Game globals
//
define('ROUND', 10);
//
// Automas (2)
//
define('AUTOMA', 0);
define('FARMERS', -1);
define('SLAVERS', -2);
//
// Galacic stories (4)
//
define('JOURNEYS', 1);
define('MIGRATIONS', 2);
define('RIVALRY', 3);
define('WAR', 4);
//
// Galacic goals (8)
//
define('NONE', 0);
define('CONTROL', 1);
define('COOPERATION', 2);
define('DISCOVERY', 3);
define('LEADERSHIP', 4);
define('LEGACY', 5);
define('PERSONALGROWTH', 6);
define('POWER', 7);
define('PRESENCE', 8);
//
// Domination cards (13)
//
define('ACQUISITION', 0);
define('ALIGNMENT', 1);
define('CENTRAL', 2);
define('DEFENSIVE', 3);
define('DENSITY', 4);
define('DIPLOMATIC', 5);
define('ECONOMIC', 6);
define('ETHERIC', 7);
define('EXPLORATORY', 8);
define('GENERALSCIENTIFIC', 9);
define('MILITARY', 10);
define('SPATIAL', 11);
define('SPECIALSCIENTIFIC', 12);
//
// Gain star
//
define('COLONIZE', 1);
define('SUBJUGATE', 2);
define('LIBERATE', 3);
define('CONQUERVS', 4);
define('ALLY', 5);
define('CONQUER', 6);
//
// Ancient Relics (10
//
define('ANCIENTPYRAMIDS', 0);
define('ANCIENTTECHNOLOGYGENETICS', 1);
define('ANCIENTTECHNOLOGYMILITARY', 2);
define('ANCIENTTECHNOLOGYPROPULSION', 3);
define('ANCIENTTECHNOLOGYROBOTICS', 4);
define('ANCIENTTECHNOLOGYSPIRITUALITY', 5);
define('BURIEDSHIPS', 6);
define('PLANETARYDEATHRAY', 7);
define('DEFENSEGRID', 8);
define('SUPERSTARGATE', 9);
