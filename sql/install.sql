CREATE TABLE `grading_definitions` (
  `id` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `course_id` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `item` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tool` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(256) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int(11) NOT NULL DEFAULT '0',
  `weight` float UNSIGNED NOT NULL,
  `mkdate` int(11) NOT NULL,
  `chdate` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `grading_definitions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `course_id` (`course_id`),
  ADD KEY `tool` (`tool`);

CREATE TABLE `grading_instances` (
  `definition_id` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rawgrade` decimal(6,5) UNSIGNED NOT NULL,
  `feedback` text COLLATE utf8mb4_unicode_ci,
  `mkdate` int(11) NOT NULL,
  `chdate` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `grading_instances`
  ADD PRIMARY KEY (`definition_id`,`user_id`);
