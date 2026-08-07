-- Cities (települések) — country + county, ZIP / coordinates.

CREATE TABLE IF NOT EXISTS `cities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` int(11) NOT NULL,
  `county_id` int(10) unsigned NOT NULL,
  `shortname` varchar(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `zip` varchar(10) DEFAULT NULL,
  `lat` varchar(20) NOT NULL COMMENT 'gmap',
  `lng` varchar(20) NOT NULL COMMENT 'gmap',
  `lat2` varchar(20) NOT NULL COMMENT 'import txt',
  `lng2` varchar(20) NOT NULL COMMENT 'import txt',
  PRIMARY KEY (`id`),
  KEY `shortname` (`shortname`),
  KEY `country_id` (`country_id`),
  KEY `county_id` (`county_id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Settlements / ZIP rows; clubs.city_id soft-references id';
