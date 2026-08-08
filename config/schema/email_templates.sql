-- Email templates per country + UI language (countries.id + languages.id).

CREATE TABLE IF NOT EXISTS `email_templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` int(11) NOT NULL COMMENT 'FK → countries.id',
  `language_id` int(11) NOT NULL COMMENT 'FK → languages.id',
  `slug` varchar(100) NOT NULL COMMENT 'Template key e.g. membership_application',
  `name` varchar(150) NOT NULL COMMENT 'Admin label',
  `subject` varchar(255) NOT NULL,
  `body_html` text NOT NULL,
  `body_text` text NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `pos` int(11) NOT NULL DEFAULT 1000,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_templates_country_language_slug` (`country_id`, `language_id`, `slug`),
  KEY `country_id` (`country_id`),
  KEY `language_id` (`language_id`),
  KEY `slug` (`slug`),
  KEY `enabled` (`enabled`),
  KEY `visible` (`visible`),
  KEY `pos` (`pos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Per-country + per-language email subject/body; placeholders {varName}';
