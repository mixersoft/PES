<?php
/* ProviderAccount Fixture generated on: 2010-04-02 02:04:41 : 1270173101 */
class ProviderAccountFixture extends CakeTestFixture {
	var $name = 'ProviderAccount';

	var $fields = array(
		'id' => array('type' => 'binary', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
		'user_id' => array('type' => 'binary', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'index'),
		'provider_name' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45),
		'provider_key' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45),
		'display_name' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 45),
		'auth_token' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 45),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'fk_providerAccounts_owner' => array('column' => 'user_id', 'unique' => 0)),
		'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB')
	);

	var $records = array(
		array(
			'id' => '4bb54dad-3184-4c08-b040-07e0f67883f5',
			'user_id' => 'Lorem ipsum dolor sit amet',
			'provider_name' => 'Lorem ipsum dolor sit amet',
			'provider_key' => 'Lorem ipsum dolor sit amet',
			'display_name' => 'Lorem ipsum dolor sit amet',
			'auth_token' => 'Lorem ipsum dolor sit amet',
			'created' => '2010-04-02 02:51:41',
			'modified' => '2010-04-02 02:51:41'
		),
	);
}
?>