<?php
class Workorder extends AppModel {
	public $name = 'Workorder';
	public $useDbConfig = 'workorders';

	public $belongsTo = array(
		'Manager' => array('className' => 'WorkorderEditor', 'foreignKey' => 'manager_id'),
		'Client' => array('className' => 'User', 'foreignKey' => 'client_id'),
		'Source' => array('className' => 'WorkorderSource', 'foreignKey' => 'source_id'),			// NOTE: this only works because we use UUID for Users/Groups, otherwise we need to join with source_model
	);
		
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
		), 
		'ActivityLog' => array(								
			'className' => 'ActivityLog',				
			'foreignKey' => 'workorder_id',
			'dependent' => true,
		), 
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
			// 'name'=>'Rate Photos',		// just test with one task for now
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
			'name'=>'edit photos',
		);
		$options = array_merge($TEST_default, $options);
		$workorder = $this->create($options);
		$workorder['Workorder']['id'] = null;			// cakephp automagic
		$workorder['Workorder']['client_id'] = $clientId;
		$workorder['Workorder']['source_id'] = $sourceId;
		$workorder['Workorder']['source_model'] = $sourceModel;
		// assign workorder at create to AppController::$userid, ROLE = EDITOR
		if (isset($options['manager_id']) && strlen($options['manager_id'])==36) {
			$workorder['Workorder']['manager_id'] = $this->getManagerIdFromUUID($options['manager_id']);
		} else { // assume manager_id is snappi_wms.Editor.id
			$workorder['Workorder']['manager_id'] = $options['manager_id'];
		}		
		$ret = $this->saveAll($workorder, array('validate'=>'first'));
		return ($ret) ? $this->read() : false;
	}
	
	public function createTaskWorkorders($wo, $options = array()){
		if ($wo['Workorder']['name'] == 'edit photos') {
			$TEST_default = array(
				'task_id'=>1,	// Task = edit snaps
				'task_sort'=>0,
			);
			$options = array_merge($TEST_default, $options);
			$options['workorder_id'] = $wo['Workorder']['id'];
			$taskWorkorder = $this->TasksWorkorder->createNew($options);
		} else {
			throw new Exception("WARNING: not TasksWorkorders defined for Workorder.name={$wo['Workorder']['name']}", 1);
		}
		return ($taskWorkorder) ? $this->TasksWorkorder->read() : false;
	}
		
	/**
	 * lookup snappi_wms.Editor.id from snappi_wms.Editor.user_id and save
	 * return int, snappi_wms.Editor.id
	 */
	public function getManagerIdFromUUID ($manager_id){
		// lookup WMS Editor.id from UUID
		if ($manager = $this->Manager->findByUserId($manager_id)) {
			return $manager['Manager']['id'];
		} else {
			throw new Exception("Error: Workorder manager_id not found. Is the manager in the Edtiors table?", 1);
		}
		return false;
	}

	
	// deprecate, use UpdateStatus
	public function resetStatus($woid) {
		$status = $this->read('status', $woid); 
		if ($status == 'done') $this->saveField('status', 'ready');
		// TODO: add entry to status log?
	}

	public function getAssets($woid) {
		$options = array(
			'contain' => array('AssetsWorkorder.asset_id',
				'Manager'
			),
			'conditions' => array(
				'`Workorder`.id'=>$woid,
				'`Manager`.user_id' => AppController::$userid,
			),
		);
		$data = $this->find('all', $options);
		return Set::extract('/AssetsWorkorder/asset_id', $data);
		
	}	
	

	function updateAllCounts() {
		$SQL = "
UPDATE snappi_wms.`workorders` as Workorder
LEFT JOIN (
	SELECT w.id AS workorder_id, COUNT(DISTINCT aw.asset_id) AS `assets_workorder_count`
	FROM snappi_wms.`workorders` w
	LEFT JOIN snappi_wms.assets_workorders aw ON w.id = aw.workorder_id
	GROUP BY w.id
) AS t ON (`Workorder`.id = t.workorder_id)
SET Workorder.assets_workorder_count = t.assets_workorder_count;
";
		$result = $this->query($SQL);
		return true;
	}
	
	
	/***********************************************************************
	 * the following methods were copied from the WMS Workorder Model
	 ***********************************************************************/
	
	/**
	* get workorders, filterd by various params
	*/
	public function getAll($params = array()) {

		$findParams = array(
			'conditions' => array('Workorder.active' => true),
			'contain' => array(
				'Manager',
				'Source',
				'Client',
			),
		);
		$possibleParams = array('id', 'manager_id');
		foreach ($possibleParams as $param) {
			if (!empty($params[$param])) {
				$findParams['conditions'][] = array('Workorder.' . $param => $params[$param]);
			}
		}
		$options = $this->addTimeStats($findParams);
		$workorders = $this->find('all', $options);
		$workorders = $this->addTimes($workorders);
		return $workorders;
	}




	/**
	* add joins and derived fields to include time calculations, worktime, slacktime, etc.
	* original SQL:
	*	SELECT
	* sum(3600*TasksWorkorder.assets_task_count/Task.target_work_rate) as target_work_time,
	* sum(3600*TasksWorkorder.assets_task_count/coalesce(Skill.rate_7_day,Task.target_work_rate)) as operator_work_time,
	* UNIX_TIMESTAMP(coalesce(Workorder.due, date_add(now(), interval 3 hour))) as workorder_due, -- using coalesce because testdata has Workorder.due==null
	* UNIX_TIMESTAMP(coalesce(Workorder.due, date_add(now(), interval 3 hour))) - sum(3600*TasksWorkorder.assets_task_count/coalesce(Skill.rate_7_day,Task.target_work_rate)) - UNIX_TIMESTAMP(now()) as slack_time,
	*  TasksWorkorder.*
	* FROM workorders AS Workorder
	* JOIN tasks_workorders AS TasksWorkorder ON TasksWorkorder.workorder_id = Workorder.id
	* JOIN tasks AS Task ON Task.id = TasksWorkorder.task_id
	* LEFT JOIN skills AS Skill ON Skill.task_id = TasksWorkorder.task_id and Skill.editor_id = TasksWorkorder.operator_id
	* GROUP BY Workorder.id
	 * @return $options array for Model->find('all', $options); 
	*/
	public function addTimeStats($options) {
		$time_options = array(
			'fields' => array(
				'sum(3600*TasksWorkorder.assets_task_count/Task.target_work_rate) 
					as target_work_time',
				'sum(3600*TasksWorkorder.assets_task_count/coalesce(Skill.rate_7_day,Task.target_work_rate))
					as operator_work_time',
				'UNIX_TIMESTAMP(coalesce(Workorder.due,
				
					 date_add(now(), interval 3 hour)		
					 
					 )) 				
					as workorder_due', 		// testing with coalesce
				'UNIX_TIMESTAMP(
				coalesce(Workorder.due, 
				
					date_add(now(), interval 3 hour)
					
					))
					- sum(3600*TasksWorkorder.assets_task_count/coalesce(Skill.rate_7_day,Task.target_work_rate)) 
					- UNIX_TIMESTAMP(now())
					as slack_time',			// testing with coalesce
			),
			'joins' => array(
				// WARNING : should not mix contains and joins for the same table
				array(
					'table' => 'tasks_workorders', 'alias' => 'TasksWorkorder', 'type' => 'INNER',
					'conditions' => array(
						'Workorder.id = TasksWorkorder.workorder_id'
					),
				),
				array(
					'table' => 'tasks', 'alias' => 'Task', 'type' => 'INNER',
					'conditions' => array(
						'Task.id = TasksWorkorder.task_id'
					),
				),
				array(
					'table' => 'skills', 'alias' => 'Skill', 'type' => 'LEFT',
					'conditions' => array(
						'Skill.task_id = TasksWorkorder.task_id',
						'Skill.editor_id = TasksWorkorder.operator_id',
					),
				),
			),
			'group'=>array('Workorder.id'),
			'order' => array('slack_time'=>'ASC'),
		);
		// merge
		if (empty($options['fields'])) $options['fields'][] = '*';
		$options = Set::merge($options, $time_options);
		return $options;		
	}


	/**
	* add slack_time and work_time as virtual fields from derived values
	*/
	public function addTimes($records) {
		foreach ($records as $i => $record) {
			if ($records[$i][0]['operator_work_time']) {
				$records[$i]['Workorder']['work_time'] = $records[$i][0]['operator_work_time'];
				$records[$i]['Workorder']['operator_work_time'] = $records[$i][0]['operator_work_time'];
			} else {
				$records[$i]['Workorder']['work_time'] = $records[$i][0]['target_work_time'];
				$records[$i]['Workorder']['operator_work_time'] = '';
			}
			$records[$i]['Workorder']['target_work_time'] = $records[$i][0]['target_work_time'];	
			$records[$i]['Workorder']['slack_time'] = $records[$i][0]['slack_time'];
			
			// reformat to match TasksWorkorder nexted Containable result
			$records[$i]['Workorder']['Source'] = & $records[$i]['Source'];
			$records[$i]['Workorder']['Client'] = & $records[$i]['Client'];			
		}
		return $records;
	}

	/**
	* update the workorder status based ont the status of its tasks
	*
	* rules:
	* QA: if all the tasks are done
	* Working: if at least one of the tasks is working or paused
	* otherwise, do nothing
	*
	* @return true if the status change is made, false otherwise
	*/
	public function updateStatus($id) {
		$workorder = $this->findById($id);
		$tasksWorkorders = $this->TasksWorkorder->find('all', array('conditions' => array('TasksWorkorder.workorder_id' => $id)));
		$countDone = 0;
		foreach ($tasksWorkorders as $tasksWorkorder) {
			switch ($tasksWorkorder['TasksWorkorder']['status']) {
				case 'Working': case 'Paused':
					$newStatus = 'Working';
				break;
				case 'Done':
					$countDone++;
				break;
			}
		}
		if ($countDone != 0  and count($tasksWorkorders) == $countDone) {
			$newStatus = 'QA';
		}
		if (!empty($newStatus) and $newStatus != $workorder['Workorder']['status']) {
			$this->ActivityLog->saveWorkorderStatusChange($id, $workorder['Workorder']['status'], $newStatus);
			$dataToSave = array('id' => $id, 'status' => $newStatus);
			if (empty($workorder['Workorder']['started'])) {
				$dataToSave['started'] = Configure::read('now');
			}
			return $this->save($dataToSave);
		} else {
			return false;
		}
	}
	 	
}
?>