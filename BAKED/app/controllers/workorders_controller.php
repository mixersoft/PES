<?php
class WorkordersController extends AppController {

	public $name = 'Workorders';
	public $layout = 'snappi';
	// public $local_components = array('Brownie.panel');
	
	
	public static $test = array();
	function __construct() {
       parent::__construct();
	   // $this->components = array_merge($this->components, $this->local_components);
	   WorkordersController::$test['task_id'] = '4fb294c2-525c-4871-9ba5-09fcf67883f5';  // only task
	   
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
			// 'extras'=>array(
				// 'show_edits'=>true,
				// 'join_shots'=>'Usershot', 
				// 'show_hidden_shots'=>false
			// ),
			'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>false, 
				'show_hidden_shots'=>false
			),
			'recursive'=> -1,	
			'fields' =>array(
				'Asset.*'
			),
		),
	);
	/*
	 * POST, respond as JSON
	 * check for Role in EDITOR/MANAGER
	 * create new workorder if not exists, or update assignment
	 * assign to AppController::$userid
	 * harvest new Photos
	 * create TaskWorkorders if not exists, (but we are not testing TasksWorkorders yet) and harvest new Photos
	 * provide redirect to /workorder/photos/[workorder_id]
	 * 
	 * test workflow: create, $options['WOID'] = null > assign > harvest
	 */ 
	function create() {
		$forceXHR = setXHRDebug($this, 0);	
		if (isset($this->data)) {
			// POST
			if (in_array(AppController::$role, array('EDITOR', 'MANAGER')) === false) {
				$success = false;
				$message = "You do not have permission to do this task.";
	 		}
			$options = array_filter_keys($this->data['Workorder'], array('source_id', 'source_model', 'client_id', 'manager_id', 'editor_id'));
			
			// TODO: for now, use AppController::$userid instead of assignment values
			$options['client_id'] = $options['manager_id'] = $options['editor_id'] =  AppController::$userid;
			
			// TODO: for now, check for existing workorder, reuse if found
			$existing = array(
				'recursive'=>-1,
				'conditions'=>array('Workorder.source_id'=>$options['source_id'])
			);
			$data =  $this->Workorder->find('first', $existing);
			
			if ($data) {	// existing found, just update manager_id
				$this->Workorder->id = $data['Workorder']['id'];
				if (!empty($options['manager_id'])) $this->Workorder->saveField('manager_id', $options['manager_id'] );
			} else {		// create new
				$data = $this->Workorder->createNew($options['client_id'], $options['source_id'], $options['source_model'], $options);
			}
			try {
				$ret = $this->Workorder->addAssets($data);
				if (is_numeric($ret)) {
					// TODO: for now, create task, but don't use
					$taskWorkorder = $this->Workorder->TEST_createTaskWorkorder($data['Workorder']['id']);
					$ret = $this->Workorder->TasksWorkorder->addAssets($taskWorkorder);
				}
				
				// format json response
				$success = true;
				$message = "OK";
				$response = $this->Workorder->read(null, $data['Workorder']['id']);
				$response['next'] = Router::url(array('controller'=>'workorders', 'action'=>'photos', $data['Workorder']['id']), true);
				
			} catch (Exception $e) {
				$success = false;
				$message = "Error /workorders/create";
			}
			$this->viewVars['jsonData'] = compact('success', 'message', 'response');
			$done = $this->renderXHRByRequest('json', null , null, $forceXHR);
			if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false
			
		} else { 
			// testing only
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
	}
	
	/*
	 * from HTTP POST
	 * 		$this->data[manager_id]=UUID
	 * 		$this->data[task_id]=UUID (optional)
	 *		$this->data[editor_id]=UUID (optional)
	 * assign MANAGER TO workorder, grants access privileges to workorder assets
	 * (optional) assign editor to tasksWorkorder 
	 */ 
	function assign($id=null) {
		extract($this->data); // manager_id, editor_id, task_id
		
		$manager_id =  isset($manager_id) ? $manager_id :  WorkordersController::$test['editor'];
		$editor_id =  isset($editor_id) ? $editor_id :  $manager_id;
		
		// if null, use test workorder & task_workorder
		if (!$id) { 
			$id = WorkordersController::$test['person']['woid'];
			$task_id = WorkordersController::$test['person']['task_1'] ;
		}
		
		$this->Workorder->id = $id;
		$ret = $this->Workorder->saveField('manager_id', $manager_id);
		if ($ret) $message[] = "Workorder {$id}: manager set to {$manager_id}";
		
		if ($task_id) {
			$this->Workorder->TasksWorkorder->id = $task_id;
			$ret = $ret && $this->Workorder->TasksWorkorder->saveField('operator_id', $editor_id);
			if ($ret) $message[] = "Workorder {$id}/Task {$task_id}: operator set to {$editor_id}";
		}
		
		// format json response
		$success = $ret;
		$message = !empty($message) ? $message : "Error: There was problem assigning this workorder";
		$response = compact('id', 'manager_id', 'task_id', 'editor_id');
		$this->viewVars['jsonData'] = compact('success', 'message', 'response');
		$done = $this->renderXHRByRequest('json', null , null, $forceXHR);
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false
		
		$this->render('/elements/dumpSQL');
	}
	
	/*
	 * release editor assignment to remove access privileges
	 * NOTE: we'll need to keep a log on assignments for auditing 
	 */ 
	function release($id, $editor_id){
		
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
	
	/**
	 * /workorder/photos grants access privileges to assigned workorder.manager_id
	 * TODO: THERE IS NOT CHECK TO CONFIRM workorder.manager_id role=MANAGER
	 * TODO: for now, just assume manager/editor are equivalent, don't use workorder_tasks  
	 */ 
	function photos($id = null){
		$WOID = WorkordersController::$test['circle']['woid'];	// person or circle
		if (!$id) $id = $WOID; $this->passedArgs[0] = $id;
		
		$forceXHR = setXHRDebug($this, 0);
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';				
		
		// paginate 
		$SOURCE_MODEL = $this->Workorder->field('source_model', array('id'=>$id));
		if (!$id || !$SOURCE_MODEL) {
			$this->Session->setFlash("ERROR: invalid Workorder id.");
			$this->redirect(array('action' => 'all'));
		}
		$paginateModel = 'Asset';
		$Model = ClassRegistry::init($paginateModel);
		// map correct paginateArray based on $SOURCE_MODEL
		$this->paginate[$paginateModel] = $this->paginate[$SOURCE_MODEL.$paginateModel];
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $Model->getPaginatePhotosByWorkorderId($id, $this->paginate[$paginateModel]);
if (isset($this->params['url']['raw'])) {
	$paginateArray['extras']['show_hidden_shots']=1;
	$paginateArray['extras']['hide_SharedEdits']=1;
}			
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

	/**
	 * workorder version of /assets/home
	 * 	set ACL using workorder assignment, not permissionable
	 */
	function snap($id=null) {
		
	}
	
}
?>