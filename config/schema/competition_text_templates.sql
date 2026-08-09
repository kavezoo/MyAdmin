-- Competition announcement text templates (country-scoped; HTML description via Cake Translate EAV).

CREATE TABLE IF NOT EXISTS `competition_text_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` int(10) unsigned NOT NULL COMMENT 'FK → countries.id',
  `label` varchar(150) NOT NULL COMMENT 'Admin/President Select2 label',
  `description` text NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `pos` int(11) NOT NULL DEFAULT 1000,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `country_id` (`country_id`),
  KEY `label` (`label`),
  KEY `enabled` (`enabled`),
  KEY `visible` (`visible`),
  KEY `pos` (`pos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Announcement HTML text templates; {{placeholders}} resolved on display';
