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
			'order' => 'label'
		)
	);

	public $hasMany = array(
		'ThriftSession' => array(
			'className' => 'ThriftSession',
			'foreignKey' => 'thrift_device_id',
			'dependent' => true,
		),
		'ThriftFolder' => array(
			'className' => 'ThriftFolder',
			'foreignKey' => 'thrift_device_id',
			'dependent' => true,
		),
	);	
	
}
?>