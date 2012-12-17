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
	
	function setup_Controller (){
		App::import('Controller', 'Thrift');
		$tc = new ThriftController();
		loadComponent('Session', $tc);
		AppController::$userid = '5013ddf3-069c-41f0-b71e-20160afc480d'; // should be manager
	}
	
	function get_TaskID($id){
		$DEVICE[1] = array(
			'auth_token'=>'b34f54557023cce43ab7213e0eb7da2a6b9d6b27',
			'device_id'=>1,
			'provider_account_id'=>'50996b75-425c-4261-a0ee-14c8f67883f5',
			'device_UUID'=>'2738ebe4-95a1-4d4a-aefe-761d97881535', 
			'session_id'=>'50a3fb31-7514-4db3-b730-1644f67883f5',
		);
		$DEVICE[2] = array(
			'auth_token'=>'b34f54557023cce43ab7213e0eb7da2a6b9d6b27',
			'device_id'=>2,
			'provider_account_id'=>'50996b75-425c-4261-a0ee-14c8f67883f5',
			'device_UUID'=>'2738ebe4-XXXX-4d4a-aefe-761d97881535', 
			'session_id'=>'509d820e-b990-4822-bb9c-11d0f67883f5'
		);
		$DEVICE[3] = array(
			'auth_token'=>'08e89e9bba58544fe3a0dcab8ac102d158ecd42f',
			'device_id'=>3,		// alexey
			'provider_account_id'=>'50a680d5-d460-4971-90fc-7f180afc6d44',
			'device_UUID'=>'b6673a7f-c151-4eff-91f3-9c45a61d6f36', 
			'session_id'=>'50a4fd3b-034c-48c7-9f87-1644f67883f5'
		);
		return $DEVICE[$id];
	}

	function startTest() {
		$this->setup_Controller();
		$this->ThriftSession =& ClassRegistry::init('ThriftSession');
	}

	function endTest() {
		unset($this->ThriftSession);
		unset($tc);
		ClassRegistry::flush();
	}

	function testNewSession() {
		// create new session, device_id should be null/unbound
		$result = $this->ThriftSession->newSession();
		$skip = array_flip(array('is_cancelled','created','modified', 'BatchId')); 
		$result['ThriftSession'] = array_diff_key($result['ThriftSession'], $skip);
		$expected = Array
			(
			    'ThriftSession' => Array
			        (
			        	'id' => $result['ThriftSession']['id'],
			            'thrift_device_id' => '',
			            'DuplicateFileException' => '0',
			            'OtherException' => '0',
			            'AuthException' => '0',
			            'FolderUpdateCount' => '0',
			            'FileUpdateCount' => '0',
			            'IsCancelled' => '0',
			            'active' => '1',
			        )
			
			);	
		$this->assertEqual($result, $expected);	
		// read from Cakephp Session
		$fromSession['ThriftSession'] = Session::read('ThriftSession');
		$fromSession['ThriftSession'] = array_diff_key($fromSession['ThriftSession'], $skip);
		$this->assertEqual($fromSession, $expected);	
		
		// create new session, fixed session_id, called by SW task, GetWatchedFolder
		$task = $this->get_TaskID(1);
		$options['session_id'] = $task['session_id'];
		$result = $this->ThriftSession->newSession($options);
		$this->assertEqual($result['ThriftSession']['id'], $task['session_id']);	
	}
	
	function testCheckDevice() {
		$task = $this->get_TaskID(1);
		$task3 = $this->get_TaskID(3);
		// correct call
		$result = $this->ThriftSession->checkDevice($task['session_id'], $task['device_UUID'], $task['provider_account_id']);
		$this->assertEqual($result['ThriftSession']['id'], $task['session_id']);
		$this->assertEqual($result['ThriftDevice']['id'], $task['device_id']);
		
		// check again, but get from Cakephp Session
		$this->assertEqual(Session::read('ThriftSession.id'), $task['session_id']);
		$this->assertEqual(Session::read('ThriftDevice.id'), $task['device_id']);
		
		// WRONG pa_id
		$result = $this->ThriftSession->checkDevice($task['session_id'], $task['device_UUID'], 'WRONG');
		$this->assertEqual($result, false);	
		// WRONG device_UUID
		$result = $this->ThriftSession->checkDevice($task['session_id'], 'WRONG');
		$this->assertEqual($result, false);
	}

	function testFindDevice() {
		$task = $this->get_TaskID(1);
		$result = $this->ThriftSession->findDevice($task['session_id']);
		$this->assertEqual($result['ThriftDevice']['id'], $task['device_id']);
	}

	function testBindDeviceToSession() {
		// Session::write('ThriftSession', null);
		$task = $this->get_TaskID(1);
		// device already bound, do nothing
		$result = $this->ThriftSession->bindDeviceToSession($task['session_id'], $task['auth_token'], $task['device_UUID']);
		$this->assertEqual($result['ThriftSession']['id'], $task['session_id']);
		$this->assertEqual($result['ThriftDevice']['id'], $task['device_id']);
		$this->assertEqual($result['ProviderAccount']['auth_token'], $task['auth_token']);
		// check again, but get from Cakephp Session
		$this->assertEqual(Session::read('ThriftSession.id'), $task['session_id']);
		$this->assertEqual(Session::read('ThriftDevice.device_UUID'), $task['device_UUID']);
		
		
		// new Session with no Device, bind Device to Session
		$session = $this->ThriftSession->newSession();
		$result = $this->ThriftSession->bindDeviceToSession($session['ThriftSession']['id'], $task['auth_token'], $task['device_UUID']);
		$this->assertEqual($result['ThriftSession']['id'], $session['ThriftSession']['id']);
		$this->assertEqual($result['ThriftDevice']['id'], $task['device_id']);
		$this->assertEqual($result['ProviderAccount']['auth_token'], $task['auth_token']);
		
	}
	
	function testGetTaskState() {
		$task = $this->get_TaskID(1);
		$this->ThriftSession->checkDevice($task['session_id'], $task['device_UUID'], $task['provider_account_id']);
		$result = $this->ThriftSession->getTaskState($task['session_id']);
		$this->assertEqual($result['ThriftSession']['id'], $task['session_id']);
		$this->assertEqual($result['ThriftSession']['BatchId'], $result['ThriftSession']['modified']);
	}
	
	function testSaveTaskState() {
		$task = $this->get_TaskID(1);
		$this->ThriftSession->checkDevice($task['session_id'], $task['device_UUID'], $task['provider_account_id']);
		// cake session set to $task
		
		$newState['bad_key'] = 'not allowed';
		$result = $this->ThriftSession->saveTaskState($task['session_id'], $newState);
		$this->assertEqual($result, false);
		
		$newState['IsCancelled'] = 1;
		$newState['FolderUpdateCount'] = 99;
		$result = $this->ThriftSession->saveTaskState($task['session_id'], $newState);
		$this->assertEqual($result['ThriftSession']['FolderUpdateCount'], $newState['FolderUpdateCount']);	
		$this->assertEqual($result['ThriftSession']['IsCancelled'], $newState['IsCancelled']);
		$this->assertEqual(Session::read('ThriftSession.FolderUpdateCount'), $newState['FolderUpdateCount']);
		$this->assertEqual(Session::read('ThriftSession.IsCancelled'), $newState['IsCancelled']);
		$saved = $this->ThriftSession->getTaskState($task['session_id']);
		$this->assertEqual($saved['ThriftSession']['id'], $result['ThriftSession']['id']);
		$this->assertEqual($saved['ThriftSession']['FolderUpdateCount'], $result['ThriftSession']['FolderUpdateCount']);
		$this->assertEqual($saved['ThriftSession']['BatchId'], $result['ThriftSession']['modified']);
	}
	
	
	

}
