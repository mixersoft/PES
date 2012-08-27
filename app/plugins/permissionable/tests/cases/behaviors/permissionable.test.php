<?php

App::import('Lib', 'Permissionable.Permissionable');

/**
 * Generic Thing Model
 *
 * @package     permissionable
 * @subpackage  permissionable.tests.cases.behaviors
 * @uses        CakeTestModel
 * @author      Joshua McNeese <jmcneese@gmail.com>
 */
class Thing extends CakeTestModel {

	public $actsAs = array(
		'Permissionable.Permissionable' => array('defaultBits'=>95)  // 4000 => 95
	);

}

/**
 * Permissionable Test Case
 *
 * @package     permissionable
 * @subpackage  permissionable.tests.cases.behaviors
 * @see         PermissionableBehavior
 * @author      Joshua McNeese <jmcneese@gmail.com>
 */
final class PermissionableTestCase extends CakeTestCase {

	/**
	 * @var array
	 */
	public $fixtures = array(
		'plugin.permissionable.thing',
		'plugin.permissionable.permission'
	);

	/**
	 * @return void
	 */
	public function start() {

		parent::start();

		$this->Thing = ClassRegistry::init('Permissionable.Thing');
		
	}

	/**
	 * Test Instance Creation
	 *
	 * @return void
	 */
	public function testInstanceSetup() {

		$this->assertIsA($this->Thing, 'Model');
		$this->assertTrue($this->Thing->Behaviors->attached('Permissionable'));
		
	}

	/**
	 * Test Find
	 *
	 * @return void
	 */
	public function testFindNoPermissions() {

		Permissionable::setUserId(2);
		Permissionable::setGroupId(2);
		Permissionable::setGroupIds(array(2, 3, 4));

		$result1 = $this->Thing->find('all');
		$this->assertFalse($result1);

		$result2 = $this->Thing->find('count');
		$this->assertEqual($result2, 0);

		$result3 = $this->Thing->find('all', array(
			'permissionable' => false
		));
		$this->assertTrue($result3);
		$this->assertTrue(Set::matches('/Thing[name=Gadget]', $result3));
		
	}

	/**
	 * Test Save
	 *
	 * @return void
	 */
	public function testSave() {

		Permissionable::setUserId(2);
		Permissionable::setGroupId(2);
		Permissionable::setGroupIds(array(2, 3, 4));

		$data1 = array(
			array(
				'name'	=> 'Foo',
				'desc'	=> 'Foo is a Thing'
			),
			array(
				'name'	=> 'Bar',
				'desc'	=> 'Bar is a Thing'
			)
		);
		$result1 = $this->Thing->saveAll($data1);
	
		$this->assertTrue($result1);
		$result2 = $this->Thing->find('all');
		$this->assertEqual(count($result2), count($data1));
		$this->assertTrue(Set::matches('/Thing[name=Foo]', $result2));
		$this->assertTrue(Set::matches('/Thing[name=Bar]', $result2));

		$this->Thing->create();
		$result3 = $this->Thing->save(array(
			'Thing' => array(
				'name'	=> 'Baz',
				'desc'	=> 'Baz is a Thing'
			),
			'Permission' => array(
				'perms' => 79		// 3782  => 79
			)
		));
		$this->assertTrue($result3);

		$result4 = $this->Thing->read();
		$this->assertTrue(Set::matches('/ThingPermission[perms=79]', $result4));

		$result5 = $this->Thing->save(array(
			'Thing' => array(
				'name'	=> 'Baz',
				'desc'	=> 'Baz is a Thing!'
			),
			'Permission' => array(
				'perms' => 75 	// 3360 => 75
			)
		));
		$this->assertTrue($result5);

		$result6 = $this->Thing->read();
		$this->assertTrue(Set::matches('/ThingPermission[perms=75]', $result6));

		Permissionable::setUserId(null);

		$result7 = $this->Thing->save(array(
			'Thing' => array(
				'name'	=> 'Baz',
				'desc'	=> 'Baz is a Thing!'
			),
			'Permission' => array(
				'perms' => 75
			)
		));
		$this->assertFalse($result7);

		Permissionable::setUserId(2);
		Permissionable::setGroupId(null);
		Permissionable::setGroupIds(array());

		$result8 = $this->Thing->save(array(
			'Thing' => array(
				'name'	=> 'Baz',
				'desc'	=> 'Baz is a Thing!'
			),
			'Permission' => array(
				'perms' => 75
			)
		));
		$this->assertFalse($result8);

		$this->Thing->create();
		$result8 = $this->Thing->save($data1[0]);
		$this->assertFalse($result8);

		Permissionable::setUserId(2);
		Permissionable::setGroupId(2);
		Permissionable::setGroupIds(array(2, 3, 4));

		$result9 = $this->Thing->save(array(
			'Thing' => array(
				'name'  => 'Gadget',
				'desc'	=> 'A Gadget is a type of Thing!'
			),
			'Permission' => array(
				'id'	=> 2,
				'perms' => 79
			)
		));
		$this->assertTrue($result9);
		
	}

	/**
	 * Test Delete
	 *
	 * @return  void
	 */
	public function testDelete() {

		return;
		Permissionable::setUserId(2);
		Permissionable::setGroupId(2);
		Permissionable::setGroupIds(array(2, 3, 4));

		$data1 = array(
			'Thing' => array(
				'name'	=> 'Foo',
				'desc'	=> 'Foo is a Thing'
			),
			'Permission' => array(
				'perms' => 79
			)
		);

		$this->Thing->create();
		$this->Thing->save($data1);

		$result1 = $this->Thing->delete();
		$this->assertTrue($result1);

		$this->Thing->create();
		$this->Thing->save($data1);

		Permissionable::setUserId(3);

		$result2 = $this->Thing->delete();
		$this->assertFalse($result2);

		Permissionable::setGroupId(5);
		Permissionable::setGroupIds(array(6));

		$result3 = $this->Thing->delete();
		$this->assertFalse($result3);
		
	}

	/**
	 * Test Save
	 *
	 * @return  void
	 */
	public function testFindMixed() {

		Permissionable::setUserId(2);
		Permissionable::setGroupId(2);
		Permissionable::setGroupIds(array(2, 3, 4));

		$data1 = array(
			array(
				'name'	=> 'Foo',
				'desc'	=> 'Foo is a Thing'
			),
			array(
				'name'	=> 'Bar',
				'desc'	=> 'Bar is a Thing'
			)
		);
		$result1 = $this->Thing->saveAll($data1);
		$this->assertTrue($result1);

		Permissionable::setUserId(1);
		Permissionable::setGroupId(1);
		Permissionable::setGroupIds(array(1));

		$result2 = $this->Thing->find('all', array(
			'fields' => array(
				'Thing.*'
			)
		));
		$this->assertEqual(count($result2), 5);
		$this->assertTrue(Set::matches('/Thing[name=Foo]', $result2));
		$this->assertTrue(Set::matches('/Thing[name=Bar]', $result2));

		Permissionable::setUserId(null);
		Permissionable::setGroupId(null);
		Permissionable::setGroupIds(array());

		$result3 = $this->Thing->find('all');
		$this->assertFalse($result3);
		
	}

	/**
	 * Test hasPermission()
	 *
	 * @return  void
	 */
	public function testHasPermission() {

		Permissionable::setUserId(2);
		Permissionable::setGroupId(2);
		Permissionable::setGroupIds(array(2, 3, 4));

		$this->Thing->create();
		$this->Thing->save(array(
			'Thing' => array(
				'name'	=> 'Baz',
				'desc'	=> 'Baz is a Thing'
			),
			'Permission' => array(
				'perms' => 79
			)
		));

		$result1 = $this->Thing->hasPermission('read');
		$this->assertTrue($result1);

		Permissionable::setUserId(null);
		Permissionable::setGroupId(null);
		Permissionable::setGroupIds(array());

		$result2 = $this->Thing->hasPermission('read');
		$this->assertFalse($result2);

		Permissionable::setUserId(1);
		Permissionable::setGroupId(1);
		Permissionable::setGroupIds(array(1));

		$result3 = $this->Thing->hasPermission('read');
		$this->assertTrue($result3);
		
	}

	/**
	 * Test getPermission()
	 *
	 * @return  void
	 */
	public function testGetPermission() {

		Permissionable::setUserId(2);
		Permissionable::setGroupId(2);
		Permissionable::setGroupIds(array(2, 3, 4));

		$this->Thing->create();
		$this->Thing->save(array(
			'Thing' => array(
				'name'	=> 'Baz',
				'desc'	=> 'Baz is a Thing'
			),
			'Permission' => array(
				'perms' => 79
			)
		));

		$result1 = $this->Thing->getPermission();
		$this->assertTrue($result1);
		$this->assertEqual($result1['perms'], 79);

		$this->Thing->id = null;
		$result2 = $this->Thing->getPermission();
		$this->assertFalse($result2);
		
	}

	/**
	 * Test disablePermissionable()
	 *
	 * @return  void
	 */
	public function testDisablePermissionable() {

		$this->Thing->disablePermissionable();
		$this->assertTrue($this->Thing->isPermissionableDisabled());

		$this->Thing->disablePermissionable(false);
		$this->assertFalse($this->Thing->isPermissionableDisabled());
		
	}

	/**
	 * Test setting disablePermissionable before reading
	 *
	 * @return  void
	 */
	public function testReadWithPermissionableDisabled() {

		Permissionable::setUserId(2);
		Permissionable::setGroupId(2);
		Permissionable::setGroupIds(array(2, 3, 4));

		$this->Thing->create();
		$this->Thing->save(array(
			'Thing' => array(
				'name'	=> 'Baz',
				'desc'	=> 'Baz is a Thing'
			),
			'Permission' => array(
				'perms' => 79
			)
		));

		Permissionable::setUserId(null);
		Permissionable::setGroupId(null);
		Permissionable::setGroupIds(array());

		$result1 = $this->Thing->hasPermission('read');
		$this->assertFalse($result1);

		$this->Thing->disablePermissionable();

		$result2 = $this->Thing->hasPermission('read');
		$this->assertTrue($result2);
		
	}

	/**
	 * Test setting disablePermissionable before saving
	 *
	 * @return  void
	 */
	public function testSaveWithPermissionableDisabled() {

		Permissionable::setUserId(2);
		Permissionable::setGroupId(2);
		Permissionable::setGroupIds(array(2, 3, 4));

		$this->Thing->create();
		$result1 = $this->Thing->save(array(
			'Thing' => array(
				'name'	=> 'Baz',
				'desc'	=> 'Baz is a Thing'
			),
			'Permission' => array(
				'perms' => 79
			)
		));
		$this->assertTrue($result1);

		$this->Thing->disablePermissionable();

		$result2 = $this->Thing->read();
		$this->assertFalse(isset($result2['Permission']));
		
	}

	/**
	 * Test setting disablePermissionable before deleting
	 *
	 * @return  void
	 */
	public function testDeleteWithPermissionableDisabled() {

		Permissionable::setUserId(2);
		Permissionable::setGroupId(2);
		Permissionable::setGroupIds(array(2, 3, 4));

		$this->Thing->create();
		$this->Thing->save(array(
			'Thing' => array(
				'name'	=> 'Baz',
				'desc'	=> 'Baz is a Thing'
			),
			'Permission' => array(
				'perms' => 79
			)
		));

		Permissionable::setUserId(5);
		Permissionable::setGroupId(5);
		Permissionable::setGroupIds(array(5, 6));

		$this->Thing->disablePermissionable();
		$result1 = $this->Thing->delete();
		$this->assertTrue($result1);
		
	}

	/**
	 * Test default root user id and root group id
	 *
	 * @return  void
	 */
	public function testDefaultRootIds() {

		$this->assertEqual(Permissionable::getRootUserId(), 1);
		$this->assertEqual(Permissionable::getRootGroupId(), 1);
		
	}

	/**
	 * Test setting root user id and root group id
	 *
	 * @return  void
	 */
	public function testSetRootIds() {

		Permissionable::setRootUserId(2);
		Permissionable::setRootGroupId(2);

		$this->assertEqual(Permissionable::getRootUserId(), 2);
		$this->assertEqual(Permissionable::getRootGroupId(), 2);
		
	}

	/**
	 * Test isRoot
	 *
	 * @return  void
	 */
	public function testIsRootWithUuids() {

		Permissionable::setUserId('2bceb022-344e-11df-bcba-e984d7a9c8ef');
		Permissionable::setGroupId('441961bf-344e-11df-bcba-e984d7a9c8ef');
		Permissionable::setGroupIds(array('441961bf-344e-11df-bcba-e984d7a9c8ef', '4c421828-344e-11df-bcba-e984d7a9c8ef'));

		// User is Root user and in Root group
		Permissionable::setRootUserId('2bceb022-344e-11df-bcba-e984d7a9c8ef');
		Permissionable::setRootGroupId('441961bf-344e-11df-bcba-e984d7a9c8ef');

		$this->assertTrue(Permissionable::isRoot());

		// User is the Root user, but not in the Root group
		Permissionable::setRootGroupId('de129dca-344e-11df-bcba-e984d7a9c8ef');
		$this->assertTrue(Permissionable::isRoot());

		// User is not the Root user, but is in the Root group
		Permissionable::setRootUserId('b4fdc759-344f-11df-bcba-e984d7a9c8ef');
		Permissionable::setRootGroupId('441961bf-344e-11df-bcba-e984d7a9c8ef');

		$this->assertTrue(Permissionable::isRoot());

		// User is neither the Root user nor in the Root group
		Permissionable::setRootUserId('60741ba2-344f-11df-bcba-e984d7a9c8ef');
		Permissionable::setRootGroupId('f2d4a9b2-344f-11df-bcba-e984d7a9c8ef');

		$this->assertFalse(Permissionable::isRoot());
		
	}

}

?>