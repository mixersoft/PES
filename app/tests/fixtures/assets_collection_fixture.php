<?php
/* AssetsCollection Fixture generated on: 2012-09-24 22:08:08 : 1348524488 */
class AssetsCollectionFixture extends CakeTestFixture {
	var $name = 'AssetsCollection';
	// var $import = array('model' => 'AssetsCollection');

	var $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'collate' => 'latin1_general_ci', 'charset' => 'latin1', 'key' => 'primary'),
		'collection_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary', 'collate' => 'latin1_general_ci', 'charset' => 'latin1'),
		'asset_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary', 'collate' => 'latin1_general_ci', 'charset' => 'latin1'),
		'user_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => array('collection_id', 'asset_id'), 'unique' => 1), 'fk_assets_collections_collections' => array('column' => 'collection_id', 'unique' => 0), 'fk_assets_collections_assets' => array('column' => 'asset_id', 'unique' => 0)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'InnoDB')
	);

	var $records = array(
		array(
			'id' => 'b84b8b44-8212-11e1-bf63-22000afc480d',
			'collection_id' => '4f828ac7-e698-422e-a1d1-5e7a0afc480d',
			'asset_id' => '4bbb3976-1c9c-40aa-a405-11a0f67883f5',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-09 07:07:51'
		),
		array(
			'id' => 'b84b87e8-8212-11e1-bf63-22000afc480d',
			'collection_id' => '4f828ac7-e698-422e-a1d1-5e7a0afc480d',
			'asset_id' => '4bbb3976-2650-4f14-b0d9-11a0f67883f5',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-09 07:07:51'
		),
		array(
			'id' => 'b84b8a72-8212-11e1-bf63-22000afc480d',
			'collection_id' => '4f828ac7-e698-422e-a1d1-5e7a0afc480d',
			'asset_id' => '4bbb3976-a080-44f5-84c8-11a0f67883f5',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-09 07:07:51'
		),
		array(
			'id' => 'b84b88b0-8212-11e1-bf63-22000afc480d',
			'collection_id' => '4f828ac7-e698-422e-a1d1-5e7a0afc480d',
			'asset_id' => '4bbb3976-b76c-4907-a195-11a0f67883f5',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-09 07:07:51'
		),
		array(
			'id' => 'b84b8982-8212-11e1-bf63-22000afc480d',
			'collection_id' => '4f828ac7-e698-422e-a1d1-5e7a0afc480d',
			'asset_id' => '4bbb3976-e008-4cd1-9d32-11a0f67883f5',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-09 07:07:51'
		),
		array(
			'id' => 'b84b85fe-8212-11e1-bf63-22000afc480d',
			'collection_id' => '4f828ac7-e698-422e-a1d1-5e7a0afc480d',
			'asset_id' => '4bbb3976-eeac-4771-a7f4-11a0f67883f5',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-09 07:07:51'
		),
		array(
			'id' => 'd43edece-8216-11e1-bf63-22000afc480d',
			'collection_id' => '4f8291ab-2014-4f38-af7e-636f0afc480d',
			'asset_id' => '4bbb3976-2894-4c09-a61c-11a0f67883f5',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-09 07:37:15'
		),
		array(
			'id' => 'd43ed9f6-8216-11e1-bf63-22000afc480d',
			'collection_id' => '4f8291ab-2014-4f38-af7e-636f0afc480d',
			'asset_id' => '4bbb3976-2d1c-43e6-876b-11a0f67883f5',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-09 07:37:15'
		),
		array(
			'id' => 'd43edce4-8216-11e1-bf63-22000afc480d',
			'collection_id' => '4f8291ab-2014-4f38-af7e-636f0afc480d',
			'asset_id' => '4bbb3976-5204-4280-8a76-11a0f67883f5',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-09 07:37:15'
		),
		array(
			'id' => 'd43eddc0-8216-11e1-bf63-22000afc480d',
			'collection_id' => '4f8291ab-2014-4f38-af7e-636f0afc480d',
			'asset_id' => '4bbb3976-b5d0-4f20-862b-11a0f67883f5',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-09 07:37:15'
		),
		array(
			'id' => 'd43ee0b8-8216-11e1-bf63-22000afc480d',
			'collection_id' => '4f8291ab-2014-4f38-af7e-636f0afc480d',
			'asset_id' => '4bbb3976-d540-4515-b67f-11a0f67883f5',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-09 07:37:15'
		),
		array(
			'id' => 'd43edbf4-8216-11e1-bf63-22000afc480d',
			'collection_id' => '4f8291ab-2014-4f38-af7e-636f0afc480d',
			'asset_id' => '4bbb3976-dbec-4b96-93d1-11a0f67883f5',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-09 07:37:15'
		),
		array(
			'id' => 'd43edf96-8216-11e1-bf63-22000afc480d',
			'collection_id' => '4f8291ab-2014-4f38-af7e-636f0afc480d',
			'asset_id' => '4bbb3976-e048-463f-b9c6-11a0f67883f5',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-09 07:37:15'
		),
		array(
			'id' => 'd43ee1da-8216-11e1-bf63-22000afc480d',
			'collection_id' => '4f8291ab-2014-4f38-af7e-636f0afc480d',
			'asset_id' => '4bbb3976-f2d8-41a1-bafa-11a0f67883f5',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-09 07:37:15'
		),
		array(
			'id' => '4505f6ea-8921-11e1-bf63-22000afc480d',
			'collection_id' => '4f8e61aa-7e90-42f6-a9a2-3ca10afc480d',
			'asset_id' => '4bbb38cc-97ac-4616-a2d0-11a0f67883f5',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-18 06:39:38'
		),
		array(
			'id' => '4505f29e-8921-11e1-bf63-22000afc480d',
			'collection_id' => '4f8e61aa-7e90-42f6-a9a2-3ca10afc480d',
			'asset_id' => '4bbb3907-bfe4-46cd-9b78-11a0f67883f5',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-18 06:39:38'
		),
		array(
			'id' => '4505f032-8921-11e1-bf63-22000afc480d',
			'collection_id' => '4f8e61aa-7e90-42f6-a9a2-3ca10afc480d',
			'asset_id' => '4bbb3907-c8ec-45b4-931f-11a0f67883f5',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-18 06:39:38'
		),
		array(
			'id' => '4505f47e-8921-11e1-bf63-22000afc480d',
			'collection_id' => '4f8e61aa-7e90-42f6-a9a2-3ca10afc480d',
			'asset_id' => '4bbb3907-e7e0-4d7d-891b-11a0f67883f5',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-18 06:39:38'
		),
		array(
			'id' => '45022c7c-8921-11e1-bf63-22000afc480d',
			'collection_id' => '4f8e61aa-7e90-42f6-a9a2-3ca10afc480d',
			'asset_id' => '4bbb3907-f610-4e1e-bbf5-11a0f67883f5',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-18 06:39:38'
		),
		array(
			'id' => '64b6454e-8921-11e1-bf63-22000afc480d',
			'collection_id' => '4f8e61aa-7e90-42f6-a9a2-3ca10afc480d',
			'asset_id' => '4bbb3976-eeac-4771-a7f4-11a0f67883f5',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-18 06:40:31'
		),
		array(
			'id' => '64b64364-8921-11e1-bf63-22000afc480d',
			'collection_id' => '4f8e61aa-7e90-42f6-a9a2-3ca10afc480d',
			'asset_id' => '67B9ED53-4E6B-4165-A3DA-C595626960CC',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-18 06:40:31'
		),
		array(
			'id' => '64b64ada-8921-11e1-bf63-22000afc480d',
			'collection_id' => '4f8e61aa-7e90-42f6-a9a2-3ca10afc480d',
			'asset_id' => 'CDCB4ACC-7C53-4866-BB74-F5B5A6848097',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-18 06:40:31'
		),
		array(
			'id' => '64b646d4-8921-11e1-bf63-22000afc480d',
			'collection_id' => '4f8e61aa-7e90-42f6-a9a2-3ca10afc480d',
			'asset_id' => 'DC0E1087-B4A4-4D8A-8E9A-8711B30C9DA7',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-18 06:40:31'
		),
		array(
			'id' => '64b648d2-8921-11e1-bf63-22000afc480d',
			'collection_id' => '4f8e61aa-7e90-42f6-a9a2-3ca10afc480d',
			'asset_id' => 'E432F73D-19A3-4867-A1D3-3425CA2F09CE',
			'user_id' => '12345678-1111-0000-0000-venice------',
			'modified' => '2012-04-18 06:40:31'
		),
	);
}
