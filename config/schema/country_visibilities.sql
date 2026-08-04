-- Country → country visibility (self-referencing junction)
-- When Users.country_id = country_id, only visible_country_id rows with visible=1
-- appear on form language tabs. Login list = DISTINCT visible_country_id WHERE visible=1.
-- en_GB country must always be present and first (enforced in app + seed).

CREATE TABLE IF NOT EXISTS `country_visibilities` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` int(10) unsigned NOT NULL COMMENT 'Active / viewer country (Users.country_id context)',
  `visible_country_id` int(10) unsigned NOT NULL COMMENT 'Country visible in that context',
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `pos` int(11) NOT NULL DEFAULT 1000,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `country_visible_country` (`country_id`, `visible_country_id`),
  KEY `country_visible` (`country_id`, `visible`),
  KEY `visible_country_visible` (`visible_country_id`, `visible`),
  KEY `pos` (`pos`),
  CONSTRAINT `country_visibilities_country_id_fk`
    FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT `country_visibilities_visible_country_id_fk`
    FOREIGN KEY (`visible_country_id`) REFERENCES `countries` (`id`) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Per-active-country visibility of other countries (language tabs + login union)';
