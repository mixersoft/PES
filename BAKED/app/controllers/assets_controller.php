<?php
class AssetsController extends AppController {
	public $name = 'Assets';
	
	public $titleName = 'Photos';
	public $displayName = 'Photo';	// section header

	public $components = array(
		'Comments.Comments' => array('userModelClass' => 'User'	),
		'Search.Prg',
	);

	public $helpers  = array(
		'Tags.TagCloud',
		// 'Time',
		'Text',
		'CastingCallJson',
//		'Js' => array('Jquery'),
	);


	public $paginate = array(
		'Asset'=>array(
			'limit' => 16,
			'big_limit' =>48,
			'order'=>array('Asset.dateTaken'=>'ASC'),
			'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>'Usershot', 
				'show_hidden_shots'=>false
			),	
		),
		'Group'=>array(
			'limit' => 8,
			'big_limit' =>36,
			'order'=>array('Group.title'=>'ASC'),
			'recursive'=> -1,
			'fields' =>'Group.*',
		),
		'Comment' =>array(
			'limit'=>3,
			'contain'=>array('User.src_thumbnail', 'User.asset_count', 'User.groups_user_count', 'User.last_login'),
		)
	);
	
	
	/*
	 * search plugin
	 */
//	public $presetVars = array(
//		array('field' => 'q', 'type' => 'value', 'model'=>'Search'),
//	);
	

	function beforeFilter() {
		// only for snaphappi user login, not rpxnow
		parent::beforeFilter();
		/*
		 *	These actions are allowed for all users
		 */
		$this->Auth->allow(
		/*
		 * main
		 */
		'home', 'groups', 'discussion', 'trends', 'hiddenShots', 'substitutes', 'neighbors', 'search', 
		/*
		 * all
		 */'index', 'all', 'most_active', 'most_views', 'most_recent', 'top_rated',   
		/*
		 * actions
		 */'get_asset_info', 'shot',
		/*
		 * experimental
		 */'test', 'addACL'
		);
		AppController::$writeOk = $this->Asset->hasPermission('write', AppController::$uuid);
	}


	function beforeRender() {
		Configure::write('js.bootstrap_snappi', true);
		if (!($this->RequestHandler->isAjax() || $this->RequestHandler->ext == 'json') && AppController::$uuid) {
			$label = !empty($this->viewVars['data']['Asset']['caption']) ? $this->viewVars['data']['Asset']['caption'] : '';
			if (Session::read("lookup.trail.{$this->keyName}.uuid") == AppController::$uuid) {
				Session::write("lookup.trail.{$this->keyName}.label", $label);	
			}
		}
		parent::beforeRender();
	}

	/**
	 * copy previews from original src to staging location
	 */
	function __reimportPhotos(){
		$this->Asset->disablePermissionable();
		$this->Asset->recursive=0;
		$json = $this->Asset->find('all', array('fields'=>'json_src'));
		$json  =Set::extract($json, '/Asset/json_src');
		foreach ($json as $row) {
			$imgSrc = json_decode($row);
			$src = "Summer2009/".basename($imgSrc->orig);
			$dest = $imgSrc->preview;
			$src_baseurl = Configure::read('path.local.preview');
			$dest_baseurl = Configure::read('stageroot.basepath');
			$shell_cmd[] ="cp {$src_baseurl}/{$src} {$dest_baseurl}/{$dest}";
			//			mkdir(dirname(Configure::read('stageroot.basepath')."/{$dest}"), 0777, $recursive=true);
			//			copy( Configure::read('path.local.preview')."/{$src}", Configure::read('stageroot.basepath')."/{$dest}");
		}
		debug($shell_cmd);
	}

	function __unshare($aid, $gids, $checkAuth = true, $updateCount = false) {
		if ($checkAuth) {
			// check for write perm on Asset 
			// for admin/moderator perm on Group use something like groups/unshare/[photo id]
			if (!$this->Asset->hasPermission('write',$aid) ) return false;
		}
		if ($gids && is_string($gids))  $gids = explode(',', $gids );
		// format assets for saveAll
		$AssetsGroup = ClassRegistry::init('AssetsGroup');
		$conditions['asset_id']=$aid;
		if (empty($gids)) {
			// get all gids to updateCount
			$options = array('conditions'=>array('asset_id'=>$aid), 'fields'=>'group_id', 'recursive'=>-1);
			$result = $AssetsGroup->find('all',$options);
			$gids = Set::extract('/AssetsGroup/group_id', $result);
		} else $conditions['group_id']=$gids;

		/*
		 * debug
		 */
		if (!isset($conditions['group_id'])) {
			debug("WARNING: UNSHARE ALL GROUPS IS NOT TESTED");
			$this->Session->setFlash("WARNING: UNSHARE ALL GROUPS IS NOT TESTED");
			return false;
		}
		/*
		 * end debug
		 */
		$ret = $AssetsGroup->deleteAll($conditions, false);
		if ($updateCount && $ret) {
			foreach ($gids as $gid) {
				ClassRegistry::init('group')->updateCounter($gid);
			}
		}
		if($ret){
			/*
			 * check if we have removed (deleted) the last Asset from a ProviderAccount, 
			 * if so, also delete from GroupsProviderAccount
			 */
			// get provider_account_id for removed asset
			$pa_options = array(
				'fields' => array('Asset.provider_account_id'),
				'conditions' => array('Asset.id'=>$aid),
				'permissionable'=>false
			);
			$data = $this->Asset->find('first', $pa_options );
			$providerAccountId = $data['Asset']['provider_account_id'];
			 			
			foreach ($gids as $gid) {
				// check if an asset with the same provider_account_id exists in this group
				$same_provider_account_options = array(
					'conditions' => array('Asset.provider_account_id' => $providerAccountId+1),
					'permissionable'=>false,				
					'joins' => array(
						array('table' => 'assets_groups',
					        'alias' => 'AssetsGroup',
					        'type' => 'INNER',
					        'conditions' => array(
								'AND' => array(
									'AssetsGroup.asset_id = Asset.id',
									'AssetsGroup.group_id' => $gid
								)
					        )
					    )
				    )
				);
				$count = $this->Asset->find('count', $same_provider_account_options);
				if($count == 0){
					// provider_account_id not found, so delete from GroupsProviderAccount
					$ret = $ret && $this->deleteFromGroupsProviderAccount($providerAccountId, $gid);
				}
				// TODO: remove from groupShots
			}
		}
		return $ret != false;
	}
	
	function deleteFromGroupsProviderAccount($providerAccountId, $gid){
		$GroupsProviderAccount = ClassRegistry::init('GroupsProviderAccount');
		$conditions['provider_account_id'] = $providerAccountId;
		$conditions['group_id'] = $gid;
		$ret = $GroupsProviderAccount->deleteAll($conditions, false);
		return $ret;
	}

	function test() {
		/*
		 * tag by batchId
		 */
		$batches_tags = array('1281421709'=>'Oregon',
				'1281422166'=>'SF Bay', 
				'1281422334'=>'New England', 
				'1281422998'=>'DC' );

		// get assets for user=2010
		$options = array(
					'conditions'=>array('Asset.owner_id'=>'4c5f9050-d8bc-4baa-ad2d-03b0f67883f5')
		, 'recursive'=>-1
		, 'fields'=>'Asset.id, Asset.batchId, Asset.json_src, Asset.dateTaken, Asset.owner_id'
		, 'order'=>'dateTaken'
		, 'permissionable'=>false);
		$data = $this->Asset->find('all', $options);
		$batchData = Set::combine($data, '/Asset/id', null, '/Asset/batchId');
		foreach ($batchData as $batchId=>$asset_keys){
			$assetIds = array_keys($asset_keys);
			$tagString = $batches_tags[$batchId];
			//					debug($assetIds);
			foreach($assetIds as $assetId) {
				$ret = $this->__addTag($tagString, $assetId);
				if (!$ret) {
					$errors[] = "Error tagging: {$assetId} => {$tagString}";
				} else $success[] = "tagged: {$assetId} => {$tagString}";
			}
		}
		debug($errors);
		debug($success);
	}

	function index() {
		$this->redirect(array('controller'=>Configure::read('controller.alias'), 'action'=>'all'));
	}

	function most_active(){
		$paginateModel = 'Asset';
		$this->paginate[$paginateModel]['order'] = array('Asset.comment_count'=>'DESC');
		$this->all();
	}
	
	function most_views(){
		$paginateModel = 'Asset';
		$this->paginate[$paginateModel]['order'] = array('Asset.assets_group_count'=>'DESC');
		$this->paginate[$paginateModel]['limit']  = Configure::read('feeds.paginate.perpage');
		$this->all();
	}

	function top_rated(){
		$paginateModel = 'Asset';
		$this->paginate[$paginateModel]['order'] = array('IF(`UserEdit`.rating,`UserEdit`.rating,`SharedEdit`.score)'=>'DESC');
		$this->paginate[$paginateModel]['limit']  = Configure::read('feeds.paginate.perpage');
		$this->all();
	}

	function most_recent(){
		$paginateModel = 'Asset';
		$this->paginate[$paginateModel]['order'] = array('Asset.dateTaken'=>'DESC');
		$this->paginate[$paginateModel]['limit']  = Configure::read('feeds.paginate.perpage');
		$this->all();
	}
	
	function all(){
		// paginate 
		$paginateModel = 'Asset';
		$Model = $this->Asset;
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $this->paginate[$paginateModel];
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		if ($this->action !== 'all') {
			// force perpage set from feeds
			Configure::write('passedArgs.perpage',$this->paginate[$paginateModel]['limit']);
			$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		} else {
			// use action = 'members' for session key
			$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		}			
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate		
		if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
		$castingCall = $this->CastingCall->getCastingCall($pageData);
		$this->viewVars['jsonData']['castingCall'] = $castingCall;
		 
		$this->getLookups(array('Users'=> array_keys(Set::combine($pageData, '/owner_id', ''))));
		$done = $this->renderXHRByRequest('json', '/elements/photo/roll');
		if ($done) return;
		// or autoRender
		$this->action='index';  
	}
	

	function search() {
//		$this->Prg->commonProcess();

		// paginate 
		$paginateModel = 'Asset';
		$Model = $this->Asset;
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $this->paginate[$paginateModel];	
		$named = Configure::read('passedArgs.complete');
		
		if (isset($named['User'])) {
			$paginateArray = $Model->getPaginatePhotosByUserId($named['User'], $paginateArray);
		} else if (isset($named['Group'])) {
			$paginateArray = $Model->getPaginatePhotosByGroupId($named['Group'], $paginateArray);
		} else if (isset($named['Tag'])) {
			$paginateArray = $Model->getPaginatePhotosByTagId($named['Tag'], $paginateArray);
		} else {
			$paginateArray = $Model->getPaginatePhotos($paginateArray);	
		}		
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray, 'members');
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate		
		
		
		if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
		$castingCall = $this->CastingCall->getCastingCall($pageData);
		$this->viewVars['jsonData']['castingCall'] = $castingCall;	
		/*
		 * get Lookups
		 */
		$this->getLookups(array('Users'=> array_keys(Set::combine($pageData, '/owner_id', ''))));
		$done = $this->renderXHRByRequest('json', '/elements/photo/roll');
		if ($done) return;
		// or autoRender
		$this->action='index';  
	}
		
	
	function groups($id){
		$this->helpers[] = 'Time';
				// paginate 
		$paginateModel = 'Group';
		$Model = $this->Asset->{$paginateModel};
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $Model->getPaginateGroupsByPhotoId($id, $this->paginate[$paginateModel]);
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate
		$this->viewVars['jsonData'][$paginateModel] = $pageData;
		
		$done = $this->renderXHRByRequest('json', '/elements/group/roll');
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false	
		
		
		$options = array(
			'conditions'=>array('Asset.id'=>$id),
		);
		$this->Asset->contain(null);		
		$data = @$this->Asset->find('first', $options);
		$data['Group']  = $pageData;
		$this->set('data', $data);	
//		debug($data);
	}	

	
	/**
	 * get castingCall/photos showing hidden shots.
	 * 	- typcially called by XHR, GET for debugging only
	 * @param $id
	 * @param $shotType	string [Usershot| Groupshot] default 'Usershot'
	 * @return unknown_type
	 */
	function hiddenShots($id, $shotType){
		// paginate 
		$paginateModel = 'Asset';
		$Model = $this->{$paginateModel};
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $this->paginate[$paginateModel];	
		
		switch ($this->action) {
			case 'substitutes':
				// not tested
				$paginateArray = $Model->getPaginatePhotos($paginateArray);
				break;
			case 'hiddenShots':
			case 'setprop':	// deprecate	
				$paginateArray = $Model->getPaginatePhotosByShotId($id, $paginateArray, $shotType);
			break;
		}		
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate		
		
		if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
		$castingCall = $this->CastingCall->getCastingCall($pageData);
		$this->viewVars['jsonData']['castingCall'] = $castingCall;
		
		$done = $this->renderXHRByRequest('json', '/elements/assets/hidden_shots', null , 0);
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false
		
		// or $this->autoRender
		// get primary asset, for section label on HTTP request
		$options = array(
			'conditions'=>array('Asset.id'=>$id),
		);
		$this->Asset->contain(null);		
		$data = @$this->Asset->find('first', $options);
		$this->set('asset', $data);	// for PRIMARY asset label
	}	
	

	/**
	 * get CCID from query string, or 
	 * @return mixed 
	 * - FALSE if ccid is given and invalid, 
	 * - null if no ccid given and no mostRecent found
	 * - associative array castingCall   
	 */
	function __getCcid() {
		$ccid = (isset($this->params['url']['ccid'])) ? $ccid = $this->params['url']['ccid'] : null;
		if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
		if ($ccid) {
			if (!$this->Session->check("castingCall.{$ccid}")) {
				return false;	// invalid ccid, not saved in Session
			} 
			$cc = $this->Session->read("castingCall.{$ccid}");
		} else {
			$cc = $this->CastingCall->cache_MostRecent();
		}
		
		if ($cc === null) return null;
		if (isset($cc['Stale'])) {
			$cc = $this->CastingCall->cache_Refresh($ccid);
		} 
		return isset($cc['ID']) ? $cc['ID'] : null;
	}
	
	/**
	 * @deprecated ? see $this->CastingCall->cacheRefresh()
	 *	get and cache a generic/unsorted/unordered castingCall by inference from  env('HTTP_REFERER')
	 */
	function __cache_genericCastingCall($ccid = null) {
			$from = env('HTTP_REFERER');  // example: http://git:88/my/photos
			if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
debug("WARNING: This code path is not tested");			
			return $this->CastingCall->cache_Refresh($from);
			
			// legacy
			$from = explode('/', $from);
			if (isset($from[3])) {
				$type = $from[3];
				if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
				switch ($type) {
					case 'groups':
					case 'circles':
					case 'events':
					case 'weddings':
						// paginate 
						if (isset($from[5])) {
							$id = $from[5];
							$paginateModel = 'Asset';
							$Model = ClassRegistry::init($paginateModel);
							$Model->Behaviors->attach('Pageable');
							$paginateArray = $Model->getPaginatePhotosByGroupId($id, $this->paginate[$paginateModel]);
							$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
							$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
							$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
							// end paginate
						}
						break;						
					case 'my':	
					case 'users':
					default:
						if (isset($from[5])) {
							$id = $from[5];
						} else $id = AppController::$userid;
						// paginate
						$paginateModel = 'Asset';
						$Model = $this->User->{$paginateModel};
						$Model->Behaviors->attach('Pageable');
						$paginateArray = $Model->getPaginatePhotosByUserId($id, $this->paginate[$paginateModel]);
						$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
						$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
						$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
						// end paginate
						break;
				}
				return $castingCall = $this->CastingCall->getCastingCall($pageData, true);		// cache=true	
			}		
			return null;
	}


	/**
	 * get cached CastingCall, extend if necessary for filmstrip Navigation
	 */
	function neighbors($ccid=null) {
		// force JSON request
		if ($this->RequestHandler->ext !== 'json') $this->redirectSafe();
		
		$FILMSTRIP_LIMIT = 999;
		$options = $this->params['named'];
		if (empty($options['perpage'])) $options['perpage'] = $FILMSTRIP_LIMIT;
		/*
		 * navFilmstrip processing
		 */
		if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
		if (empty($ccid) && isset($this->params['url']['ccid'])) $ccid = $this->params['url']['ccid'];
		$castingCall = $this->CastingCall->cache_Refresh($ccid, $options);		
		$this->viewVars['jsonData']['castingCall'] = $castingCall;
		$done = $this->renderXHRByRequest('json', null, null , 0);
		return;
	}


	function home($id = null) {
		$FILMSTRIP_LIMIT = 999;
		$forceXHR = setXHRDebug($this, 0);
	
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
//		$this->Asset->contain('Owner.id', 'Owner.username', 'ProviderAccount.id', 'ProviderAccount.provider_name', 'ProviderAccount.display_name');
		$data = $this->Asset->find('first', $options);
//debug($data);		
		if (empty($data)) {
			$this->Session->setFlash(sprintf(__('No %s found.', true), 'Photos'));
			$this->redirectSafe();
		} else {
			$this->set('data', $data);	
			Session::write('lookup.owner_names', Set::merge(Session::read('lookup.owner_names'), Set::combine($data, '/Owner/id', '/Owner/username')));
			if (empty($castingCall['CastingCall'])) {
				// cache miss, build a new castingCall with one photo
				if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
				$castingCall = $this->CastingCall->getCastingCall(array($data['Asset']), false);
				$this->viewVars['jsonData']['castingCall'] = $castingCall; 
			} 
		}
	}

	function add() {
		if (!empty($this->data)) {
			$this->Asset->create();
			if ($this->Asset->save($this->data)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'asset'));
				$this->redirectSafe();
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'asset'));
			}
		}
		$providerAccounts = $this->Asset->ProviderAccount->find('list');
		$owners = $this->Asset->Owner->find('list');
		$sharedEdits = $this->Asset->SharedEdit->find('list');
		$collections = $this->Asset->Collection->find('list');
		$groups = $this->Asset->Group->find('list');
		$this->set(compact('providerAccounts', 'owners', 'sharedEdits', 'collections', 'groups'));
	}



	function __getPrivacyConfig(){
		/*
		 * configure privacy defaults for radio buttons
		 */
		$privacy['Asset'][519]="<b>Public</b> - are publicly listed and visible to anyone.";
		$privacy['Asset'][71]="<b>Members only </b> - are NOT publicly listed, and are visible only when shared in Groups or Events, and only by Group members.";
		$privacy['Asset'][7]="<b>Private</b> - are NOT publicly listed and visible only to me.";

		// TODO: possibly redundant? isn't it set in Asset????
		$privacy['Groups'][519]="Public - are visible to anyone.";
		$privacy['Groups'][79]="Members only - visible to all common members.";
		$privacy['Groups'][7]="Private - visible only to designated Admins.";

		$privacy['SecretKey'][1]="Members - are visible to all site members.";
		$privacy['SecretKey'][2]="Admins - are visible only to Owners and Admins ";
		$privacy['SecretKey'][4]="Nobody - disable Secret Key sharing";
		return $privacy;
	}

	function __decodePerms(& $data){
		$perms = @ifed($data['AssetPermission']['perms'], null);
		$data['Asset']['privacy_assets'] = $perms;

	}

	function __encodePerms(& $data){
		$perms = @ifed($data['Asset']['privacy_assets']);
		if ($perms) $data['Permission']['perms'] = $perms;
	}
	/*
	 * end permissionable
	 */

	function settings ($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'asset'));
			$this->redirectSafe();
		}
		if (!AppController::$writeOk) {
			$this->Session->setFlash(__('You are not authorized to view this page'));
			$this->redirectSafe();
		}
		if (!empty($this->data)) {
			/*
			 * redirect to edit with setting=[form id]
			 */
			$qs = @http_build_query(array('setting'=>$this->data['Asset']['setting']));
			$redirect = Router::url(array('action'=>'edit', $id)). ($qs ? "?{$qs}" : '');
			$this->redirect($redirect, null, true);
		}

		$privacy = $this->__getPrivacyConfig();
		//		$policy =  $this->__getPolicyConfig();
		$this->set(compact('policy', 'privacy'));

		$options = array(
			'conditions'=>array('Asset.id'=>$id), 
		);
		$this->Asset->contain('Owner.id', 'Owner.username', 'ProviderAccount.id', 'ProviderAccount.provider_name', 'ProviderAccount.display_name');		
		$data = @$this->Asset->find('first', $options);

		$this->__decodePerms($data);


		$this->data = $data;
		$this->set('data', $data);	
			
		//		$providerAccounts = $this->Asset->ProviderAccount->find('list');
		//		$owners = $this->Asset->Owner->find('list');
		//		$sharedEdits = $this->Asset->SharedEdit->find('list');
		//		$collections = $this->Asset->Collection->find('list');
		//		$groups = $this->Asset->Group->find('list');
		//		$this->set(compact('providerAccounts', 'owners', 'sharedEdits', 'collections', 'groups'));
		
		$xhrFrom = Configure::read('controller.xhrFrom');
		if ($xhrFrom) {
			$viewElement = '/elements/assets/'.$xhrFrom['view'];
		} else $viewElement = null;
		$done = $this->renderXHRByRequest(null, $viewElement, 0);		
	}
	function edit($id = null) {
		if (0 || $this->RequestHandler->isAjax()) {
			Configure::write('debug', 0);
			$this->layout='ajax';
			$this->autoRender=false;
		}
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'asset'));
			if (!$this->RequestHandler->isAjax()) $this->redirectSafe();
		}
		if (!AppController::$writeOk) {
			$this->Session->setFlash(__('You are not authorized to view this page'));
			if (!$this->RequestHandler->isAjax()) $this->redirectSafe();
		}
		if (!empty($this->data)) {
			/*
			 * update
			 */
			// check role permissions
			$allowed = array('ADMIN');
			if ( AppController::$writeOk || in_array(Session::read('Auth.User.role'), $allowed)) {
				$this->__encodePerms($this->data);
				if ($this->Asset->save($this->data)) {
					$this->Session->setFlash(sprintf(__('The %s has been saved', true), Configure::read('controller.alias')));
					$redirect = Router::url(array('action'=>'settings', $id));
					$retval = 1;
				} else {
					$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'user'));
					$retval = 0;
				}
			}
			if (0 || $this->RequestHandler->isAjax()) {
				return $retval;
			} else  {
				if ($redirect) $this->redirect($redirect, null, true);
			}
		}


		$privacy = $this->__getPrivacyConfig();
		//		$policy =  $this->__getPolicyConfig();
		$this->set(compact('policy', 'privacy'));


		$this->Asset->recursive = 0;
		$data = $this->Asset->read(null, $id);

		$this->__decodePerms($data);

		$this->data = $data;
		$this->set('asset', $data);
	}

	function delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid id for %s', true), 'asset'));
			$this->redirectSafe();
		}
		if ($this->Asset->hasPermission('write',$id))  {
			// get all gids to updateCount
			$options = array('conditions'=>array('asset_id'=>$id), 'fields'=>'group_id', 'recursive'=>-1);
			$result = ClassRegistry::init('AssetsGroup')->find('all',$options);
			$gids = Set::extract('/AssetsGroup/group_id', $result);

			if ($this->Asset->delete($id, true )) {
				$this->Session->setFlash(sprintf(__('%s deleted', true), 'Asset'));
				foreach ($gids as $gid) {
					ClassRegistry::init('Group')->updateCounter($gid);
				}
				$next = "/my/home";
				$this->redirect($next, null, true);
			}
			$this->Session->setFlash(sprintf(__('%s was not deleted', true), 'Asset'));
		}
		$this->redirectSafe();

	}

	function unshare ($id) {
		$forceXHR = 0;
		if ($forceXHR) {
			if (isset($this->params['url']['data'])) $this->data = $this->params['url']['data'];
		}
		if (empty($this->data['Asset']['id'])) $this->data['Asset']['id'] = $id;
		if (!isset($this->data['Group']['gids'])) $this->data['Group']['gids'] = null;
		if ($forceXHR || $this->RequestHandler->isAjax()) {
			$this->layout='ajax';
			$this->autoRender=false;
			if ($this->data) {
				$gids = $this->data['Group']['gids'];
				$aid = $this->data['Asset']['id'];
				$retval = $this->__unshare($aid, $gids, true, true);
			} else $retval = 0;
		}

		if ($this->RequestHandler->isAjax()) {
			return $retval;
		} else {
			if ($retval) $this->Session->setFlash('This photo was successfully unshared.');
			else  {
				$this->Session->setFlash('ERROR: There was a problem unsharing this photo. Please try again.');
				if ($this->data['Group']['gids']===null)
				$this->Session->setFlash("WARNING: UNSHARE ALL GROUPS IS NOT TESTED");
			}
			if (!$forceXHR) {
				$next = env('HTTP_REFERER');
				$this->redirect($next, null, true);
			}
		}
	}
	function set_as_photo($id) {
		$this->Asset->id = $id;
		$src = $this->Asset->field('src_thumbnail');
		$User = ClassRegistry::init('User');
		$User->id = Session::read('Auth.User.id');
		$ret = $User->saveField('src_thumbnail', getImageSrcBySize($src, 'sq'));
		if ($ret) $this->Session->setFlash('Your photo was successfully set to this photo');
		else $this->Session->setFlash('There was an error setting your photo. Please try again.');
		$next = env('HTTP_REFERER');
		$this->redirect($next, null, true);
	}

	function set_as_group_cover($id, $gid=null) {
		if ($gid==null && Session::read('lookup.context.keyName')=='group') $gid = Session::read('lookup.context.uuid');
		$next = env('HTTP_REFERER');
		if (!$gid) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'group'));
			$this->redirect($next, null, true);
		}
		$Group = ClassRegistry::init('Group');
		if (!$Group->hasPermission('write',$gid)) {
			$this->Session->setFlash('You do not have permission to set the cover photo for this group.');
			$this->redirect($next, null, true);
		}
		$this->Asset->id = $id;
		$src = $this->Asset->field('src_thumbnail');
		$Group->id = $gid;
		$ret = $Group->saveField('src_thumbnail', getImageSrcBySize($src, 'tn'));
		if ($ret) {
			$this->Session->setFlash('The group cover photo was successfully set.');
			$this->redirect($next, null, true);
		}
		$this->Session->setFlash('There was an error setting the group cover photo. Please try again.');
		$this->redirect($next, null, true);
	}

	function discussion($id) {
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'Snap'));
			$this->redirectSafe();
		}		
		$BIG_LIMIT = 10;
//		debug("$shotType, showHidden=$showHidden");
		$options = array(
			'conditions'=>array('Asset.id'=>$id),
			'contain'=> array('Comment', 'Owner.id', 'Owner.username', 'ProviderAccount.id', 'ProviderAccount.provider_name', 'ProviderAccount.display_name'),
			'fields'=>'Asset.*',		// MUST ADD 'fields' for  containable+permissionable
			'extras'=>array(
				'show_edits'=>false,
				'join_shots'=>false, 		// join shots to get shot_count?
				'join_bestshot'=>false,			// do NOT need bestShots when we access by $asset_id
				'show_hidden_shots'=>true,		// by $asset_id, hidden shots ok, or DONT join_bestshot
			),
		);		
		$data = $this->Asset->find('first', $options);
		$this->set('data', $data);	
		
		if (Configure::read('controller.action')=='discussion') $this->paginate['Comment']['limit'] = $BIG_LIMIT;
		
		$done = $this->renderXHRByRequest(null, '/elements/comments/discussion');
		// or $this->autoRender
	}
	/********************************************************************
	 * ajax POST methods
	 */
	
	
	/**
	 * used by PageMaker to get castingCall for included assets by ids 
	 */
	function getCC($lightboxAssetIds = null) {
		$forceXHR = 0;
		if ($lightboxAssetIds) {
			$aids = $lightboxAssetIds;
		} else {
			if ($forceXHR) {
				// use http GET for debug only
				$this->data = $this->params['url']['data'];
				debug($this->data);
			}		
			if ($this->data) {
				$aids = $this->data['Asset']['ids'];
			}
		};
		if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
		$cached = $this->CastingCall->cache_Lightbox($aids);
		if ($cached !== false) return $cached;
		
		// get SQL response for $aids
		$this->Asset->contain();
		$options = array(
			'recursive' => -1,	// 0
			'fields'=>'Asset.*',
			'order'=>array('Asset.dateTaken'=> 'ASC'),
//			'showEdits'=>true,
//			'showSubstitutes'=>false,	
			'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>'Usershot', 		// TODO: for lightbox, this could be Groupshot!!!
				'show_hidden_shots'=>true,		// TODO: true, fetching by assetId
			),	
//			'permissionable'=>false, 
			'conditions' => array(
				'Asset.id'=>explode(',',$aids)
			)
		);				
		$data = $this->Asset->find('all', $options);
		$data = Set::extract($data,'{n}.Asset');
		/**
		 * return response
		 * - XHR POST, response method will be set as json, OR
		 * - return to calling function, usually from  /views/users/pagemaker.ctp
		 */
		$ccLightbox = $this->CastingCall->getCastingCall($data, false);	// do not cache lightbox CC
		$ccLightbox['CastingCall']['Request'] = 'lightbox';

		if ($data) {
			if ($lightboxAssetIds) {
				// called directly from page request. see /views/users/pagemaker.ctp
				return $ccLightbox;
			} else {
				// XHR, pass in viewVars
				$this->viewVars['jsonData']['castingCall'] = $ccLightbox;
				$done = $this->renderXHRByRequest('json', '/elements/json/serialize', null, $forceXHR);
				return;
			}
		}
	}

	function setprop(){
		$forceXHR = setXHRDebug($this, 0);
		$success = true; $message=array(); $response=array();
		$resp0 = compact('success', 'message', 'response'); 
		if ($this->RequestHandler->isAjax() || $forceXHR) {		
			$this->layout='ajax';
			$this->autoRender=false;

			/*
			 * use this sample data to test a post
			 */
			//					$this->data['Asset']['id']='4bbb3976-a080-44f5-84c8-11a0f67883f5';
			//					$this->data['Asset']['rating']=4;
			if ($this->data) {
				// check write permission????
				$fields = array_keys($this->data['Asset']);
				$aids = (array)@explode(',', $this->data['Asset']['id']);
				$ret = true; 
				//TODO: begin SQL transaction for batch commit
				$updateBestShot_assetIds = array();
				$activePerAssetFields = array_intersect(array('rating','tags','chunk','privacy','unshare'), $fields);
				foreach ($aids as $aid) {
					if (count($activePerAssetFields)==0) break;	// skip if ther eare not active PerAssset Fields
					/*
					 * one asset_id per DB call
					 */
					$this->Asset->contain('ProviderAccount');
					$options = array(
						'conditions'=>array('Asset.id'=>$aid),
						'extras' => array(
							'join_shots'=>false,	// get ALL photos
						),
//						'showSubstitutes'=>true,
					);
					if (!in_array('rating', $fields)) {
					} else {
//						$options['showEdits'] = true;
						$options['extras']['show_edits'] = true;
					}
					$data = $this->Asset->find('first',$options);
					$asset_hash = $data['Asset']['asset_hash'];
					// get asset_hash for each asset. why?
					
					//TODO: consolidate all Asset operations into one SQL stmt
					if (in_array('rating', $fields)) {
						// update rating and score
						$newRating = $this->data['Asset']['rating'];
						$oldRating = @ifed($data['Asset']['rating'], 0);
						$delta = $newRating-$oldRating;
						$oldVotes = @ifed($data['SharedEdit']['votes'], 0);

						// adjust votes
						$votes = ($oldRating > 0) ? $oldVotes: $oldVotes+1;
						$votes = ($newRating == 0) ? $votes-1 : $votes;
						$data['SharedEdit']['votes'] = $votes;
						// adjust points
						
						$points = $oldVotes * $data['SharedEdit']['score'] + $delta;
						$data['SharedEdit']['points'] = $points;
						// adjust score
						$data['SharedEdit']['score'] = $points/$data['SharedEdit']['votes'];
						$data['UserEdit']['rating'] = $newRating;
						$data['UserEdit']['id'] = String::uuid();
						$data['UserEdit']['owner_id'] = Session::read('Auth.User.id');
						$data['UserEdit']['asset_hash'] = $asset_hash;
						$data['SharedEdit']['asset_hash'] = $asset_hash;
						unset($data['AssetPermission']);
						$return = $this->Asset->UserEdit->saveEdit($data);
						$return = $return && $this->Asset->SharedEdit->save($data);
						if ($return) $updateBestShot_assetIds[] = $aid;
						$ret = $ret && $return;
					}
					if (in_array('tags', $fields) && !empty($this->data['Asset']['tags'])) {
						// update tags

						$ret = $ret && $this->__addTag($this->data['Asset']['tags'], $aid);

					}


					if (in_array('chunk', $fields) && !empty($this->data['Asset']['chunk'])) {
						// update substitution group
						$uuid = @ifed($this->data['Asset']['chunk'], String::uuid());
						$ret = $ret && $this->__addChunks($uuid, $aid);

					}
					if (in_array('privacy', $fields) && !empty($this->data['Asset']['privacy'])) {
						$ret =  $ret && $this->__setPrivacy($this->data['Asset']['privacy'], $aid);
					}
					// unshare Asset from Group(s)			
					if (in_array('unshare', $fields) && !empty($this->data['Group']['id']) ) {
						$ret = $ret && $this->__unshare($aid, $this->data['Group']['id'], true, false);
						// update counterCache 
						ClassRegistry::init('Group')->updateCounter($this->data['Group']['id']);
					}
					
				}	// end foreach
				
				if (!empty($this->data['updateBestshot']) && count($updateBestShot_assetIds)) {
					// updateBestshot
					$Usershot = ClassRegistry::init('Usershot');
					$Usershot->updateBestShotFromTopRated(AppController::$userid, null, $updateBestShot_assetIds); 
					$Groupshot = ClassRegistry::init('Groupshot');
					$Groupshot->updateBestShotFromTopRated(AppController::$userid, null, $updateBestShot_assetIds); 
				}
				if (!empty($this->data['setBestshot'])) {
					$Shot = ClassRegistry::init($this->data['shotType']);
					$Shot->setBestshot(AppController::$userid, $this->data['Shot']['id'], $this->data['Asset']['id'] );
					$ret = true;
				}

				// share Asset(s) with Group: /groups/contributephoto
				
				// TODO: unshare Asset(s) with Group: /groups/removephoto
				// using /photos/setprop with data[Assets][unshare] for now...
				
				if($ret == 0){
					$success = 'false';
					$message = 'Your data is wrong! Your changes have not been saved! Please try again!';
				}else{
					$success = 'true';
					$message = 'Your changes have been saved!';
				}
			} else {
				$success = 'false';
				$message = 'Your data is empty!';
			}
		} else {
			$success = 'false';
			$message = 'The request is not allowed!';
		}
		if (!empty($resp0['message'])) $this->viewVars['jsonData'] = $resp0; // new codepath
		else {
			// deprecate this codepath
			$this->viewVars['jsonData'] = compact('success', 'message', 'response');
		}
		$done = $this->renderXHRByRequest('json', null, null, $forceXHR);
		return;
	}
	
	function shot(){
		$forceXHR = setXHRDebug($this, 0);
		$success = true; $message=array(); $response=array();
		$resp0 = compact('success', 'message', 'response'); 
		if ($this->RequestHandler->isAjax() || $forceXHR) {		
			$this->layout='ajax';
			$this->autoRender=false;

			/*
			 * use this sample data to test a post
			 */
			//					$this->data['Asset']['id']='4bbb3976-a080-44f5-84c8-11a0f67883f5';
			//					$this->data['Asset']['rating']=4;
			if ($this->data) {
				// check write permission????
				$fields = array_keys($this->data['Asset']);
				$aids = (array)@explode(',', $this->data['Asset']['id']);
				
				if ($this->data['shotType'] == 'unknown') {
					// assets from lightbox can be from group or user
					/*
					 * Do the right thing:
					 * 	- for groupShot, we have to know which group is active
					 *  - for userShot, we have to check permissions, 
					 * 		photos should be from the same users
					 * - disable 'group as Shot' if 'unknown'?
					 * */
					
				}

				/*
				 * these action accept an array of asset_ids per DB call
				 */								
				// group Assets into a Shot
				if (in_array('group', $fields) ) {
					// update substitution group
					$shotId = null;
					$shotType = $this->data['shotType'];
					$uuid = $this->data['shotUuid'];	// TODO: CHANGE TO "uuid"
					$resp1 = $this->__groupAsShot($aids, $shotId, $shotType, $uuid);
					$success = $success && $resp1['success'];
					$resp0 = Set::merge($resp0, $resp1);
					$resp0['success'] = $success;
				} 
				
				// ungroup Shot, 
				if (in_array('ungroup', $fields) ) {
					if (isset($this->data['Shot']['id'])) {
						// ungroup the entire shot
						$shotIds = explode(',',$this->data['Shot']['id']);
						$shotType = $this->data['shotType'];
						$hiddenShot_CC  = $this->__hiddenShots($shotIds, $shotType);
						$resp1 = $this->__ungroupShot($shotIds, $shotType);
						$success = $success && $resp1['success'];
						if ($resp1['success']) {
							// add hiddenShots back to photoroll using castingcall
							$auditions = & $hiddenShot_CC['CastingCall']['Auditions']['Audition']; 
							if (!empty($auditions)) {
								foreach ( $auditions as & $audition) {
									$audition['Shot'] = array('id'=>null, 'count'=>null);
									$audition['SubstitutionREF']=null;
								}
								$resp1['response']['unGroupShot']['hiddenShots'] = $hiddenShot_CC;
							}
						}
						$resp0 = Set::merge($resp0, $resp1);
						$resp0['success'] = $success;						
					}
				}	
				
				// removeFromShot Shot, 
				if (in_array('remove', $fields) ) {
					if (isset($this->data['Asset']['id'])) {
						$aids = explode(',',$this->data['Asset']['id']);
						$shotId = $this->data['Shot']['id'];
						$shotType = $this->data['shotType'];
						// if data[Asset][id], just remove $aids from the Group
						// update isBest for remaining Group
						$resp1 = $this->__removeFromShot($aids, $shotId, $shotType);
						$success = $success && $resp1['success'];
						$resp0 = Set::merge($resp0, $resp1);
						$resp0['success'] = $success;
					}					
				}
			} else {
				$success = 'false';
				$message[] = 'AssetsController->shot: Warning, $this->data empty';
			}
		} else {
			$success = 'false';
			$message = 'AssetsController->shot: The request is not allowed!';
		}
		if ($resp0['success']===true) $resp0['success']='true';
		$this->viewVars['jsonData'] = $resp0; 
		$done = $this->renderXHRByRequest('json', null, null, $forceXHR);
		return;
	}

	function __addTag($tagString, $assetId) {
		// Taggable->saveTags()
		return $this->Asset->saveTags($tagString, $assetId, $replace = false);
	}
	
	
	/**
	 * @params $assetIds string, CSV of asset uuids
	 * @params $shotId string, uuid of shot, null to create new shot
	 * @params $shotType string, [Usershot | Groupshot]
	 * @params $shotUuid string, uuid of Usershot/Groupshot 
	 * @return array('shotId', 'bestshotId') or false
	 */
	function __groupAsShot( $assetIds, $shotId = null, $shotType='Usershot', $shotUuid = null, $owner_id = null ){
//debug('/photos/setprop: groupAsShot');		
		/*
		 * TODO: snappi Editors must set $owner_id to Asset owner, NOT Auth user
		 */
		if (!isset($owner_id)) $owner_id = AppController::$userid;
		switch ($shotType) {
			case 'Usershot':
				$Usershot = ClassRegistry::init('Usershot');
				$resp = $Usershot->groupAsShot($assetIds, $shotUuid);				
				break;
			case 'Groupshot':
				$Groupshot = $this->Asset->Groupshot;
				$resp = $Groupshot->groupAsShot($assetIds, $shotUuid, $owner_id);				
				break;
		}
		// mark ccid as stale
		if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
		$castingCall = $this->CastingCall->cache_MarkStale($this->data['ccid']);
		return $resp;
	}
	
	function __removeFromShot($assetIds, $shotId, $shotType='Usershot', $shotUuid = null, $owner_id = null ) {
//		return $this->Asset->Shot->removeFromShot($assetIds, $shotIds);
		if (!isset($owner_id)) $owner_id = AppController::$userid;
		switch ($shotType) {
			case 'Usershot':
				$Usershot = ClassRegistry::init('Usershot');
				$resp = $Usershot->removeFromShot($assetIds, $shotId, $owner_id);				
				break;
			case 'Groupshot':
				$Groupshot = $this->Asset->Groupshot;
				$resp = $Groupshot->removeFromShot($assetIds, $shotId, $owner_id);				
				break;
		}		
		return $resp;		
	}
	
	function __ungroupShot($shotIds, $shotType='Usershot', $owner_id = null){
		if (!$owner_id) $owner_id = AppController::$userid;
		switch ($shotType) {
			case 'Usershot':
				$Usershot = ClassRegistry::init('Usershot');
				$resp = $Usershot->unGroupShot($shotIds, $owner_id);				
				break;
			case 'Groupshot':
				$Groupshot = $this->Asset->Groupshot;
				$resp = $Groupshot->unGroupShot($shotIds, $owner_id);				
				break;
		}		
		return $resp;
	}
	
	function __hiddenShots($shotIds, $shotType){
		if (is_string($shotIds)) $shotIds = explode(',', $shotIds);
		$paginateModel = 'Asset';
		Configure::write('paginate.Model', $paginateModel);
		$this->paginate[$paginateModel]['limit']  = empty($this->params['url']['preview']) ? $this->paginate[$paginateModel]['limit'] : $this->paginate[$paginateModel]['big_limit'];

		// get paginate joins/conditions from model
		$Model = isset($this->{$paginateModel}) ? $this->{$paginateModel} : ClassRegistry::init('Asset');
		$this->paginate[$paginateModel] = $Model->getPaginatePhotosByShotId($shotIds, $this->paginate[$paginateModel], $shotType);
		$this->paginate[$paginateModel]['showSubstitutes'] = true;	// do NOT hide substitutes in results 

		/*
		 * get paginate options
		 */ 
		$this->paginate[$paginateModel] = $Model->getPaginateOptions($this->paginate[$paginateModel], $paginateModel);
		Configure::write('paginate.Options.'.$paginateModel, $this->paginate[$paginateModel]);
		// end paginate options
		
		// paginate
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// TODO: refactor. use standard form for paging count
		$this->params['paging']['total'][$paginateModel] = $this->params['paging'][$paginateModel]['count'];
		/*
		 * end Paginate
		 */
		if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
		$castingCall = $this->CastingCall->getCastingCall($pageData);
		return $castingCall;		
	}	
	
	function __addChunks($chunk_uuid, $assetIds) {
		$ids = explode(',',$assetIds);
		$errors=array();
		foreach ($ids as $asset_id) {
			if (empty($asset_id)) continue;
			$this->Asset->id = $asset_id;
			$ret = $this->Asset->saveField('chunk', $chunk_uuid, false);
			if (!ret) $errors[] = $asset_id;
		}
		return empty($errors);
	}
	function __setPrivacy($privacy, $assetIds) {
		$privacySetting = $this->__getPrivacyConfig();
		if (!in_array((int)$privacy, array_keys($privacySetting['Asset']))) {
			// TODO:  setFlash message
			return false;
		}
		$ids = explode(',',$assetIds);
		$errors=array();
		foreach ($ids as $asset_id) {
			if (empty($asset_id)) continue;
			$this->Asset->id = $asset_id;
			/*
			 * Q: is Asset['privacy_groups'] the same as Permissionable privacy?
			 */
			$data['Asset']['id'] = $asset_id;
			$data['Asset']['privacy_groups'] = $privacy;
			$data['Permission']['perms'] = $privacy;
//			$this->Asset->Behaviors->Permissionable->settings['Asset']['defaultBits'] = $privacy;
			$ret = $this->Asset->save($data, false);
			$this->Session->setFlash("The privacy setting was successfully updated.");
		}
		return empty($errors);
	}
	
	
	function get_asset_info(){
		$forceXHR = false;
		if ($forceXHR) {
			debug($this->params['url']);
		}
		if ($this->RequestHandler->isAjax() || $forceXHR) {
			$asset_id = $this->params['url']['data']['Asset']['id'];	
			$options = array(
				'conditions'=>array('Asset.id'=>$asset_id),
				'permissionable'=>false);
			$this->Asset->contain();
			$assetInfo = $this->Asset->find('first', $options);
			$this->log($assetInfo);
			$success = 'true';
			$message = 'Get asset info successfully!';
			$response = $assetInfo;
		}else{
			$success = 'false';
			$message = 'Request is not Ajax!';
			$response = '';
		}
		$this->viewVars['jsonData'] = compact('success', 'message', 'response');
		$done = $this->renderXHRByRequest('json');
		return;
	}
	
	/************************************
	 * legacy code
	 */
	
	/**
	 * @deprecated use filmstrip with HScroll instead
	 */
	function __getFilmstripInit($id, $ccIdorCastingCall) {
		$FILMSTRIP_HALFSIZE = 3;
//		App::import('Helper', 'Js');
//		$this->Js = new JsHelper();	
		/*
		 * neighbor/filmstrip processing
		 */
		$castingCall = is_numeric($ccIdorCastingCall) ? Session::read("castingCall.{$ccIdorCastingCall}") : $ccIdorCastingCall;
		$Auditions = !empty($castingCall['Auditions']) ? $castingCall['Auditions'] : array();
		$Total=$Perpage=$Page=$Audition=0;
		extract($Auditions, EXTR_IF_EXISTS);
		$selected = array_search($id, Set::extract("/Audition/id", $Auditions));
		if ($selected===false) {
			return false;
		} else {	
			// calculate filmstrip params
			$start =  max(0, $selected - $FILMSTRIP_HALFSIZE);
			$length = min($Total,(2*$FILMSTRIP_HALFSIZE+1));
			$start = min($start, $Total- $length);
			$init = compact('selected','start','length','Total', 'Perpage', 'Page', 'ccid');
			$castingCall = array('CastingCall'=>$castingCall);
			return compact('init', 'castingCall', 'ccid');
		}
	}
		
	/**
	 * get photos before/after selected photo
	 */
	function XXXneighbors($id, $ccid) {
		/*
		 * neighborhood filmstrip, 
		 * - allow JSON request only, for rendering in JS
		 */
		$result = $this->__getFilmstripInit($id, $ccid);
//		$this->set('jsonData',array('init'=>$result['init'], 'CastingCall'=>$result['castingCall']));
		$this->viewVars['jsonData']['filmstrip']['init'] = $result['init'];
		$this->viewVars['jsonData']['castingCall'] = $result['castingCall'];
		$this->viewVars['jsonData']['ccid'] = $ccid;		
		
		
		$done = $this->renderXHRByRequest('json', '/elements/photo/filmstrip');
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false
		
		// or autoRender
//		debug($result);
		$this->set($result);
		$options = array('conditions'=>array('Asset.id'=>$id)		);
		$this->Asset->contain(null);		
		$data = @$this->Asset->find('first', $options);
		$this->set('data', $data);	
		Configure::write('js.render_lightbox', false);
	}	
	
}
?>