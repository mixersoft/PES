<?php
/* UserEdit Fixture generated on: 2010-04-02 03:04:38 : 1270173998 */
class UserEditFixture extends CakeTestFixture {
	var $name = 'UserEdit';

	var $fields = array(
		'id' => array('type' => 'binary', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'asset_hash' => array('type' => 'binary', 'null' => false, 'default' => NULL, 'length' => 16, 'key' => 'index'),
		'owner_id' => array('type' => 'binary', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
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
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'fk_userEdits_assets' => array('column' => 'asset_hash', 'unique' => 0), 'fk_userEdits_users' => array('column' => 'owner_id', 'unique' => 0)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB')
	);

	var $records = array(
		array(
			'id' => '4bb5512e-f148-454f-be43-1474f67883f5',
			'asset_hash' => 'Lorem ipsum do',
			'owner_id' => 'Lorem ipsum dolor sit amet',
			'isEditor' => 1,
			'isReviewed' => 1,
			'isPublished' => 1,
			'rotate' => 1,
			'rating' => 1,
			'syncOffset' => 1,
			'isScrubbed' => 1,
			'isCroppped' => 1,
			'isLocked' => 1,
			'isExported' => 1,
			'isDone' => 1,
			'src_json' => 'Lorem ipsum dolor sit amet',
			'edit_json' => 'Lorem ipsum dolor sit amet',
			'lastVisit' => '1270173998',
			'flaggedAt' => '1270173998',
			'flag_json' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'created' => '2010-04-02 03:06:38',
			'modified' => '2010-04-02 03:06:38'
		),
	);
}
?>