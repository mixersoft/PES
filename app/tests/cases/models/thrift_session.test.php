<?php
/* ThriftSession Test cases generated on: 2012-11-14 20:48:40 : 1352926120*/
App::import('Model', 'ThriftSession');

class ThriftSessionTestCase extends CakeTestCase {
	var $fixtures = array('app.thrift_session', 'app.thrift_device', 'app.provider_account', 'app.user', 
		'app.group', 'app.groupshot', 'app.best_groupshot', 
		'app.asset', 'app.shared_edit', 'app.user_edit', 'app.best_usershot', 'app.usershot', 'app.assets_usershot', 
		'app.collection', 'app.assets_collection', 'app.collections_group', 
		'plugin.tags.tag', 'plugin.tags.tagged', 'plugin.metadata.metadatum',	
		'app.assets_group', 'app.assets_groupshot', 'app.groups_user', 'app.groups_provider_account', 
		'app.profile', 'app.auth_account', 'app.thrift_folder'
	);

	function setup_AppController (){
		App::import('Controller', 'app');
		$ac = new AppController();
		AppController::$userid = '5013ddf3-069c-41f0-b71e-20160afc480d'; // should be manager
	}

	function startTest() {
		$this->setup_AppController();
		$this->ThriftSession =& ClassRegistry::init('ThriftSession');
	}

	function endTest() {
		unset($this->ThriftSession);
		unset($ac);
		ClassRegistry::flush();
	}

	function testNewSession() {
		// create new session, device_id should be null/unbound
		$result = $this->ThriftSession->newSession(); 
		unset($result['ThriftSession']['id']);
		unset($result['ThriftSession']['created']);
		unset($result['ThriftSession']['modified']);
		$expected = Array
			(
			    'ThriftSession' => Array
			        (
			            'thrift_device_id' => '',
			            'DuplicateFileException' => '0',
			            'OtherException' => '0',
			            'is_cancelled' => '0',
			            'active' => '1',
			        )
			
			);	
		$this->assertEqual($result, $expected);		
		
		// attach to existing session/device_id
		$DEVICE[1] = array(
			'device_id'=>1,
			'device_UUID'=>'2738ebe4-95a1-4d4a-aefe-761d97881535', 
			'session_id'=>'50a3fb31-7514-4db3-b730-1644f67883f5'
		);
		$DEVICE[2] = array(
			'device_id'=>2,
			'device_UUID'=>'2738ebe4-XXXX-4d4a-aefe-761d97881535', 
			'session_id'=>'509d820e-b990-4822-bb9c-11d0f67883f5'
		);
		$thrift_device_id = 1;
		$result = $this->ThriftSession->newSession($DEVICE[$thrift_device_id]);
		unset($result['ThriftSession']['modified']);
		$expected = Array
			(
			    'ThriftSession' => Array
			        (
			            'id' => '50a3fb31-7514-4db3-b730-1644f67883f5',
			            'thrift_device_id' => '1',
			            'DuplicateFileException' => '0',
			            'OtherException' => '0',
			            'is_cancelled' => '0',
			            'active' => '1',
			            'created' => '2012-11-14 20:12:33',
			        )
			
			);	
		$this->assertEqual($result, $expected);
	}

	function testCheckDevice() {

	}

	function testFindDevice() {

	}

	function testBindDeviceToSession() {

	}

	function testSaveTaskState() {

	}

}
