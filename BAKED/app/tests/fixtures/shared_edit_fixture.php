<?php
/* SharedEdit Fixture generated on: 2010-04-02 03:04:26 : 1270173986 */
class SharedEditFixture extends CakeTestFixture {
	var $name = 'SharedEdit';

	var $fields = array(
		'asset_hash' => array('type' => 'binary', 'null' => false, 'default' => NULL, 'length' => 16, 'key' => 'primary'),
		'rotate' => array('type' => 'integer', 'null' => true, 'default' => '1', 'length' => 4),
		'votes' => array('type' => 'integer', 'null' => true, 'default' => '0'),
		'points' => array('type' => 'integer', 'null' => true, 'default' => '0'),
		'score' => array('type' => 'float', 'null' => true, 'default' => NULL, 'length' => 10),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'asset_hash', 'unique' => 1), 'asset_hash_UNIQUE' => array('column' => 'asset_hash', 'unique' => 1)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB')
	);

	var $records = array(
		array(
			'asset_hash' => 'Lorem ipsum do',
			'rotate' => 1,
			'votes' => 1,
			'points' => 1,
			'score' => 1,
			'modified' => '2010-04-02 03:06:26'
		),
	);
}
?>