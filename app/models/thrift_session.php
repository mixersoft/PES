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
			'order' => array('is_cancelled', 'modified DESC')
		)
	);		
}
?>