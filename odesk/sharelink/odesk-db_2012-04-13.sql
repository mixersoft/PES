-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Apr 13, 2012 at 03:07 AM
-- Server version: 5.1.41
-- PHP Version: 5.3.1

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `odesk`
--

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
  `id` varchar(36) NOT NULL,
  `parent_id` varchar(36) DEFAULT NULL,
  `foreign_key` varchar(36) NOT NULL,
  `user_id` varchar(36) DEFAULT NULL,
  `lft` int(10) NOT NULL,
  `rght` int(10) NOT NULL,
  `model` varchar(255) NOT NULL,
  `approved` tinyint(1) NOT NULL DEFAULT '1',
  `is_spam` varchar(20) NOT NULL DEFAULT 'clean',
  `title` varchar(255) DEFAULT NULL,
  `slug` varchar(255) DEFAULT NULL,
  `body` text,
  `author_name` varchar(255) DEFAULT NULL,
  `author_url` varchar(255) DEFAULT NULL,
  `author_email` varchar(128) NOT NULL DEFAULT '',
  `language` varchar(6) DEFAULT NULL,
  `comment_type` varchar(32) NOT NULL DEFAULT 'comment',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`id`, `parent_id`, `foreign_key`, `user_id`, `lft`, `rght`, `model`, `approved`, `is_spam`, `title`, `slug`, `body`, `author_name`, `author_url`, `author_email`, `language`, `comment_type`, `created`, `modified`) VALUES('4e6029f3-c8b4-4438-9d4c-1d14691dff02', '0', '4e5ef2fc-faac-4c81-a037-0756691dff02', '4e5eeb85-4ad4-49ec-a18c-0754691dff02', 1, 2, 'Post', 1, 'clean', 'a comment from michael', 'a_comment_from_michael', 'this is the body of the comment\r\n', NULL, NULL, '', 'en-us', 'comment', '2011-09-02 01:57:23', '2011-09-02 01:57:23');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `id` varchar(36) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `name`, `created`, `modified`) VALUES('4e5ef2fc-faac-4c81-a037-0756691dff02', 'Sample Post 1', '2011-09-01 14:50:36', '2011-09-01 14:50:36');
INSERT INTO `posts` (`id`, `name`, `created`, `modified`) VALUES('4e5ef303-b6c4-4d99-b1cb-074f691dff02', 'Sample Post 2', '2011-09-01 14:50:43', '2011-09-01 14:50:43');
INSERT INTO `posts` (`id`, `name`, `created`, `modified`) VALUES('4e5efac3-362c-4df8-bbea-0756691dff02', 'Hello', '2011-09-01 15:23:47', '2011-09-01 15:23:47');

-- --------------------------------------------------------

--
-- Table structure for table `schema_migrations`
--

DROP TABLE IF EXISTS `schema_migrations`;
CREATE TABLE `schema_migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=5 ;

--
-- Dumping data for table `schema_migrations`
--

INSERT INTO `schema_migrations` (`id`, `version`, `type`, `created`) VALUES(1, 1, 'migrations', '2011-09-01 14:15:44');
INSERT INTO `schema_migrations` (`id`, `version`, `type`, `created`) VALUES(2, 1, 'users', '2011-09-01 14:15:44');
INSERT INTO `schema_migrations` (`id`, `version`, `type`, `created`) VALUES(3, 1, 'comments', '2011-09-01 14:15:54');
INSERT INTO `schema_migrations` (`id`, `version`, `type`, `created`) VALUES(4, 1, 'posts', '2011-09-01 14:16:01');

-- --------------------------------------------------------

--
-- Table structure for table `share_links`
--

DROP TABLE IF EXISTS `share_links`;
CREATE TABLE `share_links` (
  `id` char(36) NOT NULL,
  `secret_key` char(6) NOT NULL,
  `hashed_password` varchar(255) NOT NULL,
  `security_level` tinyint(2) NOT NULL,
  `expiration_date` datetime NOT NULL,
  `expiration_count` int(11) NOT NULL,
  `target_id` char(36) NOT NULL,
  `target_type` varchar(255) NOT NULL,
  `target_owner` char(255) NOT NULL,
  `active` tinyint(1) NOT NULL,
  `owner_id` char(255) NOT NULL,
  `count` int(11) NOT NULL,
  `created` datetime NOT NULL,
  `modified` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `share_links`
--

INSERT INTO `share_links` (`id`, `secret_key`, `hashed_password`, `security_level`, `expiration_date`, `expiration_count`, `target_id`, `target_type`, `target_owner`, `active`, `owner_id`, `count`, `created`, `modified`) VALUES('4e5ef896-0d7c-47b0-ad71-0755691dff02', 'abc123', '353e8061f2befecb6818ba0c034c632fb0bcae1b', 1, '2011-09-03 15:10:00', 5, '4e5ef2fc-faac-4c81-a037-0756691dff02', 'Post', '4e5eeb85-4ad4-49ec-a18c-0754691dff02', 1, '4e5eeb85-4ad4-49ec-a18c-0754691dff02', 7, '2011-09-01 15:14:30', '2011-09-02 02:07:56');
INSERT INTO `share_links` (`id`, `secret_key`, `hashed_password`, `security_level`, `expiration_date`, `expiration_count`, `target_id`, `target_type`, `target_owner`, `active`, `owner_id`, `count`, `created`, `modified`) VALUES('4e5eff07-0ea8-4194-9a56-00e5691dff02', 'def456', '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8', 2, '2011-09-01 15:40:00', 10, '4e5ef303-b6c4-4d99-b1cb-074f691dff02', 'Post', '4e5eeb85-4ad4-49ec-a18c-0754691dff02', 0, '4e5eeb85-4ad4-49ec-a18c-0754691dff02', 1, '2011-09-01 15:41:59', '2011-09-01 15:53:35');
INSERT INTO `share_links` (`id`, `secret_key`, `hashed_password`, `security_level`, `expiration_date`, `expiration_count`, `target_id`, `target_type`, `target_owner`, `active`, `owner_id`, `count`, `created`, `modified`) VALUES('4e602a80-7490-43e5-b005-1d14691dff02', 'lkdfjl', '5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8', 2, '2011-09-03 01:58:00', 3, '4e5ef2fc-faac-4c81-a037-0756691dff02', 'Post', '4e5eeb85-4ad4-49ec-a18c-0754691dff02', 0, '4e5eeb85-4ad4-49ec-a18c-0754691dff02', 4, '2011-09-02 01:59:44', '2011-09-02 02:00:36');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` varchar(36) NOT NULL,
  `username` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `passwd` varchar(128) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `active` tinyint(1) DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `slug`, `passwd`, `email`, `active`, `created`, `modified`) VALUES('4e5eeb85-4ad4-49ec-a18c-0754691dff02', 'demo', 'demo', 'c8229c200b43f93c76aa668549a0f55ffb264db3', 'demo@demo.com', 1, '2011-09-01 14:18:45', '2011-09-01 14:18:45');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
