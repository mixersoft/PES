<?php
/* Group Fixture generated on: 2010-03-30 05:03:06 : 1269923886 */
class GroupFixture extends CakeTestFixture {
	var $name = 'Group';

	var $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'owner_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
		'isSystem' => array('type' => 'boolean', 'null' => false, 'default' => '0'),
		'title' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45),
		'description' => array('type' => 'text', 'null' => true, 'default' => NULL),
		'membership_policy' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 45),
		'invitation_policy' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 45),
		'submission_policy' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 45),
		'isNC17' => array('type' => 'boolean', 'null' => true, 'default' => '0'),
		'lastVisit' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'fk_groups_owner' => array('column' => 'owner_id', 'unique' => 0)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'MyISAM')
	);

	var $records = array(
		array(
			'id' => '4bb1802e-37e8-4a6d-bde4-12f0f67883f5',
			'owner_id' => 'Lorem ipsum dolor sit amet',
			'isSystem' => 1,
			'title' => 'Lorem ipsum dolor sit amet',
			'description' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'membership_policy' => 'Lorem ipsum dolor sit amet',
			'invitation_policy' => 'Lorem ipsum dolor sit amet',
			'submission_policy' => 'Lorem ipsum dolor sit amet',
			'isNC17' => 1,
			'lastVisit' => '1269923886',
			'created' => '2010-03-30 05:38:06',
			'modified' => '2010-03-30 05:38:06'
		),
	);
}
?>