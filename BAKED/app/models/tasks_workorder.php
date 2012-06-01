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
			'order' => 'task_sort, created'
		),	
		'Workorder' => array(								// User hasMany Usershots
			'className' => 'Workorder',
			'foreignKey' => 'workorder_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),	
	);
	public $hasMany = array(
		'AssetsTask' => array(								// TasksWorkorder habtm Assets
			'className' => 'AssetsTask',				
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
	
	/**
	 * add assets to an existing workorder task, i.e. harvest new photos
	 * @params $data array, from taskWorkorder->find('first')
	 * @params $assets, array optional, array of asset Ids 
	 * 		use null to add new assets by LEFT JOIN 
	 */
	public function harvestAssets($task_id, $woid){
		if (!$woid) {
			$woid = $this->field('workorder_id', array('id'=>$task_woid));
		}
		$model = ClassRegistry::init('AssetsWorkorder');
		$options = array(
			'recursive' => -1,
		);  
		$options['conditions'] = array('`AssetsWorkorder`.workorder_id'=>$woid);
		$options['fields'] = array('`AssetsWorkorder`.asset_id');
		$subSelect = "SELECT at.asset_id FROM assets_tasks at  JOIN tasks_workorders tw 
ON ( tw.workorder_id = '{$woid}' AND    tw.task_id = '{$task_id}' AND    at.tasks_workorder_id = tw.id)  ";
		$options['conditions'][] = $model->getDataSource()->expression("`{$model->alias}`.`asset_id` NOT IN ({$subSelect})");	
		$data2 = $model->find('all', $options);
		$assets = Set::extract("/{$model->name}/asset_id", $data2);
		return $assets;
	}	
	
	public function addAssets($data, $assets = array()){
		if (empty($assets)) {
			$assets = $this->harvestAssets($data['TasksWorkorder']['task_id'], $data['TasksWorkorder']['workorder_id']);
		} else if (isset($assets['id'])) {
			$assets = Set::extract("/id", $assets);
		} else if (is_string($assets)) {
			$assets = explode(',', $assets);
		}
		$assetsTask = array();
		$twoid = $data['TasksWorkorder']['id'];
		foreach ($assets as $aid) {
			if (!$aid) continue;
			$assetsTask[]  = array(
				'tasks_workorder_id'=>$twoid,
				'asset_id'=>$aid,
			);
		}
		// $data['AssetsTask'] = $assetsTask;
		$count = count($assetsTask);
		if ($count) {
			$ret = $this->AssetsTask->saveAll($assetsTask, array('validate'=>'first'));
			return $ret ? $count : false;
		} else return true;  	// nothing new to add;
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
}
?>