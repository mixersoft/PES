<?php
class ThriftFolder extends AppModel {
	public $name = 'ThriftFolder';
	// public $useDbConfig = 'default';
	public $displayField = 'native_path';
	
	public $belongsTo = array(
		'ThriftDevice' => array(
			'foreignKey' => 'thrift_device_id',
			'conditions' => '',
			'fields' => '',
			'counterCache' => true,
		)
	);	
	
	
	public function beforeSave() {
		if (isset($this->data[$this->alias]['native_path'])) {
			$this->data[$this->alias]['native_path_hash'] = crc32($this->data[$this->alias]['native_path']);
		}
		return true;
	}	
	
	/**
	 * @param $paid UUID, provider_account_id for name=native-uploader
	 */
	public function addFolder($thriftDeviceId, $nativePath, $options=array()) {
		$data = $this->create();
		$data['ThriftFolder']['thrift_device_id'] = $thriftDeviceId;
		$data['ThriftFolder']['native_path'] = $nativePath;
		if (!empty($options['is_scanned'])) $data['ThriftFolder']['is_scanned'] = $options['is_scanned'];
		if (!empty($options['is_watched'])) $data['ThriftFolder']['is_watched'] = $options['is_watched'];
		$ret = $this->save($data);
		return  $ret ? $this->read(null, $this->id) : false;
	}	
	
	
}
?>