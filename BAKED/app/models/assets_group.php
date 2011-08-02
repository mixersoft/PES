<?php
class AssetsGroup extends AppModel {
	var $name = 'AssetsGroup';
	var $table = 'assets_groups';
	var $displayField = 'id';
	//The Associations below have been created with all possible keys, those that are not needed can be removed

	var $belongsTo = array(
		'Asset' => array(
			'className' => 'Asset',
			'foreignKey' => 'asset_id',
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