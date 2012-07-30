<?php
class TasksWorkordersController extends AppController {

	public $name = 'TasksWorkorders';
	public $layout = 'snappi';
	
	public $scaffold;
	
	public static $test = array();
	function __construct() {
       parent::__construct();
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
			'order' => array('batchId'=>'DESC', 'created'=>'ASC'),
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
		$myAllowedActions = array(	);
		$this->Auth->allow( array_merge($this->Auth->allowedActions , $myAllowedActions));
		if (!empty($this->passedArgs[0])) {
			$this->__saveWorkorderToSession($this->passedArgs[0]);
		}	
		$this->TasksWorkorder->Asset->Behaviors->detach('Permissionable'); // mutually exclusive	
		
		/*
		 * for testing only
		 */ 
		 if (in_array(AppController::$role, array('EDITOR', 'MANAGER')) === false) {
			throw new Exception("Error: TasksWorkorder actions require Role privileges");
 		}
	}

	function beforeRender(){
		
	}
	
	function __saveWorkorderToSession($twoid){
		$options = array(
			'contain'=>array('Workorder'),
			'conditions'=>array('TasksWorkorder.id'=>$twoid),
		);
		$row = $this->TasksWorkorder->find('first', $options);
		if (empty($row)) return;
		
		// Save active Workorder to Session for POST processing through other controllers 
		Session::write("WMS.{$twoid}.Workorder", $row['Workorder']);
		$this->__setOwnerId($twoid, $row);
	}
	
	function __setOwnerId ($twoid, $data=null){
		// Save active Workorder to Session for POST processing through other controllers 
		if (empty($data)) $data = Session::read("WMS.{$twoid}");
		if ($data['Workorder']['source_model']=='User') AppController::$ownerid = $data['Workorder']['source_id']; 
		if ($data['Workorder']['source_model']=='Group') AppController::$ownerid = $data['Workorder']['client_id'];
	}
	
	/**
	 * from HTTP POST
	 * 		$this->data[manager_id]=UUID
	 * 		$this->data[task_id]=UUID (optional), TasksWorkorder.id
	 *		$this->data[editor_id]=UUID (optional)
	 * assign EDITOR TO TasksWorkorder, grants access privileges to TasksWorkorder assets
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
				$editor_id =  AppController::$userid;
			}		
		}
		try {
			// extract($this->data); // manager_id, editor_id, task_id
			$this->TasksWorkorder->id = $id;
			$ret = $this->TasksWorkorder->saveField('operator_id', $editor_id);
			if ($ret) $message[] = "TasksWorkorder {$id}: operator set to {$editor_id}";
			else throw new Exception("Error saving editor assignment, twoid={$id}", 1);
			
			// format json response
			$success = $ret;
			$response = compact('id', 'task_id', 'editor_id');
		}catch(Exception $e) {
			$success = false;
			$message[] = $e->getMessage();
		}
		
		// admin only
		if (strpos(env('HTTP_REFERER'),'/workorders/all')>1) {	// Admin only
			$this->redirect(env('HTTP_REFERER'), null, true);
		}
		
		$this->viewVars['jsonData'] = compact('success', 'message', 'response');
		$done = $this->renderXHRByRequest('json', null , null);
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false
		
		$this->render('/elements/dumpSQL');
	}
	
	function assignment($twoid) {
		$assets = $this->TasksWorkorder->getAssets($twoid);
debug($assets);		
		$this->render('/elements/dumpSQL');
	}
	
	/**
	 * harvest new AssetsWorkorder to [new|existing] TaskWorkorder
	 */
	function harvest($id) {
		if (empty($this->data)) {
			// throw new Exception("Error: HTTP POST required", 1);
		}
		$options = array(
			'recursive'=>-1,
			'conditions'=>array('`TasksWorkorder`.id'=>$id)
		);
		$data = $this->TasksWorkorder->find('first', $options);
		if (!$data) throw new Exception("Error TasksWorkorder not found, id={$id}", 1);
		
		//TODO: add TasksWorkorder.in_batches Boolean. better field name??? realtime? 
		// to specify if new Assets should be added as a NEW TasksWorkorder for same Task
		$data['TasksWorkorder']['in_batches'] = true;
		
		$assets = $this->TasksWorkorder->harvestAssets($data['TasksWorkorder']['task_id'], $data['TasksWorkorder']['workorder_id'], 'NEW');
		if (!empty($assets)) {
			// NEW assets found, create NEW TasksWorkorder for NEW assets (realtime wo processing)
			if ($data['TasksWorkorder']['in_batches']) {
				$options = array_filter_keys($data['TasksWorkorder'], array('workorder_id', 'task_id', 'task_sort'));
				$taskWorkorder = $this->TasksWorkorder->createNew($options);
				$ret = $this->TasksWorkorder->addAssets($taskWorkorder, $assets);
				if (!$ret) throw new Exception("Error adding new Assets to AssetsTasks, twoid={$id}", 1);
			} else { // add NEW Assets to existing TasksWorkorder
				$ret = $this->TasksWorkorder->addAssets($taskWorkorder, $assets);
				if (!$ret) throw new Exception("Error harvesting new Assets to AssetsTasks, twoid={$id}", 1);
			}
		}
		$this->Session->setFlash(is_numeric($ret) ? $ret : 0 ." new Snaps found.");
		// admin only
		if (strpos(env('HTTP_REFERER'),'/workorders/all')>1) {	// Admin only
			$this->redirect(env('HTTP_REFERER'), null, true);
		}
		
		$this->render('/elements/dumpSQL');
	}
	
	function photos($id = null){
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
		$SOURCE_MODEL = Session::read("WMS.{$id}.Workorder.source_model"); // TasksWorkordersController::$source_model;
		$displayName = $SOURCE_MODEL=='User' ? 'Person' : 'Group';
		Configure::write('controller.label', $displayName);					// for section-header
		$paginateModel = 'Asset';
		$Model = ClassRegistry::init($paginateModel);
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
	// $paginateArray['extras']['bestshot_system_only']=1;
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
	
	/**
	 * workorder version of /assets/home
	 */
	function snap($id = null){
		$FILMSTRIP_LIMIT = 999;
		$forceXHR = setXHRDebug($this, 0);
		Configure::write('controller.label', 'Photo');
		Configure::write('controller.titleName', 'Photos');
		/*
		 * navFilmstrip processing
		 */
		if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
		$ccid = (isset($this->params['url']['ccid'])) ? $ccid = $this->params['url']['ccid'] : null;
		if ($ccid) {
			$castingCall = $this->CastingCall->cache_Refresh($ccid, array('perpage_on_cache_stale'=>$FILMSTRIP_LIMIT));
	// debug($ccid);			exit;
	// debug($castingCall['CastingCall']['Request']); 	
	// debug(Session::read('castingCall'));exit;	
			$this->viewVars['jsonData']['castingCall'] = $castingCall;
			$done = $this->renderXHRByRequest('json', null, null , 0);
		
			if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false
			
			if (!$castingCall) {
				// handle cacheMiss, drop $ccid from request
				$this->redirect(Router::url($this->passedArgs));
			}
		}
		/*
		 * navFilmstrip done
		 */
		
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'asset'));
			$this->redirectSafe();
		}		
		/*
		 * get Permissionable associated data manually, add paging
		 */
		if (!empty($this->params['url']['shotType'])) {  
			$shotType = $this->params['url']['shotType'];
		} else if (!empty($castingCall['CastingCall']['Auditions']['ShotType'])) {
			$shotType = $castingCall['CastingCall']['Auditions']['ShotType'];
		} else {
			$shotType = 'Usershot';
		}
		$options = array(
			'conditions'=>array('Asset.id'=>$id),
			'contain'=> array('Owner.id', 'Owner.username', 'ProviderAccount.id', 'ProviderAccount.provider_name', 'ProviderAccount.display_name'),
			'fields'=>'Asset.*',		// MUST ADD 'fields' for  containable+permissionable
			'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>$shotType, 		// join shots to get shot_count?
				'join_bestshot'=>false,			// do NOT need bestShots when we access by $asset_id
				'show_hidden_shots'=>true,		// by $asset_id, hidden shots ok, or DONT join_bestshot
			),
		);
		$data = $this->TasksWorkorder->Asset->find('first', $options);
//debug($data);		
		if (empty($data)) {
			$this->Session->setFlash(sprintf(__('No %s found.', true), 'Photos'));
			$this->redirectSafe();
		} else {
			$this->set('data', $data);	
			$this->viewVars['jsonData']['Asset'][]=$data['Asset'];
			Session::write('lookup.owner_names', Set::merge(Session::read('lookup.owner_names'), Set::combine($data, '/Owner/id', '/Owner/username')));
			if (empty($castingCall['CastingCall'])) {
				// cache miss, build a new castingCall with one photo
				if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
				$castingCall = $this->CastingCall->getCastingCall(array($data['Asset']), false);
				$this->viewVars['jsonData']['castingCall'] = $castingCall; 
			} 
		}
		
		$this->viewPath = 'workorders';		
	}
}
?>