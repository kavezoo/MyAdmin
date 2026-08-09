-- Competition day staff (check-in / table judge). Does not change Users.role.

CREATE TABLE IF NOT EXISTS `competition_staff` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `competition_id` varchar(36) NOT NULL COMMENT 'FK competitions.id',
  `user_id` varchar(36) NOT NULL COMMENT 'FK users.id',
  `staff_role` varchar(20) NOT NULL COMMENT 'checkin|judge',
  `visible` tinyint(1) NOT NULL DEFAULT 1,
  `pos` int(11) NOT NULL DEFAULT 1000,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `competition_staff_unique` (`competition_id`,`user_id`,`staff_role`),
  KEY `competition_staff_user` (`user_id`),
  KEY `competition_staff_role` (`staff_role`),
  KEY `competition_staff_competition` (`competition_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
