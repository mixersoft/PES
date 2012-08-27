<?php
class AssetsUsershot extends AppModel {
	public $name = 'AssetsUsershot';
	public $table = 'assets_usershots';
	public $displayField = 'id';
	//The Associations below have been created with all possible keys, those that are not needed can be removed

	public $belongsTo = array(
		'Asset' => array(
			'className' => 'Asset',			// Asset hasOne AssetsUsershot, 
			'foreignKey' => 'asset_id',
			'counterCache' => false,		// hasOne 
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Usershot' => array(				// Usershot hasMany AssetsUsershot
			'className' => 'Usershot',
			'foreignKey' => 'usershot_id',
			'counterCache' => true,			// add usershots.assets_usershot_count
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
}
?>