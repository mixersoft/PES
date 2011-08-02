<?php
class M4cf756bfe2a84a8b951907acf67883f5 extends CakeMigration {

/**
 * Migration description
 *
 * @var string
 * @access public
 */
	public $description = '';

/**
 * Actions to be performed
 *
 * @var array $migration
 * @access public
 */
	public $migration = array(
		'up' => array(
			'create_table' => array(
				'assets' => array(
					'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'provider_name' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45),
					'provider_key' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45),
					'provider_account_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
					'asset_hash' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 32, 'key' => 'index'),
					'owner_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
					'dateTaken' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'src_thumbnail' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 1024),
					'json_src' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 4096),
					'json_exif' => array('type' => 'string', 'null' => true, 'default' => NULL),
					'json_iptc' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 8192),
					'cameraId' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 45),
					'isFlash' => array('type' => 'boolean', 'null' => true, 'default' => '0'),
					'isRGB' => array('type' => 'boolean', 'null' => true, 'default' => '1'),
					'uploadId' => array('type' => 'integer', 'null' => true, 'default' => NULL),
					'batchId' => array('type' => 'integer', 'null' => true, 'default' => NULL),
					'caption' => array('type' => 'string', 'null' => true, 'default' => NULL),
					'keyword' => array('type' => 'string', 'null' => true, 'default' => NULL),
					'assets_group_count' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 8),
					'comment_count' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 9),
					'privacy_groups' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 3),
					'privacy_secret_key' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 3),
					'substitute' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36),
					'chunk' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36),
					'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'index_assetHash' => array('column' => array('asset_hash', 'owner_id'), 'unique' => 1),
						'fk_assets_providerAccounts' => array('column' => 'provider_account_id', 'unique' => 0),
						'index_assets_owner' => array('column' => 'owner_id', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB'),
				),
				'assets_collections' => array(
					'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'collection_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'asset_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'user_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
					'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => array('collection_id', 'asset_id'), 'unique' => 1),
						'fk_assets_collections_collections' => array('column' => 'collection_id', 'unique' => 0),
						'fk_assets_collections_assets' => array('column' => 'asset_id', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB'),
				),
				'assets_groups' => array(
					'id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'asset_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'group_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'isApproved' => array('type' => 'boolean', 'null' => true, 'default' => '1'),
					'user_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
					'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => array('asset_id', 'group_id'), 'unique' => 1),
						'fk_assets_groups_assets' => array('column' => 'asset_id', 'unique' => 0),
						'fk_assets_groups_groups' => array('column' => 'group_id', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB'),
				),
				'auth_accounts' => array(
					'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'unique_hash' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 32, 'key' => 'unique'),
					'user_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
					'provider_name' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45),
					'provider_key' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 1000),
					'password' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 40),
					'display_name' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45),
					'email' => array('type' => 'string', 'null' => true, 'default' => NULL),
					'url' => array('type' => 'string', 'null' => true, 'default' => NULL),
					'src_thumbnail' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 1024),
					'country' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 45),
					'city' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 45),
					'utcOffset' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 6),
					'gender' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 1),
					'profile_json' => array('type' => 'text', 'null' => true, 'default' => NULL),
					'active' => array('type' => 'boolean', 'null' => true, 'default' => '1'),
					'lastVisit' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
					'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'unique_hash_UNIQUE' => array('column' => 'unique_hash', 'unique' => 1),
						'fk_auth_accounts_users' => array('column' => 'user_id', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB'),
				),
				'collections' => array(
					'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'title' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45),
					'owner_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
					'description' => array('type' => 'text', 'null' => true, 'default' => NULL),
					'markup' => array('type' => 'text', 'null' => true, 'default' => NULL),
					'src' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 1000),
					'lastVisit' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
					'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'fk_collections_owner' => array('column' => 'owner_id', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB'),
				),
				'collections_groups' => array(
					'id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'collection_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'group_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'isApproved' => array('type' => 'boolean', 'null' => true, 'default' => '1'),
					'user_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
					'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => array('collection_id', 'group_id'), 'unique' => 1),
						'fk_collections_groups_collections' => array('column' => 'collection_id', 'unique' => 0),
						'fk_collections_groups_groups' => array('column' => 'group_id', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB'),
				),
				'groups' => array(
					'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'owner_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
					'isSystem' => array('type' => 'boolean', 'null' => false, 'default' => '0'),
					'title' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45),
					'description' => array('type' => 'text', 'null' => true, 'default' => NULL),
					'membership_policy' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 45),
					'invitation_policy' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 45),
					'submission_policy' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 45),
					'privacy_secret_key' => array('type' => 'integer', 'null' => true, 'default' => '1', 'length' => 3),
					'src_thumbnail' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 1024),
					'assets_group_count' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 8),
					'comment_count' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 9),
					'groups_user_count' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 9),
					'slug' => array('type' => 'string', 'null' => true, 'default' => NULL),
					'isNC17' => array('type' => 'boolean', 'null' => true, 'default' => '0'),
					'lastVisit' => array('type' => 'timestamp', 'null' => false, 'default' => 'CURRENT_TIMESTAMP'),
					'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'fk_groups_owner' => array('column' => 'owner_id', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB'),
				),
				'groups_users' => array(
					'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'user_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
					'group_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
					'isApproved' => array('type' => 'boolean', 'null' => true, 'default' => '1'),
					'role' => array('type' => 'string', 'null' => false, 'default' => 'member', 'length' => 45),
					'isActive' => array('type' => 'boolean', 'null' => false, 'default' => '1'),
					'suspendUntil' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'lastVisit' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
					'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'group_user_idx' => array('column' => array('group_id', 'user_id'), 'unique' => 1),
						'fk_memberships_users' => array('column' => 'user_id', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB'),
				),
				'profiles' => array(
					'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'user_id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'key' => 'unique'),
					'fname' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 45),
					'lname' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 45),
					'gender' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 1),
					'country' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 45),
					'city' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 45),
					'utcOffset' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 6),
					'email' => array('type' => 'string', 'null' => true, 'default' => NULL),
					'isHtmlEmailOk' => array('type' => 'boolean', 'null' => true, 'default' => NULL),
					'email_promotions' => array('type' => 'boolean', 'null' => true, 'default' => NULL),
					'email_updates' => array('type' => 'boolean', 'null' => true, 'default' => NULL),
					'notify_members' => array('type' => 'boolean', 'null' => true, 'default' => NULL),
					'notify_comments' => array('type' => 'boolean', 'null' => true, 'default' => NULL),
					'notify_tags' => array('type' => 'boolean', 'null' => true, 'default' => NULL),
					'notify_favorites' => array('type' => 'boolean', 'null' => true, 'default' => NULL),
					'notify_downloads' => array('type' => 'boolean', 'null' => true, 'default' => NULL),
					'privacy_assets' => array('type' => 'integer', 'null' => false, 'default' => '519', 'length' => 5),
					'privacy_groups' => array('type' => 'integer', 'null' => false, 'default' => '567', 'length' => 5),
					'privacy_secret_key' => array('type' => 'integer', 'null' => false, 'default' => '1', 'length' => 5),
					'social_comments' => array('type' => 'boolean', 'null' => true, 'default' => NULL),
					'social_tags' => array('type' => 'boolean', 'null' => true, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'fk_profiles_users' => array('column' => 'user_id', 'unique' => 1),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB'),
				),
				'provider_accounts' => array(
					'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'user_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
					'provider_name' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45),
					'provider_key' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45),
					'display_name' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45),
					'baseurl' => array('type' => 'string', 'null' => true, 'default' => NULL),
					'auth_token' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 45),
					'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'fk_providerAccounts_owner' => array('column' => 'user_id', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB'),
				),
				'providers' => array(
					'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'name' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45),
					'description' => array('type' => 'text', 'null' => true, 'default' => NULL),
					'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB'),
				),
				'shared_edits' => array(
					'asset_hash' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 32, 'key' => 'primary'),
					'rotate' => array('type' => 'integer', 'null' => true, 'default' => '1', 'length' => 4),
					'votes' => array('type' => 'integer', 'null' => true, 'default' => '0'),
					'points' => array('type' => 'integer', 'null' => true, 'default' => '0'),
					'score' => array('type' => 'float', 'null' => true, 'default' => NULL, 'length' => '3,2'),
					'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => 'asset_hash', 'unique' => 1),
						'asset_hash_UNIQUE' => array('column' => 'asset_hash', 'unique' => 1),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB'),
				),
				'tagged' => array(
					'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'foreign_key' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
					'tag_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
					'model' => array('type' => 'string', 'null' => false, 'default' => NULL, 'key' => 'index'),
					'language' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 6, 'key' => 'index'),
					'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'times_tagged' => array('type' => 'integer', 'null' => false, 'default' => '1'),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'UNIQUE_TAGGING' => array('column' => array('model', 'foreign_key', 'tag_id', 'language'), 'unique' => 1),
						'INDEX_TAGGED' => array('column' => 'model', 'unique' => 0),
						'INDEX_LANGUAGE' => array('column' => 'language', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB'),
				),
				'user_edits' => array(
					'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'asset_hash' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 32, 'key' => 'index'),
					'owner_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
					'isEditor' => array('type' => 'boolean', 'null' => true, 'default' => '0'),
					'isReviewed' => array('type' => 'boolean', 'null' => true, 'default' => '0'),
					'isPublished' => array('type' => 'boolean', 'null' => true, 'default' => '0'),
					'rotate' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 4),
					'rating' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 4),
					'syncOffset' => array('type' => 'integer', 'null' => true, 'default' => '0'),
					'isScrubbed' => array('type' => 'boolean', 'null' => true, 'default' => '0'),
					'isCroppped' => array('type' => 'boolean', 'null' => true, 'default' => '0'),
					'isLocked' => array('type' => 'boolean', 'null' => true, 'default' => '0'),
					'isExported' => array('type' => 'boolean', 'null' => true, 'default' => '0'),
					'isDone' => array('type' => 'boolean', 'null' => true, 'default' => '0'),
					'src_json' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 1024),
					'edit_json' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 1024),
					'lastVisit' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
					'flaggedAt' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
					'flag_json' => array('type' => 'text', 'null' => true, 'default' => NULL),
					'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'fk_userEdits_users' => array('column' => array('owner_id', 'asset_hash'), 'unique' => 1),
						'fk_userEdits_assets' => array('column' => 'asset_hash', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB'),
				),
				'users' => array(
					'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'username' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45, 'key' => 'unique'),
					'password' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 40),
					'email' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 45),
					'src_thumbnail' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 1024),
					'active' => array('type' => 'boolean', 'null' => false, 'default' => '1'),
					'primary_group_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
					'asset_count' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 9),
					'slug' => array('type' => 'string', 'null' => true, 'default' => NULL),
					'privacy' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 4),
					'lastVisit' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
					'groups_user_count' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 9),
					'comment_count' => array('type' => 'integer', 'null' => true, 'default' => '0', 'length' => 8),
					'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1),
						'username_UNIQUE' => array('column' => 'username', 'unique' => 1),
						'credential_idx' => array('column' => array('username', 'password'), 'unique' => 0),
						'fk_users_groups' => array('column' => 'primary_group_id', 'unique' => 0),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB'),
				),
			),
		),
		'down' => array(
			'drop_table' => array(
				'assets', 'assets_collections', 'assets_groups', 'auth_accounts', 'collections', 'collections_groups', 'groups', 'groups_users', 'profiles', 'provider_accounts', 'providers', 'shared_edits', 'tagged', 'user_edits', 'users'
			),
		),
	);

/**
 * Before migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	public function before($direction) {
		return true;
	}

/**
 * After migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	public function after($direction) {
		return true;
	}
}
?>