<?php
//
// Galacic stories (4)
//
$this->STORIES = [
	JOURNEYS => clienttranslate('Journeys'),
	MIGRATIONS => clienttranslate('Migrations'),
	RIVALRY => clienttranslate('Rivalry'),
	WARS => clienttranslate('Wars')
];
//
// Galacic goals (8)
//
$this->GOALS = [
	NONE => clienttranslate('None'),
	CONTROL => clienttranslate('Control'),
	COOPERATION => clienttranslate('Cooperation'),
	DISCOVERY => clienttranslate('Discovery'),
	LEADERSHIP => clienttranslate('Leadership'),
	LEGACY => clienttranslate('Legacy'),
	PERSONALGROWTH => clienttranslate('Personal Growth'),
	POWER => clienttranslate('Power'),
	PRESENCE => clienttranslate('Presence')
];
//
// Domination deck (13)
//
$this->DOMINATION = [
	['type' => ACQUISITION, 'type_arg' => 0, 'nbr' => 1],
	['type' => ALIGNMENT, 'type_arg' => 0, 'nbr' => 1],
	['type' => CENTRAL, 'type_arg' => 0, 'nbr' => 1],
	['type' => DEFENSIVE, 'type_arg' => 0, 'nbr' => 1],
	['type' => DENSITY, 'type_arg' => 0, 'nbr' => 1],
	['type' => DIPLOMATIC, 'type_arg' => 0, 'nbr' => 1],
	['type' => ECONOMIC, 'type_arg' => 0, 'nbr' => 1],
	['type' => ETHERIC, 'type_arg' => 0, 'nbr' => 1],
	['type' => EXPLORATORY, 'type_arg' => 0, 'nbr' => 1],
	['type' => GENERALSCIENTIFIC, 'type_arg' => 0, 'nbr' => 1],
	['type' => MILITARY, 'type_arg' => 0, 'nbr' => 1],
	['type' => SPATIAL, 'type_arg' => 0, 'nbr' => 1],
	['type' => SPECIALSCIENTIFIC, 'type_arg' => 0, 'nbr' => 1],
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
// Star Peoples (17)
//
$this->STARPEOPLES = [
	'Alliance' => ['STO' => clienttranslate('Alliance of Light'), 'STS' => clienttranslate('Alliance of Darkness')],
	'Anchara' => ['STO' => clienttranslate('Anchara Coalition'), 'STS' => clienttranslate('Anchara Coalition')],
	'Annunaki' => ['STO' => clienttranslate('Annunaki'), 'STS' => clienttranslate('Annunaki')],
	'Avians' => ['STO' => clienttranslate('Avians'), 'STS' => clienttranslate('Avians')],
	'Caninoids' => ['STO' => clienttranslate('Caninoids'), 'STS' => clienttranslate('Caninoids')],
	'Dracos' => ['STO' => clienttranslate('Dracos'), 'STS' => clienttranslate('Dracos')],
	'Felines' => ['STO' => clienttranslate('Felines'), 'STS' => clienttranslate('Felines')],
	'Galactic' => ['STO' => clienttranslate('Galactic Confederation'), 'STS' => clienttranslate('Galactic Empire')],
	'Greys' => ['STO' => clienttranslate('Greys'), 'STS' => clienttranslate('Greys')],
	'ICC' => ['STO' => clienttranslate('ICC'), 'STS' => clienttranslate('ICC')],
	'Mantids' => ['STO' => clienttranslate('Mantids'), 'STS' => clienttranslate('Mantids')],
	'Mayans' => ['STO' => clienttranslate('Cosmic Mayans'), 'STS' => clienttranslate('Cosmic Mayans')],
	'Orion' => ['STO' => clienttranslate('Orion Republic'), 'STS' => clienttranslate('Orion Empire')],
	'Plejars' => ['STO' => clienttranslate('Plejars'), 'STS' => clienttranslate('Plejars')],
	'Progenitors' => ['STO' => clienttranslate('Progenitors'), 'STS' => clienttranslate('Progenitors')],
	'Rogue' => ['STO' => clienttranslate('Rogue AI'), 'STS' => clienttranslate('Rogue AI')],
	'Yowies' => ['STO' => clienttranslate('Yowies'), 'STS' => clienttranslate('Yowies')],
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
	clienttranslate('Ancient Pyramids'),
	clienttranslate('Ancient Technology: Genetics'),
	clienttranslate('Ancient Technology: Military'),
	clienttranslate('Ancient Technology: Propulsion'),
	clienttranslate('Ancient Technology: Robotics'),
	clienttranslate('Ancient Technology: Spirituality'),
	clienttranslate('Buried Ships'),
	clienttranslate('Planetary Death Ray'),
	clienttranslate('Defense Grid'),
	clienttranslate('Super-Stargate')
];

//
// <editor-fold defaultstate="collapsed" desc="Description of sectors">
//
$this->SECTORS = [
	0 => [
		'+0+0+0' => clienttranslate('Arcuturus'),
		'-1+0+1' => clienttranslate('Apu'),
		'+0-1+1' => clienttranslate('Janos'),
		'-1-1+2' => clienttranslate('Tiamat'),
		'+2+0-2' => clienttranslate('Sidar'),
		'+3-2-1' => clienttranslate('Inxtria'),
		'-1+3-2' => clienttranslate('Teetonia'),
		'+4-4+0' => clienttranslate('Zenetae'),
	],
	1 => [
		'+0+0+0' => clienttranslate('Arcuturus'),
	],
	2 => [
		'+0+0+0' => clienttranslate('Khaa'),
		'+0+2-2' => clienttranslate('Capella'),
		'+0-3+3' => clienttranslate('Yagsisa'),
		'+3-1-2' => clienttranslate('Cygnus'),
		'-1+3-2' => clienttranslate('Canus Major'),
		'-3+3+0' => clienttranslate('Maldek'),
		'-4+0+4' => clienttranslate('Vega'),
	],
	3 => [
		'+0+0+0' => clienttranslate('Khaa'),
		'+0-2+2' => clienttranslate('Capela'),
		'-3+0+3' => clienttranslate('Maldek'),
		'-1-2+3' => clienttranslate('Canus Major'),
		'+3-2-1' => clienttranslate('Cygnus'),
		'+0+3-3' => clienttranslate('Yagsisa'),
		'-4+4+0' => clienttranslate('Vega'),
	],
	4 => [
		'+0+0+0' => clienttranslate('Alpha Draconis'),
		'+0+1-1' => clienttranslate('Mars'),
		'-3+0+3' => clienttranslate('Taygeta'),
		'+3+0-3' => clienttranslate('Marcab'),
		'+0+3-3' => clienttranslate('Deneb'),
		'-2+3-1' => clienttranslate('ICC 13'),
		'-3+1+2' => clienttranslate('Serpo'),
		'+4-4+0' => clienttranslate('Korender'),
	],
	5 => [
		'+0+0+0' => clienttranslate('Alpha Draconis'),
	],
	6 => [
		'+0+0+0' => clienttranslate('Zeta Reticuli'),
		'-1+2-1' => clienttranslate('Hyades'),
		'+1-3+2' => clienttranslate('Alnilan'),
		'+3-2-1' => clienttranslate('Thiaoouba'),
		'+0+3-3' => clienttranslate('Altair'),
		'-3+1+2' => clienttranslate('Koshnak'),
		'+1-4+3' => clienttranslate('Ba\'avi'),
	],
	7 => [
		'+0+0+0' => clienttranslate('Zeta Reticuli'),
	],
	8 => [
		'+0+0+0' => clienttranslate('Sirius'),
		'+0-2+2' => clienttranslate('Ummo'),
		'+1+1-2' => clienttranslate('Harus'),
		'+0+2-2' => clienttranslate('Planet X'),
		'+3-3+0' => clienttranslate('Iarga'),
		'+3+0-3' => clienttranslate('Epsilon Eridani'),
		'-3+3+0' => clienttranslate('Axthada'),
		'-2-2+4' => clienttranslate('Fomalhaut'),
	],
	9 => [
		'+0+0+0' => clienttranslate('Sirius'),
	],
	10 => [
		'+0+0+0' => clienttranslate('Pleiades'),
		'-1-1+2' => clienttranslate('Tishtae'),
		'+2-3+1' => clienttranslate('Nibiru'),
		'+3+0-3' => clienttranslate('Bellatrix'),
		'-1+3-2' => clienttranslate('Mintaka'),
		'-2-2+4' => clienttranslate('Onoogi'),
		'-4+3+1' => clienttranslate('Tau Ceti'),
	],
	11 => [
		'+0+0+0' => clienttranslate('Pleiades'),
	],
	12 => [
		'+0+0+0' => clienttranslate('Lyra'),
		'+1+1-2' => clienttranslate('Kappa Fornacis'),
		'-2+1+1' => clienttranslate('Izar'),
		'-3+0+3' => clienttranslate('Sagittarius B'),
		'+3-1-2' => clienttranslate('Giliese 581'),
		'-2+3-1' => clienttranslate('Procyon'),
		'+1+3-4' => clienttranslate('Uru'),
	],
	13 => [
		'+0+0+0' => clienttranslate('Lyra'),
	],
	14 => [
		'+0+0+0' => clienttranslate('Xilox'),
	],
	15 => [
		'+0+0+0' => clienttranslate('Xilox'),
	],
	16 => [
		'+0+0+0' => clienttranslate('Rigel'),
	],
	17 => [
		'+0+0+0' => clienttranslate('Rigel'),
	],
];
// </editor-fold>
//
