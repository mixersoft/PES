<?php
App::import('Controller', 'Collections');
class StoriesController extends CollectionsController {
	public $name = 'Collections';
	public $modelClass = 'Collection';	// or Story
	public $modelKey = 'collections';
	public $viewPath = 'stories';
	public $titleName = 'Stories';
	public $displayName = 'Story';	// section header
	
	public $validate = array();

	public $components = array(
		'Comments.Comments' => array('userModelClass' => 'User'	),
		'Search.Prg',
	);

	public $helpers  = array(
		'Tags.TagCloud',
		'Text',
	);


	public $paginate = array(
		'Collection'=>array(
			'preview_limit'=>6,
			'paging_limit' =>24,
			'order'=>array('Collection.modified'=>'DESC'),
			// 'order'=>array('SharedEdit.score DESC', 'Collection.modified'=>'DESC'),
		),
		'Asset'=>array(
			'preview_limit'=>6,
			'paging_limit' =>24,
			'photostream_limit' => 4,
			// 'order' => array('dateTaken_syncd'=>'ASC'),
			'order' => array('batchId'=>'DESC'),
			// 'order' => array('batchId'=>'DESC', 'dateTaken_syncd'=>'ASC'),
			'showSubstitutes'=>0,
			'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>false, // TODO: Usershot or Groupshots???
				'show_hidden_shots'=>false
			),		
			'recursive'=> -1,	
			// 'fields' =>array("DATE_ADD(`Asset`.`dateTaken`, INTERVAL coalesce(`AssetsGroup`.dateTaken_offset,'00:00:00')  HOUR_SECOND) AS dateTaken_syncd",
				// 'Asset.*'
			// ),
			'fields' =>array('Asset.*'),
		),
		'Group'=>array(
			'preview_limit'=>4,
			'paging_limit' =>8,
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
		parent::beforeFilter();
		/*
		 *	These actions are allowed for all snaps
		 */
		$allowedActions = array(
		/*
		 * main
		 */
		'home', 'groups', 'assets', 'discussion', 'trends', 'search', 
		/*
		 * all
		 */'index', 'all', 'most_active', 'most_views', 'most_recent', 'top_rated',   
		/*
		 * actions
		 */'get_asset_info', 
		
		);
		$this->Auth->allow( array_merge($this->Auth->allowedActions , $allowedActions));
		AppController::$writeOk = $this->Collection->hasPermission('write', AppController::$uuid);
	}


	function beforeRender() {
		if (!($this->RequestHandler->isAjax() || $this->RequestHandler->ext == 'json') && AppController::$uuid) {
			$label = !empty($this->viewVars['data']['Collection']['title']) ? $this->viewVars['data']['Collection']['title'] : '';
			if (Session::read("lookup.trail.{$this->displayName}.uuid") == AppController::$uuid) {
				Session::write("lookup.trail.{$this->displayName}.label", $label);	
			}
		}
		parent::beforeRender();
	}

	function __unshare($aid, $gids, $checkAuth = true, $updateCount = false) {
		$message = $response = $resp1 = $resp2 = array();
		if ($checkAuth) {
			// check for write perm on Asset 
			// for admin/moderator perm on Group use something like groups/unshare/[photo id]
			if (!$this->Collection->hasPermission('write',$aid) ) return false;
		}
		if ($gids && is_string($gids))  $gids = explode(',', $gids );
		// format assets for saveAll
		$CollectionsGroup = ClassRegistry::init('CollectionsGroup');
		$conditions['asset_id']=$aid;
		if (empty($gids)) {
			// get all gids to updateCount
			$options = array('conditions'=>array('asset_id'=>$aid), 'fields'=>'group_id', 'recursive'=>-1);
			$result = $CollectionsGroup->find('all',$options);
			$gids = Set::extract('/CollectionsGroup/group_id', $result);
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
		$ret = $CollectionsGroup->deleteAll($conditions, false);
		// $ret = 1;
// debug($ret);		
		if ($updateCount && $ret) {
			foreach ($gids as $gid) {
				ClassRegistry::init('group')->updateCounter($gid);
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

	function index() {
		$this->redirect(array('controller'=>Configure::read('controller.alias'), 'action'=>'all'));
	}

	function most_active(){
		$paginateModel = 'Collection';
		$this->paginate[$paginateModel]['order'] = array('Collection.comment_count'=>'DESC');
		$this->all();
	}
	
	function most_views(){
		$paginateModel = 'Collection';
		$this->paginate[$paginateModel]['order'] = array('Collection.collections_group_count'=>'DESC');
		$this->paginate[$paginateModel]['limit']  = Configure::read('feeds.paginate.perpage');
		$this->all();
	}

	function top_rated(){
		$paginateModel = 'Collection';
		$this->paginate[$paginateModel]['order'] = array('IF(`UserEdit`.rating,`UserEdit`.rating,`SharedEdit`.score)'=>'DESC');
		$this->paginate[$paginateModel]['limit']  = Configure::read('feeds.paginate.perpage');
		$this->all();
	}

	function most_recent(){
		$paginateModel = 'Collection';
		$this->paginate[$paginateModel]['order'] = array('Collection.modified'=>'DESC');
		$this->paginate[$paginateModel]['limit']  = Configure::read('feeds.paginate.perpage');
		$this->all();
	}
	
	function all(){
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';	
		// paginate 
		$paginateModel = 'Collection';
		$Model = $this->Collection;
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $this->paginate[$paginateModel];
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		if ($this->action !== 'all') {
			// force perpage set from feeds
			Configure::write('passedArgs.perpage',$this->paginate[$paginateModel]['limit']);
			$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		} else {
			$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		}			
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate		
		$this->viewVars['jsonData'][$paginateModel] = $pageData;	 
		$this->getLookups(array('Users'=> array_keys(Set::combine($pageData, '/owner_id', ''))));
		$done = $this->renderXHRByRequest('json', '/elements/collections/roll');
		if ($done) return;
		$options = array(
			'permissionable'=>false,
			'recursive'=>-1,
			'showEdits'=>false,
		);
		$ownerCount = $this->Collection->find('count', $options);
		$this->set(compact('ownerCount'));
		$this->action='index';  
	}
	

	function search(){
//		$this->Prg->commonProcess();

		// paginate 
		$paginateModel = 'Collection';
		$Model = $this->Collection;
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $this->paginate[$paginateModel];	
		$named = Configure::read('passedArgs.complete');
		
		if (isset($named['User'])) {
			$paginateArray = $Model->getPaginateCollectionsByUserId($named['User'], $paginateArray);
		} else if (isset($named['Asset'])) {
			$paginateArray = $Model->getPaginateCollectionsByPhotoId($named['Asset'], $paginateArray);
		} else if (isset($named['Tag'])) {
			$paginateArray = $Model->getPaginateCollectionsByTagId($named['Tag'], $paginateArray);
		} else {
			$paginateArray = $Model->getPaginateCollections($paginateArray);	
		}		
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate		
		
		$this->viewVars['jsonData'][$paginateModel] = $pageData; 
		$this->getLookups(array('Users'=> array_keys(Set::combine($pageData, '/owner_id', ''))));
		$done = $this->renderXHRByRequest('json', '/elements/collections/roll');
		if ($done) return;
		$this->action='index'; 
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
		
		// paginate 
		$paginateModel = 'Asset';
		$Model = $this->Collection->{$paginateModel};
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $Model->getPaginatePhotosByCollectionId($id, $this->paginate[$paginateModel]);
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate
		if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
		$castingCall = $this->CastingCall->getCastingCall($pageData);
		$this->viewVars['jsonData']['castingCall'] = $castingCall;

		if (Session::read('lookup.context.keyName')!='person')  {
			// add owner_names to lookup.
			$this->getLookups(array('Users'=> array_keys(Set::combine($pageData, '/owner_id', ''))));
		}
					
		$done = $this->renderXHRByRequest('json', '/elements/photo/roll');
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false	
		$options = array('conditions'=>array('Collection.id'=>$id));
		$this->Collection->contain('Owner.username');	
		$data = $this->Collection->find('first', $options);
		if (empty($data)) {
			/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash(sprintf(__('No %s found.', true), 'Photos'));
			$this->redirectSafe();
		} else {
			$this->set('data', $data);
			$this->viewVars['jsonData']['Collection'][]=$data['Collection'];
			Session::write('lookup.owner_names', Set::merge(Session::read('lookup.owner_names'), Set::combine($data, '/Owner/id', '/Owner/username')));
		}
		$this->set(array('assets'=>$data,'class'=>'Asset'));
	}

	function groups($id){
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';	
				// paginate 
		$paginateModel = 'Group';
		$Model = $this->Collection->{$paginateModel};
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $Model->getPaginateGroupsByCollectionId($id, $this->paginate[$paginateModel]);
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate
		$this->viewVars['jsonData'][$paginateModel] = $pageData;
		
		$done = $this->renderXHRByRequest('json', '/elements/group/roll');
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false	
		
		
		$options = array(
			'conditions'=>array('Collection.id'=>$id),
		);
		$this->Collection->contain(null);		
		$data = @$this->Collection->find('first', $options);
		$data['Group']  = $pageData;
		$this->set('data', $data);	
//		debug($data);
	}	

	


	/**
	 * get cached CastingCall, extend if necessary for filmstrip Navigation
	 */
	function neighbors($ccid=null) {
		return;
	}

	/**
	 * called from "Create New Story"
	 * - assign UUID for new story, 
	 * 		somehow pass UUID to storymaker player, by cookie? 
	 * - if UUID is provided, title is optional
	 * ???: what about Guest users?
	 * ???: does Create New story still render in Dialog.Alert, or new page?
	 */
	function create(){
		
	}
	function home($id = null) {
		$this->__redirectIfTouchDevice();
		
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';		
		if (!$id) {
			$this->redirect('/my/stories', null, true);
		}
		/*
		 * get Permissionable associated data manually, add paging
		 */
		
		// get Group data
		$options = array(
			'contain'=>array('Owner.id', 'Owner.username'),
			'fields'=>'Collection.*',		// MUST ADD 'fields' for  containable+permissionable
//				'permissionable'=>false,
			'conditions'=>array('Collection.id'=>$id),
		);
		$data = $this->Collection->find('first', $options);
			if (empty($data)) {
				/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash(sprintf(__('No %s found.', true), 'Story'));
			$this->redirectSafe();
		} else {
			$title = "{$data['Collection']['title']} :: Stories@Snaphappi"; 
			$this->set('title_for_layout', $title);
			$this->set('data', $data);
			
			$page_gallery = explode('\n',$data['Collection']['markup']);
			$link = $this->here;
            $isPreview = isset($this->params['url']['preview']) ? $this->params['url']['preview'] !== '0' : false;
			$this->set(compact('page_gallery', 'link', 'isPreview', 'title'));
		}
	}
	
	function story($id = null) {
		$this->__redirectIfTouchDevice();
		
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		if (!$id) {
			$this->redirect('/my/stories', null, true);
		}
		/*
		 * get Permissionable associated data manually, add paging
		 */
		
		// get Group data
		$options = array(
			'contain'=>array('Owner.id', 'Owner.username'),
			'fields'=>'Collection.*',		// MUST ADD 'fields' for  containable+permissionable
//				'permissionable'=>false,
			'conditions'=>array('Collection.id'=>$id),
		);
		$data = $this->Collection->find('first', $options);
			if (empty($data)) {
				/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash(sprintf(__('No %s found.', true), 'Story'));
			$this->redirectSafe();
		} else {
			/*
			 * copied from /gallery/story
			 */ 
			$title = "{$data['Collection']['title']} :: Stories@Snaphappi"; 
			$page_gallery = explode('\n',$data['Collection']['markup']);
			$host = env('HTTP_HOST');
			$link = "http://{$host}{$this->here}";
            $isPreview = isset($this->params['url']['preview']) ? $this->params['url']['preview'] !== '0' : false;
			$this->set(compact('page_gallery', 'link', 'isPreview', 'title'));
			$done = $this->renderXHRByRequest(null, '/stories/story');
			if ($done) return;
        	$this->set('title_for_layout', $title);
			
			$this->layout = "story";
		}
	}


	function __getPrivacyConfig(){
		/*
		 * configure privacy defaults for radio buttons
		 */
		$privacy['Collection'][519]="<b>Public</b> - are publicly listed and visible to anyone.";
		$privacy['Collection'][71]="<b>Members only </b> - are NOT publicly listed, and are visible only when shared in Groups or Events, and only by Group members.";
		$privacy['Collection'][7]="<b>Private</b> - are NOT publicly listed and visible only to me.";

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
		$data['Collection']['privacy_assets'] = $perms;

	}

	function __encodePerms(& $data){
		$perms = @ifed($data['Collection']['privacy_assets']);
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
			$qs = @http_build_query(array('setting'=>$this->data['Collection']['setting']));
			$redirect = Router::url(array('action'=>'edit', $id)). ($qs ? "?{$qs}" : '');
			$this->redirect($redirect, null, true);
		}

		$privacy = $this->__getPrivacyConfig();
		//		$policy =  $this->__getPolicyConfig();
		$this->set(compact('policy', 'privacy'));

		$options = array(
			'conditions'=>array('Collection.id'=>$id),
			'contain'=> array('Owner.id', 'Owner.username', 'ProviderAccount.id', 'ProviderAccount.provider_name', 'ProviderAccount.display_name'),
			'fields'=>'Collection.*',		// MUST ADD 'fields' for  containable+permissionable
			'extras'=>array(
				'show_edits'=>true,
				// 'join_shots'=>$shotType, 		// join shots to get shot_count?
				'join_bestshot'=>false,			// do NOT need bestShots when we access by $asset_id
				// 'show_hidden_shots'=>true,		// by $asset_id, hidden shots ok, or DONT join_bestshot
			),
		);
		$data = $this->Collection->find('first', $options);

		$this->__decodePerms($data);


		$this->data = $data;
		$this->set('data', $data);	
			
		//		$providerAccounts = $this->Collection->ProviderAccount->find('list');
		//		$owners = $this->Collection->Owner->find('list');
		//		$sharedEdits = $this->Collection->SharedEdit->find('list');
		//		$collections = $this->Collection->Collection->find('list');
		//		$groups = $this->Collection->Group->find('list');
		//		$this->set(compact('providerAccounts', 'owners', 'sharedEdits', 'collections', 'groups'));
		
		$xhrFrom = Configure::read('controller.xhrFrom');
		if ($xhrFrom) {
			$viewElement = '/elements/collections/'.$xhrFrom['view'];
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
				if ($this->Collection->save($this->data)) {
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


		$this->Collection->recursive = 0;
		$data = $this->Collection->read(null, $id);

		$this->__decodePerms($data);

		$this->data = $data;
		$this->set('asset', $data);
	}

	/*
	 * copied from Asset::delete, but not sure this is the correct template
	 */
	function XXXdelete ($id = null) {
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
				$aids = (array)@explode(',', $this->data['Collection']['id']);
				$usershots = $this->__findShots($aids, 'Usershot');
				$shotsByAssetId = Set::combine($usershots, '/AssetsShot/asset_id', '/Shot');
// debug($shotsByAssetId);				
				$retval = true;
				/*
				 * NOTE: must delete assets individually to cleanup User/Groupshots properly
				 * 	update counterCache at end
				 */ 
				foreach ($aids as $aid) {
					if ($this->Collection->hasPermission('delete',$aid) == false) {
						$errors[] = "WARNING: no delete permissions on uuid={$aid}";
						$retval = false;
						continue;
					}
					// _unshare() -> remove from CollectionsGroup, remove from GroupShots, update GroupCount 
					if (empty($gids)){
						// get all gids to updateCount
						$options = array('conditions'=>array('asset_id'=>$aid), 
							'fields'=>'group_id', 
							'recursive'=>-1);
						$result = ClassRegistry::init('CollectionsGroup')->find('all',$options);
						$gids_1 = Set::extract('/CollectionsGroup/group_id', $result);					
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
						//TODO: need $uuid of Collection.owner_id, check when role=EDITOR, etc.			
						if ($shot['count'] > 2 ) {
							$resp2 = $this->__removeFromShot(array($aid), $shot['shot_id'], 'Usershot', $owner_id);
						} else {
							
							$resp2 = $this->__ungroupShot(array($shot['shot_id']), 'Usershot', $owner_id);	
						}
						$retval = $retval && ($resp2['success'] && $resp2['success']!=='false');
						/*
						 * on Delete, we just reload/render "refreshed" castingCall for updates
						 */
					}
					// remove from Assets	
					$delete_retval = @$this->Collection->delete($aid, true);
					$retval = $retval && $delete_retval;
					$jsonResp['response']['aids'][$aid] = $delete_retval;

				}
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
		if (empty($this->data['Collection']['id'])) $this->data['Collection']['id'] = $id;
		if (!isset($this->data['Group']['gids'])) $this->data['Group']['gids'] = null;
		if ($forceXHR || $this->RequestHandler->isAjax()) {
			$this->layout='ajax';
			$this->autoRender=false;
			if ($this->data) {
				$gids = $this->data['Group']['gids'];
				$aid = $this->data['Collection']['id'];
				$response = $this->__unshare($aid, $gids, true, true);
				$retval = ($response['success'] && $response['success']!=='false');
			} else $retval = 0;
		}

		if ($this->RequestHandler->isAjax()) {
			return $retval;
		} else {
			if ($retval) $this->Session->setFlash('This Story was successfully unshared.');
			else  {
				$this->Session->setFlash('ERROR: There was a problem unsharing this Story. Please try again.');
				if ($this->data['Group']['gids']===null)
				$this->Session->setFlash("WARNING: UNSHARE ALL GROUPS IS NOT TESTED");
			}
			if (!$forceXHR) {
				$next = env('HTTP_REFERER');
				$this->redirect($next, null, true);
			}
		}
	}

	function set_as_story_cover($id, $cid=null) {
		if ($cid==null && Session::read('lookup.context.keyName')=='collection') $cid = Session::read('lookup.context.uuid');
		$next = env('HTTP_REFERER');
		if (!$cid) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'Story'));
			$this->redirect($next, null, true);
		}
		$Collection = ClassRegistry::init('Collection');
		if (!$Collection->hasPermission('write',$cid)) {
			$this->Session->setFlash('You do not have permission to set the cover photo for this Story.');
			$this->redirect($next, null, true);
		}
		$this->Collection->id = $id;
		$src = $this->Collection->field('src_thumbnail');
		$Collection->id = $cid;
		$ret = $Collection->saveField('src_thumbnail', Stagehand::getImageSrcBySize($src, 'tn'));
		if ($ret) {
			$this->Session->setFlash('The group cover photo was successfully set.');
			$this->redirect($next, null, true);
		}
		$this->Session->setFlash('There was an error setting the group cover photo. Please try again.');
		$this->redirect($next, null, true);
	}

	
	function trends($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'Story'));
			$this->redirectSafe();
		}	
		$options = array(
			'conditions'=>array('Collection.id'=>$id),
			'contain'=> array('Owner.id', 'Owner.username'),
			'fields'=>'Collection.*',		// MUST ADD 'fields' for  containable+permissionable
		);
		$data = $this->Collection->find('first', $options);
		if (empty($data)) {
			/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash("Story not found.");
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
			'conditions'=>array('Collection.id'=>$id),
			'contain'=> array('Comment', 'Owner.id', 'Owner.username'),
			'fields'=>'Collection.*',		// MUST ADD 'fields' for  containable+permissionable
			'extras'=>array(
				'show_edits'=>false,
			),
		);		
		$data = $this->Collection->find('first', $options);
		$this->set('data', $data);	
		
		if (Configure::read('controller.action')=='discussion') $this->paginate['Comment']['limit'] = $BIG_LIMIT;
		
		$done = $this->renderXHRByRequest(null, '/elements/comments/discussion');
		// or $this->autoRender
	}
	/********************************************************************
	 * ajax POST methods
	 */
	function save_page() {
    	$forceXHR = setXHRDebug($this, 1);
        $this->layout = 'snappi-guest';
        $ret = 0;
        if ($this->data) {
        	$title = !empty($this->data['dest']) ? $this->data['dest'] : null;
			$replace =  isset($this->params['url']['reset']) ? 'replace' : null;
        	$uuid = !empty($this->data['Collection']['id']) ? $this->data['Collection']['id'] : null;
        	$uuid = $this->Collection->save_page($this->data, $uuid, $replace);
		}
		$this->autoRender = false;
		Configure::write('debug',0);
		$success = $uuid ? true : false;
		if ($success) {
			header("HTTP/1.1 201 CREATED");
			$message = "Your Story was saved.";
			$response = array(
				'uuid'=>$uuid,
				'key'=>$uuid,
				// 'key'=>$secretKeyDest, 
				'title'=> $title,
				// 'href'=>"/gallery/story/{$dest}_{$secretKeyDest}",
				'href'=>"/stories/home/{$uuid}",
			);
		} else {
			$message = "There was an error saving your Story. Please try again.";
			$response = array();
		}
		$this->viewVars['jsonData'] = compact('success', 'message', 'response');
		$done = $this->renderXHRByRequest('json', '/elements/dumpSQL');
		return;
    }

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
			//					$this->data['Collection']['id']='4bbb3976-a080-44f5-84c8-11a0f67883f5';
			//					$this->data['Collection']['rating']=4;
			if ($this->data) {
				// check write permission????
				$fields = array_keys($this->data['Collection']);
				$aids = (array)@explode(',', $this->data['Collection']['id']);
				$ret = true; 
				//TODO: begin SQL transaction for batch commit
				$updateBestShot_assetIds = array();
				$activePerAssetFields = array_intersect(array('rating','rotate','tags','chunk','privacy','unshare'), $fields);
// debug($aids);				
				foreach ($aids as $aid) {
					if (count($activePerAssetFields)==0) break;	// skip if ther eare not active PerAssset Fields
					/*
					 * one asset_id per DB call
					 */
					$this->Collection->contain('ProviderAccount');
					$options = array(
						'conditions'=>array('Collection.id'=>$aid),
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
					$data = $this->Collection->find('first',$options);
					$asset_hash = $data['Collection']['asset_hash'];
					// get asset_hash for each asset. why?
					
					//TODO: consolidate all Asset operations into one SQL stmt
					if (in_array('rating', $fields)) {
						// update rating and score
						$newRating = $this->data['Collection']['rating'];
						$oldRating = @ifed($data['Collection']['rating'], 0);
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
						$data['UserEdit']['isEditor'] = AppController::$userid!=AppController::$ownerid;
						$data['UserEdit']['asset_hash'] = $asset_hash;
						$data['SharedEdit']['asset_hash'] = $asset_hash;
						unset($data['AssetPermission']);
						$return = $this->Collection->UserEdit->saveEdit($data);
						$return = $return && $this->Collection->SharedEdit->save($data);
						if ($return) $updateBestShot_assetIds[] = $aid;
						$ret = $ret && $return;
					}
					if (in_array('rotate', $fields) && !empty($this->data['Collection']['rotate'])) {
						// get exif data from DB
						$options = array(
							'conditions'=>array('Collection.id'=>$aid), 
							'fields'=>array('id', 'json_exif', 'json_src'),
							'extras' => array(
								'join_shots'=>false,	// get ALL photos
								'show_edits' => false,
							),							
						);
						$data = $this->Collection->find('first', $options);
						$rotate = $this->data['Collection']['rotate'];
						if (in_array($rotate, array(3,6,8))) {
							$basepath = Configure::read('path.stageroot.basepath');
							// goal: set Audition.Photo.Fix.Rotate
							if (!empty($data['Collection'])) {
								$json_exif = json_decode($data['Collection']['json_exif'], true);
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
								$repsonse['uuid'] = $data['Collection']['id'];
								// get src for preview derived asset
								$json_src = json_decode($data['Collection']['json_src'], true);
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
								$data['Collection']['json_exif']=json_encode($json_exif);
								$this->Collection->id = $data['Collection']['id'];
								$this->Collection->disablePermissionable(true);
								$return = $this->Collection->saveField('json_exif', $data['Collection']['json_exif'], false);
								$this->Collection->disablePermissionable(false);
								// update rotate preview
								// $previewSrc = Stagehand::getImageSrcBySize($thumb_src, 'bp');
								if (!isset($this->Jhead)) $this->Jhead = loadComponent('Jhead', $this);
								$errors =  $this->Jhead->exifRotate($new_rotate, $previewSrc);
								
								$response['rotate'] = $new_rotate;
								$response['uuid'][] = $data['Collection']['id'];
								$return = $return && empty($errors);
							}
						} else $return = false;
						$ret = $ret && $return;
					}					
					
					if (in_array('tags', $fields) && !empty($this->data['Collection']['tags'])) {
						// update tags

						$ret = $ret && $this->__addTag($this->data['Collection']['tags'], $aid);

					}


					if (in_array('chunk', $fields) && !empty($this->data['Collection']['chunk'])) {
						// update substitution group
						$uuid = @ifed($this->data['Collection']['chunk'], String::uuid());
						$ret = $ret && $this->__addChunks($uuid, $aid);

					}
					if (in_array('privacy', $fields) && !empty($this->data['Collection']['privacy'])) {
						$ret =  $ret && $this->__setPrivacy($this->data['Collection']['privacy'], $aid);
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
					$Usershot->updateBestShotFromTopRated(AppController::$ownerid, null, $updateBestShot_assetIds); 
					$Groupshot = ClassRegistry::init('Groupshot');
					$Groupshot->updateBestShotFromTopRated(AppController::$userid, null, $updateBestShot_assetIds); 
				}
				if (!empty($this->data['setBestshot'])) {
					$Shot = ClassRegistry::init($this->data['shotType']);
					$Shot->setBestshot(AppController::$userid, $this->data['Shot']['id'], $this->data['Collection']['id'] );
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

	function __addTag($tagString, $assetId) {
		// Taggable->saveTags()
		// tag all assets in Shot, assume Usershot
		return $this->Collection->saveTags($tagString, $assetId, $replace = false);
	}
	function __setPrivacy($privacy, $assetIds) {
		$privacySetting = $this->__getPrivacyConfig();
		if (!in_array((int)$privacy, array_keys($privacySetting['Collection']))) {
			// TODO:  setFlash message
			return false;
		}
		$ids = explode(',',$assetIds);
		$errors=array();
		foreach ($ids as $asset_id) {
			if (empty($asset_id)) continue;
			$this->Collection->id = $asset_id;
			/*
			 * Q: is Asset['privacy_groups'] the same as Permissionable privacy?
			 */
			$data['Collection']['id'] = $asset_id;
			// $data['Collection']['privacy_groups'] = $privacy;
			$data['Permission']['perms'] = $privacy;
			$ret = $this->Collection->save($data, false);
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
			$asset_id = $this->params['url']['data']['Collection']['id'];	
			$options = array(
				'conditions'=>array('Collection.id'=>$asset_id),
				'permissionable'=>false);
			$this->Collection->contain();
			$assetInfo = $this->Collection->find('first', $options);
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
	
	
}
?>