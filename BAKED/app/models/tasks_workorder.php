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
			'counterCache' => true,	
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
	 * get WorkorderAssets to add to TasksWorkorderAssets
	 * @param $task_id, FK to Task
	 * @param $woid, FK to Workorder 
	 * @param $filter string [NEW|ALL]
	 */
	public function harvestAssets($task_id, $woid, $filter='NEW'){
		if (!$woid) {
			$woid = $this->field('workorder_id', array('id'=>$task_woid));
		}
		$model = ClassRegistry::init('AssetsWorkorder');
		$options = array(
			'recursive' => -1,
		);  
		$options['conditions'] = array('`AssetsWorkorder`.workorder_id'=>$woid);
		$options['fields'] = array('`AssetsWorkorder`.asset_id');
		if ($filter == "NEW") { // only new AssetsWorkorders
			$subSelect = "SELECT at.asset_id FROM assets_tasks at  JOIN tasks_workorders tw 
	ON ( tw.workorder_id = '{$woid}' AND    tw.task_id = '{$task_id}' AND    at.tasks_workorder_id = tw.id)  ";
			$options['conditions'][] = $model->getDataSource()->expression("`{$model->alias}`.`asset_id` NOT IN ({$subSelect})");	
		}
		$data2 = $model->find('all', $options);
		$assets = Set::extract("/{$model->name}/asset_id", $data2);
		return $assets;
	}	
	
	/**
	 * add assets to an existing TasksWorkorder, i.e. harvest new photos
	 * @params $data array, from taskWorkorder->find('first')
	 * @params $assets, mixed 
	 * 		array of asset_ids from $Model->find()
	 * 		comma delimited string of asset_ids; 
	 * 		use string "NEW" to add new assets by LEFT JOIN using harvest
	 *  	use string "ALL" to add all assets from workorder
	 */
	public function addAssets($data, $assets){
		try{
			if ($assets == 'ALL') {
				$assets = $this->harvestAssets($data['TasksWorkorder']['task_id'], $data['TasksWorkorder']['workorder_id'], 'ALL');
			} else if ($assets == 'NEW') {
				$assets = $this->harvestAssets($data['TasksWorkorder']['task_id'], $data['TasksWorkorder']['workorder_id'], 'NEW');
			} else if (isset($assets['id'])) { // from Model->find()
				$assets = Set::extract("/id", $assets);
			} else if (is_string($assets)) {
				$assets = explode(',', $assets);
			}
			$assetsTask = array();
			$twoid = $data['TasksWorkorder']['id'];
			foreach ($assets as $aid) {
				$assetsTask[]  = array(
					'tasks_workorder_id'=>$twoid,
					'asset_id'=>$aid,
				);
			}
			// $data['AssetsTask'] = $assetsTask;
			$count = count($assetsTask);
			if ($count) {
				$ret = $this->AssetsTask->saveAll($assetsTask, array('validate'=>'first'));
				if ($ret) $this->resetStatus($twoid);
				return $ret ? $count : false;
			} else return true;  	// nothing new to add;
		}catch(Exception $e) {
			
		}
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
}
?>