SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- --------------------------------------------------------

--
-- Table structure for table `places`
--

CREATE TABLE IF NOT EXISTS `places` (
  `id` char(16) CHARACTER SET ascii NOT NULL,
  `place_type` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `country` varchar(255) NOT NULL,
  `centroid_lat` decimal(10,8) NOT NULL,
  `centroid_lon` decimal(11,8) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tweets`
--

CREATE TABLE IF NOT EXISTS `tweets` (
  `id` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  `text` varchar(280) NOT NULL,
  `source` varchar(255) NOT NULL,
  `reply_id` bigint(20) unsigned NOT NULL,
  `rt_id` bigint(20) unsigned NOT NULL,
  `place_id` char(16) CHARACTER SET ascii NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `touched` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `urls`
--

CREATE TABLE IF NOT EXISTS `urls` (
  `url` varchar(100) NOT NULL,
  `expanded_url` varchar(255) NOT NULL,
  PRIMARY KEY (`url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
