-- Membership fee payment dates on users (calendar year on date = paid membership year).

ALTER TABLE `users`
  ADD COLUMN `club_membership_fee_date` date DEFAULT NULL
    COMMENT 'Local club fee paid on' AFTER `membership_status`,
  ADD COLUMN `national_membership_fee_date` date DEFAULT NULL
    COMMENT 'National association fee paid on (e.g. MPE)' AFTER `club_membership_fee_date`,
  ADD KEY `club_membership_fee_date` (`club_membership_fee_date`),
  ADD KEY `national_membership_fee_date` (`national_membership_fee_date`);
