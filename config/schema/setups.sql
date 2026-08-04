-- Setups: typed application settings (EAV — value TEXT by type), per country
-- Apply: mysql … < config/schema/setups.sql

CREATE TABLE IF NOT EXISTS `setups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` int(10) unsigned NOT NULL COMMENT 'FK countries.id — Admin working country',
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL COMMENT 'Key per country: a-z0-9 underscore',
  `type` varchar(20) NOT NULL COMMENT 'string|text|integer|float|boolean|date|time|datetime|json|array|secret',
  `edit_by` varchar(20) NOT NULL DEFAULT 'admin' COMMENT 'superuser|admin|president — who may edit',
  `value` text NOT NULL,
  `visible` tinyint(1) unsigned NOT NULL DEFAULT 1,
  `pos` int(11) NOT NULL DEFAULT 1000,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `country_slug` (`country_id`, `slug`),
  KEY `name` (`name`),
  KEY `type` (`type`),
  KEY `edit_by` (`edit_by`),
  KEY `visible` (`visible`),
  KEY `pos` (`pos`),
  KEY `country_id` (`country_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Typed application settings per country';
