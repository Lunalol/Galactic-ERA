<?php
//
// Galacic stories (4)
//
$this->STORIES = [
	NONE => clienttranslate('None'),
	JOURNEYS => clienttranslate('Journeys'),
	MIGRATIONS => clienttranslate('Migrations'),
	RIVALRY => clienttranslate('Rivalry'),
	WAR => clienttranslate('War')
];
//
// Galacic goals (8)
//
$this->GOALS = [
	NONE => clienttranslate('None'),
	CONTROL => clienttranslate('Control'),
//	COOPERATION => clienttranslate('Cooperation'),
//	DISCOVERY => clienttranslate('Discovery'),
//	LEADERSHIP => clienttranslate('Leadership'),
	LEGACY => clienttranslate('Legacy'),
	PERSONALGROWTH => clienttranslate('Personal Growth'),
	POWER => clienttranslate('Power'),
	PRESENCE => clienttranslate('Presence')
];
//
// Domination deck (13)
//
$this->DOMINATION = [
	ACQUISITION => ['type' => ACQUISITION, 'type_arg' => 0, 'nbr' => 1],
	ALIGNMENT => ['type' => ALIGNMENT, 'type_arg' => 0, 'nbr' => 1],
	CENTRAL => ['type' => CENTRAL, 'type_arg' => 0, 'nbr' => 1],
	DEFENSIVE => ['type' => DEFENSIVE, 'type_arg' => 0, 'nbr' => 1],
	DENSITY => ['type' => DENSITY, 'type_arg' => 0, 'nbr' => 1],
	DIPLOMATIC => ['type' => DIPLOMATIC, 'type_arg' => 0, 'nbr' => 1],
	ECONOMIC => ['type' => ECONOMIC, 'type_arg' => 0, 'nbr' => 1],
	ETHERIC => ['type' => ETHERIC, 'type_arg' => 0, 'nbr' => 1],
	EXPLORATORY => ['type' => EXPLORATORY, 'type_arg' => 0, 'nbr' => 1],
	GENERALSCIENTIFIC => ['type' => GENERALSCIENTIFIC, 'type_arg' => 0, 'nbr' => 1],
	MILITARY => ['type' => MILITARY, 'type_arg' => 0, 'nbr' => 1],
	SPATIAL => ['type' => SPATIAL, 'type_arg' => 0, 'nbr' => 1],
	SPECIALSCIENTIFIC => ['type' => SPECIALSCIENTIFIC, 'type_arg' => 0, 'nbr' => 1],
];
//
// Domination cards (13)
//
$this->DOMINATIONCARDS = [
	ACQUISITION => clienttranslate('Acquisition'),
	ALIGNMENT => clienttranslate('Alignment'),
	CENTRAL => clienttranslate('Central'),
	DEFENSIVE => clienttranslate('Defensive'),
	DENSITY => clienttranslate('Density'),
	DIPLOMATIC => clienttranslate('Diplomatic'),
	ECONOMIC => clienttranslate('Economic'),
	ETHERIC => clienttranslate('Etheric'),
	EXPLORATORY => clienttranslate('Exploratory'),
	GENERALSCIENTIFIC => clienttranslate('General Scientific'),
	MILITARY => clienttranslate('Military'),
	SPATIAL => clienttranslate('Spatial'),
	SPECIALSCIENTIFIC => clienttranslate('Special Scientific'),
];
//
// Domination play phase (4)
//
$this->PHASES = [
	'startOfRound' => clienttranslate('Start of round'),
	'dominationRetreatPhase' => clienttranslate('Retreat before combat'),
	'dominationCombatPhase' => clienttranslate('Combat Phase'),
	'changeTurnOrder' => clienttranslate('Start of Growth Phase'),
	'tradingPhase' => clienttranslate('Trading Phase'),
	'scoringPhase' => clienttranslate('End of round')
];
//
// Star Peoples (17)
//
$this->STARPEOPLES = [
	'Alliance' => ['STO' => clienttranslate('Alliance of Light'), 'STS' => clienttranslate('Alliance of Darkness')],
	'Anchara' => ['STO' => clienttranslate('Anchara Coalition'), 'STS' => clienttranslate('Anchara Coalition')],
//	'Annunaki' => ['STO' => clienttranslate('Annunaki'), 'STS' => clienttranslate('Annunaki')],
	'Avians' => ['STO' => clienttranslate('Avians'), 'STS' => clienttranslate('Avians')],
	'Caninoids' => ['STO' => clienttranslate('Caninoids'), 'STS' => clienttranslate('Caninoids')],
	'Dracos' => ['STO' => clienttranslate('Dracos'), 'STS' => clienttranslate('Dracos')],
//	'Felines' => ['STO' => clienttranslate('Felines'), 'STS' => clienttranslate('Felines')],
//	'Galactic' => ['STO' => clienttranslate('Galactic Confederation'), 'STS' => clienttranslate('Galactic Empire')],
//	'Greys' => ['STO' => clienttranslate('Greys'), 'STS' => clienttranslate('Greys')],
	'ICC' => ['STO' => clienttranslate('ICC'), 'STS' => clienttranslate('ICC')],
	'Mantids' => ['STO' => clienttranslate('Mantids'), 'STS' => clienttranslate('Mantids')],
//	'Mayans' => ['STO' => clienttranslate('Cosmic Mayans'), 'STS' => clienttranslate('Cosmic Mayans')],
	'Orion' => ['STO' => clienttranslate('Orion Republic'), 'STS' => clienttranslate('Orion Empire')],
	'Plejars' => ['STO' => clienttranslate('Plejars'), 'STS' => clienttranslate('Plejars')],
//	'Progenitors' => ['STO' => clienttranslate('Progenitors'), 'STS' => clienttranslate('Progenitors')],
//	'Rogue' => ['STO' => clienttranslate('Rogue AI'), 'STS' => clienttranslate('Rogue AI')],
	'Yowies' => ['STO' => clienttranslate('Yowies'), 'STS' => clienttranslate('Yowies')],
#
	'Farmers' => ['STO' => clienttranslate('Genetic Farmers')],
	'Slavers' => ['STS' => clienttranslate('Slavers')],
#
];
//
// Technologies (5)
//
$this->TECHNOLOGIES = [
	'Military' => clienttranslate('Military'),
	'Spirituality' => clienttranslate('Spirituality'),
	'Propulsion' => clienttranslate('Propulsion'),
	'Robotics' => clienttranslate('Robotics'),
	'Genetics' => clienttranslate('Genetics')
];
//
// Relics (10)
//
$this->RELICS = [
	ANCIENTPYRAMIDS => clienttranslate('Ancient Pyramids'),
	ANCIENTTECHNOLOGYGENETICS => clienttranslate('Ancient Technology: Genetics'),
	ANCIENTTECHNOLOGYMILITARY => clienttranslate('Ancient Technology: Military'),
	ANCIENTTECHNOLOGYPROPULSION => clienttranslate('Ancient Technology: Propulsion'),
	ANCIENTTECHNOLOGYROBOTICS => clienttranslate('Ancient Technology: Robotics'),
	ANCIENTTECHNOLOGYSPIRITUALITY => clienttranslate('Ancient Technology: Spirituality'),
	BURIEDSHIPS => clienttranslate('Buried Ships'),
	PLANETARYDEATHRAY => clienttranslate('Planetary Death Ray'),
	DEFENSEGRID => clienttranslate('Defense Grid'),
	SUPERSTARGATE => clienttranslate('Super-Stargate')
];
//
// Growth actions (6)
//
$this->OVAL = ['research', 'growPopulation', 'gainStar', 'gainStar', 'buildShips', 'switchAlignment'];
//
// <editor-fold defaultstate="collapsed" desc="Description of sectors">
//
$this->SECTORS = [
	0 => [
		'+0+0+0' => /* clienttranslate */('Arcuturus'),
		'-1+0+1' => /* clienttranslate */('Apu'),
		'+0-1+1' => /* clienttranslate */('Janos'),
		'-1-1+2' => /* clienttranslate */('Tiamat'),
		'+2+0-2' => /* clienttranslate */('Sidar'),
		'+3-2-1' => /* clienttranslate */('Inxtria'),
		'-1+3-2' => /* clienttranslate */('Teetonia'),
		'+4-4+0' => /* clienttranslate */('Zenetae'),
	],
	1 => [
		'+0+0+0' => /* clienttranslate */('Arcuturus'),
		'-1+1+0' => /* clienttranslate */('Apu'),
		'+0+1-1' => /* clienttranslate */('Janos'),
		'-1+2-1' => /* clienttranslate */('Tiamat'),
		'+2-2+0' => /* clienttranslate */('Sidar'),
		'+3-1-2' => /* clienttranslate */('Inxtria'),
		'-1-2+3' => /* clienttranslate */('Teetonia'),
		'+4+0-4' => /* clienttranslate */('Zenetae'),
	],
	2 => [
		'+0+0+0' => /* clienttranslate */('Khaa'),
		'+0+2-2' => /* clienttranslate */('Capella'),
		'+0-3+3' => /* clienttranslate */('Yagsisa'),
		'+3-1-2' => /* clienttranslate */('Cygnus'),
		'-1+3-2' => /* clienttranslate */('Canus Major'),
		'-3+3+0' => /* clienttranslate */('Maldek'),
		'-4+0+4' => /* clienttranslate */('Vega'),
	],
	3 => [
		'+0+0+0' => /* clienttranslate */('Khaa'),
		'+0-2+2' => /* clienttranslate */('Capela'),
		'-3+0+3' => /* clienttranslate */('Maldek'),
		'-1-2+3' => /* clienttranslate */('Canus Major'),
		'+3-2-1' => /* clienttranslate */('Cygnus'),
		'+0+3-3' => /* clienttranslate */('Yagsisa'),
		'-4+4+0' => /* clienttranslate */('Vega'),
	],
	4 => [
		'+0+0+0' => /* clienttranslate */('Alpha Draconis'),
		'+0+1-1' => /* clienttranslate */('Mars'),
		'-3+0+3' => /* clienttranslate */('Taygeta'),
		'+3+0-3' => /* clienttranslate */('Marcab'),
		'+0+3-3' => /* clienttranslate */('Deneb'),
		'-2+3-1' => /* clienttranslate */('ICC 13'),
		'-3+1+2' => /* clienttranslate */('Serpo'),
		'+4-4+0' => /* clienttranslate */('Korender'),
	],
	5 => [
		'+0+0+0' => /* clienttranslate */('Alpha Draconis'),
		'+0-1+1' => /* clienttranslate */('Mars'),
		'-3+3+0' => /* clienttranslate */('Taygeta'),
		'+3-3+0' => /* clienttranslate */('Marcab'),
		'+0-3+3' => /* clienttranslate */('Deneb'),
		'-2-1+3' => /* clienttranslate */('ICC 13'),
		'-3+2+1' => /* clienttranslate */('Serpo'),
		'+4+0-4' => /* clienttranslate */('Korender'),
	],
	6 => [
		'+0+0+0' => /* clienttranslate */('Zeta Reticuli'),
		'-1+2-1' => /* clienttranslate */('Hyades'),
		'+1-3+2' => /* clienttranslate */('Alnilan'),
		'+3-2-1' => /* clienttranslate */('Thiaoouba'),
		'+0+3-3' => /* clienttranslate */('Altair'),
		'-3+1+2' => /* clienttranslate */('Koshnak'),
		'+1-4+3' => /* clienttranslate */('Ba\'avi'),
	],
	7 => [
		'+0+0+0' => /* clienttranslate */('Zeta Reticuli'),
		'-1-1+2' => /* clienttranslate */('Hyades'),
		'+1+2-3' => /* clienttranslate */('Alnilan'),
		'+3-1-2' => /* clienttranslate */('Thiaoouba'),
		'+0-3+3' => /* clienttranslate */('Altair'),
		'-3+2+1' => /* clienttranslate */('Koshnak'),
		'+1+3-4' => /* clienttranslate */('Ba\'avi'),
	],
	8 => [
		'+0+0+0' => /* clienttranslate */('Sirius'),
		'+0-2+2' => /* clienttranslate */('Ummo'),
		'+1+1-2' => /* clienttranslate */('Harus'),
		'+0+2-2' => /* clienttranslate */('Planet X'),
		'+3-3+0' => /* clienttranslate */('Iarga'),
		'+3+0-3' => /* clienttranslate */('Epsilon Eridani'),
		'-3+3+0' => /* clienttranslate */('Axthada'),
		'-2-2+4' => /* clienttranslate */('Fomalhaut'),
	],
	9 => [
		'+0+0+0' => /* clienttranslate */('Sirius'),
		'+0+2-2' => /* clienttranslate */('Ummo'),
		'+1-2+1' => /* clienttranslate */('Harus'),
		'+0-2+2' => /* clienttranslate */('Planet X'),
		'+3+0-3' => /* clienttranslate */('Iarga'),
		'+3-3+0' => /* clienttranslate */('Epsilon Eridani'),
		'-3+0+3' => /* clienttranslate */('Axthada'),
		'-2+4-2' => /* clienttranslate */('Fomalhaut'),
	],
	10 => [
		'+0+0+0' => /* clienttranslate */('Pleiades'),
		'-1-1+2' => /* clienttranslate */('Tishtae'),
		'+2-3+1' => /* clienttranslate */('Nibiru'),
		'+3+0-3' => /* clienttranslate */('Bellatrix'),
		'-1+3-2' => /* clienttranslate */('Mintaka'),
		'-2-2+4' => /* clienttranslate */('Onoogi'),
		'-4+3+1' => /* clienttranslate */('Tau Ceti'),
	],
	11 => [
		'+0+0+0' => /* clienttranslate */('Pleiades'),
		'-1+2-1' => /* clienttranslate */('Tishtae'),
		'+2+1-3' => /* clienttranslate */('Nibiru'),
		'+3-3+0' => /* clienttranslate */('Bellatrix'),
		'-1-2+3' => /* clienttranslate */('Mintaka'),
		'-2+4-2' => /* clienttranslate */('Onoogi'),
		'-4+1+3' => /* clienttranslate */('Tau Ceti'),
	],
	12 => [
		'+0+0+0' => /* clienttranslate */('Lyra'),
		'+1+1-2' => /* clienttranslate */('Kappa Fornacis'),
		'-2+1+1' => /* clienttranslate */('Izar'),
		'-3+0+3' => /* clienttranslate */('Sagittarius B'),
		'+3-1-2' => /* clienttranslate */('Giliese 581'),
		'-2+3-1' => /* clienttranslate */('Procyon'),
		'+1+3-4' => /* clienttranslate */('Uru'),
	],
	13 => [
		'+0+0+0' => /* clienttranslate */('Lyra'),
		'+1-2+1' => /* clienttranslate */('Kappa Fornacis'),
		'-2+1+1' => /* clienttranslate */('Izar'),
		'-3+3+0' => /* clienttranslate */('Sagittarius B'),
		'+3-2-1' => /* clienttranslate */('Giliese 581'),
		'-2-1+3' => /* clienttranslate */('Procyon'),
		'+1-4+3' => /* clienttranslate */('Uru'),
	],
	14 => [
		'+0+0+0' => /* clienttranslate */('Xilox'),
		'-2-1+3' => /* clienttranslate */('Neb-Heru'),
		'+2-3+1' => /* clienttranslate */('Aldebaran'),
		'+2+1-3' => /* clienttranslate */('Barnard\'s Star'),
		'+0+3-3' => /* clienttranslate */('Wolf 424'),
		'-3+3+0' => /* clienttranslate */('Alpha Centauri'),
		'+1+3-4' => /* clienttranslate */('Teka'),
	],
	15 => [
		'+0+0+0' => /* clienttranslate */('Xilox'),
		'-2+3-1' => /* clienttranslate */('Neb-Heru'),
		'+2+1-3' => /* clienttranslate */('Aldebaran'),
		'+2-3+1' => /* clienttranslate */('Barnard\'s Star'),
		'+0-3+3' => /* clienttranslate */('Wolf 424'),
		'-3+0+3' => /* clienttranslate */('Alpha Centauri'),
		'+1-4+3' => /* clienttranslate */('Teka'),
	],
	16 => [
		'+0+0+0' => /* clienttranslate */('Rigel'),
		'+0-1+1' => /* clienttranslate */('Blaveh'),
		'+1+0-1' => /* clienttranslate */('Intibi Ra II'),
		'+0-3+3' => /* clienttranslate */('Betelgeuse'),
		'+2+1-3' => /* clienttranslate */('New Erra'),
		'-4+0+4' => /* clienttranslate */('P\'taah'),
		'-2+4-2' => /* clienttranslate */('Denala'),
		'-3+4-1' => /* clienttranslate */('Ectom'),
	],
	17 => [
		'+0+0+0' => /* clienttranslate */('Rigel'),
		'+0+1-1' => /* clienttranslate */('Blaveh'),
		'+1-1+0' => /* clienttranslate */('Intibi Ra II'),
		'+0+3-3' => /* clienttranslate */('Betelgeuse'),
		'+2-3+1' => /* clienttranslate */('New Erra'),
		'-4+4+0' => /* clienttranslate */('P\'taah'),
		'-2-2+4' => /* clienttranslate */('Denala'),
		'-3-1+4' => /* clienttranslate */('Ectom'),
	],
];
// </editor-fold>
