<?php 
class WorkorderPermissionableBehavior extends ModelBehavior {
	
/**
 * Settings array
 *
 * @var array
 */
	
	public $settings = array(

	);	
	
/**
 * controller reference
 *
 * @var Controller
 */
	public $controller = null;
	
/**
 * named params array, defaults to Configure::read('passedArgs.complete');
 *
 * @var Controller
 */
	public $named = null;	
	
/**
 * Default settings
 *
 * @var array
 */
	protected $_defaults = array(
	);	
	

/**
 * Setup
 *
 * @param AppModel $Model
 * @param array $settings
 */
	public function setup(Model $Model, $settings = array()) {
		if (AppController::$role && in_array(AppController::$role, array('EDITOR', 'MANAGER')) === false) {
			throw new Exception("Error: WorkorderPermissionable Behavior requires role privileges.", 1);
		};
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = $this->_defaults;
		}
		if (!empty($settings['controller'])) $this->controller = $this->settings['controller'];
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], $settings);
		
		// detach behavior permissionable, if attached. 
		// TODO: check race condition.	
		if ($Model->Behaviors->attached('Permissionable')) {
			$Model->Behaviors->detach('Permissionable');	
		}
	}


	
/**
 * beforeFind Callback
 *
 * @param AppModel $Model 
 * @param array $queryData 
 * @return array
 */
	public function beforeFind(Model $Model, $queryData) {
		if (AppController::$role && in_array(AppController::$role, array('EDITOR', 'MANAGER')) === false) {
			throw new Exception("Error: WorkorderPermissionable Behavior requires role privileges.", 1);
		};

		extract($this->settings[$Model->alias]);
		switch ($type) {
			case 'TasksWorkorder': 
				$queryData = $this->getPaginatePhotosByTasksWorkorderId($uuid, $queryData);
			break;
			case 'Workorder': 
				$queryData = $this->getPaginatePhotosByWorkorderId($uuid, $queryData);
			break;
		}
		return $queryData;
	}	
	
/**
 * afterFind Callback
 *
 * @param AppModel $Model 
 * @param array $results 
 * @param boolean $primary 
 * @return array
 */
	public function afterFind(Model $Model, $results, $primary) {
		extract($this->settings[$Model->alias]);
		return $results;
	}	
	
	
		/**
	 * Notes for workorderId: 
	 *  - in_array(AppController::$role, array('EDITOR', 'MANAGER'))
	 *  - WorkOrder.active = 1
	 */
	function getPaginatePhotosByWorkorderId ($woid , $paginate = array()) {
		if (in_array(AppController::$role, array('EDITOR', 'MANAGER')) === false) return $paginate;
		$paginate['permissionable'] = false;
		$joins[] = array(
			'table'=>'snappi_workorders.assets_workorders',
			'alias'=>'AssetsWorkorder',
			'type'=>'INNER',
			'conditions'=>array(
				"`AssetsWorkorder`.`asset_id` = `Asset`.id",
				"`AssetsWorkorder`.`workorder_id`"=>$woid,
				
			),
		);
		$joins[] = array(
			'table'=>'snappi_workorders.workorders',
			'alias'=>'Workorder',
			'type'=>'INNER',
			'conditions'=>array(
				"`Workorder`.`id`" =>$woid,
				'`Workorder`.manager_id' => AppController::$userid,
			),
		);
		if (!empty($joins)) $paginate['joins'] = @mergeAsArray($paginate['joins'], $joins);
		if (!empty($conditions)) $paginate['conditions'] = @mergeAsArray($paginate['conditions'], $conditions);
		return $paginate;	
	}
	function getPaginatePhotosByTasksWorkorderId ($task_woid , $paginate = array()) {
		if (in_array(AppController::$role, array('EDITOR', 'MANAGER')) === false) return $paginate;
		$joins[] = array(
			'table'=>'snappi_workorders.assets_tasks',
			'alias'=>'AssetsTask',
			'type'=>'INNER',
			'conditions'=>array(
				"`AssetsTask`.`asset_id` = `Asset`.id",
				"`AssetsTask`.`tasks_workorder_id`"=>$task_woid,
				
			),
		);
		$joins[] = array(
			'table'=>'snappi_workorders.tasks_workorders',
			'alias'=>'TasksWorkorder',
			'type'=>'INNER',
			'conditions'=>array(
				"`TasksWorkorder`.`id`" =>$task_woid,
				'`TasksWorkorder`.operator_id' => AppController::$userid,
			),
		);
		if (!empty($joins)) $paginate['joins'] = @mergeAsArray($paginate['joins'], $joins);
		if (!empty($conditions)) $paginate['conditions'] = @mergeAsArray($paginate['conditions'], $conditions);
		return $paginate;	
	}
	
	
}
?>