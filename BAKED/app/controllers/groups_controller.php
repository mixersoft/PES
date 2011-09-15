<?php
class GroupsController extends AppController {
	public $name = 'Groups';
	
	public $titleName = 'Groups';
	public $displayName = 'Group';	// section header

	public $components = array(
		'Comments.Comments' => array( 'userModelClass' => 'User'),
		'Search.Prg',
	);

	public $helpers  = array(
		'Tags.TagCloud',
		'Time','Text',
		'CastingCallJson',
//		'Js' => array('Jquery'),
//		'Paginator',
	);

	public $paginate = array(
		'photostream'=>array('limit'=>20),
		'Group'=>array(
			'limit' => 16,
			'big_limit' =>36,
			'order'=>array('Group.title'=>'ASC'),
		),
		'ProviderAccount'=>array(
				'limit' => 5,
				'big_limit' =>20,
				'order' => array('ProviderAccount.created'=>'ASC'),
				'fields' =>'ProviderAccount.*',
		),	
		'Asset'=>array(
				'limit' => 16,
				'big_limit' =>48,
				'photostream_limit' => 4,
				'order' => array('dateTaken_syncd'=>'ASC'),
				'showSubstitutes'=>0,
				'extras'=>array(
					'show_edits'=>true,
					'join_shots'=>'Groupshot', 
					'show_hidden_shots'=>false
				),		
				'recursive'=> -1,	
				'fields' =>array("DATE_ADD(`Asset`.`dateTaken`, INTERVAL coalesce(`AssetsGroup`.dateTaken_offset,'00:00:00')  HOUR_SECOND) AS dateTaken_syncd",
					'Asset.*'
				),
		),
		'Member'=>array(
			'limit' => 8,
			'big_limit' =>36,
			'order'=>array('Member.created'=>'ASC'),
			'recursive'=> -1,
			'fields' =>'Member.*',
			'joins' => array()
		),
		'Comment' =>array(
			'limit'=>5,
			'order'=>array('Comment.created'=>'DESC'),
	)

	);


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
			'home', 'photos', 'members', 'search', 'trends', 'discussion',  
			/*
			 * public
			 */'index', 'all', 'open', 'most_active', 'most_recent','most_members','most_photos',
			/*
			 * experimental 
			 */'setRandomGroupCoverPhoto', 'test', 'load', 'addACL'		
		);
		AppController::$writeOk = $this->Group->hasPermission('write',AppController::$uuid);
		// TODO: why can't I just attach Taggable on the fly??
		$this->Group->Behaviors->detach('Taggable');
	}
	
	function beforeRender() {
		Configure::write('js.bootstrap_snappi', true);
		if (!$this->RequestHandler->isAjax() && AppController::$uuid) {
			$label = @ifed($this->viewVars['data']['Group']['title'], null);
			if (Session::read("lookup.trail.{$this->keyName}.uuid") == AppController::$uuid) {
				Session::write("lookup.trail.{$this->keyName}.label", $label);	
			}
		}
		parent::beforeRender(); 
	}

	function test(){
		$this->autoRender = false;
//		$this->__setRandomGroupCoverPhoto();
		$this->__setCounts(AppController::$uuid);
	}

	function __setRandomGroupCoverPhoto($id = null){
		$this->Group->contain('Asset.src_thumbnail');
		$this->Group->disablePermissionable();
		$options = array('conditions'=>array('isSystem'=>0), 'recursive' => -1, 'fields'=>'id');
		if ($id) $options['conditions']['id'] = $id;
		$data = $this->Group->find('all', $options);
		$gids = Set::extract('/Group/id', $data);
		foreach ($gids as $gid) {
			$this->Group->id = $gid;
			$sql = "
SELECT Asset.src_thumbnail, SharedEdit.score, Asset.id
FROM assets Asset
LEFT JOIN assets_groups ag on ag.asset_id = Asset.id
LEFT JOIN shared_edits AS `SharedEdit` ON (`SharedEdit`.`asset_hash` = `Asset`.`asset_hash`)
WHERE group_id='{$gid}'
order by score desc
LIMIT 5;";
			$asset = $this->Group->query($sql);
			$srcs = Set::extract('/Asset/src_thumbnail', $asset);
			shuffle($srcs);
			$ret = $this->Group->saveField('src_thumbnail', array_shift($srcs));
		}
		return $ret;
	}
	function __setCounts($id){
		return $this->Group->updateCounter($id);
	}

	function load(){
		App::import('lib', 'initdata');
		//		Data::load_testDB();
		//		Data::load_Acl_groups($this);
		Data::update_ACLs();
		//	Data::load_testMemberGroups();
		$this->autoRender = false;
	}
	function __submissionPolicy($id) {
		$this->Group->id=$id;
		$submission_policy = $this->Group->field('submission_policy');
		return $submission_policy;		
	}

	function __canPublish($id) {
		$submission_policy = (int)$this->__submissionPolicy($id);
		return $submission_policy === 1;		// 1=publish, 2=queue for Admin approval
	}
	
	function __canSubmit($id) {
		return $this->Group->hasPermission('read', $id);		// for now, assume if we have READ access, we have SHARE/CONTRIBUTE permission		
	}

	function __membershipPolicy($id) {
		$this->Group->id=$id;
		$policy = $this->Group->field('membership_policy');
		// use placeholder for now.
		return true;
	}

	function __joinGroup($groupId, $userId){
		// TODO:  check permission to write to group before adding
		$isApproved = $this->__membershipPolicy($groupId);

		$data['group_id'] = $groupId;
		$data['user_id'] = $userId;
		$data['isApproved'] = $isApproved;
		$data['role'] = 'member';
		$data['isActive'] = $isApproved;
		$data['lastVisit'] = time();
		$GroupUsers = ClassRegistry::init('GroupsUser');
		$GroupUsers->create();
		$ret =  @$GroupUsers->save($data);
		if ($ret) {
			// reinitialize Permissionable::group_ids
//			$this->Session->delete('Auth.Permissions.group_ids');
//			$this->Permissionable->initialize($this);
			$groupIds = Permissionable::getGroupIds();
			array_push($groupIds, $groupId);
			Permissionable::setGroupIds($groupIds);
			$this->Session->write('Auth.Permissions.group_ids', Permissionable::$group_ids);
		}
		return $ret;
	}

	function join($id) {
		$forcePOST = 0;		// for debugging POST in debugger using GET 
		if ($forcePOST && !empty($this->params['url']['data'])) {
			$this->data = $this->params['url']['data'];
		}
		if ($this->data) {
			if (isset($this->data['Group'])) {
				$join = $this->data['Group'];
				if (!isset($join['id']) || $join['id']!=$id ) {
					$this->Session->setFlash('Warning: There was some kind of mistake. Please try again');
				} else {
					$ret = $this->__joinGroup($join['id'], Session::read('Auth.User.id'));
					if ($ret) {
						$this->Session->setFlash("Welcome! You are now a member of group <b>{$join['title']}</b>.");
						$this->redirect(array('action'=>'home', $id), null, true);
					} else {
						$this->Session->setFlash('Error: there was a problem joining this group. Please try again.');
					}
				}
			}
		}

		$options = array('conditions'=>array('Group.id'=>$id));
		$this->Group->contain(null);
		$data = @$this->Group->find('first', $options);
		
		$isMember = in_array(AppController::$uuid, Permissionable::getGroupIds());
		$isOwner = $data['Group']['owner_id'] == AppController::$userid;
		if ($isMember || $isOwner ) {
			$this->Session->setFlash('You are already a member of this group.');
			$this->redirect(array('action'=>'home', $id));
		}  else {
			$owner_name = Session::read('lookup.owner_names.'.$data['Group']['owner_id']);
			if (empty($owner_name)) $owner_name = $data['Group']['owner_id'];
			$this->set(compact('id', 'owner_name', 'data', 'isMember'));
		}
	}
	
	// push this method into Model
	function __contributePhotostream($groupId, $providerAccountId, $batchId=null){
		// TODO:  check permission to write to group before adding

		$isApproved = $this->__submissionPolicy($groupId);
		$userid = Session::read('Auth.User.id');
		$options = array(
			'conditions'=>array('Asset.provider_account_id'=>$providerAccountId, 
									'Asset.owner_id'=>$userid),
			'fields'=>array('Asset.id'),
		//			'limit'=>5,
			'order'=>'Asset.dateTaken ASC',
			'recursive'=>-1,
		);
		if ($batchId) $options['conditions']['Asset.batchId'] = $batchId;
		$assets = $this->Group->Asset->find('all', $options);

		// format assets for saveAll
		$assets = Set::extract($assets, '/Asset/id');
		return $this->__contributePhoto($groupId, $assets, false);
	}

	// push this method into Model
	function __contributePhoto($groupId, $assets, $checkAuth=true){
		$debug = Configure::read('debug');
		$aids = $assets;
		if ($checkAuth) {
			if (!$this->__canSubmit($groupId)) return false;
		}
		$isApproved = $this->__canPublish($groupId);
		if (is_string($assets))  $assets = explode(',', $assets );

		// format assets for saveAll
		$saveAll_assets = array();
		$userid = Session::read('Auth.User.id');
		foreach($assets as $asset) {
			$VALUES_assets_groups[] = "( UUID(), '{$asset}', '{$groupId}', '{$isApproved}', '{$userid}', null, now() )";
		}		
		$INSERT_assets_groups = "INSERT INTO `assets_groups` (id, asset_id, group_id, isApproved, user_id, dateTaken_offset, modified ) 
VALUES  :chunk:
ON DUPLICATE KEY UPDATE `user_id`=VALUES(`user_id`), `dateTaken_offset`=VALUES(`dateTaken_offset`), `modified`=VALUES(`modified`);
";
		$VALUES = insertByChunks($INSERT_assets_groups, $VALUES_assets_groups);		
		$AssetGroup = ClassRegistry::init('AssetsGroup');
		foreach ($VALUES as $chunk) {
			$INSERT = str_replace(':chunk:', $chunk, $INSERT_assets_groups);
			$AssetGroup->query($INSERT);	
		}
		$result = $this->insertIntoGroupsProviderAccount($groupId, $assets);
		ClassRegistry::init('Usershot')->copyToGroupshots($groupId, $assets);
		return count($VALUES_assets_groups);
		
//		foreach($assets as $asset) {
//			// TODO: check for group Write to allow non-owner to contribute photos
//			$saveAll_assets[] = array('asset_id'=>$asset, 'group_id'=>$groupId, 'user_id'=>Session::read('Auth.User.id'), 'isApproved'=>$isApproved);
//		}
//		$AssetsGroup = ClassRegistry::init('AssetsGroup');
//		Configure::write('debug', 0);	// supress mysql duplicate key warnings
//		$ret =  $AssetsGroup->saveAll($saveAll_assets);
//		if ($ret) {
//			$result = $this->insertIntoGroupsProviderAccount($groupId, $assets);
//			if ($result) {
//				Configure::write('debug', $debug);	
//				ClassRegistry::init('Usershot')->copyToGroupshots($groupId, $assets);
//				return count($saveAll_assets);
//			}else{
//				return $result;
//			}
//		} else {
//			// try to save assets one by one
//			$ret=array(0=>0,1=>0);
//			Configure::write('debug', 0);	// supress mysql duplicate key warnings
//			foreach($saveAll_assets as & $asset) {
//				$data['AssetsGroup'] = $asset;
//				$AssetsGroup->create();
//				$retval = $AssetsGroup->save($data);
//				if ($retval) $ret[1]++;
//				else $ret[0]++;
//			}
//			Configure::write('debug', $debug);				
//			$result = $this->insertIntoGroupsProviderAccount($groupId, $assets);
//			if ($result) {
//				ClassRegistry::init('Usershot')->copyToGroupshots($groupId, $assets);
//				return $ret[1];		// return number of items added
//			}else{
//				return $result;
//			}
//		}
	}	
	
	function insertIntoGroupsProviderAccount($groupId, $assets){
		$ret = 1;
		$Asset = ClassRegistry::init('Asset');
		$GroupsProviderAccount = ClassRegistry::init('GroupsProviderAccount');
		if (is_string($assets))  $assets = explode(',', $assets );
		
		$options = array(
			'fields' => array('DISTINCT Asset.provider_account_id'), 
			'conditions' => array('Asset.id' => $assets),
			'permissionable' => false, 
		); 
		$data = $Asset->find('all', $options);
		$providerAccountIds = Set::extract('/Asset/provider_account_id', $data);
		
		foreach ($providerAccountIds as $pa_id) {
			$VALUES[]= "( UUID(), '{$groupId}', '{$pa_id}', now() )";
		}		
		$INSERT_paids = "INSERT IGNORE INTO `groups_provider_accounts` (`id`, `group_id`, `provider_account_id`, `created`) 
VALUES :chunk: ";
		$VALUES = insertByChunks($INSERT_paids, $VALUES);		
		foreach ($VALUES as $chunk) {
			$INSERT = str_replace(':chunk:', $chunk, $INSERT_paids);
			$GroupsProviderAccount->query($INSERT);	
		}			
		return 1;
		
// 		$distinct_new_provider_ids = array_keys(array_flip(Set::extract($providerAccountIds , '/Asset/provider_account_id')));
// 				
// 		// $existingProviderAccountIds is distinct/unique because of table index
// 		$options = array(
//	 		'fields' => array('GroupsProviderAccount.provider_account_id'), 
//	 		'conditions' => array('GroupsProviderAccount.group_id' => $groupId)
// 		);
//		$existingProviderAccountIds = $GroupsProviderAccount->find('all', $options);
//		$new_pa_ids = array_diff($distinct_new_provider_ids, Set::extract($existingProviderAccountIds , '/GroupsProviderAccount/provider_account_id'));
//		
//		// insert new providerAccounts into GroupsProviderAccounts
//		$groupsProviderAccounts = array();
//		foreach($new_pa_ids as $new_pa_id) {
//			$groupsProviderAccounts[] = array('group_id'=>$groupId, 'provider_account_id'=>$new_pa_id, 'created'=>date("Y-m-d H:i:s"));
//		}
//		if (!empty($groupsProviderAccounts)){
//			$ret =  $GroupsProviderAccount->saveAll($groupsProviderAccounts);
//		}
//		return $ret;
	}
	
	//TODO: update response to standard json format
	function contributePhoto(){
		if (Configure::read('debug') && ! $this->RequestHandler->isAjax()) {
			$this->data = $this->params['url']['data'];
		}
		if (1 || $this->RequestHandler->isAjax()) {
			$this->layout='ajax';
			$this->autoRender=false;
			if ($this->data) {
				$gid = $this->data['Group']['id'];
				$aids = $this->data['Asset']['id'];
				$retval = $this->__contributePhoto($gid, $aids);		// checks share permission, check SHARE perm on Photo 
				if ($retval) {
					$this->Group->updateCounter($gid);
					// set random photo if none
					$this->Group->id = $gid;
					$src = $this->Group->field('src_thumbnail');
					if ( empty($src )) {
						$this->__setRandomGroupCoverPhoto($gid);		// set cover photo, if none
					}
					//echo $retval;
					$success = 'true';
					$message = 'Share successfully!';
					$response = '';
					//return;
				}else{
					$success = 'true';
					$message = 'Shared before!';
					$response = '';
				}
			}else{
				$success = 'false';
				$message = 'There is no data!';
				$response = '';
			}
			//echo "0";
			$this->viewVars['jsonData'] = compact('success', 'message', 'response');
			$done = $this->renderXHRByRequest('json');
			return;
		}
	}

	function contribute($id) {
		if ($this->data) {
			if (isset($this->data['Group'])) {
				$contribute = $this->data['Group'];
				/*
				 * check Group perms, if public, perms==567, then we must offer to upgrde asset perms to public=519
				 */
				$perms = $this->Group->getPermission($contribute['id']);
				$GROUP_PRIVACY_IS_PUBLIC = 567;
				$CONTENT_PRIVACY_IS_PUBLIC = 519;
				if ($perms == $GROUP_PRIVACY_IS_PUBLIC) {
					$asset_perms = $CONTENT_PRIVACY_IS_PUBLIC;
					/*
					 * DIALOG BOX, ASK TO UPGRADE PRIVACY FOR AssetPermission.perms=members only or private
					 */
					// TODO: ask to upgrade asset Privacy on upgrade.
					// use JS?
				}

				if (isset($contribute['id']) && $contribute['id']==$id ) {
					unset($contribute['id']);
				} else {
					$this->Session->setFlash('Warning: There was some kind of mistake. Please try again');
				}
				$count=0;
				foreach ($contribute as $k=>$v) {
					if ($v==0) break;
					$parts = explode(':', $v);
					$paId = array_shift($parts);
					$batchId = array_shift($parts);
					$count += $this->__contributePhotostream($id, $paId, $batchId);
				}

				if ($v==0) {
					$this->Session->setFlash('Please make a selection.');
				} else if (!$count) {
					$this->Session->setFlash('Error: there was a problem contributing your selected photostreams.');
				} else {
					$this->Session->setFlash("Your selected Photostreams were contributed to this Group. <br>A total of {$count} photos were shared.");
//					$this->redirect(array('action'=>'home', $id));
				}
			}
		}
		
		// get group data for section header
		$options = array('conditions'=>array('Group.id'=>$id));
		$this->Group->contain(null);
		$data = @$this->Group->find('first', $options);
		if (empty($data)) {
			/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash(sprintf(__('No %s found.', true), 'Photos'));
			$this->redirectSafe();
		} else {
			$this->set('data', $data);
			Session::write('lookup.owner_names', Set::merge(Session::read('lookup.owner_names'), Set::combine($data, '/Owner/id', '/Owner/username')));
		}
		$this->set(array('assets'=>$data,'class'=>'Asset'));			
		
		$title = $data['Group']['title'];
		$ProviderAccount = isset($this->Group->ProviderAccount) ? $this->Group->ProviderAccount : ClassRegistry::init('ProviderAccount');
		$userid = AppController::$userid;
		
		// TODO: do we let members contribute public photos to groups?
		// TODO: do we let members contribute entire photostreams to public groups?
		$batches = $this->Group->Member->getOwnBatches();
		foreach ($batches as &$batch) {
			$batch['key']="{$batch['ProviderAccount']['id']}:{$batch['Asset']['batchId']}";
			$options = array(
				'fields' => array('src_thumbnail', 'caption'),
				'conditions'=>array('Asset.provider_account_id'=>$batch['ProviderAccount']['id'],
									'Asset.batchId'=>$batch['Asset']['batchId'],
				),
				'order'=>'Asset.dateTaken',
				'noEdit'=>true,
				'permissionable'=>false,
				'recursive'=>-1,
			);
			$previews = $ProviderAccount->Asset->find('all', $options);
			$previews = array_slice($previews, 0,5);
			$previews = Set::combine($previews, '/Asset/src_thumbnail', '/Asset/caption');
			$batch['Asset']['previews'] = $previews;
		}
		$photostreams = $batches;
		$this->set(compact('id', 'title', 'photostreams'));
	
	}

	function index() {
		$this->redirect(array('controller'=>Configure::read('controller.alias'), 'action'=>'all'));
	}

	function most_active(){
		$paginateModel = 'Group';		
		$this->paginate[$paginateModel]['order'] = array('Group.comment_count'=>'DESC');
		$this->all();
	}
	
	function most_photos(){
		$paginateModel = 'Group';		
		$this->paginate[$paginateModel]['order'] = array('Group.assets_group_count'=>'DESC');
		$this->paginate[$paginateModel]['limit']  = Configure::read('feeds.paginate.perpage');
		$this->all();
	}

	function most_members(){
		Configure::write('feeds.action', 'most_members');
		$paginateModel = 'Group';
		$this->paginate[$paginateModel]['order'] = array('Group.groups_user_count'=>'DESC');
		$this->paginate[$paginateModel]['limit']  = Configure::read('feeds.paginate.perpage');
		$this->all();
	}

	function most_recent(){
		$paginateModel = 'Group';
		$this->paginate[$paginateModel]['order'] = array('Group.lastVisit'=>'DESC');
		$this->paginate[$paginateModel]['limit']  = Configure::read('feeds.paginate.perpage');
		$this->all();
	}
	
	function all(){
		
		// paginate 
		$paginateModel = 'Group';
		$Model = $this->Group;
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $this->paginate[$paginateModel];
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		if ($this->action !== 'all') {
			// force perpage set from feeds
			Configure::write('passedArgs.perpage',$this->paginate[$paginateModel]['limit']);	// Configure::read('feeds.paginate.perpage')
			$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		} else {
			$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		}			
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate		
				
		$this->viewVars['jsonData'][$paginateModel] = $pageData;
		$this->getLookups(array('Users'=> array_keys(Set::combine($pageData, '/owner_id', ''))));
		$done = $this->renderXHRByRequest('json', '/elements/group/roll');
		if ($done) return;
		$this->action='index'; 
	}
	
	function search(){
//		$this->Prg->commonProcess();

		// paginate 
		$paginateModel = 'Group';
		$Model = $this->Group;
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $this->paginate[$paginateModel];	
		$named = Configure::read('passedArgs.complete');
		
		if (isset($named['User'])) {
			$paginateArray = $Model->getPaginateGroupsByUserId($named['User'], $paginateArray);
		} else if (isset($named['Asset'])) {
			$paginateArray = $Model->getPaginateGroupsByPhotoId($named['Asset'], $paginateArray);
		} else if (isset($named['Tag'])) {
			$paginateArray = $Model->getPaginateGroupsByTagId($named['Tag'], $paginateArray);
		} else {
			$paginateArray = $Model->getPaginateGroups($paginateArray);	
		}		
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray, 'members');
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate		
		$this->viewVars['jsonData'][$paginateModel] = $pageData; 
		$this->getLookups(array('Users'=> array_keys(Set::combine($pageData, '/owner_id', ''))));
		$done = $this->renderXHRByRequest('json', '/elements/group/roll');
		if ($done) return;
		$this->action='index'; 
	}
	
	/**
	 * NOT SURE IF WE ARE STILL USING THIS
	 *
	 * check if Group Content Privacy set to:  Members only - regardless of group access, only members can browse Broup contents
	 * NOTE: admin handling is incomplete.
	 * 		not sure if we will implement via Admin Groups/Permissionable
	 * 			-: how do we know we are admin w/o hitting db?
	 * 		or GroupsUser.role=admin
	 * 			-: how do we limit access to admins only?
	 */
	function __browseContentOk($data) {
		$lookup_privacyClassName = array(
			'0567'=>'public',
			'0631'=>'listing',
			'0119'=>'members',
			'0063'=>'admin',
		);
		$privacy = $lookup_privacyClassName[$data['Group']['perms']];
		$groupId = $data['Group']['id'];
		if ($privacy == 'admin') {
			// check if owner/admin
			// track admins via GroupsUser or Group.admin column?
				//		$this->Group->recursive=-1;
				//		$result = $this->Group->read(array('owner_id', 'id'), $groupId);
		
				$sql = "
SELECT `Group`.`owner_id`, GroupsUser.user_id, GroupsUser.role
FROM `groups` AS `Group`
JOIN groups_users AS GroupsUser ON GroupsUser.group_id=`Group`.`id` AND GroupsUser.isActive=1
WHERE `Group`.`id` = '{$groupId}' AND GroupsUser.role='admin'";			
				$result = $this->Group->query($sql);
				if (Session::read('Auth.User.id') == $result[0]['Group']['owner_id']) {
					return true;
				}
				$admins = (array)Set::extract($result, '/GroupsUser[role=admin]/user_id');
				if (in_array(Session::read('Auth.User.id'), $admins)) {
					return true;
				}
				return false;		
		} else if ($privacy == 'members') {
			// check if member
			if (!in_array($groupId, Permissionable::getGroupIds())) {
				return false;
			}
		} 
		return true;
	}

	function home($id = null) {
		$this->layout = 'snappi';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';		
		if (!$id) {
			$this->redirect('/my/groups', null, true);
		}
		/*
		 * get Permissionable associated data manually, add paging
		 */
		
			// get Group data
			$options = array(
				'contain'=>array('Owner.id', 'Owner.username'),
				'fields'=>'Group.*',		// MUST ADD 'fields' for  containable+permissionable
//				'permissionable'=>false,
				'conditions'=>array('Group.id'=>$id),
			);
			$this->Group->contain(array('Owner.id', 'Owner.username'));
			$data = $this->Group->find('first', $options);
			if (empty($data)) {
				/*
				 * handle no permission to view record
				 */
				$this->Session->setFlash(sprintf(__('No %s found.', true), 'Group'));
				$this->redirectSafe();
			} else {
				$this->set('browseContentOk', $this->__browseContentOk($data));
				$this->set('data', $data);
				/*
				 * get Lookups
				 */
				Session::write('lookup.owner_names', Set::merge(Session::read('lookup.owner_names'), Set::combine($data, '/Owner/id', '/Owner/username')));
			}
	}

	function photos($id = null){
		$forceXHR = setXHRDebug($this, 0);
		$this->layout = 'snappi';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';				
		if (!$id) {
			$this->Session->setFlash("ERROR: invalid Photo id.");
			$this->redirect(array('action' => 'all'));
		}
		
		// paginate 
		$paginateModel = 'Asset';
		$Model = $this->Group->{$paginateModel};
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $Model->getPaginatePhotosByGroupId($id, $this->paginate[$paginateModel]);
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
		$options = array('conditions'=>array('Group.id'=>$id));
//		$this->Group->contain(null);	// TODO: why does contain(null) throw a warning only for photos?
		$this->Group->contain('Owner.username');	
		$data = $this->Group->find('first', $options);
		if (empty($data)) {
			/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash(sprintf(__('No %s found.', true), 'Photos'));
			$this->redirectSafe();
		} else {
			$this->set('data', $data);
			Session::write('lookup.owner_names', Set::merge(Session::read('lookup.owner_names'), Set::combine($data, '/Owner/id', '/Owner/username')));
		}
		$this->set(array('assets'=>$data,'class'=>'Asset'));
	}
	
	
	function members($id=null){
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'group'));
			$this->redirectSafe();
		}
		
		// paginate 
		$paginateModel = 'Member';
		$Model = $this->Group->{$paginateModel};
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $Model->getPaginateUsersByGroupId($id, $this->paginate[$paginateModel]);
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate		
		$this->viewVars['jsonData'][$paginateModel] = $pageData;
		
		$done = $this->renderXHRByRequest('json', '/elements/member/roll', null ,0);
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false	
			
		// get Group data
		$options = array('conditions'=>array('Group.id'=>$id));
		$this->Group->contain(null);
		$data = @$this->Group->find('first', $options);
		if (empty($data)) {
			/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash(sprintf(__('No %s found.', true), 'Members'));
			$this->redirectSafe();
		} else {
			$this->set('data', $data);
			Session::write('lookup.owner_names', Set::merge(Session::read('lookup.owner_names'), Set::combine($data, '/Owner/id', '/Owner/username')));
		}
	}


	function photostreams($id=null){
		$this->autoRender = false;

		// paginate 
		$paginateModel = 'ProviderAccount';
		$Model = $this->Group->Member->{$paginateModel};
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $Model->getPaginateProviderAccountsByGroupId($id, $this->paginate[$paginateModel]);
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData['ProviderAccount'] = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate		ProviderAccount
	
		// get a preview of Assets for each provider account
		$paginateModel = 'Asset';	// now we are getting Assets
		$this->params['url']['preview']=1;		// manually set isPreview
		$this->paginate[$paginateModel]['limit'] = $this->paginate[$paginateModel]['photostream_limit'];
		$Model = $this->Group->{$paginateModel};
		$Model->Behaviors->attach('Pageable');		
		foreach($pageData['ProviderAccount'] as $key => $value){
			$paginateArray = $Model->getPaginatePhotosByProviderAccountId($value["id"], $this->paginate[$paginateModel]);
			//$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
			$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
			$pageData['ProviderAccount'][$key]['Assets'] = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
			// this is after considering Permissions, and NOT the same as counterCache result
			$pageData['ProviderAccount'][$key]['found_rows'] = $this->params['paging']['Asset']['count'];
		}		
		$paginateModel = 'ProviderAccount';   // switch back to ProviderAccount
		/*
		 * end Paginate ProviderAccount hasMany Asset
		 */
				
		
//debug($pageData);		
		$this->viewVars['jsonData'][$paginateModel] = $pageData;
		$done = $this->renderXHRByRequest('json', '/elements/photostream/roll', null ,0);
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false
		
		// get Group data for section header
		$options = array('conditions'=>array('Group.id'=>$id));
		$this->Group->contain(null);
		$data = @$this->Group->find('first', $options);
		if (empty($data)) {
			/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash(sprintf(__('No %s found.', true), 'Photostreams'));
			$this->redirectSafe();
		} else {
			// add $pageData to $data, until we render providerAccounts in JS from jsonData
			$data['ProviderAccount'] = $pageData['ProviderAccount'];			
			$this->set('data', $data);
			$owner_lookup = Set::combine($data, '/ProviderAccount/user_id', '/ProviderAccount/display_name');			
			Session::write('lookup.owner_names', Set::merge(Session::read('lookup.owner_names'),$owner_lookup ));
//			debug(Session::read('lookup.owner_names'));
		}

		//Configure::write('lookup.photostreams', $this->Group->lookupPhotostreams($id));
		$this->render('photostreams');
	}
	
	function trends($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'group'));
			$this->redirectSafe();
		}	
		$this->Group->contain(null);	
		$options = array('conditions'=>array('Group.id'=>$id));
		$data = @$this->Group->find('first', $options);
		if (empty($data)) {
			/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash("Group not found.");
			$this->redirectSafe();
		} else {
			$this->set('data', $data);
		}
	}
		
	function discussion($id) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'group'));
			$this->redirectSafe();
		}		
		$this->Group->contain('Comment');
		$options = array('conditions'=>array('Group.id'=>$id));
		$data = $this->Group->find('first', $options);	
		$this->set('data', $data);
		
		$done = $this->renderXHRByRequest(null, '/elements/comments/discussion', 0);
		// or $this->autoRender
	}


	function create() {
		$step = @ifed($this->data['Group']['step'], 'create-choose');
		if (!empty($this->data)) {
				
				debug($this->data);
				$this->Group->create();
				$this->data['Group']['owner_id'] = Session::read('Auth.User.id');
//				$this->Group->Behaviors->Permissionable->settings['Group']['defaultBits'] = $this->data['Group']['privacy_groups'];
				$this->data['Permission']['perms'] = $this->data['Group']['privacy_groups'];
				if ($this->Group->save($this->data)) {
					$this->Session->setFlash("Your group was created.");
					$next = array('action'=>'home', $this->Group->id);
					$this->redirect($next, null, true);
				} else {
					$this->Session->setFlash("There was a problem creating this Group. Please try again.");
				}
		}
		
		$privacy=null; $policy=null;
		/*
		 * set default Group perms from Profile
		 */
		$profile = $this->getProfile();
		$default_privacy = $profile['Profile']['privacy_groups'];
		$this->data['Group']['privacy_groups'] = $default_privacy;
		// privacy config
		$privacy = $this->__getPrivacyConfig();
		
		
		$this->data['Group']['privacy_secret_key']=2;	// default
		$policyDefaults = $this->__getPolicyDefaults($this->data['Group']['privacy_groups']);
		
		$policyDefaultsJson = array_flip(array('567','631','119','63'));
		foreach ($policyDefaultsJson as $key=>$value) {
			$policyDefaultsJson[$key] = $this->__getPolicyDefaults($key);
		}
		
		
		$policy =  $this->__getPolicyConfig();
		$this->data['Group']= array_merge($policyDefaults, $this->data['Group']);				
		$this->set(compact('step', 'privacy', 'policy','policyDefaultsJson'));

		//		debug($this->data);
	}

	function add() {
		if (!empty($this->data)) {
			$this->Group->create();
			/*
			 * set default Group perms from Profile
			 */
			$profile = $this->getProfile();
			$this->data['Permission']['perms'] = $profile['Profile']['privacy_groups'];
			if ($this->Group->save($this->data)) {
				$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'group'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'group'));
			}
		}
		//		$owners = $this->Group->Owner->find('list');
		//		$assets = $this->Group->Asset->find('list');
		//		$collections = $this->Group->Collection->find('list');
		//		$members = $this->Group->Member->find('list');
		//		$this->set(compact('owners', 'assets', 'collections', 'members'));
	}




	function __getPrivacyConfig(){
		/*
		 * configure privacy defaults for radio buttons
		 */
		// The Photos and Page Galleries I import, upload or create:
		$privacy['Asset'][519]="<b>Public</b> - are publicly listed and visible to anyone.";
		$privacy['Asset'][71]="<b>Members only </b> - are NOT publicly listed, and are visible only when shared in Groups or Events, and only by Group members.";
		$privacy['Asset'][7]="<b>Private</b> - are NOT publicly listed and visible only to me.";

		// The Groups and Events I create:
		$privacy['Groups'][567]="<b>Public</b> - are publicly listed and visible to anyone. Contributors are also asked to make their shared Content <b>Public</b>.";
		$privacy['Groups'][631]="<b>Public Listings Only</b> -  are publicly listed and visible to anyone, but shared Content is visible to Group members only.";
		$privacy['Groups'][119]="<b>Members only</b> - are NOT listed, and visible to Group members only.";
		$privacy['Groups'][63]="<b>Admin</b> - are NOT listed, and visible only to me and other Group admins.";

		$privacy['SecretKey'][1]="Members - are visible to all site members.";
		$privacy['SecretKey'][2]="Admins - are visible only to Owners and Admins ";
		$privacy['SecretKey'][4]="Nobody - disable Secret Key sharing";
		return $privacy;
	}


	function __getPolicyDefaults($privacy_groups){
		switch($privacy_groups) {
			case 567:	// public
				$defaults['GroupMembershipPolicy']= 1;
				$defaults['GroupInvitationPolicy']= 1;
				$defaults['GroupSubmissionPolicy']= 1;
				break;
			case 631:	// public listings only
				$defaults['GroupMembershipPolicy']= 1;
				$defaults['GroupInvitationPolicy']= 1;
				$defaults['GroupSubmissionPolicy']= 1;
				break;
			case 119:	// members only
				$defaults['GroupMembershipPolicy']= 2;
				$defaults['GroupInvitationPolicy']= 2;
				$defaults['GroupSubmissionPolicy']= 1;
				break;
			case 63:	// admin
				$defaults['GroupMembershipPolicy']= 4;
				$defaults['GroupInvitationPolicy']= 4;
				$defaults['GroupSubmissionPolicy']= 2;
				break;
		}
		return $defaults;
	}
	function __getPolicyConfig(){
		/*
		 * configure moderator defaults for radio buttons
		 */
		$policy['membership'][1]="Public - a 'Join' link will appear on the Group home page.";
		$policy['membership'][2]="Invitation only - members must be invited to join.";
		$policy['membership'][4]="Approval Required - members must be approved by Group administrators.";


		$policy['invitation'][1]="anyone - an 'Invite' link will appear on the Group home page.";
		$policy['invitation'][2]="Members only - only Members will see the 'Invite' link.";
		$policy['invitation'][4]="Admins only.";

		$policy['submission'][1]="Immediately visible.";
		$policy['submission'][2]="are visible only after approval by Group moderators.";
			
		return $policy;
	}

	function __decodeGroupPerms(& $data){
		$perms = ifed($data['GroupPermission']['perms'], null);
		//		$readBits = $perms & (PermissionableBehavior::OTHER_READ + PermissionableBehavior::MEMBER_READ);
		//		debug($readBits);
		$data['Group']['privacy_groups'] = $perms;
	}

	function __encodeGroupPerms(& $data){
		$perms = @ifed($data['Group']['privacy_groups'], null);
		if ($perms) $data['Permission']['perms'] = $perms;
	}

	function settings($id = null) {
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'group'));
			if (!$this->RequestHandler->isAjax()) $this->redirectSafe();
		}
		if (!AppController::$writeOk) {
			$this->Session->setFlash(__('You are not authorized to view this page'));
			if (!$this->RequestHandler->isAjax()) $this->redirectSafe();
		}

		if (!empty($this->data)) {
			/*
			 * redirect to edit with setting=[form id]
			 * 		//deprecate. using Js to manage settings/edit
			 */
			$qs = @http_build_query(array('setting'=>$this->data['Group']['setting']));
			$redirect = Router::url(array('action'=>'edit', $id)). ($qs ? "?{$qs}" : '');
			$this->redirect($redirect, null, true);
		}

		$privacy = $this->__getPrivacyConfig();
		$policy =  $this->__getPolicyConfig();
		$this->set(compact('policy', 'privacy'));

		$options = array('conditions'=>array('Group.id'=>$id));
		$this->Group->contain('Owner.id', 'Owner.username');
		$data = @$this->Group->find('first', $options);		

		$this->__decodeGroupPerms($data);

		$this->data = $data;
		$this->set('data', $data);
		//		$owners = $this->Group->Owner->find('list');
		//		$assets = $this->Group->Asset->find('list');
		//		$collections = $this->Group->Collection->find('list');
		//		$members = $this->Group->Member->find('list');
		//		$this->set(compact('owners', 'assets', 'collections', 'members'));
		
		
		$xhrFrom = Configure::read('controller.xhrFrom');
		if ($xhrFrom) {
			$viewElement = '/elements/groups/'.$xhrFrom['view'];
		} else $viewElement = null;
		$done = $this->renderXHRByRequest(null, $viewElement, 0);
		return;
	}
	
	function edit($id = null) {
		if (0 || $this->RequestHandler->isAjax()) {
			Configure::write('debug', 0);
			$this->layout='ajax';
			$this->autoRender=false;
		}
		if (!$id && empty($this->data)) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'group'));
			if (!$this->RequestHandler->isAjax()) $this->redirectSafe();
		}
		if (!AppController::$writeOk) {
			$this->Session->setFlash(__('You are not authorized to view this page'));
			if (!$this->RequestHandler->isAjax()) $this->redirectSafe();
		}
		if (!empty($this->data)) {
			// check role permissions
			$allowed = array('ADMIN');
			if ( AppController::$writeOk || in_array(Session::read('Auth.User.role'), $allowed)) {
				$this->__encodeGroupPerms($this->data);
				$redirect = Router::url(array('action'=>'settings', $id));
				if ($this->Group->save($this->data)) {
					$this->Session->setFlash(sprintf(__('The %s has been saved', true), 'group'));
					$retval = 1;
				} else {
					$this->Session->setFlash(sprintf(__('The %s could not be saved. Please, try again.', true), 'group'));
					// don't redirect back, we want to show validation errors
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
		$policy =  $this->__getPolicyConfig();
		$this->set(compact('policy', 'privacy'));

		$options = array('conditions'=>array('Group.id'=>$id));
		$this->Group->contain('Owner.id', 'Owner.username');
		$data = @$this->Group->find('first', $options);	
		$this->data = $data;
		$this->set('data', $data);
	}

	function delete($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid id for %s', true), 'group'));
			$this->redirectSafe();
		}
		if ($this->Group->hasPermission('write',$id))  {
			$this->Group->id = $id;
			if ($this->Group->field('assets_group_count') || $this->Group->field('groups_user_count')) {
				$this->Session->setFlash("Error: You cannot delete a Group that is not empty.");
				$this->redirectSafe();
			} else {
				if ($this->Group->delete($id)) {
					$this->Session->setFlash(sprintf(__('%s deleted', true), 'Group'));
					$this->redirect(array('controller'=>'users','action' => 'groups', Session::read('Auth.User.id')));
				}
				$this->Session->setFlash(sprintf(__('%s was not deleted', true), 'Group'));
			}
		}
		$this->redirectSafe();
	}
	
	function invite ($id) {
		$host = env('HTTP_HOST');
		$referer = env('HTTP_REFERER');

		$this->Group->recursive = 0;
		$data = $this->Group->read(null,$id);
		$this->set('data', $data);
				
		if (strpos($referer, $host) > 0) {
			// from site, show invitation form
			$invitation = Router::url(array('action'=>'join', AppController::$uuid), true);
			$this->set('invitation', $invitation);
			
		} else {
			// from off-site, show jump page to accept invitation
			// TODO: should we link back to /groups/inivite, or /groups/join
			$isMember = in_array(AppController::$uuid, Permissionable::getGroupIds());
			if ($isMember ) {
				$this->Session->setFlash('You are already a member of this group.');
			}	
			$this->set('isMember', $isMember);		
		}
	}
	
	/**
	 * get all public groups, by permission, not group membership
	 * 		/groups/all - get all group by membership
	 */
	function open(){
		
		// paginate 
		$paginateModel = 'Group';
		$Model = $this->Group;
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $this->paginate[$paginateModel];
		$paginateArray = $Model->getPublicPaginateGroups($paginateArray);
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate			
		$this->viewVars['jsonData'][$paginateModel] = $pageData;
		$this->getLookups(array('Users'=> array_keys(Set::combine($pageData, '/owner_id', ''))));
		$done = $this->renderXHRByRequest('json', '/elements/group/roll');
		if ($done) return;
		$this->action='index'; 
	}
}
?>