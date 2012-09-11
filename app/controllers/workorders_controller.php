<?php
class DuplicateException extends Exception{}

class WorkordersController extends AppController {

	public $name = 'Workorders';
	public $layout = 'snappi';
	public $titleName = 'Workorders';
	public $displayName = 'Workorder';	// section header
	// public $local_components = array('Brownie.panel');
	
	public $scaffold;
	
	public $helpers  = array(
		'Text',
		'Layout',
	);
	
	public static $test = array();
	function __construct() {
       parent::__construct();
	   // $this->components = array_merge($this->components, $this->local_components);
	}

	public $paginate = array(
		'Workorder'=>array(
			'limit'=>2,
			'preview_limit'=>4,
			'paging_limit' =>20,
			'order'=>array('Workorder.work_status'=>'ASC', 'Workorder.created'=>'ASC'),
			'recursive'=> 1,
			'contain'=>array('TasksWorkorder'),
		),
		'GroupAsset'=>array(
			'preview_limit'=>6,
			'paging_limit' =>48,
			'photostream_limit' => 4,	// deprecate?
			'order' => array('batchId'=>'DESC', 'dateTaken_syncd'=>'ASC'),
			'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>'Groupshot', 
				'show_hidden_shots'=>false,
				'group_as_shot_permission'=>'Groupshot',
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
			'order' => array('dateTaken'=>'ASC'),
			'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>'Usershot', 
				'show_hidden_shots'=>false,
				'group_as_shot_permission'=>'Usershot',
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
	
	function beforeFilter(){
		parent::beforeFilter();
		/*
		 *	These actions are allowed for all users
		 */
		$myAllowedActions = array(	);
		$this->Auth->allow( $myAllowedActions);
		if (!empty($this->passedArgs[0])) {
			$this->__saveWorkorderToSession($this->passedArgs[0]);
		}
		$this->Workorder->Asset->Behaviors->detach('Permissionable'); // mutually exclusive	
		
		/*
		 * for testing only
		 */ 
		if (AppController::$role && in_array(AppController::$role, array('EDITOR', 'MANAGER')) === false) {
			$this->Session->setFlash("Error: Workorder actions require Role privileges");
 		} 
		
		/*
		 *  TODO: deprecate, check WorkordersController::$test['task_id']
		 * 		$this->create(), 
		 * 		$this->Workorder->TEST_createTaskWorkorder
		 */ 
		$task = $this->Workorder->Task->find('first');
		WorkordersController::$test['task_id'] = $task['Task']['id']; 
	}

	function __saveWorkorderToSession($woid){
		$row = $this->Workorder->read(null, $woid);
		if (empty($row)) return;
		
		// Save active Workorder to Session for POST processing through other controllers 
		Session::write("WMS.{$woid}.Workorder", $row['Workorder']);
		$this->__setOwnerId($woid, $row);
	}
	
	function __setOwnerId ($woid, $data=null){
		// Save active Workorder to Session for POST processing through other controllers 
		if (empty($data)) $data = Session::read("WMS.{$woid}");
		if ($data['Workorder']['source_model']=='User') AppController::$ownerid = $data['Workorder']['source_id']; 
		if ($data['Workorder']['source_model']=='Group') AppController::$ownerid = $data['Workorder']['client_id'];
	}
	
	function all() {
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		// paginate 
		$paginateModel = 'Workorder';
		$Model = $this->{$paginateModel};
		$this->helpers[] = 'Time';
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $this->paginate[$paginateModel];
		// $paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$rawPageData = $this->paginate($paginateModel);
		$pageData = Set::extract($rawPageData, "{n}.{$paginateModel}");
		// end paginate		
		$this->viewVars['jsonData'][$paginateModel] = $pageData;
		$data[$paginateModel] = $pageData;
		
		// extract hasMany relationships: TasksWorkorders from rawPageData
		$hasManyModel = 'TasksWorkorder';
		$data[$hasManyModel] = Set::combine( $rawPageData, "{n}.{$paginateModel}.id", "{n}.{$hasManyModel}");
		
		// lookups for belongsTo relationships, do NOT page, change to Behavior
		$lookup['Group'] = Set::extract('/Workorder[source_model=Group]/source_id', $data);
		$lookup['User']= Set::extract('/Workorder[source_model=User]/source_id', $data);
		$lookup['Task']= array_unique(Set::extract('/TasksWorkorder/task_id', $rawPageData));

		$manager_ids = Set::extract('/Workorder/manager_id', $data);
		$operator_ids = Set::extract('/TasksWorkorder/operator_id', $rawPageData);
		$lookup['User'] = array_unique(array_merge($lookup['User'], $manager_ids, $operator_ids));
		foreach (array_keys($lookup) as $paginateModel) {
			$Model = ClassRegistry::init($paginateModel);
			$options = array(
				'conditions'=>array("{$paginateModel}.id"=>$lookup[$paginateModel]),
			);
			if ($Model->Behaviors->attached('Permissionable')) {
				$Model->Behaviors->detach('Permissionable');
			}
			$belongsTo = $Model->find('all', $options);
			$data[$paginateModel] = Set::combine( $belongsTo, "{n}.{$paginateModel}.id","{n}.{$paginateModel}");
			// end paginate		
			$this->viewVars['jsonData'][$paginateModel] = $data[$paginateModel];
		}
		
		
		$done = $this->renderXHRByRequest('json');
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false	
		
		
		$this->set(compact('data'));
	}
	
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
		try{
		 	$forceXHR = setXHRDebug($this, 0);	
			if (empty($this->data)) throw new Exception("Error: HTTP POST required", 1);
			
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
				throw new DuplicateException("Warning: existing Workorder for type={source_id={$options['source_model']}}, id={$options['source_id']}", 1);
			} else {		// create new
				$data = $this->Workorder->createNew($options['client_id'], $options['source_id'], $options['source_model'], $options);
				$ret = $this->Workorder->addAssets($data);
				if (is_numeric($ret)) {
					// TODO: for now, create task, but don't use
					$taskWorkorder = $this->Workorder->TEST_createTaskWorkorder($data['Workorder']['id']);
					$ret = $this->Workorder->TasksWorkorder->addAssets($taskWorkorder, "NEW");
				}
			}
			
			// format json response
			$success = true;
			$message = "OK";
			$response = $this->Workorder->read(null, $data['Workorder']['id']);
			$response['next'] = Router::url(array('controller'=>'workorders', 'action'=>'photos', $data['Workorder']['id']), true);
		}catch (DuplicateException $e) {
			//TODO: for now, just assign workorder if it already exists
			$this->Workorder->id = $data['Workorder']['id'];
			if (!empty($options['manager_id'])) $this->Workorder->saveField('manager_id', $options['manager_id'] );
			$success = true;
			$message = "OK";
			$response = $this->Workorder->read(null, $data['Workorder']['id']);
			$response['next'] = Router::url(array('controller'=>'workorders', 'action'=>'photos', $data['Workorder']['id']), true);
			$this->viewVars['jsonData'] = compact('success', 'message', 'response');
		}catch (Exception $e) {
			$message =  $e->getMessage(); 
			
		}
		$this->viewVars['jsonData'] = compact('success', 'message', 'response');
		$done = $this->renderXHRByRequest('json', null , null, $forceXHR);
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false
	}
	
	/**
	 * from HTTP POST
	 * 		$this->data[manager_id]=UUID
	 * 		$this->data[task_id]=UUID (optional), TasksWorkorder.id
	 *		$this->data[editor_id]=UUID (optional)
	 * assign MANAGER TO workorder, grants access privileges to workorder assets
	 * (optional) assign editor to tasksWorkorder if task_id provided
	 * @param $DEVoptions string, use /[id]/me to assign to current user for testing 
	 */ 
	function assign($id, $DEVoptions = null) {
		if (isset($this->data)) {
			extract($this->data); // manager_id, editor_id, task_id
		} else {
			// TODO: testing only
			// if (empty($this->data)) throw new Exception("Error: HTTP POST required", 1);
			if ($DEVoptions == 'me') {
				if (AppController::$role == 'MANAGER') $manager_id = AppController::$userid;
				$editor_id = AppController::$userid;
			}		
		}
		try {
			// extract($this->data); // manager_id, editor_id, task_id
			$this->Workorder->id = $id;
			$ret = $this->Workorder->saveField('manager_id', $manager_id);
			if ($ret) $message[] = "Workorder {$id}: manager set to {$manager_id}";
			else throw new Exception("Error saving manager assignment, woid={$id}", 1);
			
			
			if ($task_id && $editor_id) {
				$this->Workorder->TasksWorkorder->id = $task_id;
				$ret = $this->Workorder->TasksWorkorder->saveField('operator_id', $editor_id);
				if ($ret) $message[] = "Workorder {$id}/Task {$task_id}: operator set to {$editor_id}";
				else throw new Exception("Error saving editor assignment, twoid={$task_id}", 1);
			}
			
			// format json response
			$success = $ret;
			$response = compact('id', 'manager_id', 'task_id', 'editor_id');
		}catch(Exception $e) {
			$success = false;
			$message[] = $e->getMessage();
		}
		
		// admin only
		if (strpos(env('HTTP_REFERER'),'/workorders/all')>1) {	// Admin only
			$this->redirect(env('HTTP_REFERER'), null, true);
		}
		
		
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
	
	/**
	 * harvest new Assets to existing workorder
	 */
	function harvest($id) {
		if (empty($this->data)) {
			// throw new Exception("Error: HTTP POST required", 1);
		}
		$options = array(
			'recursive'=>-1,
			'conditions'=>array('Workorder.id'=>$id)
		);
		$data = $this->Workorder->find('first', $options);
		$count = $this->Workorder->addAssets($data, 'NEW');
		/**
		 * should offer switch to add to TasksWorkorders in batches or not 
		 */
		$this->Session->setFlash(is_numeric($ret) ? $ret : 0 ." new Snaps found.");
		// admin only
		if (strpos(env('HTTP_REFERER'),'/workorders/all')>1) {
			$this->redirect(env('HTTP_REFERER'), null, true);
		}
		$this->render('/elements/dumpSQL');
	}
	
	/**
	 * /workorder/photos grants access privileges to assigned workorder.manager_id
	 * TODO: THERE IS NOT CHECK TO CONFIRM workorder.manager_id role=MANAGER
	 * TODO: for now, just assume manager/editor are equivalent, don't use workorder_tasks  
	 */ 
	function photos($id = null){
		$forceXHR = setXHRDebug($this, 0);
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';				
		if (!$id) {
			$this->Session->setFlash("ERROR: invalid Workorder id.");
			$this->redirect(array('action' => 'all'));
		}

		// paginate 
		$SOURCE_MODEL = Session::read("WMS.{$id}.Workorder.source_model"); // WorkordersController::$source_model;
		$paginateModel = 'Asset';
		$Model = ClassRegistry::init($paginateModel);
		// map correct paginateArray based on $SOURCE_MODEL
		$this->paginate[$paginateModel] = $this->paginate[$SOURCE_MODEL.$paginateModel];
		$Model->Behaviors->attach('Pageable');
		$Model->Behaviors->attach('WorkorderPermissionable', array('type'=>$this->modelClass, 'uuid'=>$id));
		$paginateArray = $this->paginate[$paginateModel];
		
/*
 * operator options
 *   	raw = hide_SharedEdits=1;show_hidden_shots=0
 * 		= hide score, editor not influenced by existing score
 * 		= show ONLY UserEdit.rating, by AppController::userid
 * 		= show hidden shots, why? for training sets, but required for workorders?
 * 		?? = join to BestShotSystem only, for workorder processing?
 * 		
 */ 		
if (!empty($this->passedArgs['raw'])) {
	$paginateArray['extras']['show_hidden_shots']=1;
	$paginateArray['extras']['hide_SharedEdits']=1;
	// $paginateArray['extras']['bestshot_system_only']=0;
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
				
		/*
		 * HACK: special XHR preview for testing WMS app w/Mauro
		 */ 		
		if (!empty($this->params['url']['preview'])
			&& ($this->RequestHandler->isAjax() || $forceXHR)
		) {
				$this->render('XHR/preview', 'ajax');
				return;
		}		
						
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
	 * Training methods, 
	 * TODO: move to training controller
	 * 
	 */
	 /**
	  * clone TaskWorkorder for new operator
	  * NOTES: 
	  * 	- uses harvest(ALL) to add all Assets to TasksWorkorder
	  * 	- assigns task to $operator_id
	  * TODO: OE system should get task_id, but for training, just use fixed value for now
	  */
	 public function train ($woid, $task_id="null", $operator_id=null, $dataset='ALL') {
	 	try {
		 	$forceXHR = setXHRDebug($this, 1);	
			
			if (isset($this->data['Workorder'])) {
				$data = $this->data;
			} else {
				// if (empty($this->data)) throw new Exception("Error: HTTP POST required", 1);
				// NOTE: find existing workorder. REQUIRED
				$data =  $this->Workorder->findById($woid);	
				if (!$data) throw new Exception("Error: workorder not found, id={$woid}");		
			}
			
			$options = array_filter_keys($data['Workorder'], array('source_id', 'source_model', 'client_id', 'manager_id', 'editor_id'));
			// TODO: for now, use AppController::$userid instead of assignment values
			$options['client_id'] = $options['manager_id'] = AppController::$userid;
			// TODO: TESTING ONLY, add to Task,
			if ($task_id=="null") $task_id = WorkordersController::$test['task_id'];
			$task_options = array(
				'task_id'=>$task_id,
				'task_sort'=>0,
				'operator_id' => $operator_id,
			);
			$taskWorkorder = $this->Workorder->TEST_createTaskWorkorder($data['Workorder']['id'], $task_options);
			$ret = $this->Workorder->TasksWorkorder->addAssets($taskWorkorder, $dataset);
			
			// format json response
			$success = true;
			$message = "OK";
			$response = $this->Workorder->read(null, $data['Workorder']['id']);
			$response['next'] = Router::url(array('controller'=>'tasks_workorders', 'action'=>'photos', $taskWorkorder['TasksWorkorder']['id']), true);
			// TODO: return as JSON
$this->redirect($response['next'], null, true);
				
			$this->viewVars['jsonData'] = compact('success', 'message', 'response');
			$done = $this->renderXHRByRequest('json', null , null, $forceXHR);
			if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false
		} catch (Exception $e) {
			$success = false;
			$message =  $e->getMessage(); 
			$this->viewVars['jsonData'] = compact('success', 'message', 'response');
			$done = $this->renderXHRByRequest('json', null , null, $forceXHR);
			
		}
		debug($this->viewVars['jsonData']);
		$this->render('/elements/dumpSQL');
	}
	 
	
	/**
	 * utility methods for testing, test data, etc.
	 */
	 
 	/**
	 * get assets from live site, 
	 * 	export to local filesystem, or 
	 * 	stage to local filesystem
	 */  
	function export($id, $stage = true) {
		set_time_limit(600);
		$baseurl = "http://dev.snaphappi.com".Stagehand::$stage_baseurl;
		if ($stage === true) {
			$dest_basepath = Stagehand::$stage_basepath; 
		} else {
			$dest_basepath = '/home/michael/Downloads/snappi-export/'.$id;		
		}

		$assets = $this->Workorder->getAssets($id);
		// $assets = array_slice($assets, 0,55);
		foreach ($assets as $aid) {
			if ($stage) {
					$data = $this->Workorder->Asset->read('json_src', $aid);
					$json_src = json_decode($data['Asset']['json_src'], true);
					$dest = $dest_basepath.DS.$json_src['root'];
					$preview = str_replace('/', '/.thumbs/', $json_src['root']);
					$src = $baseurl.Stagehand::getImageSrcBySize($preview, 'bp');
					if (!file_exists(dirname($dest))) mkdir(dirname($dest), 0777,true);
					$ret = copy($src,$dest);
					if (!$ret) {
						$errors[]="error: copy {$src} {$dest}";
					} else {
						debug("copy {$src} > {$dest}");
					}
			} else {
				$dest = cleanpath($dest_basepath.DS.$aid.'.jpg');
				if (!file_exists($dest)){
				// copy asset to local folder
					$data = $this->Workorder->Asset->read('json_src', $aid);
					$data = json_decode($data['Asset']['json_src'], true);
					$preview = str_replace('/', '/.thumbs/', $data['root']);
					$src = $baseurl.Stagehand::getImageSrcBySize($preview, 'bp');
					if (!file_exists(dirname($dest))) mkdir(dirname($dest), 0777,true);
					$ret = copy($src,$dest);
					if (!$ret) {
						$errors[]="error: copy {$src} {$dest}";
					} else {
						debug("copy {$src} > {$dest}");
					}
				}
			}
		}
		debug("result={$ret}");
		if (!empty($errors)) debug($errors);
		$this->render('/elements/dumpSQL');
	}

	//  read Asset.json_exif and put into cameraId
	function exif2camera ($woid) {
		$assets = $this->Workorder->getAssets($woid);
		$options = array(
			'conditions' => array(
				'`Asset`.id'=>$assets,
			),
			// 'limit'=>1,
		);
		$Asset = $this->Workorder->Asset; 
		$data = $Asset->find('all', $options);
		foreach ($data as $row) {
			$json_exif = json_decode($row['Asset']['json_exif'], true);
			$camera_string = "{$json_exif['Make']} {$json_exif['Model']}";
			$Asset->id = $row['Asset']['id'];
			$ret = $Asset->savefield('cameraId', $camera_string);
			if (!$ret) {
				$errors[] = "Error saving camera string={$camera_string} for id={$row['Asset']['id']}";
			} else {
				debug("{$row['Asset']['id']}={$camera_string}");
			}
		}
		debug("result={$ret}");
		if (!empty($errors)) debug($errors);
		$this->render('/elements/dumpSQL');
	}
}
?>