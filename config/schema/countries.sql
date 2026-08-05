-- Countries (ISO 3166-1) + primary locale + continent_id
-- Seed continents: php tmp/seed_continents.php
-- Translations: i18n (Countries.name, Continents.name)
-- Per-active-country language visibility: country_visibilities.sql + seed_country_visibilities.php

CREATE TABLE IF NOT EXISTS `countries` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `iso2` char(2) NOT NULL COMMENT 'ISO 3166-1 alpha-2',
  `name` varchar(150) NOT NULL COMMENT 'English canonical name',
  `endonim_name` varchar(150) NOT NULL COMMENT 'Endonym — native script (e.g. Magyarország, Россия, 中国)',
  `locale` varchar(10) NOT NULL COMMENT 'Primary locale e.g. hu_HU',
  `timezone` varchar(64) NOT NULL DEFAULT 'UTC' COMMENT 'IANA timezone e.g. Europe/Budapest',
  `phone_prefix` varchar(16) NOT NULL DEFAULT '' COMMENT 'E.164 calling prefix e.g. +36',
  `continent_id` int(10) unsigned NOT NULL COMMENT 'FK continents.id',
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `pos` int(11) NOT NULL DEFAULT 1000,
  `user_count` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `iso2` (`iso2`),
  KEY `name` (`name`),
  KEY `endonim_name` (`endonim_name`),
  KEY `locale` (`locale`),
  KEY `timezone` (`timezone`),
  KEY `phone_prefix` (`phone_prefix`),
  KEY `continent_id` (`continent_id`),
  KEY `visible` (`visible`),
  KEY `pos` (`pos`),
  CONSTRAINT `countries_continent_id_fk` FOREIGN KEY (`continent_id`) REFERENCES `continents` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Countries table';
