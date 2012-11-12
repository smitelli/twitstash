-- phpMyAdmin SQL Dump
-- version 3.3.5.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 11, 2012 at 11:42 PM
-- Server version: 5.1.51
-- PHP Version: 5.2.17

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `twitstash`
--

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tweets`
--

CREATE TABLE IF NOT EXISTS `tweets` (
  `id` bigint(20) unsigned NOT NULL,
  `created_at` datetime NOT NULL,
  `text` varchar(140) NOT NULL,
  `source` varchar(255) NOT NULL,
  `reply_id` bigint(20) unsigned NOT NULL,
  `rt_id` bigint(20) unsigned NOT NULL,
  `place_id` char(16) CHARACTER SET ascii NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `touched` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `deleted` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `urls`
--

CREATE TABLE IF NOT EXISTS `urls` (
  `url` varchar(255) NOT NULL,
  `expanded_url` varchar(255) NOT NULL,
  PRIMARY KEY (`url`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
