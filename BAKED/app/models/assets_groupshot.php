<?php
class AssetsGroupshot extends AppModel {
	public $name = 'AssetsGroupshot';
	public $table = 'assets_groupshots';
	public $displayField = 'id';
	//The Associations below have been created with all possible keys, those that are not needed can be removed

	public $belongsTo = array(
		'Asset' => array(
			'className' => 'Asset',			// Asset habtm Groupshot, Asset hasMany AssetsGroupshot
			'foreignKey' => 'asset_id',
			'counterCache' => false,		// use groups.assets_group_count counterCache instead(?)
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Groupshot' => array(				// Groupshot habtm Asset, Groupshot hasMany AssetsUsershot (implied)
			'className' => 'Groupshot',		
			'foreignKey' => 'groupshot_id',
			'counterCache' => true,			// add groupshots.assets_groupshot_count
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
}
?>