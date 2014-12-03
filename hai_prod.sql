-- phpMyAdmin SQL Dump
-- version 3.3.10.4
-- http://www.phpmyadmin.net
--
-- Host: db.hai.social
-- Generation Time: Dec 02, 2014 at 10:54 PM
-- Server version: 5.1.56
-- PHP Version: 5.4.20

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `hai_prod`
--

-- --------------------------------------------------------

--
-- Table structure for table `account_recovery`
--

CREATE TABLE IF NOT EXISTS `account_recovery` (
  `id` char(36) NOT NULL,
  `created` int(11) NOT NULL,
  `user` char(36) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `blocks`
--

CREATE TABLE IF NOT EXISTS `blocks` (
  `id` char(36) NOT NULL,
  `blocker` char(36) NOT NULL,
  `troll` char(36) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `broadcasts`
--

CREATE TABLE IF NOT EXISTS `broadcasts` (
  `id` char(36) NOT NULL,
  `created` int(10) unsigned NOT NULL,
  `user` char(36) NOT NULL,
  `post` char(36) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `carriers`
--

CREATE TABLE IF NOT EXISTS `carriers` (
  `id` char(36) NOT NULL,
  `name` text,
  `sms_domain` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE IF NOT EXISTS `comments` (
  `id` char(36) NOT NULL,
  `created` int(11) NOT NULL,
  `author` char(36) NOT NULL,
  `post` char(36) NOT NULL,
  `content` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `phone_carriers`
--

CREATE TABLE IF NOT EXISTS `phone_carriers` (
  `id` char(36) NOT NULL,
  `name` text,
  `sms_domain` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `pings`
--

CREATE TABLE IF NOT EXISTS `pings` (
  `id` char(36) NOT NULL,
  `user` char(36) NOT NULL,
  `created` int(10) unsigned NOT NULL,
  `content_type` char(1) NOT NULL,
  `content_id` char(36) NOT NULL,
  `is_read` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE IF NOT EXISTS `posts` (
  `id` char(36) NOT NULL,
  `created` int(11) NOT NULL,
  `author` char(36) NOT NULL,
  `content` text NOT NULL,
  `parent` char(36) DEFAULT NULL,
  `public` tinyint(1) DEFAULT NULL,
  `editable` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `post_groups`
--

CREATE TABLE IF NOT EXISTS `post_groups` (
  `post` char(36) NOT NULL,
  `usergroup` char(36) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `post_history`
--

CREATE TABLE IF NOT EXISTS `post_history` (
  `id` char(36) NOT NULL,
  `post` char(36) NOT NULL,
  `author` char(36) NOT NULL,
  `edited` int(10) unsigned NOT NULL,
  `original_content` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `post_locks`
--

CREATE TABLE IF NOT EXISTS `post_locks` (
  `id` char(36) NOT NULL,
  `post` char(36) NOT NULL,
  `user` char(36) NOT NULL,
  `timeout` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `rooms`
--

CREATE TABLE IF NOT EXISTS `rooms` (
  `id` char(36) NOT NULL,
  `name` varchar(50) NOT NULL,
  `topic` text,
  `public` tinyint(1) NOT NULL,
  `hidden` tinyint(1) NOT NULL,
  `invite_only` tinyint(1) NOT NULL,
  `password` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `room_members`
--

CREATE TABLE IF NOT EXISTS `room_members` (
  `id` char(36) NOT NULL,
  `room` char(36) NOT NULL,
  `user` char(36) NOT NULL,
  `op` tinyint(1) NOT NULL,
  `public` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `room_posts`
--

CREATE TABLE IF NOT EXISTS `room_posts` (
  `id` char(36) NOT NULL,
  `room` char(36) NOT NULL,
  `post` char(36) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `tracking`
--

CREATE TABLE IF NOT EXISTS `tracking` (
  `id` char(36) NOT NULL,
  `post` char(36) NOT NULL,
  `user` char(36) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` char(36) NOT NULL,
  `username` text NOT NULL,
  `visible_name` text NOT NULL,
  `password` text NOT NULL,
  `real_name` text NOT NULL,
  `created` int(11) NOT NULL,
  `paid` tinyint(1) NOT NULL,
  `profile_public` tinyint(1) NOT NULL,
  `about` text,
  `admin` tinyint(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_avatars`
--

CREATE TABLE IF NOT EXISTS `user_avatars` (
  `user` int(11) NOT NULL,
  `data` text
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_emails`
--

CREATE TABLE IF NOT EXISTS `user_emails` (
  `user` char(36) NOT NULL,
  `email` text,
  `public` tinyint(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_media`
--

CREATE TABLE IF NOT EXISTS `user_media` (
  `id` char(36) NOT NULL,
  `created` int(10) unsigned DEFAULT NULL,
  `user` char(36) NOT NULL,
  `filename` text NOT NULL,
  `type` varchar(5) NOT NULL DEFAULT 'image',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_phones`
--

CREATE TABLE IF NOT EXISTS `user_phones` (
  `user` char(36) NOT NULL,
  `phone` text,
  `carrier` char(36) NOT NULL,
  `public` tinyint(1) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_teams`
--

CREATE TABLE IF NOT EXISTS `user_teams` (
  `id` char(36) NOT NULL,
  `user` char(36) NOT NULL,
  `name` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_team_members`
--

CREATE TABLE IF NOT EXISTS `user_team_members` (
  `team` char(36) NOT NULL,
  `user` char(36) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `user_worlds`
--

CREATE TABLE IF NOT EXISTS `user_worlds` (
  `id` char(36) NOT NULL,
  `user` char(36) NOT NULL,
  `world` char(36) NOT NULL,
  `public` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `worlds`
--

CREATE TABLE IF NOT EXISTS `worlds` (
  `id` char(36) NOT NULL,
  `basic_name` varchar(50) NOT NULL,
  `display_name` varchar(50) NOT NULL,
  `class` char(36) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Table structure for table `world_posts`
--

CREATE TABLE IF NOT EXISTS `world_posts` (
  `id` char(36) NOT NULL,
  `world` char(36) NOT NULL,
  `post` char(36) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
