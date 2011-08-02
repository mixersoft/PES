<?php
/* AssetsCollection Fixture generated on: 2010-03-30 05:03:05 : 1269923885 */
class AssetsCollectionFixture extends CakeTestFixture {
	var $name = 'AssetsCollection';

	var $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'collection_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'asset_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => array('collection_id', 'asset_id'), 'unique' => 1), 'fk_assets_collections_collections' => array('column' => 'collection_id', 'unique' => 0), 'fk_assets_collections_assets' => array('column' => 'asset_id', 'unique' => 0)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'MyISAM')
	);

	var $records = array(
		array(
			'id' => '4bb1802d-f7b0-4057-917c-12f0f67883f5',
			'collection_id' => '4bb1802d-0f20-4e03-9474-12f0f67883f5',
			'asset_id' => '4bb1802d-2370-49f5-97bd-12f0f67883f5',
			'modified' => '2010-03-30 05:38:05'
		),
	);
}
?>