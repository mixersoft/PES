<?php
class TasksWorkordersController extends AppController {

	public $name = 'TasksWorkorders';
	public $layout = 'snappi';
	public $viewPath = 'workorders';			// same views as workorders
	public $titleName = 'Workorder Tasks';
	public $displayName = 'Workorder Tasks';	// section header
	public $scaffold;
	
	public static $test = array();
	function __construct() {
       parent::__construct();
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
				'group_as_shot_permission'=>false,
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
				'group_as_shot_permission'=>false,
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
		$this->Auth->allow( $myAllowedActions);
		if (!empty($this->passedArgs[0])) {
			$this->__saveWorkorderToSession($this->passedArgs[0]);
		}	
		$this->TasksWorkorder->Asset->Behaviors->detach('Permissionable'); // mutually exclusive	
		/*
		 * for testing only
		 */ 
		 if (AppController::$role && in_array(AppController::$role, array('EDITOR', 'MANAGER')) === false) {
			$this->Session->setFlash("Error: Workorder actions require Role privileges");
			$this->redirect('/users/signin', null, true);
 		} 
	}

	function beforeRender(){
		parent::beforeRender();
	}
	
	/**
	 * save Workorder to Session::write("WMS.{$twoid}.Workorder") 
	 * - required for activating WorkorderPermissionable in /photos/setprop
	 */
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

	/**
	 * create shots from SCRIPT
	 * TODO: should this be a queued process, i.e. from gearmand, etc.
	 * 	NOTE: use WorkorderPermissionable, not available to end users
	 * 
	 * 
	 * same as action=photos except
	 *		$paginateArray['extras']['show_hidden_shots']=1;
	 *		$paginateArray['extras']['hide_SharedEdits']=1;	// TODO:??? use score, if any, for bestshot here? 
	 * 		$paginateArray['perpage']=999;					// TODO: how do we deal with paging?
	 * 		JSON output only
	 */
	function image_group($id) {
		// $forceXHR = setXHRDebug($this, 0);
		/*
		 * test and debug code. $config['isDev'], 
		 * hostname = snappi-dev or dev.snaphappi.com
		 */ 
		if (Configure::read('isDev')) {
			if (!isset($this->passedArgs['reset']) || !empty($this->passedArgs['reset'])) {
				// default is to delete old SCRIPT shots, use /reset:0 to preserve
				/*
				 *  NOTE: snappi-dev use: 		delete snappi.usershots `s`, snappi.assets_usershots `au`
				 * 		dev.snaphappi.com use: 	delete `s`, `au`
				 */ 
				$multiDelete = (Configure::read('Config.os')=='win') ? 'delete snappi.usershots `s`, snappi.assets_usershots `au`' : 'delete `s`, `au`';
				$reset_SQL = "
	{$multiDelete}
	from snappi.usershots s
	join snappi.users u on u.id = s.owner_id
	join snappi.assets_usershots au on au.usershot_id = s.id
	join snappi_wms.assets_workorders aw on aw.asset_id = au.asset_id
	where s.priority = 30
	  and u.username like 'image-group%'
	  and aw.workorder_id ='{$id}';";
	  			$this->TasksWorkorder->query($reset_SQL);
			}
			
			if ($this->RequestHandler->ext !== 'json') Configure::write('debug',0);	// DEBUG
			// debug: see test results
			$markup = "<A href=':url' target='_blank'>click here</A>";
			$show_shots['see-all-shots'] = str_replace(':url',Router::url(array('action'=>'shots', 0=>$id, 'perpage'=>10,  'all-shots'=>1), true), $markup);
			$show_shots['see-only-script-shots'] = str_replace(':url',Router::url(array('action'=>'shots', 0=>$id, 'perpage'=>10, 'only-script-shots'=>1), true), $markup);
			debug($show_shots);
		}
		/*
		 * end test and debug code
		 */
		 
		$perpage = !empty($this->passedArgs['perpage']) ? $this->passedArgs['perpage'] : 999;
		$required_options['extras']['join_shots'] = false;
		$required_options['extras']['show_hidden_shots']=1;
		$required_options['extras']['hide_SharedEdits']=0;
		// $default_options['extras']['only_bestshot_system']=0;
				 
		// paginate 
		$SOURCE_MODEL = Session::read("WMS.{$id}.Workorder.source_model"); // WorkordersController::$source_model;
		$paginateModel = 'Asset';
		$Model = ClassRegistry::init($paginateModel);
		// map correct paginateArray based on $SOURCE_MODEL
		$this->paginate[$paginateModel] = $this->paginate[$SOURCE_MODEL.$paginateModel];
		$this->paginate[$paginateModel]['perpage'] = $perpage; 	// get all photos for image-group processing	
		$Model->Behaviors->attach('Pageable');
		$Model->Behaviors->attach('WorkorderPermissionable', array('type'=>$this->modelClass, 'uuid'=>$id));
		$paginateArray = Set::merge($this->paginate[$paginateModel], $required_options);
		// TODO: add /raw:1 to filter by UserEdit.rating only, and skip SharedEdit.score
		$paginateArray['extras']['group_as_shot_permission'] = $Model->Behaviors->attached('WorkorderPermissionable');
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		/*
		 *  force sort=dateTaken for image-group
		 */ 
		$paginateArray['order'] = array('`Asset`.dateTaken');
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData = $this->paginate($paginateModel);
		$pageData = Set::extract($pageData, "{n}.{$paginateModel}");
		// end paginate
		/*
		 * get image_group output for castingCall as JSON string
		 */ 
		if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
		if (!isset($this->Gist)) $this->Gist = loadComponent('Gist', $this); 
		$castingCall = $this->CastingCall->getCastingCall($pageData, $cache=false);
		
		// bind $script_owner to image-group runtime settings 
		$script_owner = empty($this->passedArgs['circle']) ? 'image-group' : 'image-group-circles';
		$preserveOrder = $script_owner == 'image-group';
	
		$image_groups = $this->Gist->getImageGroupFromCC($castingCall, $preserveOrder);
		/*
		 * import image_group output as Usershots with correct ROLE/priority
		 * use Usershot.priority=30
		 */ 
		// use ROLE=SCRIPT, Usershot.priority=30
		$ScriptUser_options = array(
			'conditions'=>array(
				'primary_group_id'=>Configure::read('lookup.roles.SCRIPT'),
				'username'=>$script_owner,
			)
		);
		$data = ClassRegistry::init('User')->find('first', $ScriptUser_options);
		// change user to role=SCRIPT
		AppController::$userid = $data['User']['id'];
		AppController::$role = 'SCRIPT'; 		// from conditions, disables assignment check in WorkordersPermissionable
		// create Script Usershots
		$newShots = array();
		$Usershot = ClassRegistry::init('Usershot');
		/**
		 * Q: should we delete all Shots owned by image-group first?
		 */
		foreach($image_groups['Groups'] as $i => $groupAsShot_aids) {
			
			// debug
			// if ($i > 5) break;
			
			if (count($groupAsShot_aids)==1) {
				unset($image_groups['Groups'][$i]); 
				continue;		// skip if only one uuid, group of 1
			}
			$result = $Usershot->groupAsShot($groupAsShot_aids, $force=true);
			if ($result['success']) {
				$newShots[] = array(
					'asset_ids'=>$groupAsShot_aids, 
					'shot'=>$result['response']['groupAsShot'],
				);
			} else {
				$newShots[] = array(
					'asset_ids'=>$groupAsShot_aids, 
					'shot'=>$result['message'],
				);
			}
			
		}
		
		if ($this->RequestHandler->ext == 'json') {
			// debug GistComponent output		
			$image_groups = json_encode($image_groups);
			$this->log(	"GistComponent->getImageGroupFromCC(): filtered output", LOG_DEBUG);
			$this->log(	$image_groups, LOG_DEBUG);
			debug($image_groups);
			debug($newShots);
			
			$this->viewVars['jsonData']['imageGroups'] = $newShots;
			// $this->viewVars['jsonData']['castingCall'] = $castingCall;
			$done = $this->renderXHRByRequest('json');
			if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false	
		} else {
			// $this->render('/elements/dumpSQL');
			$this->Session->setFlash(count($newShots)." duplicate Shots found");
			$next = $_SERVER['HTTP_REFERER'];
			$this->redirect($next, null, true);		
		}
	
	}

	/**
	 * /tasks_workorder/shots get all shots for tasks_workorder, for reviewing SCRIPT shots, uses WorkorderPermissionable for ACL
	 * 		renders shots as a series of filmstrips
	 * @param $id String, $workorder_id
	 * $passedArgs['all-shots'] = default false, if true, show active shots
	 * 
	 */ 
	public function shots($id) {
		$forceXHR = setXHRDebug($this, 0);
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';				

		// paginate 
		$SOURCE_MODEL = Session::read("WMS.{$id}.Workorder.source_model"); 
		$paginateModel = 'Asset';
		$Model = ClassRegistry::init($paginateModel);
		// map correct paginateArray based on $SOURCE_MODEL
		$this->paginate[$paginateModel] = $this->paginate[$SOURCE_MODEL.$paginateModel];
		$Model->Behaviors->attach('Pageable');
		$Model->Behaviors->attach('WorkorderPermissionable', array('type'=>$this->modelClass, 'uuid'=>$id));
		$paginateArray = $this->paginate[$paginateModel];
		
/*
 * operator options:
 * 		= hide score, editor not influenced by existing score
 * 		= show ONLY UserEdit.rating, by AppController::userid
 * 		= show hidden shots, why? for training sets, but required for workorders?
 * 		?? = join to BestShotSystem only, for workorder processing?
 * 		
 */ 
 	$paginateArray['extras']['show_hidden_shots'] = 0;
	$paginateArray['extras']['only_shots'] = 1;	
	$paginateArray['extras']['only_bestshot_system'] = 1;	
	$paginateArray['extras']['show_inactive_shots'] = !empty($this->passedArgs['all-shots']);
	if (!empty($this->passedArgs['only-script-shots'])) {
		$paginateArray['extras']['show_inactive_shots'] = 1;
		$paginateArray['extras']['only_shots'] = 1;	
		$paginateArray['extras']['shot-priority'] = 'SCRIPT';
	};
		
		$paginateArray['extras']['group_as_shot_permission'] = $Model->Behaviors->attached('WorkorderPermissionable');	
		
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData = $this->paginate($paginateModel);
		$raw_shots = Set::extract($pageData, "{n}.Shot");	// for PAGE.jsonData.Shot
		$pageData = Set::extract($pageData, "{n}.{$paginateModel}");
		// end paginate
		if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
		$castingCall = $this->CastingCall->getCastingCall($pageData);
		$this->viewVars['jsonData']['castingCall'] = $castingCall;
		
		/*
		 * add Shot data inline
		 */ 
		$shotIds = Set::extract("/shot_id",$pageData);
		$shotType = $SOURCE_MODEL=='User' ? 'Usershot' : 'Groupshot';
		$paginateAlias = 'Shot';		// store paginate data/results under this key
		
// TODO: cannot use habtm Alias because Fields uses Model->name=Asset for the From table	
// 			should we fix this to make a cleaner implementation?	
		// $habtm['hasAndBelongsToMany'][$paginateAlias]=$this->Workorder->hasAndBelongsToMany['Asset'];
		// $this->Workorder->bindModel($habtm);
		// $this->Workorder->{$paginateAlias}->Behaviors->attach('WorkorderPermissionable', array('type'=>$this->modelClass, 'uuid'=>$id));
		
		// this version uses paginate('Asset'), but manually places paging data under a different key, ['PageableAlias']
		// TODO: fix ['PageableAlias'] and ['$paginateCacheKey'] overlap
		$shot_paginateArray = $this->paginate[$SOURCE_MODEL.$paginateModel]; //array_merge($this->paginate[$SOURCE_MODEL.$paginateModel], $this->paginate[$paginateModel]['extras']); 
		$shot_paginateArray =  $Model->getPaginatePhotosByShotId($shotIds, $shot_paginateArray, $shotType);
		$shot_paginateArray['PageableAlias'] = $paginateAlias;					// Pageable?
		$shot_paginateArray['extras']['$paginateCacheKey'] = $paginateAlias;	// AppModel
		$this->paginate[$paginateAlias] = $Model->getPageablePaginateArray($this, $shot_paginateArray, $paginateAlias);
		Configure::write("paginate.Options.{$paginateAlias}.limit", 999);			// Pageable?
// We need to preserve the paging counts under a different key, and restore the original paging Counts for Assets		
$paging[$paginateModel] = $this->params['paging'][$paginateModel];		
		$shotData = $this->paginate($paginateModel);		// must paginate using Model->name because of how fields and conditions are set up
		$shotData = Set::extract($shotData, "{n}.{$paginateModel}");
$paging[$paginateAlias] = $this->params['paging'][$paginateModel];
$this->params['paging'] = $paging;
		Configure::write('paginate.Model', $paginateModel);		// original paging Counts for Asset, not Shot
		
		$this->viewVars['jsonData']['shot_CastingCall'] = $this->CastingCall->getCastingCall($shotData);
		// extract Shot data from Assets
		foreach ($raw_shots as $i=>$row) {
			$shot = array('id'=>$row['shot_id']);
			$shot['owner_id'] = $row['shot_owner_id'];
			$shot['priority'] = $row['shot_priority'];
			$shot['count'] = $row['shot_count'];
			$shot['active'] = $row['shot_active'];
			$shots[$row['shot_id']] = $shot;
		}
		$this->viewVars['jsonData']['shot_CastingCall']['shot_extras'] = $shots;
		$done = $this->renderXHRByRequest('json', '/elements/photo/roll');
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false	

				
		/*
		 * render page
		 */
		 
		$options = array(
			'contain'=>array(
				'Task',
				'Workorder'=>array(
					'Source',
					'Client',
				)),
			'conditions'=>array('TasksWorkorder.id'=>$id)
		);
		$data = $this->TasksWorkorder->find('first', $options);
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
			// Session::write('lookup.owner_names', Set::merge(Session::read('lookup.owner_names'), Set::combine($data, '/Owner/id', '/Owner/username')));
			
			
			$description = array();
			$description['id'] = "t:{$data['TasksWorkorder']['id']}"; 
			$description['tw-type'] = $data['Task']['name']; 
			$description['wo-type'] = strtolower($data['Workorder']['source_model']);
			$description['wo-label'] = $data['Workorder']['Source']['label'];
			$description['wo-count'] = $data['TasksWorkorder']['assets_task_count'];
			$this->set('description', $description);
		}
		$this->set(array('assets'=>$data,'class'=>'Asset'));
		// $this->render('/elements/dumpSQL');		
		
	}

	function _addEventsAsShots ($event_groups, $cc){
		// var shotId = o.Audition.SubstitutionREF;
		$i=0;
		$Auditions = $cc['CastingCall']['Auditions']['Audition'];
		$event_Auditions = array();
		foreach ($event_groups['Events'] as $event ){
			$first = $event['FirstPhotoID'];
			$count = $event['PhotoCount'];
			$counting = false;
// debug("$first : $count i={$i}, next={$Auditions[$i]['id']} ");			
			// iterate through $cc Auditions and set ShotId
			for ($i; $i< count($Auditions); $i++ ){
				if ($Auditions[$i]['id']==$first) {
					$counting = $count-1;
					$Auditions[$i]['SubstitutionREF'] = $first;
					$Auditions[$i]['Shot']['id'] = $first;
					$Auditions[$i]['Shot']['count'] = $count;
					$event_Auditions[] =  $Auditions[$i];
					continue;
				} 
				if ($counting>0) {
					$counting--;
					$Auditions[$i]['SubstitutionREF'] = $first;
					$Auditions[$i]['Shot']['id'] = $first;
					$Auditions[$i]['Shot']['count'] = $count;
					continue;
				} else if ($counting === false){
					$event_Auditions[] =  $Auditions[$i];		// not in any event
debug("$first : $count i={$i}, single={$Auditions[$i]['id']} ");					
				} else {
					// this should be the first item of the NEXT event
					break;
				}
			}
		}
		$cc['CastingCall']['Auditions']['Audition'] = $Auditions;
		$this->viewVars['jsonData']['shot_CastingCall'] = $cc;		// copy complete with Shots added
		
		$cc['CastingCall']['Auditions']['Audition'] = $event_Auditions;	// just Shots
		return $cc;
	}

	/**
	 * create event groups from SCRIPT
	 * TODO: should this be a queued process, i.e. from gearmand, etc.
	 * 	NOTE: use WorkorderPermissionable, not available to end users
	 * 
	 * 
	 * same as action=photos except
	 *		$paginateArray['extras']['show_hidden_shots']=1;
	 *		$paginateArray['extras']['hide_SharedEdits']=1;	// TODO:??? use score, if any, for bestshot here? 
	 * 		$paginateArray['perpage']=999;					// TODO: how do we deal with paging?
	 * 		JSON output only
	 */
	function event_group($id) {
		$forceXHR = setXHRDebug($this, 0);
		/*
		 * test and debug code. $config['isDev'], 
		 * hostname = snappi-dev or dev.snaphappi.com
		 */ 
		if (Configure::read('isDev')) {
			if (!isset($this->passedArgs['reset']) || !empty($this->passedArgs['reset'])) {
				// default is to delete old SCRIPT shots, use /reset:0 to preserve 
				$reset_SQL = "";
	  			// $this->Workorder->query($reset_SQL);
			}
			
			// if ($this->RequestHandler->ext !== 'json') Configure::write('debug',0);	// DEBUG
			// debug: see test results
			// $markup = "<A href=':url' target='_blank'>click here</A>";
			// $show_shots['see-all-shots'] = str_replace(':url',Router::url(array('action'=>'shots', 0=>$id, 'perpage'=>10,  'all-shots'=>1), true), $markup);
			// $show_shots['see-only-script-shots'] = str_replace(':url',Router::url(array('action'=>'shots', 0=>$id, 'perpage'=>10, 'only-script-shots'=>1), true), $markup);
			// debug($show_shots);
		}
		/*
		 * end test and debug code
		 */
		 
		$perpage = !empty($this->passedArgs['perpage']) ? $this->passedArgs['perpage'] : 999;
		$required_options['extras']['join_shots']=0;			
		$required_options['extras']['show_hidden_shots']=1;
		$required_options['extras']['hide_SharedEdits']=0;
		// $default_options['extras']['only_bestshot_system']=0;
				 
		// paginate 
		$SOURCE_MODEL = Session::read("WMS.{$id}.Workorder.source_model"); // WorkordersController::$source_model;
		$paginateModel = 'Asset';
		$Model = ClassRegistry::init($paginateModel);
		// map correct paginateArray based on $SOURCE_MODEL
		$this->paginate[$paginateModel] = $this->paginate[$SOURCE_MODEL.$paginateModel];
		$this->paginate[$paginateModel]['perpage'] = $perpage; 	// get all photos for image-group processing
		$Model->Behaviors->attach('Pageable');
		$Model->Behaviors->attach('WorkorderPermissionable', array('type'=>$this->modelClass, 'uuid'=>$id));
		$paginateArray = Set::merge($this->paginate[$paginateModel], $required_options);
		// TODO: add /raw:1 to filter by UserEdit.rating only, and skip SharedEdit.score
		$paginateArray['extras']['group_as_shot_permission'] = $Model->Behaviors->attached('WorkorderPermissionable');
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		/*
		 *  force extras & sort=dateTaken for event-group
		 */ 
		$paginateArray['order'] = array('`Asset`.dateTaken');
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData = $this->paginate($paginateModel);
		$pageData = Set::extract($pageData, "{n}.{$paginateModel}");
		// end paginate

		/*
		 * import event_group output as Usershots with correct ROLE/priority
		 */ 
		if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
		if (!isset($this->Gist)) $this->Gist = loadComponent('Gist', $this); 
		$castingCall = $this->CastingCall->getCastingCall($pageData, $cache=false);
// debug($castingCall);		
		$timescale = empty($this->passedArgs['timescale']) ? null: $this->passedArgs['timescale'];
		$event_groups = $this->Gist->getEventGroupFromCC($castingCall, $timescale);
		$castingCall = $this->_addEventsAsShots($event_groups, $castingCall);		
	
		/*
		 * import event_group output as Usershots with correct ROLE/priority
		 * use Usershot.priority=30
		 */ 
		$script_owner = empty($this->passedArgs['circle']) ? 'image-group' : 'image-group-circles';
		$ScriptUser_options = array(
			'conditions'=>array(
				'primary_group_id'=>Configure::read('lookup.roles.SCRIPT'),
				'username'=>$script_owner,
			)
		);
		$data = ClassRegistry::init('User')->find('first', $ScriptUser_options);
		// change user to role=SCRIPT
		AppController::$userid = $data['User']['id'];
		AppController::$role = 'SCRIPT'; 		// from conditions, disables assignment check in WorkordersPermissionable
		// create Script Usershots
		$newShots = array();
		$Usershot = ClassRegistry::init('Usershot');
		/**
		 * Save event groups. where(?)
		 */
		// foreach($event_groups['Groups'] as $i => $groupAsShot_aids) {
		// }
		
		if ($this->RequestHandler->ext == 'json') {
			$this->viewVars['jsonData']['eventGroups'] = $event_groups;
			$this->viewVars['jsonData']['castingCall'] = $castingCall;
			// debug GistComponent output		
			$this->log(	"GistComponent->getImageGroupFromCC(): filtered output", LOG_DEBUG);
			$this->log(	json_encode($event_groups), LOG_DEBUG);
			// debug($event_groups);
			// $this->viewVars['jsonData']['castingCall'] = $castingCall;
			$done = $this->renderXHRByRequest('json');
			if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false	
		} else {
			$this->viewVars['jsonData']['eventGroups'] = $event_groups;
			$this->viewVars['jsonData']['castingCall'] = $castingCall;
			$options = array(
				'contain'=>array(
					'Task',
					'Workorder'=>array(
						'Source',
						'Client',
					)),
				'conditions'=>array('TasksWorkorder.id'=>$id)
			);
			$data = $this->TasksWorkorder->find('first', $options);
			$data = array_merge($this->Auth->user(), $data);
			
			$this->viewVars['jsonData']['Workorder'][]=$data['Workorder'];
			$this->viewVars['jsonData']['TasksWorkorder'][]=$data['TasksWorkorder'];
			// Session::write('lookup.owner_names', Set::merge(Session::read('lookup.owner_names'), Set::combine($data, '/Owner/id', '/Owner/username')));
			
			
			$description = array();
			$description['id'] = "t:{$data['TasksWorkorder']['id']}"; 
			$description['tw-type'] = $data['Task']['name']; 
			$description['wo-type'] = strtolower($data['Workorder']['source_model']);
			$description['wo-label'] = $data['Workorder']['Source']['label'];
			$description['wo-count'] = $data['TasksWorkorder']['assets_task_count'];
			$this->set(compact('description', 'data'));	
		}
	
	}	
	
	/**
	 * show TasksWorkorder as photo gallery
	 * TODO: add Flagged status for individual photos
	 */
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
if (!empty($this->passedArgs['only-shots'])) {
		$paginateArray['extras']['only_shots']=1;
}
if (!empty($this->passedArgs['all-shots'])) {
	$paginateArray['extras']['show_inactive_shots']=1;		// includes Shot.inactive=0	
	$paginateArray['extras']['only_shots']=1;
}	
		$paginateArray['extras']['show_flags']=1;
		
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
			'contain'=>array(
				'Task',
				'Workorder'=>array(
					'Source',
					'Client',
				)),
			'conditions'=>array('TasksWorkorder.id'=>$id)
		);
		$data = $this->TasksWorkorder->find('first', $options);		 
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
			
			$description = array();
			$description['id'] = "t:{$data['TasksWorkorder']['id']}"; 
			$description['tw-type'] = $data['Task']['name']; 
			$description['wo-type'] = strtolower($data['Workorder']['source_model']);
			$description['wo-label'] = $data['Workorder']['Source']['label'];
			$description['wo-count'] = $data['TasksWorkorder']['assets_task_count'];
			$this->set('description', $description);
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

	/**
	 * flag a TasksWorkorder Asset for later reference and log a status message
	 */
	function flag () {
		$forceXHR = setXHRDebug($this, 0,1);
		$message = $response = array();
		$success = false;
		try {
			if (empty($this->data)) throw new Exception("ERROR: HTTP POST data not found");	
			if (!isset($this->data['flag'])) throw new Exception("ERROR: HTTP POST data invalid");  
			if (!in_array(AppController::$role, array('EDITOR', 'MANAGER'))) throw new Exception("ERROR: You must be an editor to flag a snap");
			
			$Editor = ClassRegistry::init('WorkorderEditor');
			$editor = $Editor->find('first', array('conditions'=>array('`WorkorderEditor`.user_id'=>AppController::$userid)) );
			if (!$editor) throw new Exception("Error: editor not found"); 
			$data['ActivityLog']['editor_id'] = $editor['WorkorderEditor']['id'];

			$find_options['model'] = 'Asset';
			$find_options['foreign_key'] = $this->data['Asset']['id'];
			$find_options['tasks_workorder_id'] = $this->data['TasksWorkorder']['id'];
			// save or update???
			$options['conditions'] = $find_options;
			$found = $this->TasksWorkorder->ActivityLog->find('first', $options);
			if ($found) {
				$data['ActivityLog']['flag_id'] = $found['ActivityLog']['id'];
				if ($this->data['flag']==0) $data['ActivityLog']['parent_flag_status'] = $this->data['flag'];	// clear or set parent flag	
			} else {  	// NEW comment
				$data['ActivityLog'] = array_merge($data['ActivityLog'],$find_options);
				$data['ActivityLog']['flag_status'] = $this->data['flag'];	
			}
			// save data
			$data['ActivityLog']['comment'] = $this->data['message']."asset_id={$this->data['Asset']['id']}";
			// see POST to WMS/activity_logs/add			

						
			$ret = $this->TasksWorkorder->ActivityLog->save($data);
			if (!$ret) throw new Exception("ERROR: there was a problem saving the flagged comment");
			$log = $this->TasksWorkorder->ActivityLog->read(); 

			if (isset($data['ActivityLog']['parent_flag_status'])) {
				$ret2 = $this->TasksWorkorder->ActivityLog->updateParentFlag($log['ActivityLog']['id'], $data['ActivityLog']['parent_flag_status']);
				if (!$ret2) throw new Exception("ERROR: there was a problem updating the flag status of the parent comment");
			}
			$success = true;
			$message[] = 'This item was successfully flagged';
			$response[] = $log;
			$response['flagTarget'] = Router::url(array('action'=>'snap', $this->data['Asset']['id']), true);
		} catch (Exception $e) {
			$message[] = $e->getMessage();
		}
		$this->viewVars['jsonData'] = compact('success', 'message', 'response');
		$done = $this->renderXHRByRequest('json', null, null , 0);
	}
}
?>