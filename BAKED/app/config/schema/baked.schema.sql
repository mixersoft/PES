-- phpMyAdmin SQL Dump
-- version 3.2.4
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1:3306

-- Generation Time: Apr 27, 2010 at 03:52 AM
-- Server version: 5.1.41
-- PHP Version: 5.3.1

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `baked`
--

-- --------------------------------------------------------

--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
CREATE TABLE IF NOT EXISTS `assets` (
  `id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `provider_name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `provider_key` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `provider_account_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `asset_hash` char(32) COLLATE utf8_unicode_ci NOT NULL,
  `owner_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `dateTaken` datetime DEFAULT NULL,
  `src_thumbnail` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  `json_src` varchar(4096) COLLATE utf8_unicode_ci DEFAULT NULL,
  `json_exif` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `json_iptc` varchar(8192) COLLATE utf8_unicode_ci DEFAULT NULL,
  `cameraId` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `isFlash` tinyint(1) DEFAULT '0',
  `isRGB` tinyint(1) DEFAULT '1',
  `uploadId` int(11) DEFAULT NULL,
  `batchId` int(11) DEFAULT NULL,
  `caption` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `keyword` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `index_assetHash` (`asset_hash`,`owner_id`) USING BTREE,
  KEY `fk_assets_providerAccounts` (`provider_account_id`),
  KEY `index_assets_owner` (`owner_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assets_collections`
--

DROP TABLE IF EXISTS `assets_collections`;
CREATE TABLE IF NOT EXISTS `assets_collections` (
  `id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `collection_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `asset_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`collection_id`,`asset_id`),
  KEY `fk_assets_collections_collections` (`collection_id`),
  KEY `fk_assets_collections_assets` (`asset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `assets_groups`
--

DROP TABLE IF EXISTS `assets_groups`;
CREATE TABLE IF NOT EXISTS `assets_groups` (
  `id` char(36) COLLATE utf8_unicode_ci DEFAULT NULL,
  `asset_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `group_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `isApproved` tinyint(1) DEFAULT '1',
  `user_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`asset_id`,`group_id`),
  KEY `fk_assets_groups_assets` (`asset_id`),
  KEY `fk_assets_groups_groups` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `auth_accounts`
--

DROP TABLE IF EXISTS `auth_accounts`;
CREATE TABLE IF NOT EXISTS `auth_accounts` (
  `id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `unique_hash` char(32) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `provider_name` char(45) COLLATE utf8_unicode_ci NOT NULL,
  `provider_key` varchar(1000) COLLATE utf8_unicode_ci NOT NULL,
  `password` char(40) COLLATE utf8_unicode_ci NOT NULL,
  `display_name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `src_thumbnail` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  `country` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `city` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `utcOffset` char(6) COLLATE utf8_unicode_ci DEFAULT NULL,
  `gender` char(1) COLLATE utf8_unicode_ci DEFAULT NULL,
  `profile_json` text COLLATE utf8_unicode_ci,
  `active` tinyint(1) DEFAULT '1',
  `lastVisit` timestamp NULL DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_hash_UNIQUE` (`unique_hash`),
  KEY `fk_auth_accounts_users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `collections`
--

DROP TABLE IF EXISTS `collections`;
CREATE TABLE IF NOT EXISTS `collections` (
  `id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `title` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `owner_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `markup` text COLLATE utf8_unicode_ci,
  `src` varchar(1000) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastVisit` timestamp NULL DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_collections_owner` (`owner_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `collections_groups`
--

DROP TABLE IF EXISTS `collections_groups`;
CREATE TABLE IF NOT EXISTS `collections_groups` (
  `id` char(36) COLLATE utf8_unicode_ci DEFAULT NULL,
  `collection_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `group_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `isApproved` tinyint(1) DEFAULT '1',
  `user_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`collection_id`,`group_id`),
  KEY `fk_collections_groups_collections` (`collection_id`),
  KEY `fk_collections_groups_groups` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
CREATE TABLE IF NOT EXISTS `comments` (
  `id` varchar(36) COLLATE utf8_unicode_ci NOT NULL,
  `parent_id` varchar(36) COLLATE utf8_unicode_ci DEFAULT NULL,
  `foreign_key` varchar(36) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` varchar(36) COLLATE utf8_unicode_ci NOT NULL,
  `lft` int(10) NOT NULL,
  `rght` int(10) NOT NULL,
  `model` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `approved` tinyint(1) NOT NULL DEFAULT '1',
  `is_spam` varchar(20) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'clean',
  `title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `body` text COLLATE utf8_unicode_ci,
  `author_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `author_url` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `author_email` varchar(128) COLLATE utf8_unicode_ci NOT NULL DEFAULT '',
  `language` varchar(6) COLLATE utf8_unicode_ci DEFAULT NULL,
  `comment_type` varchar(32) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'comment',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
CREATE TABLE IF NOT EXISTS `groups` (
  `id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `owner_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `isSystem` tinyint(1) NOT NULL DEFAULT '0',
  `title` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `membership_policy` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `invitation_policy` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `submission_policy` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `src_thumbnail` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  `count_members` mediumint(8) unsigned DEFAULT '0',
  `count_assets` mediumint(9) DEFAULT NULL,
  `count_collections` mediumint(9) DEFAULT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `isNC17` tinyint(1) DEFAULT '0',
  `lastVisit` timestamp NULL DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_groups_owner` (`owner_id`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `groups_users`
--

DROP TABLE IF EXISTS `groups_users`;
CREATE TABLE IF NOT EXISTS `groups_users` (
  `id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `group_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `isApproved` tinyint(1) DEFAULT '1',
  `role` varchar(45) COLLATE utf8_unicode_ci NOT NULL DEFAULT 'member',
  `isActive` tinyint(1) NOT NULL DEFAULT '1',
  `suspendUntil` datetime DEFAULT NULL,
  `lastVisit` timestamp NULL DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`user_id`,`group_id`),
  UNIQUE KEY `group_user_idx` (`group_id`,`user_id`),
  KEY `fk_memberships_groups` (`group_id`),
  KEY `fk_memberships_users` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `model` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
  `foreignId` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `oid` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `gid` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `perms` int(4) unsigned zerofill NOT NULL DEFAULT '0000',
  PRIMARY KEY (`id`),
  UNIQUE KEY `polymorphic_idx` (`model`,`foreignId`),
  KEY `owner_user_idx` (`oid`),
  KEY `owner_group_idx` (`gid`),
  KEY `permission_idx` (`perms`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `providers`
--

DROP TABLE IF EXISTS `providers`;
CREATE TABLE IF NOT EXISTS `providers` (
  `id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `provider_accounts`
--

DROP TABLE IF EXISTS `provider_accounts`;
CREATE TABLE IF NOT EXISTS `provider_accounts` (
  `id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `user_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `provider_name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `provider_key` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `display_name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `baseurl` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `auth_token` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_providerAccounts_owner` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `schema_migrations`
--

DROP TABLE IF EXISTS `schema_migrations`;
CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` int(11) NOT NULL,
  `type` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=4 ;

-- --------------------------------------------------------

--
-- Table structure for table `shared_edits`
--

DROP TABLE IF EXISTS `shared_edits`;
CREATE TABLE IF NOT EXISTS `shared_edits` (
  `asset_hash` char(32) COLLATE utf8_unicode_ci NOT NULL,
  `rotate` tinyint(4) DEFAULT '1',
  `votes` int(11) DEFAULT '0',
  `points` int(11) DEFAULT '0',
  `score` decimal(10,0) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`asset_hash`),
  UNIQUE KEY `asset_hash_UNIQUE` (`asset_hash`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tagged`
--

DROP TABLE IF EXISTS `tagged`;
CREATE TABLE IF NOT EXISTS `tagged` (
  `id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `foreign_key` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `tag_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `model` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
  `language` varchar(6) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE_TAGGING` (`model`,`foreign_key`,`tag_id`,`language`),
  KEY `INDEX_TAGGED` (`model`),
  KEY `INDEX_LANGUAGE` (`language`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
CREATE TABLE IF NOT EXISTS `tags` (
  `id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `identifier` varchar(30) COLLATE utf8_unicode_ci DEFAULT NULL,
  `name` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `keyname` varchar(30) COLLATE utf8_unicode_ci NOT NULL,
  `weight` int(2) NOT NULL DEFAULT '0',
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQUE_TAG` (`identifier`,`keyname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `username` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `password` char(40) COLLATE utf8_unicode_ci DEFAULT NULL,
  `email` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `src_thumbnail` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `primary_group_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `privacy` tinyint(4) DEFAULT NULL,
  `lastVisit` timestamp NULL DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username_UNIQUE` (`username`),
  KEY `credential_idx` (`username`,`password`),
  KEY `fk_users_groups` (`primary_group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_edits`
--

DROP TABLE IF EXISTS `user_edits`;
CREATE TABLE IF NOT EXISTS `user_edits` (
  `id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `asset_hash` char(32) COLLATE utf8_unicode_ci NOT NULL,
  `owner_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `isEditor` tinyint(1) DEFAULT '0',
  `isReviewed` tinyint(1) DEFAULT '0',
  `isPublished` tinyint(1) DEFAULT '0',
  `rotate` tinyint(4) DEFAULT NULL,
  `rating` tinyint(4) DEFAULT NULL,
  `syncOffset` int(11) DEFAULT '0',
  `isScrubbed` tinyint(1) DEFAULT '0',
  `isCroppped` tinyint(1) DEFAULT '0',
  `isLocked` tinyint(1) DEFAULT '0',
  `isExported` tinyint(1) DEFAULT '0',
  `isDone` tinyint(1) DEFAULT '0',
  `src_json` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  `edit_json` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
  `lastVisit` timestamp NULL DEFAULT NULL,
  `flaggedAt` timestamp NULL DEFAULT NULL,
  `flag_json` text COLLATE utf8_unicode_ci,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_userEdits_assets` (`asset_hash`),
  KEY `fk_userEdits_users` (`owner_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `assets`
--
ALTER TABLE `assets`
  ADD CONSTRAINT `fk_assets_providerAccounts` FOREIGN KEY (`provider_account_id`) REFERENCES `provider_accounts` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `assets_collections`
--
ALTER TABLE `assets_collections`
  ADD CONSTRAINT `fk_assets` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_collections` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `assets_groups`
--
ALTER TABLE `assets_groups`
  ADD CONSTRAINT `fk_assetGroups_assets` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_assetGroups_groups` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `auth_accounts`
--
ALTER TABLE `auth_accounts`
  ADD CONSTRAINT `fk_auth_accounts_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `collections_groups`
--
ALTER TABLE `collections_groups`
  ADD CONSTRAINT `fk_collectionGroups_collections` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_collectionGroups_groups` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `groups_users`
--
ALTER TABLE `groups_users`
  ADD CONSTRAINT `fk_memberships_groups` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_memberships_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `provider_accounts`
--
ALTER TABLE `provider_accounts`
  ADD CONSTRAINT `fk_providerAccounts_users` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_groups` FOREIGN KEY (`primary_group_id`) REFERENCES `groups` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Constraints for table `user_edits`
--
ALTER TABLE `user_edits`
  ADD CONSTRAINT `fk_userEdits_assets` FOREIGN KEY (`asset_hash`) REFERENCES `assets` (`asset_hash`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `fk_userEdits_users` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;
SET FOREIGN_KEY_CHECKS=1;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
