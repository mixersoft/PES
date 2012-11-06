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
			'order' => array('is_scanned','native_path'),
			'counterCache' => true,
		)
	);	
	
	
	public function beforeSave() {
		if (isset($this->data[$this->alias]['native_path'])) {
			$this->data[$this->alias]['native_path_hash'] = crc32($this->data[$this->alias]['native_path']);
		}
		return true;
	}	
	
}
?>