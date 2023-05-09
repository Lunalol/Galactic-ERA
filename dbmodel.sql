CREATE TABLE IF NOT EXISTS `factions`
(
    `color` ENUM ('neutral','FF3333','00CC00','6666FF','FF9900','CD1FCD','FFFF00') PRIMARY KEY,
    `player_id` INT,`activation` ENUM ('no','yes','done'),
	`starPeople` ENUM ('none','Alliance','Anchara','Annunaki','Avians','Caninoids','Dracos','Felines','Galactic','Greys','ICC','Mantids','Mayans','Orion','Plejars','Progenitors','Rogue','Yowies','Farmers','Slavers'),
	`alignment` ENUM ('STO','STS'),`DP` INT(2) DEFAULT 0,`population` INT(2) DEFAULT 0,
	`Military` INT(1) DEFAULT 1,`Spirituality` INT(1) DEFAULT 1,`Propulsion` INT(1) DEFAULT 1,`Robotics` INT(1) DEFAULT 1,`Genetics` INT(1) DEFAULT 1,
	`homeStar` INT(1),`order` INT(1),`status` JSON
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `sectors`
(
	`position` INT(1) COMMENT '0 = center,1 = NE,2 = E,3 = SE,4 = SW,5 = W,6 = NW,0 = Center' PRIMARY KEY,
	`sector` INT(2) NOT null COMMENT 'EVEN = front side,ODD = back side',
	`orientation` INT(1) NOT null COMMENT 'Rotation: orientation ⨯ 60° clockwise'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `counters`
(
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `color` ENUM ('neutral','FF3333','00CC00','6666FF','FF9900','CD1FCD','FFFF00'),
    `type` ENUM ('star','relic','wormhole','populationDisk'),
    `location` CHAR(8),
	`status` JSON,
    INDEX (`type`),INDEX (`color`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `revealed`
(
    `color` ENUM ('neutral','FF3333','00CC00','6666FF','FF9900','CD1FCD','FFFF00'),
	`type` ENUM ('star', 'relic', 'dominationCard'), `id` INT,
    PRIMARY KEY(`color`,`type`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `ships`
(
    `id` INT PRIMARY KEY AUTO_INCREMENT,`activation` ENUM ('no','yes','done'),
    `color` ENUM ('neutral','FF3333','00CC00','6666FF','FF9900','CD1FCD','FFFF00'),
	`fleet` ENUM ('homeStar','ship', 'fleet'),
	`MP` INT(1) DEFAULT 0,`location` CHAR(8),
	`status` JSON,
    INDEX (`location`),INDEX (`color`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `domination` (
  `card_id` INT(10) PRIMARY KEY AUTO_INCREMENT,
  `card_type` VARCHAR(16) NOT NULL,
  `card_type_arg` INT(1) NOT NULL,
  `card_location` VARCHAR(16) NOT NULL,
  `card_location_arg` ENUM ('0','1','2','3','4','5','6','7','8','9','10','11','12','FF3333','00CC00','6666FF','FF9900','CD1FCD','FFFF00') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `undo`
(
    `undoID` INT,`id` INT,
    `color` ENUM ('neutral','FF3333','00CC00','6666FF','FF9900','CD1FCD','FFFF00'),
    `type` ENUM ('move'),
    `status` JSON,
    PRIMARY KEY(`undoID`,`id`,`color`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
