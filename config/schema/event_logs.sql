-- event_logs — append-only user activity / data-change audit.
-- Scope: country_id of the actor at event time.
-- Officers (president+) browse their country; superuser may filter any country.
-- Every user can open their own rows (Users::eventLog).

CREATE TABLE IF NOT EXISTS `event_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` int(10) unsigned DEFAULT NULL COMMENT 'Actor country context (Users.country_id / AdminCountry)',
  `user_id` char(36) DEFAULT NULL COMMENT 'FK users.id',
  `actor_role` varchar(50) DEFAULT NULL,
  `module` varchar(100) NOT NULL COMMENT 'Controller / domain module',
  `action` varchar(50) NOT NULL COMMENT 'login|logout|view|index|search|add|edit|delete|request',
  `entity` varchar(100) DEFAULT NULL COMMENT 'Table alias when applicable',
  `entity_id` varchar(64) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `url` varchar(500) DEFAULT NULL,
  `http_method` varchar(10) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  `request_data` text DEFAULT NULL COMMENT 'JSON: sanitized query/body summary',
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `country_id` (`country_id`),
  KEY `user_id` (`user_id`),
  KEY `module` (`module`),
  KEY `action` (`action`),
  KEY `entity_entity_id` (`entity`, `entity_id`),
  KEY `created` (`created`),
  KEY `country_created` (`country_id`, `created`),
  KEY `user_created` (`user_id`, `created`),
  CONSTRAINT `event_logs_country_id_fk`
    FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON UPDATE CASCADE ON DELETE SET NULL,
  CONSTRAINT `event_logs_user_id_fk`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='User activity and data-change event log';
