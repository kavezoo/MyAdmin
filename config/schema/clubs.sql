-- Clubs (users.club_id → clubs.id). club_id 0 / NULL = not chosen yet.

CREATE TABLE IF NOT EXISTS `clubs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` int(10) unsigned NOT NULL,
  `name` varchar(150) NOT NULL,
  `enabled` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT '0 = not selectable on profile',
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `pos` int(11) NOT NULL DEFAULT 1000,
  `user_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'CounterCache: users with this club_id',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `country_id` (`country_id`),
  KEY `name` (`name`),
  KEY `visible` (`visible`),
  KEY `enabled` (`enabled`),
  KEY `pos` (`pos`),
  CONSTRAINT `clubs_country_id_fk`
    FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Clubs; membership applicants pick a club on profile completion';
