<?php
/* Asset Fixture generated on: 2010-04-02 02:04:42 : 1270172982 */
class AssetFixture extends CakeTestFixture {
	var $name = 'Asset';

	var $fields = array(
		'id' => array('type' => 'binary', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'provider_name' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45),
		'provider_key' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45),
		'provider_account_id' => array('type' => 'binary', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
		'asset_hash' => array('type' => 'binary', 'null' => false, 'default' => NULL, 'length' => 16, 'key' => 'index'),
		'owner_id' => array('type' => 'binary', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
		'caption' => array('type' => 'string', 'null' => true, 'default' => NULL),
		'dateTaken' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'src_thumbnail' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 1024),
		'json_src' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 4096),
		'json_exif' => array('type' => 'string', 'null' => true, 'default' => NULL),
		'cameraId' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 45),
		'isFlash' => array('type' => 'boolean', 'null' => true, 'default' => '0'),
		'isRGB' => array('type' => 'boolean', 'null' => true, 'default' => '1'),
		'uploadId' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
		'batchId' => array('type' => 'timestamp', 'null' => true, 'default' => NULL),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'index_assets_owner' => array('column' => 'owner_id', 'unique' => 0), 'index_assetHash' => array('column' => 'asset_hash', 'unique' => 0), 'fk_assets_providerAccounts' => array('column' => 'provider_account_id', 'unique' => 0)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB')
	);

	var $records = array(
		array(
			'id' => '4bb54d36-f9f8-4a7c-b0ce-07e0f67883f5',
			'provider_name' => 'Lorem ipsum dolor sit amet',
			'provider_key' => 'Lorem ipsum dolor sit amet',
			'provider_account_id' => 'Lorem ipsum dolor sit amet',
			'asset_hash' => 'Lorem ipsum do',
			'owner_id' => 'Lorem ipsum dolor sit amet',
			'caption' => 'Lorem ipsum dolor sit amet',
			'dateTaken' => '2010-04-02 02:49:42',
			'src_thumbnail' => 'Lorem ipsum dolor sit amet',
			'json_src' => 'Lorem ipsum dolor sit amet',
			'json_exif' => 'Lorem ipsum dolor sit amet',
			'cameraId' => 'Lorem ipsum dolor sit amet',
			'isFlash' => 1,
			'isRGB' => 1,
			'uploadId' => '1270172982',
			'batchId' => '1270172982',
			'created' => '2010-04-02 02:49:42',
			'modified' => '2010-04-02 02:49:42'
		),
	);
}
?>