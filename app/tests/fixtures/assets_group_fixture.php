<?php
/* AssetsGroup Fixture generated on: 2010-03-30 05:03:05 : 1269923885 */
class AssetsGroupFixture extends CakeTestFixture {
	var $name = 'AssetsGroup';

	var $fields = array(
		'id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'asset_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'group_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'isApproved' => array('type' => 'boolean', 'null' => true, 'default' => '1'),
		'indexes' => array('PRIMARY' => array('column' => array('asset_id', 'group_id'), 'unique' => 1), 'fk_assets_groups_assets' => array('column' => 'asset_id', 'unique' => 0), 'fk_assets_groups_groups' => array('column' => 'group_id', 'unique' => 0)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'MyISAM')
	);

	var $records = array(
		array(
			'id' => '4bb1802d-5300-4534-99d1-12f0f67883f5',
			'asset_id' => '4bb1802d-6a70-4f1e-a56b-12f0f67883f5',
			'group_id' => '4bb1802d-7ec0-41f2-8dbb-12f0f67883f5',
			'isApproved' => 1
		),
	);
}
?>