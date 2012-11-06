<?php
class ThriftDevice extends AppModel {
	public $name = 'ThriftDevice';
	// public $useDbConfig = 'default';
	public $displayField = 'label';
	
	public $belongsTo = array(
		'ProviderAccount' => array(
			'foreignKey' => 'provider_account_id',
			'conditions' => '',
			'fields' => '',
		)
	);
// array('ThriftSession.is_cancelled', 'ThriftSession.modified DESC')
	public $hasMany = array(
		'ThriftSession' => array(
			'className' => 'ThriftSession',
			'foreignKey' => 'thrift_device_id',
			'dependent' => true,
		),
		'ThriftFolder' => array(
			'className' => 'ThriftFolder',
			'foreignKey' => 'thrift_device_id',
			'order' => array('ThriftFolder.is_scanned','ThriftFolder.native_path'),
			'dependent' => true,
		),
	);	

	/**
	 * @param $paid UUID, provider_account_id for name=native-uploader
	 */
	public function newDevice($paid, $device_UUID) {
		$data = $this->create();
		$data['ThriftDevice']['provider_account_id'] = $paid;
		$data['ThriftDevice']['device_UUID'] = $device_UUID;
		$ret = $this->save($data);
		return  $ret ? $this->read(null, $this->id) : false;
	}
	
	public function newDeviceForAuthToken($authToken, $device_UUID, $providerName='native-uploader'){
		$options = array(
			'conditions'=>array(
				'`ProviderAccount`.user_id'=>AppController::$userid,
				'`ProviderAccount`.auth_token'=>$authToken,
				'`ProviderAccount`.provider_name'=>$providerName,
				)
			);
		$paData = $this->ProviderAccount->find('first', $options);
		return $paData ? $this->newDevice($paData['ProviderAccount']['id'], $device_UUID) : false;
	}	
	
	/**
	 * find all thrift devices for a given ProviderAcount.auth_token 
	 * @param $authToken string, 
	 * @param $providerName string (optional), default 'native-uploader'
	 */
	public function findAllByAuthToken($authToken, $providerName='native-uploader') {
		$device_options = array(
			'contain'=>array('ProviderAccount'=>array(
				'conditions'=>array(
					'`ProviderAccount`.user_id'=>AppController::$userid,
					'`ProviderAccount`.auth_token'=>$authToken,
					'`ProviderAccount`.provider_name'=>$providerName,
					)
			)),
		);
		$device = $this->find('all', $device_options);
		if (!empty($device['ThriftDevice']) && empty($device['ProviderAccount'])) 
			throw new Exception('ThriftDevice::findAllByAuthToken() ERROR: current user does not own authToken');
		return  $device;
	}	
	
	/**
	 * find thrift devices by TaskID->DeviceID, also check ProviderAcount.auth_token 
	 * @param $authToken string, provider_account_id for name=native-uploader
	 * @param $device_UUID UUID, provided by thrift client, TaskID->DeviceID
	 * @param $providerName string (optional), default 'native-uploader' 
	 */
	public function findDeviceByDeviceId($authToken, $device_UUID, $providerName='native-uploader') {
		$device_options = array(
			'contain'=>array('ProviderAccount'=>array(
				'conditions'=>array(
					'`ProviderAccount`.user_id'=>AppController::$userid,
					'`ProviderAccount`.auth_token'=>$authToken,
					'`ProviderAccount`.provider_name'=>$providerName,
				)
			))
			,
		);
		$device = $this->find('first', $device_options);
		if (!empty($device['ThriftDevice']) && empty($device['ProviderAccount'])) 
			throw new Exception('ThriftDevice::findDeviceByDeviceId() ERROR: current user does not own authToken');
		return  $device;
	}


	
}
?>