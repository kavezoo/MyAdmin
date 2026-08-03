-- Continents (English canonical name + i18n Translate EAV)
-- Seed / migrate: php tmp/seed_continents.php
-- Translations: i18n (model=Continents, field=name) from CLDR territory codes

CREATE TABLE IF NOT EXISTS `continents` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(3) NOT NULL COMMENT 'Stable code: AFR ASI EUR NAM SAM OCE ANT',
  `name` varchar(64) NOT NULL COMMENT 'English canonical name',
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `pos` int(11) NOT NULL DEFAULT 1000,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `name` (`name`),
  KEY `visible` (`visible`),
  KEY `pos` (`pos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Continents table';
