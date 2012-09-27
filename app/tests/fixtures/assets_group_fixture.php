<?php
/* AssetsGroup Fixture generated on: 2012-09-24 21:55:06 : 1348523706 */
class AssetsGroupFixture extends CakeTestFixture {
	var $name = 'AssetsGroup';
	// var $import = array('model' => 'AssetsGroup');

	var $fields = array(
		'id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'collate' => 'latin1_general_ci', 'charset' => 'latin1', 'key' => ''),
		'asset_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary', 'collate' => 'latin1_general_ci', 'charset' => 'latin1'),
		'group_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary', 'collate' => 'latin1_general_ci', 'charset' => 'latin1'),
		'isApproved' => array('type' => 'boolean', 'null' => true, 'default' => '1'),
		'user_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'dateTaken_offset' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 12, 'collate' => 'utf8_general_ci', 'comment' => '+/- HOURS:MINUTES:SECONDS', 'charset' => 'utf8'),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => array('asset_id', 'group_id'), 'unique' => 1), 'fk_assets_groups_assets' => array('column' => 'asset_id', 'unique' => 0), 'fk_assets_groups_groups' => array('column' => 'group_id', 'unique' => 0)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_general_ci', 'engine' => 'InnoDB')
	);
	
	var $records = array(
		array(
			'id' => '4bbc1df7-bca0-4298-af00-11a0f67883f5',
			'asset_id' => '4bbb3976-22b8-442c-b29a-11a0f67883f5',
			'group_id' => 'member---0123-4567-89ab-000000000002',
			'isApproved' => 1,
			'user_id' => '12345678-1111-0000-0000-venice------',
			'dateTaken_offset' => NULL,
			'modified' => '2010-04-07 06:53:59'
		),
		array(
			'id' => '4bbc2011-de30-4135-899b-11a0f67883f5',
			'asset_id' => '4bbb3976-22b8-442c-b29a-11a0f67883f5',
			'group_id' => 'member---0123-4567-89ab-000000000003',
			'isApproved' => 1,
			'user_id' => '12345678-1111-0000-0000-venice------',
			'dateTaken_offset' => NULL,
			'modified' => '2010-04-07 07:02:57'
		),
		array(
			'id' => '4bbc1df7-9e60-4a12-b888-11a0f67883f5',
			'asset_id' => '4bbb3976-3268-4073-878d-11a0f67883f5',
			'group_id' => 'member---0123-4567-89ab-000000000002',
			'isApproved' => 1,
			'user_id' => '12345678-1111-0000-0000-venice------',
			'dateTaken_offset' => NULL,
			'modified' => '2010-04-07 06:53:59'
		),
		array(
			'id' => '4bbc2011-6cd0-4796-bbad-11a0f67883f5',
			'asset_id' => '4bbb3976-3268-4073-878d-11a0f67883f5',
			'group_id' => 'member---0123-4567-89ab-000000000003',
			'isApproved' => 1,
			'user_id' => '12345678-1111-0000-0000-venice------',
			'dateTaken_offset' => NULL,
			'modified' => '2010-04-07 07:02:57'
		),
		array(
			'id' => '4bbc1df7-a0bc-4a03-b28e-11a0f67883f5',
			'asset_id' => '4bbb3976-3498-40ea-803d-11a0f67883f5',
			'group_id' => 'member---0123-4567-89ab-000000000002',
			'isApproved' => 1,
			'user_id' => '12345678-1111-0000-0000-venice------',
			'dateTaken_offset' => NULL,
			'modified' => '2010-04-07 06:53:59'
		),
		array(
			'id' => '4bbc2011-6af0-4ddb-9a29-11a0f67883f5',
			'asset_id' => '4bbb3976-3498-40ea-803d-11a0f67883f5',
			'group_id' => 'member---0123-4567-89ab-000000000003',
			'isApproved' => 1,
			'user_id' => '12345678-1111-0000-0000-venice------',
			'dateTaken_offset' => NULL,
			'modified' => '2010-04-07 07:02:57'
		),
		array(
			'id' => '4bbc1df7-7da8-4eda-a4c3-11a0f67883f5',
			'asset_id' => '4bbb3976-4304-4c55-a7cf-11a0f67883f5',
			'group_id' => 'member---0123-4567-89ab-000000000002',
			'isApproved' => 1,
			'user_id' => '12345678-1111-0000-0000-venice------',
			'dateTaken_offset' => NULL,
			'modified' => '2010-04-07 06:53:59'
		),
		array(
			'id' => '4bbc2011-4714-4240-bdbe-11a0f67883f5',
			'asset_id' => '4bbb3976-4304-4c55-a7cf-11a0f67883f5',
			'group_id' => 'member---0123-4567-89ab-000000000003',
			'isApproved' => 1,
			'user_id' => '12345678-1111-0000-0000-venice------',
			'dateTaken_offset' => NULL,
			'modified' => '2010-04-07 07:02:57'
		),
		array(
			'id' => '4bbc1df7-4d04-4b8e-be49-11a0f67883f5',
			'asset_id' => '4bbb3976-8b5c-4ae3-b7d6-11a0f67883f5',
			'group_id' => 'member---0123-4567-89ab-000000000002',
			'isApproved' => 1,
			'user_id' => '12345678-1111-0000-0000-venice------',
			'dateTaken_offset' => NULL,
			'modified' => '2010-04-07 06:53:59'
		),
		array(
			'id' => '4bbc2011-592c-4d4b-a019-11a0f67883f5',
			'asset_id' => '4bbb3976-8b5c-4ae3-b7d6-11a0f67883f5',
			'group_id' => 'member---0123-4567-89ab-000000000003',
			'isApproved' => 1,
			'user_id' => '12345678-1111-0000-0000-venice------',
			'dateTaken_offset' => NULL,
			'modified' => '2010-04-07 07:02:57'
		),
		array(
			'id' => '4bbc1df7-4004-47e2-86dc-11a0f67883f5',
			'asset_id' => '4bbb3976-9a7c-4e05-900d-11a0f67883f5',
			'group_id' => 'member---0123-4567-89ab-000000000002',
			'isApproved' => 1,
			'user_id' => '12345678-1111-0000-0000-venice------',
			'dateTaken_offset' => NULL,
			'modified' => '2010-04-07 06:53:59'
		),
		array(
			'id' => '4bbc2011-2ee0-416a-8602-11a0f67883f5',
			'asset_id' => '4bbb3976-9a7c-4e05-900d-11a0f67883f5',
			'group_id' => 'member---0123-4567-89ab-000000000003',
			'isApproved' => 1,
			'user_id' => '12345678-1111-0000-0000-venice------',
			'dateTaken_offset' => NULL,
			'modified' => '2010-04-07 07:02:57'
		),
		array(
			'id' => '4bbc1df7-f2cc-4eb6-9cb5-11a0f67883f5',
			'asset_id' => '4bbb3976-adf8-44da-b893-11a0f67883f5',
			'group_id' => 'member---0123-4567-89ab-000000000002',
			'isApproved' => 1,
			'user_id' => '12345678-1111-0000-0000-venice------',
			'dateTaken_offset' => NULL,
			'modified' => '2010-04-07 06:53:59'
		),
		array(
			'id' => '4bbc2011-f4dc-425e-bccf-11a0f67883f5',
			'asset_id' => '4bbb3976-adf8-44da-b893-11a0f67883f5',
			'group_id' => 'member---0123-4567-89ab-000000000003',
			'isApproved' => 1,
			'user_id' => '12345678-1111-0000-0000-venice------',
			'dateTaken_offset' => NULL,
			'modified' => '2010-04-07 07:02:57'
		),
		array(
			'id' => '4bbc1df7-edcc-406b-827c-11a0f67883f5',
			'asset_id' => '4bbb3976-ca10-40c1-ae67-11a0f67883f5',
			'group_id' => 'member---0123-4567-89ab-000000000002',
			'isApproved' => 1,
			'user_id' => '12345678-1111-0000-0000-venice------',
			'dateTaken_offset' => NULL,
			'modified' => '2010-04-07 06:53:59'
		),
		array(
			'id' => '4bbc2011-de48-4882-839f-11a0f67883f5',
			'asset_id' => '4bbb3976-ca10-40c1-ae67-11a0f67883f5',
			'group_id' => 'member---0123-4567-89ab-000000000003',
			'isApproved' => 1,
			'user_id' => '12345678-1111-0000-0000-venice------',
			'dateTaken_offset' => NULL,
			'modified' => '2010-04-07 07:02:57'
		),
		array(
			'id' => '4bbc1df7-a664-4e05-93b1-11a0f67883f5',
			'asset_id' => '4bbb3976-d0ac-4115-b542-11a0f67883f5',
			'group_id' => 'member---0123-4567-89ab-000000000002',
			'isApproved' => 1,
			'user_id' => '12345678-1111-0000-0000-venice------',
			'dateTaken_offset' => NULL,
			'modified' => '2010-04-07 06:53:59'
		),
		array(
			'id' => '4bbc2011-81c8-42dc-a84b-11a0f67883f5',
			'asset_id' => '4bbb3976-d0ac-4115-b542-11a0f67883f5',
			'group_id' => 'member---0123-4567-89ab-000000000003',
			'isApproved' => 1,
			'user_id' => '12345678-1111-0000-0000-venice------',
			'dateTaken_offset' => NULL,
			'modified' => '2010-04-07 07:02:57'
		),
		array(
			'id' => '4bbc1df7-ad5c-4e51-92de-11a0f67883f5',
			'asset_id' => '4bbb3976-ea30-4216-99ee-11a0f67883f5',
			'group_id' => 'member---0123-4567-89ab-000000000002',
			'isApproved' => 1,
			'user_id' => '12345678-1111-0000-0000-venice------',
			'dateTaken_offset' => NULL,
			'modified' => '2010-04-07 06:53:59'
		),
		array(
			'id' => '4bbc2011-0c64-43c7-9b90-11a0f67883f5',
			'asset_id' => '4bbb3976-ea30-4216-99ee-11a0f67883f5',
			'group_id' => 'member---0123-4567-89ab-000000000003',
			'isApproved' => 1,
			'user_id' => '12345678-1111-0000-0000-venice------',
			'dateTaken_offset' => NULL,
			'modified' => '2010-04-07 07:02:57'
		),
	);
}
