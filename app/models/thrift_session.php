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
	
	/**
	 * @param options array, 
	 * 	options[device_UUID]: usually we will bind device_UUID on nativeUploader launch, 
	 *  options[session_id]: 'sw' scheduled actions will send session_id 
	 */
	public function newSession($options = array()) {
		$data = $this->create();
		if (!empty($options['device_UUID'])) $data['ThriftSession']['thrift_device_id'] = $options['device_UUID'];
		if (!empty($options['session_id'])) {
			$data['ThriftSession']['id'] = $options['session_id'];
			$this->id = $data['ThriftSession']['id'];
		} 
		if (empty($data['ThriftSession']['id'])) $data['ThriftSession']['id'] = String::uuid();
		$ret = $this->save($data);
		return  $ret ? $this->read() : false;
	}	
	
	/**
	 * Check if the Session and Device are correctly bound 
	 * @param $session_id UUID for existing session
	 * @param $device_UUID UUID, provided by thrift client, TaskID->DeviceID
	 * @return mixed, if true, return array with [ThriftSession], [ThriftDevice]
	 */
	function checkDevice($session_id, $device_UUID) {
		$options = array(
			'contain'=>array('ThriftDevice'=>array(
				'conditions'=>array('ThriftDevice.device_UUID'=>$device_UUID)
			)),
			'conditions'=>array('ThriftSession.id'=>$session_id),
		);
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
		    [BatchId] => 1352298912
		    [DuplicateFileException] => 0
		    [OtherException] => 0
		)
	 */
	function saveTaskState($sessionId, $taskState) {
		$data = array();
		$data['ThriftSession']['id'] = $sessionId;
		$data['ThriftSession']['is_cancelled'] = $taskState['IsCancelled'] ? 1 : 0;
		$data['ThriftSession']['DuplicateFileException'] = $taskState['DuplicateFileException'];
		$data['ThriftSession']['OtherException'] = $taskState['OtherException'];
		// batchId will be updated from modified is updated
		$updated = $this->save($data);
		return $updated;
	}
}
?>