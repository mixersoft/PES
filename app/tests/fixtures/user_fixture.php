<?php
/* User Fixture generated on: 2010-03-30 05:03:07 : 1269923887 */
class UserFixture extends CakeTestFixture {
	var $name = 'User';

	var $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'username' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45, 'key' => 'unique'),
		'password' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 40),
		'email' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 45),
		'active' => array('type' => 'boolean', 'null' => false, 'default' => '1'),
		'group_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
		'privacy' => array('type' => 'integer', 'null' => true, 'default' => NULL, 'length' => 4),
		'lastVisit' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'username_UNIQUE' => array('column' => 'username', 'unique' => 1), 'credential_idx' => array('column' => array('username', 'password'), 'unique' => 0), 'fk_users_groups' => array('column' => 'group_id', 'unique' => 0)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'MyISAM')
	);

	var $records = array(
		array(
			'id' => '4bb1802f-e0dc-4128-91e3-12f0f67883f5',
			'username' => 'Lorem ipsum dolor sit amet',
			'password' => 'Lorem ipsum dolor sit amet',
			'email' => 'Lorem ipsum dolor sit amet',
			'active' => 1,
			'group_id' => 'Lorem ipsum dolor sit amet',
			'privacy' => 1,
			'lastVisit' => '1269923887',
			'modified' => '2010-03-30 05:38:07',
			'created' => '2010-03-30 05:38:07'
		),
	);
}
?>