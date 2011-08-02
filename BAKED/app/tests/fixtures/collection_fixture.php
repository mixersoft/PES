<?php
/* Collection Fixture generated on: 2010-03-30 05:03:06 : 1269923886 */
class CollectionFixture extends CakeTestFixture {
	var $name = 'Collection';

	var $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'title' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45),
		'owner_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
		'description' => array('type' => 'text', 'null' => true, 'default' => NULL),
		'markup' => array('type' => 'text', 'null' => true, 'default' => NULL),
		'src' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 1000),
		'lastVisit' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'fk_collections_owner' => array('column' => 'owner_id', 'unique' => 0)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'MyISAM')
	);

	var $records = array(
		array(
			'id' => '4bb1802e-9cb4-44fc-9b0f-12f0f67883f5',
			'title' => 'Lorem ipsum dolor sit amet',
			'owner_id' => 'Lorem ipsum dolor sit amet',
			'description' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'markup' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'src' => 'Lorem ipsum dolor sit amet',
			'lastVisit' => '1269923886',
			'created' => '2010-03-30 05:38:06',
			'modified' => '2010-03-30 05:38:06'
		),
	);
}
?>