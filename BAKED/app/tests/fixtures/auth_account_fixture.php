<?php
/* AuthAccount Fixture generated on: 2010-03-30 05:03:05 : 1269923885 */
class AuthAccountFixture extends CakeTestFixture {
	var $name = 'AuthAccount';

	var $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'unique_hash' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 16, 'key' => 'unique'),
		'user_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
		'provider_name' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45),
		'provider_key' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 1000),
		'password' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 40),
		'display_name' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45),
		'email' => array('type' => 'string', 'null' => true, 'default' => NULL),
		'url' => array('type' => 'string', 'null' => true, 'default' => NULL),
		'photo' => array('type' => 'string', 'null' => true, 'default' => NULL),
		'country' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 45),
		'city' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 45),
		'utcOffset' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 6),
		'gender' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 1),
		'active' => array('type' => 'boolean', 'null' => true, 'default' => '1'),
		'lastVisit' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'unique_hash_UNIQUE' => array('column' => 'unique_hash', 'unique' => 1), 'fk_auth_accounts_users' => array('column' => 'user_id', 'unique' => 0)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'MyISAM')
	);

	var $records = array(
		array(
			'id' => '4bb1802d-9e98-451c-aac1-12f0f67883f5',
			'unique_hash' => 'Lorem ipsum do',
			'user_id' => 'Lorem ipsum dolor sit amet',
			'provider_name' => 'Lorem ipsum dolor sit amet',
			'provider_key' => 'Lorem ipsum dolor sit amet',
			'password' => 'Lorem ipsum dolor sit amet',
			'display_name' => 'Lorem ipsum dolor sit amet',
			'email' => 'Lorem ipsum dolor sit amet',
			'url' => 'Lorem ipsum dolor sit amet',
			'photo' => 'Lorem ipsum dolor sit amet',
			'country' => 'Lorem ipsum dolor sit amet',
			'city' => 'Lorem ipsum dolor sit amet',
			'utcOffset' => 'Lore',
			'gender' => 'Lorem ipsum dolor sit ame',
			'active' => 1,
			'lastVisit' => '1269923885',
			'created' => '2010-03-30 05:38:05',
			'modified' => '2010-03-30 05:38:05'
		),
	);
}
?>