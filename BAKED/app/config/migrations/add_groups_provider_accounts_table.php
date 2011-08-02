<?php
class M4d2cdb64f1104d1487871d98f67883f5 extends CakeMigration {

/**
 * Migration description
 *
 * @var string
 * @access public
 */
	public $description = '';

/**
 * Actions to be performed
 *
 * @var array $migration
 * @access public
 */
	public $migration = array(
		'up' => array(
			'create_table' => array(
				'groups_provider_accounts' => array(
					'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary'),
					'group_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
					'provider_account_id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36),
					'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
					'indexes' => array(
						'PRIMARY' => array('column' => 'id', 'unique' => 1), 
						'unique' => array('column' => array('group_id', 'provider_account_id'), 'unique' => 1),
					),
					'tableParameters' => array('charset' => 'utf8', 'collate' => 'utf8_unicode_ci', 'engine' => 'InnoDB'),
				),
			),
		),
		'down' => array(
			'drop_table' => array(
				'groups_provider_accounts', 
			),
		),
	);

/**
 * Before migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	public function before($direction) {
		return true;
	}

/**
 * After migration callback
 *
 * @param string $direction, up or down direction of migration process
 * @return boolean Should process continue
 * @access public
 */
	public function after($direction) {
			if ($direction=='up') {
/*
* 
// this is the SQL to add data to groups_provider_accounts
insert into groups_provider_accounts
- this is the select to recreate groups_provider_accounts table
SELECT distinct uuid() as id, g.id as group_id, pa.id as provider_account_id, now() as created
-- , g.title, pa.user_id, pa.display_name
FROM groups g
join assets_groups ag on ag.group_id = g.id
join assets a on a.id = ag.asset_id
join provider_accounts pa on pa.id = a.provider_account_id
order by id;
* 
*/			
		}		
		return true;
	}
}
?>