<?php
class Workorder extends AppModel {
	public $name = 'Workorder';
	public $useDbConfig = 'workorders';
	public $actsAs = array('Brownie.Panel');
	
	public $hasMany = array(
		'AssetsWorkorder' => array(								// Tasks habtm Workorder
			'className' => 'AssetsWorkorder',				
			'foreignKey' => 'workorder_id',
			'dependent' => true,
		),
		'TasksWorkorder' => array(								// Tasks habtm Workorder
			'className' => 'TasksWorkorder',				
			'foreignKey' => 'workorder_id',
			'dependent' => true,
		)
	);	
	
	public $hasAndBelongsToMany = array(
		'Task' => array(								// Tasks habtm Workorder
			'with' => 'TasksWorkorder',				
		),
		'Asset' => array(								// Tasks habtm Workorder
			'with' => 'AssetsWorkorder',				
		),
	);	
	

	// create new workorder AND tasksWorkder
	public function TEST_createWorkorder($options) {
		extract($options);		// $WOID, $SOURCE_MODEL, $SOURCE_ID, $CLIENT_ID
		$ret = true;
		if (!empty($WOID)) {
			$data = $this->findById($WOID);
			$assets = $this->harvestAssets($data);
			if ($assets) {	// harvest workorder assets, if any
				$ret = $this->addAssets($data, $assets);
			}
		} else {	// new
			$data = $this->createNew($CLIENT_ID, $SOURCE_ID, $SOURCE_MODEL);
			$ret = $this->addAssets($data); 
		}		
		if ($ret) {		// harvest tasksWorkorder photos, create tasksWorkorder, as necessary
			// create NEW task if there are more photos to harvest for given task
			$assets = $this->TasksWorkorder->harvestAssets(WorkordersController::$test['task_id'], $data['Workorder']['id']);
			if ($assets) {
				$taskWorkorder = $this->TEST_createTaskWorkorder($data['Workorder']['id']);
				$ret = $ret && $this->TasksWorkorder->addAssets($taskWorkorder, $assets);
			}
		}
		return $this->read(null, $data['Workorder']['id']);
	}
	
	
	public function TEST_createTaskWorkorder($woid, $options=array()){
		$TEST_default = array(
			'name'=>'Rate Photos',		// just test with one task for now
			'task_id'=>WorkordersController::$test['task_id'],
			'task_sort'=>0,
		);
		$options = array_merge($TEST_default, $options);
		$options['workorder_id'] = $woid;
		$taskWorkorder = $this->TasksWorkorder->createNew($options);
		return ($taskWorkorder) ? $this->TasksWorkorder->read() : false;
	}
	
		
	public function createNew ($clientId, $sourceId, $sourceModel, $options=array()){
		$TEST_default = array(
			'name'=>'Sort+Bestshot',
		);
		$options = array_merge($TEST_default, $options);
		$workorder = $this->create($options);
		$workorder['id'] = null;			// cakephp automagic
		$workorder['client_id'] = $clientId;
		$workorder['source_id'] = $sourceId;
		$workorder['source_model'] = $sourceModel;
		$data['Workorder'] = $workorder;
		
		$ret = $this->saveAll($data, array('validate'=>'first'));
		return ($ret) ? $this->read() : false;
	}
	
	/**
	 * add assets to an existing workorder, i.e. harvest new photos
	 * @params $data array, from workorder->find('first')
	 * @params $assets, array optional, array of asset Ids 
	 * 		use null to add new assets by LEFT JOIN 
	 */
	public function harvestAssets($data){
		// User: Assets.owner_id = $data['Workorder']['source_id']
		// Group: AssetsGroup.group_id = $data['Workorder']['source_id']
		$SOURCE_MODEL = $data['Workorder']['source_model'];
		$SOURCE_ID = $data['Workorder']['source_id'];
		$options = array(
			'recursive' => -1,
		);  
		switch ($SOURCE_MODEL){
			case 'User':
				$model = ClassRegistry::init('Asset');
				$options['conditions'] = array('`Asset`.owner_id'=>$SOURCE_ID);
				$options['fields'] = array('`Asset`.id AS asset_id');
				$options['extras'] = array(
					'show_edits'=>false,
					'join_shots'=>false, 
					'show_hidden_shots'=>true,		
				);
				$options['permissionable'] = false;
				$joins[] = array(
					'table'=>'snappi_workorders.assets_workorders',
					'alias'=>'AssetsWorkorder',		// use Shot instead of Groupshot
					'type'=>'LEFT',
					'conditions'=>array("`AssetsWorkorder`.asset_id = `Asset`.id"),
				);
				break;
			case 'Group':
				$model = ClassRegistry::init('AssetsGroup');
				$options['conditions'] = array('`AssetsGroup`.group_id'=>$SOURCE_ID);
				$options['fields'] = array('asset_id');
				$joins[] = array(
					'table'=>'snappi_workorders.assets_workorders',
					'alias'=>'AssetsWorkorder',		// use Shot instead of Groupshot
					'type'=>'LEFT',
					'conditions'=>array("`AssetsWorkorder`.asset_id = `AssetsGroup`.asset_id"),
				);
				break;
		}
// ?? add already rated photos? NO, depends on task.
		$options['conditions'][] = "`AssetsWorkorder`.asset_id IS NULL";
		$options['joins'] = $joins; 
		$data2 = $model->find('all', $options);
		$assets = Set::extract("/{$model->name}/asset_id", $data2);		
		return $assets;
	}	
	
	public function addAssets($data, $assets = array()){
		if (empty($assets)) {
			$assets = $this->harvestAssets($data);
		} else if (isset($assets['id'])) {
			$assets = Set::extract("/id", $assets);
		} else if (is_string($assets)) {
			$assets = explode(',', $assets);
		}
		$assetsWorkorder = array();
		$woid = $data['Workorder']['id'];
		foreach ($assets as $aid) {
			if (!$aid) continue;
			$assetsWorkorder[]  = array(
				'workorder_id'=>$woid,
				'asset_id'=>$aid,
			);
		}
// debug($assetsWorkorder);
		// $data['AssetsWorkorder'] = $assetsWorkorder;
		if ($assetsWorkorder) $ret = $this->AssetsWorkorder->saveAll($assetsWorkorder, array('validate'=>'first'));
		else $ret = true;  	// nothing new to add;
		return $ret;
	}
	

	public function getAssets($woid) {
		$options = array(
			'contain' => 'AssetsWorkorder.asset_id',
			'conditions' => array(
				'`Workorder`.id'=>$woid,
				'`Workorder`.manager_id' => AppController::$userid,
			),
		);
		$data = $this->find('all', $options);
		return Set::extract('/AssetsWorkorder/asset_id', $data);
		
	}	
	
	
}
?>