<?php
class TasksWorkordersController extends AppController {

	public $name = 'TasksWorkorders';
	public $layout = 'snappi';

	public static $test = array();
	function __construct() {
       parent::__construct();
       TasksWorkordersController::$test['circle']['woid'] = '4fb499f4-1f54-411e-a147-0cd0f67883f5';
	   TasksWorkordersController::$test['circle']['task_1'] = '4fb499f4-46a0-49cd-bd18-0cd0f67883f5';
	   TasksWorkordersController::$test['circle']['task_2'] = '4fb49ab0-449c-4650-90e5-0cd0f67883f5';
	   
	   TasksWorkordersController::$test['person']['woid'] = '4fb6a572-01b4-4382-b459-125cf67883f5';
	   TasksWorkordersController::$test['person']['task_1'] = '4fb6aa08-94ac-4ce7-b6ec-125cf67883f5';
	   TasksWorkordersController::$test['person']['task_2'] = '4fb6bd58-9a58-4047-a78c-125cf67883f5';
	   TasksWorkordersController::$test['person']['task_3'] = '4fb86f72-bf68-432e-b317-125cf67883f5';
		
	   TasksWorkordersController::$test['client']['User'] = '12345678-1111-0000-0000-venice------';
	   TasksWorkordersController::$test['client']['Group'] = '4bbd3bc6-4d68-4a52-9dbb-11a0f67883f5';  // friends of snaphappi
	   TasksWorkordersController::$test['editor'] = '12345678-1111-0000-0000-editor------';
	}
	
	public $paginate = array(
		'GroupAsset'=>array(
			'preview_limit'=>6,
			'paging_limit' =>48,
			'photostream_limit' => 4,	// deprecate?
			'order' => array('batchId'=>'DESC', 'dateTaken_syncd'=>'ASC'),
			'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>'Groupshot', 
				'show_hidden_shots'=>false
			),
			// 'extras'=>array(
				// 'show_edits'=>true,
				// 'join_shots'=>false, 
				// 'show_hidden_shots'=>false
			// ),
			'recursive'=> -1,	
			'fields' =>array("DATE_ADD(`Asset`.`dateTaken`, INTERVAL coalesce(`AssetsGroup`.dateTaken_offset,'00:00:00')  HOUR_SECOND) AS dateTaken_syncd",
				'Asset.*'
			),
			'joins' => array(
				array(
					'table'=>'assets_groups',
					'alias'=>'AssetsGroup',
					'type'=>'INNER',
					'conditions'=>array('`AssetsGroup`.asset_id = `Asset`.id'),
				),
				array(
					'table'=>'groups',
					'alias'=>'Group',
					'type'=>'INNER',
					'conditions'=>array('`Group`.id = `AssetsGroup`.group_id'),
				),
			)
		),
		'UserAsset'=>array(
			'preview_limit'=>6,
			'paging_limit' =>48,
			'photostream_limit' => 4,	// deprecate?
			'order' => array('batchId'=>'DESC', 'created'=>'ASC'),
			'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>'Usershot', 
				'show_hidden_shots'=>false
			),
			// 'extras'=>array(
				// 'show_edits'=>true,
				// 'join_shots'=>false, 
				// 'show_hidden_shots'=>false
			// ),
			'recursive'=> -1,	
			'fields' =>array(
				'Asset.*'
			),
		),	
	);
	
	function assign($wo_task_id = null) {
		$EDITOR = TasksWorkordersController::$test['editor'] ;
		$WOID = TasksWorkordersController::$test['person']['woid'];
		$TASK_WO = TasksWorkordersController::$test['person']['task_3'];
		
		$this->TasksWorkorder->id = $TASK_WO;
		$this->TasksWorkorder->saveField('operator_id', $EDITOR);
		
		$this->render('/elements/dumpSQL');
	}
	
	function assignment($wo_task_id = null) {
		$EDITOR = TasksWorkordersController::$test['editor'] ;
		$WOID = TasksWorkordersController::$test['person']['woid'];
		$TWOID = TasksWorkordersController::$test['person']['task_3'];
		
		// $assets
		$assets = $this->TasksWorkorder->getAssets($TWOID);
debug($assets);		
		$this->render('/elements/dumpSQL');
	}
	
	function harvest($id=null) {
		$TWOID = TasksWorkordersController::$test['person']['task_2'];	// person or circle
		if (!$id) $id = $TWOID; $this->passedArgs[0] = $TWOID;
		
		$options = array(
			'recursive'=>-1,
			'conditions'=>array('`TasksWorkorder`.id'=>$id)
		);
		$data = $this->TasksWorkorder->find('first', $options);
		$assets = $this->TasksWorkorder->harvestAssets($data['TasksWorkorder']['task_id'], $data['TasksWorkorder']['workorder_id']);
		if (!empty($assets)) {
			$options = array_filter_keys($data['TasksWorkorder'], array('workorder_id', 'task_id', 'task_sort'));
			$taskWorkorder = $this->TasksWorkorder->createNew($options);
			$this->TasksWorkorder->addAssets($taskWorkorder, $assets);
		}
		
		$this->render('/elements/dumpSQL');
	}
	
	function photos($id = null){
		$TWOID = TasksWorkordersController::$test['circle']['task_1'];
		if (!$id) $id = $TWOID; $this->passedArgs[0] = $id;	
		
		$forceXHR = setXHRDebug($this, 0);
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';				
		if (!$id) {
			$this->Session->setFlash("ERROR: invalid Photo id.");
			$this->redirect(array('action' => 'all'));
		}
		$options = array(
			'contain'=>array('Workorder'),
			'conditions'=>array('TasksWorkorder.id'=>$id)
		);
		$data = $this->TasksWorkorder->find('first', $options);
		
		// paginate 
		$SOURCE_MODEL = $data['Workorder']['source_model'];
		$paginateModel = 'Asset';
		$Model = ClassRegistry::init($paginateModel);
		$this->paginate[$paginateModel] = $this->paginate[$SOURCE_MODEL.$paginateModel];
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $Model->getPaginatePhotosByTasksWorkorderId($id, $this->paginate[$paginateModel]);
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData = $this->paginate($paginateModel);
		$pageData = Set::extract($pageData, "{n}.{$paginateModel}");
		// end paginate
		if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
		$castingCall = $this->CastingCall->getCastingCall($pageData);
		$this->viewVars['jsonData']['castingCall'] = $castingCall;

		// ownername lookup
		// if (Session::read('lookup.context.keyName')!='person')  {
			// // add owner_names to lookup.
			// $this->getLookups(array('Users'=> array_keys(Set::combine($pageData, '/owner_id', ''))));
		// }
					
		$done = $this->renderXHRByRequest('json', '/elements/photo/roll');
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false	
		
		/*
		 * render page
		 */ 
		$data = array_merge($this->Auth->user(), $data);
		if (empty($data)) {
			/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash(sprintf(__('No %s found.', true), 'Photos'));
			$this->redirectSafe();
		} else {
			$this->set('data', $data);
			$this->viewVars['jsonData']['Workorder'][]=$data['Workorder'];
			$this->viewVars['jsonData']['TasksWorkorder'][]=$data['TasksWorkorder'];
			Session::write('lookup.owner_names', Set::merge(Session::read('lookup.owner_names'), Set::combine($data, '/Owner/id', '/Owner/username')));
		}
		$this->set(array('assets'=>$data,'class'=>'Asset'));
		
		$this->viewPath = 'workorders';
	}	
	
}
?>