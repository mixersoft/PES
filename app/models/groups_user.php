<?php
class GroupsUser extends AppModel {
	var $name = 'GroupsUser';
	var $displayField = 'id';
	//The Associations below have been created with all possible keys, those that are not needed can be removed

	var $belongsTo = array(
		'User' => array(
			'className' => 'User',
			'foreignKey' => 'user_id',
			'counterCache' => true,
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Group' => array(
			'className' => 'Group',
			'foreignKey' => 'group_id',
			'counterCache' => true,		
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
}
?>