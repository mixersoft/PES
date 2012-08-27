<?php
class CollectionsGroup extends AppModel {
	var $name = 'CollectionsGroup';
	var $table = 'collections_groups';
	var $displayField = 'id';
	//The Associations below have been created with all possible keys, those that are not needed can be removed

	var $belongsTo = array(
		'Collection' => array(
			'className' => 'Collection',
			'foreignKey' => 'collection_id',
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