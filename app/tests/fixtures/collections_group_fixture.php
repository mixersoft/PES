<?php
/* CollectionsGroup Fixture generated on: 2010-03-30 05:03:06 : 1269923886 */
class CollectionsGroupFixture extends CakeTestFixture {
	var $name = 'CollectionsGroup';

	var $fields = array(
		'id' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'collection_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'group_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'isApproved' => array('type' => 'boolean', 'null' => true, 'default' => '1'),
		'indexes' => array('PRIMARY' => array('column' => array('collection_id', 'group_id'), 'unique' => 1), 'fk_collections_groups_collections' => array('column' => 'collection_id', 'unique' => 0), 'fk_collections_groups_groups' => array('column' => 'group_id', 'unique' => 0)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'MyISAM')
	);

	var $records = array(
		array(
			'id' => '4bb1802e-5568-4aa2-bda8-12f0f67883f5',
			'collection_id' => '4bb1802e-6da0-47e3-99bc-12f0f67883f5',
			'group_id' => '4bb1802e-81f0-4a6b-8621-12f0f67883f5',
			'isApproved' => 1
		),
	);
}
?>