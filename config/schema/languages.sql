-- UI languages for login language select (Translate name → i18n).
-- Synced from distinct countries.locale of login-visible countries.

CREATE TABLE IF NOT EXISTS `languages` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL COMMENT 'Locale code e.g. en_GB, hu_HU',
  `name` varchar(150) NOT NULL COMMENT 'English canonical name',
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `pos` int(11) NOT NULL DEFAULT 1000,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `name` (`name`),
  KEY `visible` (`visible`),
  KEY `pos` (`pos`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='UI languages (login select); name Translate EAV in i18n';
