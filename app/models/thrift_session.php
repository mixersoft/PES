<?php
class ThriftSession extends AppModel {
	public $name = 'ThriftSession';
	// public $useDbConfig = 'default';
	public $displayField = 'thrift_device_id';

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
			$data['ThriftSession']['is_cancelled'] = 0;
		} 
		if (empty($data['ThriftSession']['id'])) $data['ThriftSession']['id'] = String::uuid();
		$ret = $this->save($data);
		if (!$ret) throw new Exception("Error saving ThriftSession to DB");
		
		$data = $this->read();
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
		return  $data;
	}	
	
	/**
	 * Check if the Session and Device are correctly bound 
	 * @param $session_id UUID for existing session
	 * @param $device_UUID UUID, provided by thrift client, TaskID->DeviceID
	 * @param $pa_id UUID, provider account id, 
	 * 		optional extra validation when reading taskId from Session
	 * @return mixed, if true, return array with [ThriftSession], [ThriftDevice]
	 */
	function checkDevice($session_id, $device_UUID, $pa_id = null) {
		$options = array(
			'contain'=>array('ThriftDevice'=>array(
				'conditions'=>array('ThriftDevice.device_UUID'=>$device_UUID)
			)),
			'conditions'=>array('ThriftSession.id'=>$session_id),
		);
		if ($pa_id) {
			$options['contain']['ThriftDevice']['conditions']['ThriftDevice.provider_account_id'] = $pa_id;
		}
// ThriftController::log("***   ThriftSession::checkDevice, options=".print_r($options,true), LOG_DEBUG);		
		$data = $this->find('first', $options);
		if (empty($data['ThriftSession'])) {
			throw new Exception("Error: checkDevice() cannot find session, session_id={$session_id}"); 
		} 		
		return !empty($data['ThriftDevice']['id']) ? $data : false;
	}
	
	/**
	 * Get the device_UUID for the current SessionId 
	 * @param $session_id UUID for existing session
	 * @return mixed, if true, return array with [ThriftSession], [ThriftDevice]
	 */
	function findDevice($session_id) {
		$options = array(
			'contain'=>array('ThriftDevice'),
			'conditions'=>array('ThriftSession.id'=>$session_id),
		);
		$data = $this->find('first', $options);
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
		$this->id = $session_id;
		$session = $this->read(null, $this->id);
		if (!$session) throw new Exception("Error: bindDeviceToSession() cannot find session");

		if ($device['ThriftDevice']['id'] != $session['ThriftSession']['thrift_device_id']) {
			$ret = $this->saveField('thrift_device_id', $device['ThriftDevice']['id']);
			$session = $this->read(null, $session_id);
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
		
		// these keys need to save to the DB
		$data['ThriftSession'] = $taskState;
		$data['ThriftSession']['id'] = $sessionId;
		// keys which need translation
		if (isset($taskState['IsCancelled'])) $data['ThriftSession']['is_cancelled'] = $taskState['IsCancelled'] ? 1 : 0;
		// batchId will be updated from modified is updated
		$updated = $this->save($data);
		return $updated;
	}
	
	/**
	 * @param $taskID object snaphappi_api_TaskID
	 * NOTE: to set CakePHP session, call ThriftController::_custom_thrift_session($taskID);	
	 */	
	function getTaskState($sessionId) {
		if (get_class($this) != 'ThriftSession') {
			$Model = ThriftController::$controller->ThriftSession;	
		} else $Model = $this;
		$data = $this->read(null, $sessionId);
		if (empty($data)) 
			throw new Exception("Error: Session not found, session_id={$sessionId}");
		$data['ThriftSession']['IsCancelled'] = $data['ThriftSession']['is_cancelled']; 
		$data['ThriftSession']['BatchId'] = $data['ThriftSession']['modified'];
		// TODO: check timezone for batchId
		return $data;
	}
	
}
?>