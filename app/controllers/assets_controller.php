<?php
class AssetsController extends AppController {
	public $name = 'Assets';
	
	public $titleName = 'Photos';
	public $displayName = 'Photo';	// section header

	public $components = array(
		'Permissionable.Permissionable',
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
			'preview_limit'=>6,
			'paging_limit' =>24,
			// 'order'=>array('Asset.dateTaken'=>'ASC'),
			'order'=>array('SharedEdit.score DESC', 'Asset.modified'=>'DESC'),
			'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>'Usershot', 
				'show_hidden_shots'=>false
			),	
		),
		'Group'=>array(
			'preview_limit'=>4,
			'paging_limit' =>8,
			'order'=>array('Group.title'=>'ASC'),
			'recursive'=> -1,
			'fields' =>'Group.*',
		),
		'Collection'=>array(				
			'preview_limit'=>4,
			'paging_limit' =>16,
			'order'=>array('Collection.modified'=>'DESC'),
			'recursive'=> -1,
			'fields' =>'Collection.*',
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
		 */
		 'stories',  // TODO: move to ACL
		 'test', 'addACL', 'updateExif'
		);
		AppController::$writeOk = $this->Asset->hasPermission('write', AppController::$uuid);
	}


	function beforeRender() {
		if (!($this->RequestHandler->isAjax() || $this->RequestHandler->ext == 'json') && AppController::$uuid) {
			$label = !empty($this->viewVars['data']['Asset']['caption']) ? $this->viewVars['data']['Asset']['caption'] : '';
			if (Session::read("lookup.trail.{$this->displayName}.uuid") == AppController::$uuid) {
				Session::write("lookup.trail.{$this->displayName}.label", $label);	
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
		$message = $response = $resp1 = $resp2 = array();
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
// debug($conditions);		
		$ret = $AssetsGroup->deleteAll($conditions, false);
		// $ret = 1;
// debug($ret);		
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
					'conditions' => array('Asset.provider_account_id' => $providerAccountId),
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
// debug($count);				
				if($count == 0){
					// provider_account_id not found, so delete from GroupsProviderAccount
					$resp1['success'] = $this->deleteFromGroupsProviderAccount($providerAccountId, $gid);
					$resp1['message'] = "deleteFromGroupsProviderAccount, ret={$ret}";
					$resp1['response'] = array('provider_account_id'=>$providerAccountId);						
					$ret = $ret && ($resp1['success'] && $resp1['success']!=='false');
				}
				// remove from groupShots
				// check for Shots
				$groupshots = $this->__findShots($aid, 'Groupshot');
				$shotsByAssetId = Set::combine($groupshots, '/AssetsShot/asset_id', '/Shot');
// debug($shotsByAssetId);
				// remove from Groupshots
				if (isset($shotsByAssetId[$aid])) {
					$shot = $shotsByAssetId[$aid]['Shot'];
// debug($shot);						
					if ($shot['count'] > 2 ) {
						$resp2 = $this->__removeFromShot(array($aid), $shot['shot_id'], 'Groupshot');
					} else {
						$resp2 = $this->__ungroupShot(array($shot['shot_id']), 'Groupshot');
					}
					$ret = $ret && ($resp2['success'] && $resp2['success']!=='false');
					/*
					 * on Delete, we just reload/render "refreshed" castingCall for updates
					 */
// debug($resp2);
				}
			}
		}
		$success = $ret ? 'true' : 'false';
		$message = $ret ? "Unshare Asset successful." : "Unshare Asset Failed";
		$response = array('asset_id'=>$aid, 'group_ids'=>$gids);
		$resp0 = compact('success', 'message', 'response');
		$resp = Set::merge($resp0, $resp1, $resp2);		
// debug($resp);		
		return $resp;
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
			, 'permissionable'=>false
		);
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
		$origin = !empty($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : $_SERVER['HTTP_HOST'];
		if (preg_match('/(snaphappi.com|thats\-me)/i', $origin)) {
			echo header("Access-Control-Allow-Origin: {$origin}");
		}
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';	
		// paginate 
		$paginateModel = 'Asset';
		$Model = $this->Asset;
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $this->paginate[$paginateModel];
if (!empty($this->passedArgs['raw'])) {
	$paginateArray['extras']['show_hidden_shots']=1;
}		
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
		/*
		 * get montage
		 * */
		if (empty($castingCall['CastingCall']['Auditions'])) $getMontage = false;
		if ( isset($this->passedArgs['montage']) ) $getMontage = !empty($this->passedArgs['montage']);
		else $getMontage = ( 
			Session::read('section-header.Photo') == 'montage' 
			&& !$this->RequestHandler->isAjax() 
		);
		if ($getMontage) {
 			$this->Montage = loadComponent('Montage', $this);
			$Auditions = $castingCall['CastingCall']['Auditions'];
			// get a random slice, but remember to CACHE for N mins
			$show = 9;
			$Audition = $Auditions['Audition'];	// copy
			shuffle($Audition);
			$Audition = array_slice($Audition,0, $show);
			$Baseurl = $Auditions['Baseurl'];
			$this->viewVars['jsonData']['montage'] = $this->Montage->getArrangement(compact('Audition','Baseurl'));
		}	
		 
		$this->getLookups(array('Users'=> array_keys(Set::combine($pageData, '/owner_id', ''))));
		$done = $this->renderXHRByRequest('json', '/elements/photo/roll');
		if ($done) return;
		$options = array(
			'permissionable'=>false,
			'recursive'=>-1,
			'showEdits'=>false,
			'join_shots'=>false, 
			'show_hidden_shots'=>true,
		);
		$ownerCount = $this->Asset->find('count', $options);
		$this->set(compact('ownerCount'));
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
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';	
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
		if (empty($data)) {
			/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash("ERROR: You are not authorized to view this record.");
			$this->redirectSafe();
		} else {
			$data[$paginateModel] = $pageData;
			$this->set('data', $data);
			$this->viewVars['jsonData']['Asset'][]=$data['Asset'];
		}
//		debug($data);
	}	

	function stories($id=null){
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';	
		//	this should be a redirect to /groups/byuser/userid, plus context
		if (!$id) {
			$this->Session->setFlash(sprintf(__('No %s found.', true), $this->titleName));
			$this->redirect(array('action' => 'index'));
		}

		// paginate 
		$paginateModel = 'Collection';
		$Model = $this->User->{$paginateModel};
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $Model->getPaginateCollectionsByPhotoId($id, $this->paginate[$paginateModel]);
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate
		
		$this->viewVars['jsonData'][$paginateModel] = $pageData;
		$done = $this->renderXHRByRequest('json', '/elements/collections/roll');
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false	
		
		
		$options = array(
			'conditions'=>array('Asset.id'=>$id),
		);
		$this->Asset->contain(null);		
		$data = @$this->Asset->find('first', $options);
		if (empty($data)) {
			/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash("ERROR: You are not authorized to view this record.");
			$this->redirectSafe();
		} else {
			$data[$paginateModel] = $pageData;
			$this->set('data', $data);
			$this->viewVars['jsonData']['Asset'][]=$data['Asset'];
		}
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
				$this->viewVars['jsonData']['Shot']['id'] = $id;
			break;
		}		
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate		
// debug($this->paginate[$paginateModel]);		
		if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
		$castingCall = $this->CastingCall->getCastingCall($pageData);
		$this->viewVars['jsonData']['castingCall'] = $castingCall;
		// extract Shot properties
		// uses $this->viewVars['jsonData']['shot_CastingCall']['shot_extras'] pattern, see /workorders/shots		
		$shot = array('id'=>$id);
		$shot['owner_id'] = $pageData[0]['shot_owner_id'];
		$shot['priority'] = $pageData[0]['shot_priority'];
		$shot['count'] = $pageData[0]['shot_count'];
		$shot['active'] = $pageData[0]['shot_active'];
		$this->viewVars['jsonData']['castingCall']['shot_extras'][$id] = $shot;
		
		
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
						} else $id = AppController::$ownerid;
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
			$this->viewVars['jsonData']['Asset'][]=$data['Asset'];
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
		$this->layout = 'snappi';
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
			'contain'=> array('Owner.id', 'Owner.username', 'ProviderAccount.id', 'ProviderAccount.provider_name', 'ProviderAccount.display_name'),
			'fields'=>'Asset.*',		// MUST ADD 'fields' for  containable+permissionable
			'extras'=>array(
				'show_edits'=>true,
				// 'join_shots'=>$shotType, 		// join shots to get shot_count?
				'join_bestshot'=>false,			// do NOT need bestShots when we access by $asset_id
				// 'show_hidden_shots'=>true,		// by $asset_id, hidden shots ok, or DONT join_bestshot
			),
		);
		$data = $this->Asset->find('first', $options);

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
			if ( AppController::$writeOk || in_array(AppController::$role, $allowed)) {
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

	function deleteXXX($id = null) {
		// check for controller='assets' or delete by JSON POST 
		// __removeFromShot, update count
		// __unshare, update count
		// __delete
		
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
				// TODO: cleanup BestShot
			}
			$this->Session->setFlash(sprintf(__('%s was not deleted', true), 'Asset'));
		}
		$this->redirectSafe();

	}
	function delete ($id = null) {
		$forceXHR = setXHRDebug($this, 0);
		if ($forceXHR) {
			if (isset($this->params['url']['data'])) $this->data = $this->params['url']['data'];
		}
		// TODO: allow delete by Role=EDITOR, etc.
		if (in_array(AppController::$role,array('MANAGER','ADMIN','ROOT'))){
			$owner_id = AppController::$ownerid;		
		} else $owner_id = AppController::$userid;		 
		if (!empty($this->data)) {
// debug($this->data);			
// exit;
			$errors = $response = $message = $resp0 = $resp1 = $resp2 = array();
			// POST: typically XHR+JSON
			if ($forceXHR || $this->RequestHandler->isAjax()) {
			
				$gids = isset($this->data['Group']['gids']) ? (array)@explode(',', $this->data['Group']['gids']) : null;
				$aids = (array)@explode(',', $this->data['Asset']['id']);
				$usershots = $this->__findShots($aids, 'Usershot');
				$shotsByAssetId = Set::combine($usershots, '/AssetsShot/asset_id', '/Shot');
// debug($shotsByAssetId);				
				$retval = true;
				/*
				 * NOTE: must delete assets individually to cleanup User/Groupshots properly
				 * 	update counterCache at end
				 */ 
				foreach ($aids as $aid) {
					if ($this->Asset->hasPermission('delete',$aid) == false) {
						$errors[] = "WARNING: no delete permissions on uuid={$aid}";
						$retval = false;
						continue;
					}
					// _unshare() -> remove from AssetsGroup, remove from GroupShots, update GroupCount 
					if (empty($gids)){
						// get all gids to updateCount
						$options = array('conditions'=>array('asset_id'=>$aid), 
							'fields'=>'group_id', 
							'recursive'=>-1);
						$result = ClassRegistry::init('AssetsGroup')->find('all',$options);
						$gids_1 = Set::extract('/AssetsGroup/group_id', $result);					
					} else {
						// TODO: send gids as param
						$gids_1 = $gids;
					}
// debug($gids_1);	
					if (!empty($gids_1[0])) {		// unshare
						$resp1 = $this->__unshare($aid, $gids_1, true, true);
						$retval = $retval && ($resp1['success'] && $resp1['success']!=='false');
					}
					// remove from UserShots
					if (isset($shotsByAssetId[$aid])) {
						$shot = $shotsByAssetId[$aid]['Shot'];
// debug($shot);			
						//TODO: need $uuid of Asset.owner_id, check when role=EDITOR, etc.			
						if ($shot['count'] > 2 ) {
							$resp2 = $this->__removeFromShot(array($aid), $shot['shot_id'], 'Usershot');
						} else {
							
							$resp2 = $this->__ungroupShot(array($shot['shot_id']), 'Usershot');	
						}
						$retval = $retval && ($resp2['success'] && $resp2['success']!=='false');
						/*
						 * on Delete, we just reload/render "refreshed" castingCall for updates
						 */
					}
					// remove from Assets	
					$delete_retval = @$this->Asset->delete($aid, true);
					$retval = $retval && $delete_retval;
					$jsonResp['response']['aids'][$aid] = $delete_retval;

				}
				$ret = $this->Asset->Owner->updateCounter($owner_id);	// instead of counterCache
				$success = $retval ? 'true' : 'false';
				$message = $retval ? "Delete Asset successful." : "Delete Asset Failed";
				$response = array('delete_asset_ids'=>$aids);
				$resp0 = compact('success', 'message', 'response');
				$this->viewVars['jsonData'] = Set::merge($resp0, $resp1, $resp2);
// debug($this->viewVars['jsonData']);				
				// update owner counterCache 			
// Configure::write('debug',2);
// $this->layout = false;
// $this->render('/elements/dumpSQL'); 				
				$done = $this->renderXHRByRequest('json', null, null, $forceXHR);
				if ($done) return;
			}
// $this->layout = false;
// $this->render('/elements/dumpSQL');
		} else {
			// GET: no XHR, no JSON
			if (!$id) {
				$this->Session->setFlash(sprintf(__('Invalid id for %s', true), 'asset'));
				$this->redirectSafe();
			}			
		}
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
				$response = $this->__unshare($aid, $gids, true, true);
				$retval = ($response['success'] && $response['success']!=='false');
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

	function set_as_cover($asset_id, $target_id, $model) {
		$forceXHR = setXHRDebug($this, 0);
		if (!$target_id) {
			$message = sprintf(__('Invalid %s', true), $model);
		} else {
			$Model = ClassRegistry::init($model);
			$this->Asset->disablePermissionable();
			$this->Asset->id = $asset_id;
			$src = $this->Asset->field('src_thumbnail');
			$Model->id = $target_id;
			if (isset($Model->belongsTo['Owner'])) $Model->belongsTo['Owner']['counterCache']=false;
			$ret = $Model->saveField('src_thumbnail', Stagehand::getImageSrcBySize($src, 'tn'));
			if ($ret) {
				$message = ('The cover photo was successfully set.');
			} else {
				if (!$Model->hasPermission('write',$target_id)) {
					$message = ('You do not have permission to set the cover photo for this item.');
				} else $message = ('There was an error setting the cover photo. Please try again.');
			}
		}
		if ($this->RequestHandler->isAjax() || $forceXHR) {
			$sucess = $ret ? true : false;
			$response = compact('asset_id', 'target_id', 'model');
			$this->viewVars['jsonData'] = compact('success', 'message', 'response');
			$done = $this->renderXHRByRequest('json', '/elements/dumpSQL', null, $forceXHR);
		} else {
			$next = env('HTTP_REFERER');	
			$this->Session->setFlash($message);
			$this->redirect($next, null, true);
		}
	}

	
	function updateExif($uuid) {
		return parent::__updateExif($uuid);
	}
	function trends($target_id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'Snap'));
			$this->redirectSafe();
		}	
		$options = array(
			'conditions'=>array('Asset.id'=>$id),
			'contain'=> array('Owner.id', 'Owner.username', 'ProviderAccount.id', 'ProviderAccount.provider_name', 'ProviderAccount.display_name'),
			'fields'=>'Asset.*',		// MUST ADD 'fields' for  containable+permissionable
			'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>false, 		// join shots to get shot_count?
				'join_bestshot'=>false,			// do NOT need bestShots when we access by $asset_id
				'show_hidden_shots'=>true,		// by $asset_id, hidden shots ok, or DONT join_bestshot
			),
		);
		$data = $this->Asset->find('first', $options);
		if (empty($data)) {
			/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash("Snap not found.");
			$this->redirectSafe();
		} else {
			$this->set('data', $data);
		}
	}
	function discussion($id) {
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'Snap'));
			$this->redirectSafe();
		}	
		if (isset($this->params['url']['view'])) {
			$this->Session->write("comments.viewType.discussion", $this->params['url']['view']);
			$this->redirect($this->here, null, true);
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
	function __getAssetsFromBatchId($batchId) {
		if (strpos($batchId, ',')!==false) $batchId = explode(',', $batchId);
		$options = array(
			'conditions'=>array(
				'Asset.batchId'=>$batchId, 
				'Asset.owner_id'=>AppController::$ownerid,
			), 
			// 'fields'=>array('id'),
			'extras' => array(
				'join_shots'=>false,	// get ALL photos
				'show_edits' => false,
			),							
		);
		$data = $this->Asset->find('all',$options);
		return Set::extract($data, '/Asset/id');
	}
	/**
	 * @params $this->data[Asset][id] string (optional), Comma delim string of Asset UUIDs
	 * @params $this->data[Asset][batchId] string (optional), Comma delim string of Asset batchIds
	 * 
	 * for Workorder edits, add the following qs params for WorkorderPermissionable access
	 * 		data[Workorder][type] = ['Workorder' | 'TasksWorkorder' ]
	 *		data[Workorder][woid] = [uuid] Workorder.id or TasksWorkorder.id
	 * 
	 */
	function setprop(){
		$forceXHR = setXHRDebug($this, 0, 1);
		
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
				
				if (in_array(AppController::$role, array('EDITOR', 'MANAGER'))) {
					extract($this->data['Workorder']);
					// data[Workorder][type] = [Workorder|TasksWorkorder]
					// data[Workorder][woid] = [uuid]
					if (isset($type) && isset($woid)) {
						$this->Asset->Behaviors->attach('WorkorderPermissionable', array('type'=>$type, 'uuid'=>$woid));
						$wms = Session::read("WMS.{$woid}");
						$bestshot_ownerId = $wms['Workorder']['source_id'];  // set Bestshot owner to client_id
					}
				}
				
				// check write permission????
				$fields = array_keys($this->data['Asset']);
				if (!empty($this->data['Asset']['id'])) {			
					$aids = explode(',', $this->data['Asset']['id']);
				} elseif (!empty($this->data['Asset']['batchId'])) {
					$aids = $this->__getAssetsFromBatchId($this->data['Asset']['batchId']);
				}
				$ret = true; 
				//TODO: begin SQL transaction for batch commit
				$updateBestShot_assetIds = array();
				$activePerAssetFields = array_intersect(array('rating','rotate','tags','chunk','privacy','unshare'), $fields);
				foreach ($aids as $aid) {
					if (count($activePerAssetFields)==0) break;	// skip if ther eare not active PerAssset Fields
					/*
					 * one asset_id per DB call
					 */
					// $this->Asset->contain('ProviderAccount');
					$options = array(
						'fields'=>'`Asset`.*',
						'conditions'=>array('Asset.id'=>$aid),
						'contain'=>'ProviderAccount',
						'extras' => array(
							'join_shots'=>false,	// get ALL photos
						),
					);
					if (in_array('rating', $fields)) {
						$options['extras']['show_edits'] = true;
					}
					// if (in_array(AppController::$role, array('EDITOR', 'MANAGER'))) {
						// extract($this->data['Workorder']);
						// // data[Workorder][type] = [Workorder|TasksWorkorder]
						// // data[Workorder][woid] = [uuid]
						// if (isset($type) && isset($woid)) {
							// $this->Asset->Behaviors->attach('WorkorderPermissionable', array('type'=>$type, 'uuid'=>$woid));
						// }
					// }
					$data = $this->Asset->find('first',$options);
					if (empty($data)) {
						// possible no permission on Asset
						 $response[$aid] = "Asset not found, uuid={$aid}. check permissions.";
						 $ret = 0;
						 continue;
					}
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
						if (empty($data['SharedEdit']['score'])) $data['SharedEdit']['score'] = 0;
						$points = $oldVotes * $data['SharedEdit']['score'] + $delta;
						$data['SharedEdit']['points'] = $points;
						// adjust score
						$data['SharedEdit']['score'] = $data['SharedEdit']['votes'] ? $points/$data['SharedEdit']['votes'] : 0;
						$data['UserEdit']['rating'] = $newRating;
						$data['UserEdit']['id'] = String::uuid();
						$data['UserEdit']['owner_id'] = AppController::$userid;
						$data['UserEdit']['asset_id'] = $aid;
						$data['SharedEdit']['asset_id'] = $aid;
						unset($data['AssetPermission']);
						$return = $this->Asset->UserEdit->saveEdit($data);
						$return = $return && $this->Asset->SharedEdit->save($data);
						if ($return) $updateBestShot_assetIds[] = $aid;
						$ret = $ret && $return;
					}
					if (in_array('rotate', $fields) && !empty($this->data['Asset']['rotate'])) {
						// get exif data from DB
						$options = array(
							'conditions'=>array('Asset.id'=>$aid), 
							'fields'=>array('id', 'json_exif', 'json_src'),
							'extras' => array(
								'join_shots'=>false,	// get ALL photos
								'show_edits' => false,
							),							
						);
						$data = $this->Asset->find('first', $options);
						$rotate = $this->data['Asset']['rotate'];
						if (in_array($rotate, array(3,6,8))) {
							$basepath = Configure::read('path.stageroot.basepath');
							// goal: set Audition.Photo.Fix.Rotate
							if (!empty($data['Asset'])) {
								$json_exif = json_decode($data['Asset']['json_exif'], true);
								// if (!is_array($json_exif)) $json_exif = json_decode($json_exif, true);
// $this->log($json_exif, LOG_DEBUG);								
								// 8 = ccw, 6 = cw
								// 8=> 1, 8, 3, 6, 1
								// 6=> 1, 6, 3, 8, 1
								$old_rotate = !empty($json_exif['preview']['Orientation']) ? $json_exif['preview']['Orientation'] : 1;
								$new_rotate = Stagehand::orientation_sum($rotate,$old_rotate);
// debug("{$new_rotate} = rotate_lookup[{$rotate}][{$old_rotate}]"); 	
// $this->log("{$new_rotate} = rotate_lookup[{$rotate}][{$old_rotate}]", LOG_DEBUG);						
								$json_exif['preview']['Orientation'] = $new_rotate;
								$response['rotate'] = $new_rotate;
								$repsonse['uuid'] = $data['Asset']['id'];
								// get src for preview derived asset
								$json_src = json_decode($data['Asset']['json_src'], true);
								$previewSrc = $basepath.'/'.preg_replace('/\/tn~/', '/.thumbs/bp~', $json_src['thumb'], 1);
								/*
								 * patch json_exif for early assets
								 */
								if (!isset($json_exif['root'])) {
									$Import = loadComponent('Import', $this);
									$meta = $Import->getMeta($basepath.'/'.$json_src['root'], null, $json_exif);
									$json_exif = $meta['exif'];
								}
// debug("{$aid}: new rotate = $new_rotate");							
	
								if (in_array($new_rotate, array(6,8)) && isset($json_exif['preview']['imageWidth'])) {
$this->log("WARNING: json_exif['preview']['imageWidth'] MAY BE DEPRECATED!!!! ", LOG_DEBUG);									
									$temp = $json_exif['preview']['imageWidth'];
									$json_exif['preview']['imageWidth'] = $json_exif['preview']['imageHeight'];
									$json_exif['preview']['imageHeight'] = $temp;
								} else if (in_array($new_rotate, array(6,8))) {
$this->log("WARNING: json_exif['preview']['imageWidth'] may need to be scaled, if not deprecated ", LOG_DEBUG);
									$json_exif['preview']['imageWidth'] = $json_exif['root']['imageHeight'];
									$json_exif['preview']['imageHeight'] = $json_exif['root']['imageWidth'];
								} else {
									$json_exif['preview']['imageWidth'] = $json_exif['root']['imageWidth'];
									$json_exif['preview']['imageHeight'] = $json_exif['root']['imageHeight'];
								};
								 
// $this->log("using src={$previewSrc}", LOG_DEBUG);
								// save asset data
					// TODO: save to UserEdits.rotate as well						
// debug($json_exif);							
								$data['Asset']['json_exif']=json_encode($json_exif);
								$this->Asset->id = $data['Asset']['id'];
								$this->Asset->disablePermissionable(true);
								$return = $this->Asset->saveField('json_exif', $data['Asset']['json_exif'], false);
								$this->Asset->disablePermissionable(false);
								// update rotate preview
								// $previewSrc = Stagehand::getImageSrcBySize($thumb_src, 'bp');
								if (!isset($this->Jhead)) $this->Jhead = loadComponent('Jhead', $this);
								$errors =  $this->Jhead->exifRotate($new_rotate, $previewSrc);
								
								$response['rotate'] = $new_rotate;
								$response['uuid'][] = $data['Asset']['id'];
								$return = $return && empty($errors);
							}
						} else $return = false;
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
						$response = $this->__unshare($aid, $this->data['Group']['id'], true, true);
						$ret = $ret && ($response['success'] && $response['success']!=='false');
					}
					
				}	// end foreach
				
				if (!empty($this->data['updateBestshot']) && count($updateBestShot_assetIds)) {
					// updateBestshot
					$Usershot = ClassRegistry::init('Usershot');
					$Usershot->updateBestShotFromTopRated($bestshot_ownerId, null, $updateBestShot_assetIds); 
					$Groupshot = ClassRegistry::init('Groupshot');
					$Groupshot->updateBestShotFromTopRated($bestshot_ownerId, null, $updateBestShot_assetIds); 
				}
				if (!empty($this->data['setBestshot'])) {
					$Shot = ClassRegistry::init($this->data['shotType']);
					$Shot->setBestshot($bestshot_ownerId, $this->data['Shot']['id'], $this->data['Asset']['id'] );
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




	// from gallery.js: g.groupAsShot()
	function shot(){
// debug_XHRPost($this);		
		$forceXHR = setXHRDebug($this, 0);
		$success = true; $message=$response=array();
		$resp0 = compact('success', 'message', 'response'); 
		if ($this->RequestHandler->isAjax() || $forceXHR) {		
			$this->layout='ajax';
			$this->autoRender=false;
		/*
		 * detect Workorder, setup WorkorderPermissionable
		 */	
// TODO: move to beforeFilter? 
if (in_array(AppController::$role, array('EDITOR', 'MANAGER'))) {
	extract($this->data['Workorder']);
	// data[Workorder][type] = [Workorder|TasksWorkorder]
	// data[Workorder][woid] = [uuid]
	if (isset($type) && isset($woid)) {
		$this->Asset->Behaviors->attach('WorkorderPermissionable', array('type'=>$type, 'uuid'=>$woid));
		$wms = Session::read("WMS.{$woid}");
		$bestshot_ownerId = $wms['Workorder']['source_id'];  // set Bestshot owner to client_id
	}
}	

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
					// update bestShot group
					$shotType = $this->data['shotType'];
					if ($shotType=='Groupshot') {
						$group_id =  $this->data['group_id'];	
						if ($bestshot_ownerId == null) $bestshot_ownerid = AppController::$userid;
						$resp1 = $this->__groupAsShot($aids, $shotType, $group_id, $bestshot_ownerId);
					} else {
						$resp1 = $this->__groupAsShot($aids, $shotType);
					}
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
						// update bestshot for remaining Group
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
		// tag all assets in Shot, assume Usershot
		return $this->Asset->tagUsershot($tagString, $assetId, $replace = false);
	}
	
	
	/**
	 * __groupAsShot
	 * 		Usershot updated to support Usershot.priority, Usershot.active
	 * 	TODO: update Groupshot so we can deprecate $bestshot_ownerId
	 * 
	 * @param $assetIds string, CSV of asset uuids
	 * @param $shotType string, [Usershot | Groupshot]
	 * @param $gid string (optional), for Groupshot=group_id, Usershot uses AppController::$userid
	 * @param DEPRECATE $bestshot_ownerId string, for BestShotOwner, logged in user  
	 * @return array('shotId', 'bestshotId') or false
	 */
	function __groupAsShot( $assetIds, $shotType='Usershot', $gid=null, $bestshot_ownerId = null ){
// debug('/photos/shot: groupAsShot');		
		switch ($shotType) {
			case 'Usershot':
				$Usershot = ClassRegistry::init('Usershot');
				$force = $this->Asset->Behaviors->attached('WorkorderPermissionable');
				$isBestshot = !empty($this->data['isBestshot']);
				$resp = $Usershot->groupAsShot($assetIds, $isBestshot);				
				break;
			case 'Groupshot':
				// ???: can we deprecate bestshot_owner_id for Groupshot?
				$hasPerm = $this->Asset->hasPerm($assetIds, 'groupAsShot', 'Groupshot');
				$assetIds = $hasPerm[true];
				if ($hasPerm[false]) {
					$resp['message']['groupAsShot'] = "Warning: no permissions on aid=".explode(','.$hasPerm[false]);	
				}			
					// TODO: check Group Permission Settings: Permissionable->READ, or 
					// TODO: check WorkorderPermissionable->READ
				if ($bestshot_ownerId == null) $bestshot_ownerid = AppController::$userid;
				$Groupshot = $this->Asset->Groupshot;
				$resp = $Groupshot->groupAsShot($assetIds, $gid, $bestshot_ownerId);				
				break;
		}
		// mark ccid as stale
		if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
		$castingCall = $this->CastingCall->cache_MarkStale($this->data['ccid']);
		return $resp;
	}
	
	function __removeFromShot($assetIds, $shotId, $shotType='Usershot') {
		switch ($shotType) {
			case 'Usershot':
				$Usershot = ClassRegistry::init('Usershot');
				$resp = $Usershot->removeFromShot($assetIds, $shotId);				
				break;
			case 'Groupshot':
				// deprecate		
				$hasPerm = $this->Asset->hasPerm($assetIds, 'removeFromShot', 'Groupshot');
				$assetIds = $hasPerm[true];
				if ($hasPerm[false]) {
					$resp['message']['removeFromShot'] = "Warning: no permissions on aid=".explode(','.$hasPerm[false]);	
				}
												
				$Groupshot = $this->Asset->Groupshot;
				$resp = $Groupshot->removeFromShot($assetIds, $shotId);				
				break;
		}		
		return $resp;		
	}
	/**
	 * @param $shotIds array of UUID (DEPRECATE)
	 * @param $shotType
	 * 		UserShot: $uuid of owner == Asset.owner_id, or /person/photos/[$uuid]
	 * 		GroupShot: null
	 */ 
	function __ungroupShot($shotIds, $shotType='Usershot'){
		$shotIds = array_unique($shotIds);
		switch ($shotType) {
			case 'Usershot':
				$Usershot = ClassRegistry::init('Usershot');
				$resp = $Usershot->unGroupShot($shotIds);				
				break;
			case 'Groupshot':
debug("WARNING: hasPerm not checked for Groupshot");
				$Groupshot = $this->Asset->Groupshot;
				$resp = $Groupshot->unGroupShot($shotIds);				
				break;
		}		
		return $resp;
	}
	
	function __hiddenShots($shotIds, $shotType){
		if (is_string($shotIds)) $shotIds = explode(',', $shotIds);
		
		// paginate 
		$paginateModel = 'Asset';
		$Model = $this->Asset;
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $Model->getPaginatePhotosByShotId($shotIds, $this->paginate[$paginateModel], $shotType);
// debug($paginateArray);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
// debug(Set::extract('/owner_id', $pageData));				
		/*
		 * end Paginate
		 */
		if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
		$castingCall = $this->CastingCall->getCastingCall($pageData);
		return $castingCall;		
	}	
	/*
	 * @params $aids, array of uuids
	 * */
	function __findShots($aids, $shotType='Usershot'){
		if (!is_array($aids)) $aids = @explode(',', $aids);
		switch ($shotType) {
			case 'Usershot':
				if (!isset($this->Asset->Usershot)) $this->Asset->Usershot = ClassRegistry::init('Usershot');
				$shot_data = $this->Asset->Usershot->findShotsByAssetId($aids);
				return $shot_data;
			case 'Groupshot':				
				if (!isset($this->Asset->Groupshot)) $this->Asset->Groupshot = ClassRegistry::init('Groupshot');
				$shot_data = $this->Asset->Groupshot->findShotsByAssetId($aids);
				return $shot_data;				
		}
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
			$ret = $this->Asset->save($data, false);
			// $this->Session->setFlash("The privacy setting was successfully updated.");
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
	}	
	
}
?>