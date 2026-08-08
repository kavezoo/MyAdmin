-- Clubs (users.club_id → clubs.id). club_id 0 / NULL = not chosen yet.

CREATE TABLE IF NOT EXISTS `clubs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `country_id` int(10) unsigned NOT NULL,
  `city_id` int(10) unsigned NOT NULL COMMENT '0 = none; FK soft to cities.id',
  `clubpresident_id` varchar(36) NOT NULL COMMENT 'Mirror of club_president_id ("" if none)',
  `name` varchar(150) NOT NULL,
  `short_name` varchar(250) NOT NULL,
  `logo` varchar(255) DEFAULT NULL COMMENT 'Club logo path (uploads/clubs/{id}.jpg)',
  `email` varchar(50) NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0 = not selectable on profile / complete-profile',
  `address` varchar(100) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `web` varchar(1000) NOT NULL,
  `facebook` varchar(1000) NOT NULL,
  `insta` varchar(1000) NOT NULL,
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `pos` int(11) NOT NULL DEFAULT 1000,
  `user_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'CounterCache: users with this club_id',
  `competition_count` int(10) unsigned NOT NULL DEFAULT 0 COMMENT 'CounterCache: competitions hosted by this club',
  `club_president_id` char(36) DEFAULT NULL COMMENT 'Designated club president (Users.id); role may stay president/vp',
  `national_membership_fee_date` date DEFAULT NULL COMMENT 'National association club fee paid on (year on date = membership year)',
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `country_id` (`country_id`),
  KEY `city_id` (`city_id`),
  KEY `name` (`name`),
  KEY `visible` (`visible`),
  KEY `enabled` (`enabled`),
  KEY `pos` (`pos`),
  KEY `clubpresident_id` (`clubpresident_id`),
  KEY `club_president_id` (`club_president_id`),
  KEY `national_membership_fee_date` (`national_membership_fee_date`),
  CONSTRAINT `clubs_country_id_fk`
    FOREIGN KEY (`country_id`) REFERENCES `countries` (`id`) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
  COMMENT='Clubs; membership applicants pick a club on profile completion';
