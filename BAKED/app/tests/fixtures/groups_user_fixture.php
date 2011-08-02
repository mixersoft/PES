<?php
/* GroupsUser Fixture generated on: 2010-03-30 05:03:06 : 1269923886 */
class GroupsUserFixture extends CakeTestFixture {
	var $name = 'GroupsUser';

	var $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'user_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'group_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'isApproved' => array('type' => 'boolean', 'null' => true, 'default' => '1'),
		'role' => array('type' => 'string', 'null' => false, 'default' => 'member', 'length' => 45),
		'isActive' => array('type' => 'boolean', 'null' => false, 'default' => NULL),
		'suspendUntil' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'lastVisit' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => array('user_id', 'group_id'), 'unique' => 1), 'group_user_idx' => array('column' => array('group_id', 'user_id'), 'unique' => 1), 'fk_memberships_groups' => array('column' => 'group_id', 'unique' => 0), 'fk_memberships_users' => array('column' => 'user_id', 'unique' => 0)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'MyISAM')
	);

	var $records = array(
		array(
			'id' => '4bb1802e-db04-48f5-8ba4-12f0f67883f5',
			'user_id' => '4bb1802e-f2d8-4de3-bd19-12f0f67883f5',
			'group_id' => '4bb1802e-06c4-4187-a054-12f0f67883f5',
			'isApproved' => 1,
			'role' => 'Lorem ipsum dolor sit amet',
			'isActive' => 1,
			'suspendUntil' => '2010-03-30 05:38:06',
			'lastVisit' => '1269923886'
		),
	);
}
?>