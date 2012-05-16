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
	);
	public $hasMany = array(
		'AssetsTask' => array(								// TasksWorkorder habtm Assets
			'className' => 'AssetsTask',				
			'foreignKey' => 'task_id',
			'dependent' => true,
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
	public function harvestAssets($woid){
		$model = ClassRegistry::init('AssetsWorkorder');
		$options = array(
			'recursive' => -1,
		);  
		$options['conditions'] = array('`AssetsWorkorder`.workorder_id'=>$woid);
		$options['fields'] = array('asset_id');
		$joins[] = array(
			'table'=>'snappi_workorders.assets_tasks',
			'alias'=>'AssetsTask',
			'type'=>'LEFT',
			'conditions'=>array("`AssetsTask`.asset_id = `{$model->name}`.asset_id"),
		);
		$options['conditions'][] = "`AssetsTask`.asset_id IS NULL";
		$options['joins'] = $joins; 
		$data2 = $model->find('all', $options);
		$assets = Set::extract("/{$model->name}/asset_id", $data2);
		return $assets;
	}	
	
	public function addAssets($data, $assets = array()){
		if (empty($assets)) {
			$assets = $this->harvestAssets($data['TasksWorkorder']['workorder_id']);
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
		if ($assetsTask) $ret = $this->AssetsTask->saveAll($assetsTask, array('validate'=>'first'));
		else $ret = true;  	// nothing new to add;
		return $ret;		
	}
}
?>