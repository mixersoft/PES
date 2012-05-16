<?php
class Workorder extends AppModel {
	public $name = 'Workorder';
	public $useDbConfig = 'workorders';
	
	public $hasMany = array(
		'AssetsWorkorder' => array(								// Tasks habtm Workorder
			'className' => 'AssetsWorkorder',				
			'foreignKey' => 'asset_id',
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
		)
	);	
	
	
	
	public function TEST_createTaskWorkorder($woid, $options=array()){
		$TEST_default = array(
			'name'=>'Rate Photos',		// just test with one task for now
			'task_id'=>'4fb294c2-525c-4871-9ba5-09fcf67883f5',
			'task_sort'=>0,
		);
		$options = array_merge($TEST_default, $options);
		$options['workorder_id'] = $woid;
		$taskWorkorder = $this->TasksWorkorder->createNew($options);
		return ($taskWorkorder) ? $this->TasksWorkorder->read() : false;
	}
	
	public function TEST_createWorkorder($woid = null) {
		$CLIENT_ID = '12345678-1111-0000-0000-venice------';
		$SOURCE_ID = '4bbd3bc6-4d68-4a52-9dbb-11a0f67883f5';  // friends of snaphappi
		$SOURCE_MODEL = 'Group';
		// $SOURCE_ID = $CLIENT_ID;  // friends of snaphappi
		// $SOURCE_MODEL = 'User';		
		if ($woid) {
			$data = $this->findById($woid);
		} else $data = $this->createNew($CLIENT_ID, $SOURCE_ID, $SOURCE_MODEL);
		$ret = $this->addAssets($data); 
		if ($ret) {
			// create NEW task if there are more photos to harvest
			$assets = $this->TasksWorkorder->harvestAssets($data['Workorder']['id']);
			if ($assets) {
				$taskWorkorder = $this->TEST_createTaskWorkorder($data['Workorder']['id']);
				$ret = $ret && $this->TasksWorkorder->addAssets($taskWorkorder, $assets);
			}
		}
		return $this->read(null, $data['Workorder']['id']);
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
	public function addAssets($data, $assets = array()){
		if (empty($assets)) {
			// User: Assets.owner_id = $data['Workorder']['source_id']
			// Group: AssetsGroup.group_id = $data['Workorder']['source_id']
			$options = array(
				'recursive' => -1,
			);  
			switch ($data['Workorder']['source_model']){
				case 'User':
					$model = ClassRegistry::init('Assets');
					$options['conditions'] = array('`Assets`.owner_id'=>$data['Workorder']['source_id']);
					$options['fields'] = array('id AS asset_id');
					$options['extras'] = array(
						'show_edits'=>false,
						'join_shots'=>false, 
						'show_hidden_shots'=>true,		
					);
					$options['permissionable'] = false;
					break;
				case 'Group':
					$model = ClassRegistry::init('AssetsGroup');
					$options['conditions'] = array('`AssetsGroup`.group_id'=>$data['Workorder']['source_id']);
					$options['fields'] = array('asset_id');
					break;
			}
// ?? add already rated photos? NO, depends on task.
			$joins[] = array(
				'table'=>'snappi_workorders.assets_workorders',
				'alias'=>'AssetsWorkorder',		// use Shot instead of Groupshot
				'type'=>'LEFT',
				'conditions'=>array("`AssetsWorkorder`.asset_id = `{$model->name}`.asset_id"),
			);
			$options['conditions'][] = "`AssetsWorkorder`.asset_id IS NULL";
			$options['joins'] = $joins; 
			$data2 = $model->find('all', $options);
			$assets = Set::extract("/{$model->name}/asset_id", $data2);
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
	
	
	
}
?>