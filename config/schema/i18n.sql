-- CakePHP Translate EAV table (EavStrategy)
-- Used for Countries.name, Continents.name, Competitions text fields, etc.
-- foreign_key = varchar(36): supports integer PKs (as string) and UUID PKs.

CREATE TABLE IF NOT EXISTS `i18n` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `locale` varchar(6) NOT NULL,
  `model` varchar(255) NOT NULL,
  `foreign_key` varchar(36) NOT NULL COMMENT 'Parent PK (int as string or UUID)',
  `field` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Per-locale visibility (Countries translations)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `I18N_LOCALE_FIELD` (`locale`,`model`,`foreign_key`,`field`),
  KEY `I18N_FIELD` (`model`,`foreign_key`,`field`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
