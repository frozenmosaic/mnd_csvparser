CREATE TABLE `cs_menugroup` (
  `menugroupid` int(11) NOT NULL AUTO_INCREMENT,
  `locationid` int(11) DEFAULT NULL,
  `menugroupname` varchar(50) DEFAULT NULL,
  `description` varchar(50) DEFAULT NULL,
  `sequenceorder` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `no_of_different_items` int(10) DEFAULT NULL COMMENT 'How many different items can be added from this group',
  `is_dedicated` int(1) DEFAULT NULL COMMENT '1- is dedicated to specials',
  `is_default` tinyint(1) DEFAULT '1' COMMENT 'Is default show',
  `is_visible` tinyint(1) DEFAULT '1' COMMENT 'is visible',
  PRIMARY KEY (`menugroupid`),
  KEY `fi0` (`locationid`),
  KEY `menugroupid` (`menugroupid`)
) ENGINE=MyISAM AUTO_INCREMENT=10643 DEFAULT CHARSET=latin1 COMMENT='InnoDB free: 9216 kB; (locationid) REFER cruzstar_v01/cs_loc';

CREATE TABLE `cs_menucategory` (
  `catid` int(11) NOT NULL AUTO_INCREMENT,
  `menugroupid` int(11) DEFAULT NULL,
  `description` text,
  `sequenceorder` int(11) DEFAULT NULL,
  `categoryname` varchar(50) DEFAULT NULL,
  `size` int(11) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `special` tinyint(4) DEFAULT '0',
  `image` varchar(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`catid`),
  KEY `fi0` (`menugroupid`)
) ENGINE=MyISAM AUTO_INCREMENT=66757 DEFAULT CHARSET=latin1 COMMENT='InnoDB free: 9216 kB; (menugroupid) REFER cruzstar_v01/cs_me';

CREATE TABLE `cs_menuitem` (
  `menuitemid` int(11) NOT NULL AUTO_INCREMENT,
  `catid` int(11) DEFAULT NULL,
  `itemname` varchar(100) DEFAULT NULL,
  `description` text,
  `sequenceorder` int(11) NOT NULL DEFAULT '1',
  `allowperorder` int(11) DEFAULT NULL,
  `minperorder` tinyint(4) NOT NULL DEFAULT '1',
  `is_special` int(1) NOT NULL DEFAULT '0',
  `availabilitydays` varchar(50) DEFAULT NULL,
  `timeofday` varchar(50) DEFAULT NULL,
  `itempicture` varchar(50) DEFAULT NULL,
  `status` tinyint(4) DEFAULT NULL,
  `pos_name` varchar(100) DEFAULT NULL,
  `availability_for_del_pick` varchar(255) DEFAULT NULL,
  `available_time_from` time DEFAULT NULL,
  `available_time_to` time DEFAULT NULL,
  `available_date_to` date DEFAULT NULL,
  `available_date_from` date DEFAULT NULL,
  `allow_special_instructions` tinyint(1) NOT NULL DEFAULT '1',
  `visible_type` enum('ALWAYS','SPECIFIC','NEVER') NOT NULL DEFAULT 'ALWAYS',
  `available_type` enum('ALWAYS','SPECIFIC','NEVER') NOT NULL DEFAULT 'ALWAYS',
  `day_or_date_range_avail` int(1) NOT NULL DEFAULT '0',
  `day_or_date_range_visible` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`menuitemid`),
  KEY `fi0` (`catid`)
) ENGINE=MyISAM AUTO_INCREMENT=450105 DEFAULT CHARSET=latin1 COMMENT='InnoDB free: 9216 kB; (catid) REFER cruzstar_v01/cs_menucate'