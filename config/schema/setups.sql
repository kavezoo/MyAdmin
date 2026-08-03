-- Setups: typed application settings (EAV — value TEXT by type)
-- Apply: mysql … < config/schema/setups.sql

CREATE TABLE IF NOT EXISTS `setups` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `slug` varchar(150) NOT NULL COMMENT 'Unique key: a-z0-9 underscore',
  `type` varchar(20) NOT NULL COMMENT 'string|text|integer|float|boolean|date|time|datetime|json|array',
  `value` mediumtext DEFAULT NULL,
  `description` text DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `pos` int(11) NOT NULL DEFAULT 1000,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `name` (`name`),
  KEY `type` (`type`),
  KEY `visible` (`visible`),
  KEY `pos` (`pos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Typed application settings';
