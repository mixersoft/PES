CREATE DATABASE IF NOT EXISTS `snappi_workorders` CHARSET=utf8 COLLATE=utf8_unicode_ci;
USE 'snappi_workorders';

DROP TABLE IF EXISTS `workorders`;
CREATE TABLE `workorders` (
  `id` char(36) NOT NULL,
  `client_id` char(36) NOT NULL COMMENT 'customer satisfaction target',
  `source_id` char(36) NOT NULL,
  `source_model` enum('User','Group') default 'User',
  `manager_id` char(36) DEFAULT NULL COMMENT 'assignment',

  `name` varchar(255) NOT NULL,   -- determines tasks, biz logic, SLA
  `description` varchar(1000) collate utf8_unicode_ci default NULL,
  `harvest` tinyint(1) NOT NULL default 0,

-- state flags
  work_status enum('new','ready','working','flagged','done') default 'new',
-- counter_cache fields
  assets_workorder_count int(11) default 0,  -- actual count

  submitted datetime DEFAULT NULL,
  due datetime default NULL,
  started datetime default NULL,
  finished datetime default NULL,
  elapsed time default '00:00:00',

  `special_instructions` varchar(1000) collate utf8_unicode_ci default NULL,
  `notes` varchar(1000) collate utf8_unicode_ci default NULL,
  `flag` tinyint(1) default NULL,

  `active` tinyint(1) NOT NULL default '1',
  created datetime default NULL,
  modified datetime default NULL,
  PRIMARY KEY  (id),
  KEY `fk_client_id` (`client_id`),
  KEY `fk_source_id` (`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- tasks
DROP TABLE IF EXISTS `tasks`;
CREATE TABLE IF NOT EXISTS `tasks` (
  `id` char(36) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


-- workorder habtm tasks
DROP TABLE IF EXISTS `tasks_workorders`;
CREATE TABLE IF NOT EXISTS `tasks_workorders` (
  `id` char(36) NOT NULL,
  `workorder_id` char(36) NOT NULL,
  `task_id` char(36) NOT NULL,
  `task_sort` smallint unsigned DEFAULT 0, -- establish sort order for workorder tasks
  `operator_id` char(36) DEFAULT NULL COMMENT 'assignment',

-- work status & statistics
  `status` enum('new','working','paused','flagged','done') collate utf8_unicode_ci default 'new',
  assets_task_count int(11) default 0,  -- actual count
  `started` datetime default NULL,
  `finished` datetime default NULL,
  `elapsed` time default '00:00:00',
  `paused_at` datetime default NULL,
  `paused` time default '00:00:00',

-- review
  `notes` varchar(1000) collate utf8_unicode_ci default NULL,
  `flag` tinyint(1) default NULL,

  `active` tinyint(1) NOT NULL default '1',
  created datetime default NULL,
  modified datetime default NULL,
  PRIMARY KEY  (`id`),
  KEY `fk_operator_id` (`operator_id`),
  KEY `fk_workorder_id` (`workorder_id`,`task_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



DROP TABLE IF EXISTS `assets_workorders`;
CREATE TABLE IF NOT EXISTS `assets_workorders` (
  `id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `workorder_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `asset_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`workorder_id`,`asset_id`),
  KEY `fk_assets` (`asset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- assets_tasks
DROP TABLE IF EXISTS `assets_tasks`;
CREATE TABLE IF NOT EXISTS `assets_tasks` (
  `id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `tasks_workorder_id` char(36) NOT NULL,
  `asset_id` char(36) COLLATE utf8_unicode_ci NOT NULL,
  `created` datetime DEFAULT NULL,
  PRIMARY KEY (`tasks_workorder_id`,`asset_id`),
  KEY `fk_assets` (`asset_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;






