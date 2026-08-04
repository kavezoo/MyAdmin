-- Membership onboarding columns on users
ALTER TABLE `users`
  ADD COLUMN `membership_status` varchar(20) NOT NULL DEFAULT 'incomplete'
    COMMENT 'incomplete|pending|approved' AFTER `role`,
  ADD COLUMN `application_notified` tinyint(1) unsigned NOT NULL DEFAULT 0
    COMMENT '1 = clubpresident notified about application' AFTER `membership_status`,
  ADD KEY `membership_status` (`membership_status`),
  ADD KEY `club_membership` (`club_id`, `membership_status`);
