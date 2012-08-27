<?php
class Task extends AppModel {
	public $name = 'Task';
	public $useDbConfig = 'workorders';
	
	public $hasMany = array(
		'TasksWorkorder' => array(								// Tasks habtm Workorder
			'className' => 'TasksWorkorder',				
			'foreignKey' => 'workorder_id',
			'dependent' => true,
		)
	);
	
	public $hasAndBelongsToMany = array(
		'Workorder' => array(								// Tasks habtm Workorder
			'with' => 'TasksWorkorder',				
		)
	);
	
	public function createNew ($options){
		$test_default = array(
			'name'=>'Rate Photos',
		);
		$options = array_merge($test_default, $options);
		$task = $this->create();
		$task['id'] = null;			// cakephp automagic
		$ret = $this->save($task);
		return ($ret) ? $this->read() : false;
	}
}
?>