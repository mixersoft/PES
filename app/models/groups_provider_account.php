<?php
class GroupsProviderAccount extends AppModel {
	var $name = 'GroupsProviderAccount';
	var $displayField = 'id';
	//The Associations below have been created with all possible keys, those that are not needed can be removed

	var $belongsTo = array(
		'ProviderAccount' => array(
			'className' => 'ProviderAccount',
			'foreignKey' => 'provider_account_id',
			//'counterCache' => true,
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Group' => array(
			'className' => 'Group',
			'foreignKey' => 'group_id',
			//'counterCache' => true,
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
}
?>