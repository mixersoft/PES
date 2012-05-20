<?php
class WorkordersController extends AppController {

	public $name = 'Workorders';
	public $layout = 'snappi';
	
	public static $test = array();
	function __construct() {
       parent::__construct();
       WorkordersController::$test['circle']['woid'] = '4fb499f4-1f54-411e-a147-0cd0f67883f5';
	   WorkordersController::$test['circle']['task_1'] = '4fb499f4-46a0-49cd-bd18-0cd0f67883f5';
	   WorkordersController::$test['circle']['task_2'] = '4fb49ab0-449c-4650-90e5-0cd0f67883f5';
	   
	   WorkordersController::$test['person']['woid'] = '4fb6a572-01b4-4382-b459-125cf67883f5';
	   WorkordersController::$test['person']['task_1'] = '4fb6aa08-94ac-4ce7-b6ec-125cf67883f5';
	   WorkordersController::$test['person']['task_2'] = '4fb6bd58-9a58-4047-a78c-125cf67883f5';
	   WorkordersController::$test['person']['task_3'] = '4fb86f72-bf68-432e-b317-125cf67883f5';
	   
	   WorkordersController::$test['client']['User'] = '12345678-1111-0000-0000-venice------';
	   WorkordersController::$test['client']['Group'] = '4bbd3bc6-4d68-4a52-9dbb-11a0f67883f5';  // friends of snaphappi
	   WorkordersController::$test['editor'] = '12345678-1111-0000-0000-editor------';
	   
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
	/*
	 * test workflow: create, $options['WOID'] = null > assign > harvest
	 */ 
	function create() {
		$options = array();
		// $options['SOURCE_MODEL'] = 'Group';
		$options['SOURCE_MODEL'] = 'User';
		$options['SOURCE_ID'] = WorkordersController::$test['client'][$options['SOURCE_MODEL']];
		$options['CLIENT_ID'] = WorkordersController::$test['client']['User'];
		
		// reuse existing workorder
		$options['WOID'] = WorkordersController::$test['person']['woid'];
		
		$this->Workorder->TEST_createWorkorder($options);
		
		$this->render('/elements/dumpSQL');
	}
	
	// assign EDITOR TO workorder and taskWorkorder
	function assign($wo_task_id = null) {
		$EDITOR =  WorkordersController::$test['editor'];
		$WOID = WorkordersController::$test['person']['woid'];
		$TASK_1 =  WorkordersController::$test['person']['task_1'] ;
		$TASK_2 =  WorkordersController::$test['person']['task_1'] ;
		
		$this->Workorder->id = $WOID;
		$this->Workorder->saveField('manager_id', $EDITOR);
		
		$this->Workorder->TasksWorkorder->id = $TASK_1;
		$this->Workorder->TasksWorkorder->saveField('operator_id', $EDITOR);
		
		$this->render('/elements/dumpSQL');
	}
	
	//  get assets for assignment, replaced by /workorders/photos
	function assignment($wo_task_id = null) {
		$EDITOR = '12345678-1111-0000-0000-editor------';
		$WOID = '4fb499f4-1f54-411e-a147-0cd0f67883f5';
		$TASK_1 = '4fb499f4-46a0-49cd-bd18-0cd0f67883f5';
		$TASK_2 = '4fb49ab0-449c-4650-90e5-0cd0f67883f5';
		
		// $assets
		if (1) {
			$assets = $this->Workorder->getAssets($WOID);
		} else {
			$assets = $this->Workorder->TasksWorkorder->getAssets($TASK_1);
		}
		$this->render('/elements/dumpSQL');
	}
	
	function harvest($id=null) {
		$WOID = WorkordersController::$test['circle']['woid'];	// person or circle
		if (!$id) $id = $WOID; $this->passedArgs[0] = $WOID;
		
		$options = array(
			'recursive'=>-1,
			'conditions'=>array('Workorder.id'=>$id)
		);
		$data = $this->Workorder->find('first', $options);
		$assets = $this->Workorder->harvestAssets($data);
		if (!empty($assets)) $this->Workorder->addAssets($data, $assets);
		
		$this->render('/elements/dumpSQL');
	}
	
	function photos($id = null){
		$WOID = WorkordersController::$test['circle']['woid'];	// person or circle
		if (!$id) $id = $WOID; $this->passedArgs[0] = $id;
		
		$forceXHR = setXHRDebug($this, 0);
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';				
		if (!$id) {
			$this->Session->setFlash("ERROR: invalid Photo id.");
			$this->redirect(array('action' => 'all'));
		}
		
		// paginate 
		$SOURCE_MODEL = $this->Workorder->field('source_model', array('id'=>$id));
		$paginateModel = 'Asset';
		$Model = ClassRegistry::init($paginateModel);
		// map correct paginateArray based on $SOURCE_MODEL
		$this->paginate[$paginateModel] = $this->paginate[$SOURCE_MODEL.$paginateModel];
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $Model->getPaginatePhotosByWorkorderId($id, $this->paginate[$paginateModel]);
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
		$options = array(
			'contain'=>array('TasksWorkorder.id', 'TasksWorkorder.task_sort', 'TasksWorkorder.operator_id'),
			'conditions'=>array('Workorder.id'=>$id)
		);
		$data = $this->Workorder->find('first', $options);
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
			Session::write('lookup.owner_names', Set::merge(Session::read('lookup.owner_names'), Set::combine($data, '/Owner/id', '/Owner/username')));
		}
		$this->set(array('assets'=>$data,'class'=>'Asset'));
		
		// $this->render('/elements/dumpSQL');
	}	
	
}
?>