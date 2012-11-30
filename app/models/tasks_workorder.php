<?php
class TasksWorkorder extends AppModel {
	public $name = 'TasksWorkorder';
	public $useDbConfig = 'workorders';
	
	public $belongsTo = array(
		'Task' => array(								// User hasMany Usershots
			'className' => 'Task',
			'foreignKey' => 'task_id',
			'conditions' => '',
			'fields' => '',
			// 'order' => '`TasksWorkorder`.task_sort, `TasksWorkorder`.created'
		),	
		'Workorder' => array(								// User hasMany Usershots
			'className' => 'Workorder',
			'foreignKey' => 'workorder_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),	
		'Operator' => array(
			'className' => 'WorkorderEditor', 
			'foreignKey' => 'operator_id'
		),
	);
	public $hasMany = array(
		'AssetsTask' => array(								// TasksWorkorder habtm Assets
			'className' => 'AssetsTask',				
			'foreignKey' => 'tasks_workorder_id',
			'dependent' => true,
			'counterCache' => true,	
		),
		'ActivityLog' => array(								
			'className' => 'ActivityLog',				
			'foreignKey' => 'tasks_workorder_id',
			'dependent' => true,
		), 		
	);
	
	public $hasAndBelongsToMany = array(
		'Asset' => array(								// Tasks habtm Workorder
			'with' => 'AssetsTask',				
		),
	);	
	
	public function createNew ($options){
		$taskWorkorder = $this->create($options);
		$taskWorkorder['TasksWorkorder']['id'] = null;			// cakephp automagic
		$ret = $this->save($taskWorkorder);
		return ($ret) ? $this->read() : false;
	}
	

	public function resetStatus($twoid) {
		$status = $this->read('status', $twoid); 
		if ($status == 'done') $this->saveField('status', 'ready');
		// TODO: add entry to status log?
	}
	
	public function getAssets($task_woid) {
		$options = array(
			'contain' => 'AssetsTask.asset_id',
			'conditions' => array(
				'`TasksWorkorder`.id'=>$task_woid,
				'`TasksWorkorder`.operator_id' => AppController::$userid,
			),
		);
		$data = $this->find('all', $options);
		return Set::extract('/AssetsTask/asset_id', $data);
		
	}

	function updateAllCounts() {
		$SQL = "
UPDATE snappi_wms.`tasks_workorders` as TWorkorder
LEFT JOIN (
	SELECT w.id AS workorder_id, COUNT(DISTINCT at.asset_id) AS `assets_task_count`
	FROM snappi_wms.`tasks_workorders` w
	LEFT JOIN snappi_wms.assets_tasks at ON w.id = at.tasks_workorder_id
	GROUP BY w.id
) AS t ON (`TWorkorder`.id = t.workorder_id)
SET TWorkorder.assets_task_count = t.assets_task_count;
";
		$result = $this->query($SQL);
		return true;
	}
	
}
?>