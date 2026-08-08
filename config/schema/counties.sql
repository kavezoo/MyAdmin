-- Counties (megyék / régiók) — belonging to a country.

CREATE TABLE IF NOT EXISTS `counties` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` int(10) unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `shortname` varchar(100) NOT NULL,
  `capitalcity` varchar(100) NOT NULL,
  `region` varchar(100) NOT NULL,
  `pos` int(11) NOT NULL DEFAULT 1000,
  `visible` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `city_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'CounterCache: cities in this county',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `country_id` (`country_id`),
  KEY `name` (`name`),
  KEY `pos` (`pos`),
  KEY `visible` (`visible`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Counties / regions; cities.county_id soft-references id';
