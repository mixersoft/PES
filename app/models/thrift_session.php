<?php
class ThriftSession extends AppModel {
	public $name = 'ThriftSession';
	// public $useDbConfig = 'default';
	public $displayField = 'thrift_device_id';
	public static $Session = null;
	
	public $belongsTo = array(
		'ThriftDevice' => array(
			'foreignKey' => 'thrift_device_id',
			'conditions' => '',
			'fields' => '',
			'order' => array('ThriftDevice.label')
		)
	);	
	public static $ALLOWED_UPDATE_KEYS = array(
		'FolderUpdateCount', 'FileUpdateCount', 
		'IsCancelled', 
		'DuplicateFileException', 'OtherException', 
		'BatchId',	// test BatchId, should have 1 hr memory
		// NEW keys, add related code
		'AuthException', // send notification to renew authToken
	);
	
	public static $DEFAULT_TASK_STATE = ARRAY(
		'IsCancelled'=>0,
		'FolderUpdateCount'=>0, 
		'FileUpdateCount'=>0, 
		'DuplicateFileException'=>0, 
		'OtherException'=>0, 
		'AuthException'=>0,
	);
	/**
	 * @param options array, 
	 *  options[session_id]: 'sw' scheduled actions will send session_id, with unbound thrift_device_id
	 * 		call checkDevice to bind $device_UUID
	 */
	public function newSession($options = array()) {
		$data = $this->create();
		if (!empty($options['session_id'])) {
			$data['ThriftSession']['id'] = $options['session_id'];
			$this->id = $data['ThriftSession']['id'];
			// for testing from /my/uploader, should not affect SW attach
			$data['ThriftSession']['active'] = 1;
		} 
		if (empty($data['ThriftSession']['id'])) $data['ThriftSession']['id'] = String::uuid();
		$ret = $this->save($data);
		if (!$ret) throw new Exception("Error saving ThriftSession to DB");
		
		$data = $this->read();
		
		// TODO: remove these fields from db schema
		$deprecate = array_flip(array(
			'is_cancelled','FolderUpdateCount','FileUpdateCount'
			,'DuplicateFileException','OtherException')); 
		$data['ThriftSession'] = array_diff_key($data['ThriftSession'], $deprecate);
		
		$session_defaults = array(
			'FolderUpdateCount'=>0, 
			'FileUpdateCount'=>0, 
			'IsCancelled'=>0,
			'DuplicateFileException'=>0, 
			'OtherException'=>0, 
			'AuthException'=>0,
			'BatchId' => $data['ThriftSession']['modified'],
		);
		$data['ThriftSession'] = array_merge($data['ThriftSession'], $session_defaults);
		Session::write('ThriftSession', $data['ThriftSession']);
		return  $data;
	}	
	
	/**
	 * Check if the Session and Device are correctly bound 
	 * saves Session and Device to cakePHP Thrift session
	 * NOTE: must call checkDevice BEFORE getTaskState/setTaskState
	 * @param $session_id UUID for existing session
	 * @param $device_UUID UUID, provided by thrift client, TaskID->DeviceID
	 * @param $pa_id UUID, provider account id, 
	 * 		optional extra validation when reading taskId from Session
	 * @return mixed, if true, return array with [ThriftSession], [ThriftDevice]
	 */
	function checkDevice($session_id, $device_UUID, $pa_id = null) {
		$session['ThriftSession'] = Session::read('ThriftSession');
		$session['ThriftDevice'] = Session::read('ThriftDevice');
		if (
			isset($session['ThriftSession']['id'])
			&& $session['ThriftSession']['id'] == $session_id
			&& isset($session['ThriftDevice']['device_UUID'])
			&& $session['ThriftDevice']['device_UUID'] == $device_UUID
		) {
			if ($pa_id && $session['ThriftDevice']['provider_account_id'] == $pa_id) {
				return $session;
			} else if ($pa_id === null) return $session;
		}
		
		// doesn't match CakePHP Session, check DB	
		$options = array(
			'contain'=>array('ThriftDevice'=>array(
				'conditions'=>array('ThriftDevice.device_UUID'=>$device_UUID),
				'fields'=>array('id', 'provider_account_id', 'device_UUID'),
			)),
			'conditions'=>array('ThriftSession.id'=>$session_id),
		);
		if ($pa_id) {
			$options['contain']['ThriftDevice']['conditions']['ThriftDevice.provider_account_id'] = $pa_id;
		}
// ThriftController::log("***   ThriftSession::checkDevice, options=".print_r($options,true), LOG_DEBUG);		
		$data = $this->find('first', $options);
// TODO: remove these fields from db schema
		$deprecate = array_flip(array(
			'is_cancelled','FolderUpdateCount','FileUpdateCount'
			,'DuplicateFileException','OtherException'
			)); 
		$data['ThriftSession'] = array_diff_key($data['ThriftSession'], $deprecate);
				
		if (empty($data['ThriftSession'])) {
			throw new Exception("Error: checkDevice() cannot find session, session_id={$session_id}"); 
		} 		
		// merge with session data
		if (empty($data['ThriftDevice']['id'])) return false;
		Session::write('ThriftSession', $data['ThriftSession']);
		Session::write('ThriftDevice', $data['ThriftDevice']);
		return $data;
	}
	
	/**
	 * Get the device_UUID for the current SessionId 
	 * @param $session_id UUID for existing session
	 * @return mixed, if true, return array with [ThriftDevice], [ThriftSession], db fields only
	 */
	function findDevice($session_id) {
		$options = array(
			'contain'=>array('ThriftDevice'),
			'conditions'=>array('ThriftSession.id'=>$session_id),
		);
		$data = $this->find('first', $options);
// TODO: remove these fields from db schema
		$deprecate = array_flip(array(
			'is_cancelled','FolderUpdateCount','FileUpdateCount'
			,'DuplicateFileException','OtherException')); 
		$data['ThriftSession'] = array_diff_key($data['ThriftSession'], $deprecate);		
		return $data;
	}	
	
	/**
	 * bind a device to a session AFTER the native-uploader is launched
	 * if device is not found, create new Device
	 * if device is already bound then do nothing
	 * @param $session_id UUID for existing session
	 * @param $authToken string, provider_account_id for name=native-uploader
	 * @param $device_UUID UUID, provided by thrift client, TaskID->DeviceID
	 * @return array with [ThriftSession], [ThriftDevice]
	 */
	function bindDeviceToSession($session_id, $authToken, $device_UUID, $providerName='native-uploader') {
		$device = $this->ThriftDevice->findDeviceByDeviceId($authToken, $device_UUID);
		if (empty($device)) {
			$device = $this->ThriftDevice->newDeviceForAuthToken($authToken, $device_UUID);
		} 
		$session['ThriftSession'] = Session::read('ThriftSession');
		if (!$session['ThriftSession']) {
			$session = $this->read(null, $session_id);
// TODO: remove these fields from db schema
			$deprecate = array_flip(array(
				'is_cancelled','FolderUpdateCount','FileUpdateCount'
				,'DuplicateFileException','OtherException')); 
			$session['ThriftSession'] = array_diff_key($session['ThriftSession'], $deprecate);			
			Session::write('ThriftSession', $session['ThriftSession']);
		}
		if (!$session) throw new Exception("Error: bindDeviceToSession() cannot find session");

		if ($device['ThriftDevice']['id'] != $session['ThriftSession']['thrift_device_id']) {
			$this->id = $session_id;
			$ret = $this->saveField('thrift_device_id', $device['ThriftDevice']['id']);
			if (!$ret) throw new Exception("Error saving device_id to ThriftSession in DB");
			$session['ThriftSession']['thrift_device_id'] = $device['ThriftDevice']['id'];
			Session::write('ThriftSession.thrift_device_id', $device['ThriftDevice']['id']);
			$device['ThriftDevice'] = array_intersect_key($device['ThriftDevice'], array_flip(array('id','provider_account_id','device_UUID')));
			Session::write('ThriftDevice',$device['ThriftDevice']);
		}		
		return array_merge($device, $session);
	}
	
	
	/**
	 * @param $sessionId UUID
	 * @param $taskState array
	 * expecting: 
	 * Array
		(
		    [IsCancelled] => 0
		    [FolderUpdateCount] => 0
		    [FileUpdateCount] => 1
		    [BatchId] => 1352298912 // should be time?
		    [DuplicateFileException] => 0
		    [OtherException] => 0
		)
	 */
	function saveTaskState($sessionId, $taskState) {
		$taskState = array_filter_keys($taskState, ThriftSession::$ALLOWED_UPDATE_KEYS);
		$update_keys = $taskState;
		if (empty($update_keys)) {
			return false;
		}
		
		$session['ThriftSession'] = Session::read('ThriftSession');
		if ($session['ThriftSession']['id'] !== $sessionId ) {
			throw new Exception("Error: ThriftSession::saveTaskState, session_id not found, session_id={$sessionId}");
		} else {
			// just read/write to cakephp session
			$updated['ThriftSession'] = array_merge(ThriftSession::$DEFAULT_TASK_STATE, $session['ThriftSession'], $taskState);
			$updated['ThriftSession']['BatchId'] = $session['ThriftSession']['modified'];
			Session::write('ThriftSession', $updated['ThriftSession'] );
			return $updated; 
		}
	}
	
	/**
	 * @param $taskID object snaphappi_api_TaskID
	 * NOTE: to set CakePHP session, call ThriftController::_custom_thrift_session($taskID);	
	 */	
	function getTaskState($sessionId) {
		$session['ThriftSession'] = Session::read('ThriftSession');
		if ($session['ThriftSession']['id'] !== $sessionId ) {
			throw new Exception("Error: ThriftSession::saveTaskState, session_id not found, session_id={$sessionId}");
		} else {
			// just read/write to cakephp session
			$session['ThriftSession'] = array_merge(ThriftSession::$DEFAULT_TASK_STATE, $session['ThriftSession']);
			if (!isset($session['ThriftSession']['BatchId'])) $session['ThriftSession']['BatchId'] = $session['ThriftSession']['modified'];
			return $session; 
		}
	}
	
}
?>