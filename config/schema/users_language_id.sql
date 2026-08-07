-- Preferred UI / email language (languages.id). After club_id among *_id columns.

ALTER TABLE `users`
  ADD COLUMN `language_id` int(11) DEFAULT NULL COMMENT 'Preferred language (languages.id)' AFTER `club_id`,
  ADD KEY `language_id` (`language_id`);
