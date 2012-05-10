<?php

class SecurityLevel extends AppModel {

	public $hasMany = array('ShareLink' => array('foreignKey' => 'security_level'));

	public $brwConfig = array(
		'actions' => array('add' => false, 'edit' => false, 'delete' => false),
		'parent' => false,
	);

}