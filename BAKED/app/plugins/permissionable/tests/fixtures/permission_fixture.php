<?php

/**
 * Permission Fixture
 *
 * @package     permissionable
 * @subpackage  permissionable.tests.fixtures
 * @author      Joshua McNeese <jmcneese@gmail.com>
 * @license		Licensed under the MIT license: http://www.opensource.org/licenses/mit-license.php
 * @copyright	Copyright (c) 2009,2010 Joshua M. McNeese, Curtis J. Beeson
 */
class PermissionFixture extends CakeTestFixture {

	/**
	 * @var array
	 */
	public $fields = array(
		'id' => array(
			'type'		=> 'integer',
			'null'		=> false,
			'default'	=> NULL,
			'key'		=> 'primary'
		),
		'model' => array(
			'type'		=> 'string',
			'null'		=> false,
			'default'	=> NULL,
			'length'	=> 32,
			'key'		=> 'index'
		),
		'foreignId' => array(
			'type'		=> 'integer',
			'null'		=> false,
			'default'	=> NULL
		),
		'oid' => array(
			'type'		=> 'integer',
			'null'		=> false,
			'default'	=> NULL,
			'key'		=> 'index'
		),
		'gid' => array(
			'type'		=> 'integer',
			'null'		=> false,
			'default'	=> NULL,
			'key'		=> 'index'
		),
		'perms' => array(
			'type'		=> 'integer',
			'null'		=> false,
			'default'	=> '0000',
			'length'	=> 4
		),
		'indexes' => array(
			'PRIMARY' => array(
				'column' => 'id',
				'unique' => 1
			),
			'polymorphic_idx' => array(
				'column' => array(
					'model',
					'foreignId'
				),
				'unique' => 0
			),
			'oid_idx' => array(
				'column' => 'oid',
				'unique' => 0
			),
			'gid_idx' => array(
				'column' => 'gid',
				'unique' => 0
			)
		)
	);
	
	/**
	 * @var string
	 */
	public $name = 'Permission';

}

?>