--
-- Database: `snappi`
--

-- tables for thrift api

DROP TABLE IF EXISTS `thrift_devices`;
CREATE TABLE IF NOT EXISTS `thrift_devices` (
  `id` int(11) NOT NULL,
  `provider_account_id` char(36) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL COMMENT 'get pa_id from Thrift authToken',
  `device_UUID` char(36) NOT NULL COMMENT 'unique for each device, generated by native-uploader installer',
  `label` varchar(45) CHARACTER SET utf8 COLLATE utf8_general_ci,
  `thrift_folder_count` tinyint(1) DEFAULT 0,  
  `created` datetime,
  `modified` datetime,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`provider_account_id`,`device_UUID`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `thrift_sessions`;
CREATE TABLE IF NOT EXISTS `thrift_sessions` (
  `id` char(36) NOT NULL COMMENT 'UUID, used to launch native-uploader',
  `thrift_device_id` int(11) NOT NULL,
  `batch_id` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Server time in UTC',
  `DuplicateFileException` tinyint(1) DEFAULT 0 COMMENT 'count exceptions',
  `OtherException` tinyint(1) DEFAULT 0 COMMENT 'count exceptions',
  `is_cancelled` tinyint(1) DEFAULT 0,
  `created` datetime,
  `modified` datetime,
  PRIMARY KEY (`id`),
  KEY `fk_devices` (`thrift_device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `thrift_folders`;
CREATE TABLE IF NOT EXISTS `thrift_folders` (
  `id` int(11) NOT NULL COMMENT 'for cakephp, all joins by UNIQUE index',
  `thrift_device_id` int(11) NOT NULL,
  `native_path` varchar(2000) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `native_path_hash` BIGINT NOT NULL COMMENT 'use a simple CRC32()',
  `count` int(11) DEFAULT 0,
  `is_scanned` tinyint(1) DEFAULT 0,
  `is_watched` tinyint(1) DEFAULT 0,
  `created` datetime,
  `modified` datetime,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`thrift_device_id`,`native_path_hash`) USING BTREE,
  KEY `fk_devices` (`thrift_device_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

