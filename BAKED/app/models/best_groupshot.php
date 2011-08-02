<?php
class BestGroupshot extends AppModel {
	public $name = 'BestGroupshot';
	public $table = 'best_groupshots';
	
	public $belongsTo = array(	
		'Asset' => array(				// Asset hasMany BestGroupShots
			'className' => 'Asset',
			'foreignKey' => 'asset_id',
			'counterCache' => false,			
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Groupshot' => array(			// Groupshot hasMany BestGroupShots
			'className' => 'Groupshot',
			'foreignKey' => 'groupshot_id',
			'counterCache' => false,	
			'conditions' => '',
			'fields' => '',
			'order' => ''		
		),
	);	
}
?>