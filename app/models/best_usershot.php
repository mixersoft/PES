<?php
class BestUsershot extends AppModel {
	public $name = 'BestUsershot';
	public $table = 'best_usershots';
	
	public $belongsTo = array(	
		'Asset' => array(				// Asset hasMany BestUsershots
			'className' => 'Asset',
			'foreignKey' => 'asset_id',
			'counterCache' => false,			
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Usershot' => array(			// Usershot hasMany BestUsershots
			'className' => 'Usershot',
			'foreignKey' => 'usershot_id',
			'counterCache' => false,	
			'conditions' => '',
			'fields' => '',
			'order' => ''		
		),
	);	
}
?>