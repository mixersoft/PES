<?php
class SharedEdit extends AppModel {
	var $name = 'SharedEdit';
	var $primaryKey = 'asset_hash';
	var $displayField = 'score';
	//The Associations below have been created with all possible keys, those that are not needed can be removed

	var $hasMany = array(
		'Asset' => array(
			'className' => 'Asset',
			'foreignKey' => 'asset_hash',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		)
	);

}
?>