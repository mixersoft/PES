<?php
/* Permission Fixture generated on: 2010-03-30 05:03:06 : 1269923886 */
class PermissionFixture extends CakeTestFixture {
	var $name = 'Permission';

	var $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'model' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 32, 'key' => 'index'),
		'foreignId' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
		'oid' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
		'gid' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
		'perms' => array('type' => 'integer', 'null' => false, 'default' => '0000', 'length' => 4, 'key' => 'index'),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'polymorphic_idx' => array('column' => array('model', 'foreignId'), 'unique' => 1), 'owner_user_idx' => array('column' => 'oid', 'unique' => 0), 'owner_group_idx' => array('column' => 'gid', 'unique' => 0), 'permission_idx' => array('column' => 'perms', 'unique' => 0)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'MyISAM')
	);

	var $records = array(
		array(
			'id' => '4bb1802e-f300-472a-b77a-12f0f67883f5',
			'model' => 'Lorem ipsum dolor sit amet',
			'foreignId' => 'Lorem ipsum dolor sit amet',
			'oid' => 'Lorem ipsum dolor sit amet',
			'gid' => 'Lorem ipsum dolor sit amet',
			'perms' => 1
		),
	);
}
?>