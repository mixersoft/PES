<?php
App::import('Controller', 'Users');
class PersonController extends UsersController {
	public $name = 'Users';
	public $modelClass = 'Person';
	public $modelKey = 'users';
	public $viewPath = 'person';
	public $titleName = 'People';
	public $displayName = 'Person';	// section header
	
	public $helpers  = array(
		'Tags.TagCloud',
		// 'Time',
		'Text',
		'CastingCallJson',
		'Layout',
//		'Js' => array('Jquery'),
	);

	public $paginate = array(
		'photostream'=>array('limit'=>20),
		'User'=>array(
			'preview_limit'=>16,
			'paging_limit' =>24,
			// deprecate limit, big_limit
			// set limit in PageableBehavior->getPerpageLimit()
			'limit'=>16,
			'big_limit' =>24,
			
			'order'=>array('User.last_login'=>'desc'),
		),
		'ProviderAccount'=>array(
			'limit' => 5,
			'big_limit' =>20,
			'order' => array('ProviderAccount.created'=>'ASC'),
			'fields' =>'ProviderAccount.*',
		),
		'Asset'=>array(
			'preview_limit'=>6,
			'paging_limit' =>24,
			// deprecate limit, big_limit
			// set limit in PageableBehavior->getPerpageLimit()
			'limit' => 16,
			'big_limit' =>24,
			'photostream_limit' => 4,
			'order' => array('Asset.dateTaken'=>'ASC'),
//			'contain' => array('Owner.id', 'Owner.username'), 
			'conditions' => array(),
//			'recursive'=> -1,
			'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>'Usershot', 
				'show_hidden_shots'=>false
			),	
			'fields' =>'Asset.*',
	),
		'Collection'=>array(				
			'limit'=>8,
			'order'=>array('Collection.created'=>'DESC'),
	),
		'Membership'=>array(
			'preview_limit'=>4,
			'paging_limit' =>8,
			// deprecate limit, big_limit
			// set limit in PageableBehavior->getPerpageLimit()
			'limit' => 8,
			'big_limit' =>36,
			'order'=>array('Membership.title'=>'ASC'),
			'recursive'=> -1,
			'fields' =>'Membership.*',
		),
		'ExpressUploadGroup'=>array(
			'preview_limit'=>8,
			'paging_limit' =>8,
			'recursive'=> -1,
			'fields' =>'ExpressUploadGroup.*',
		)		
	);

	/*
	 * reference: http://www.studiocanaria.com/articles/cakephp_auth_component_users_groups_permissions_revisited
	 */
	function beforeFilter() {
		// only for snaphappi user login, not rpxnow
		parent::beforeFilter();
		/*
		 *	These actions are allowed for all users
		 */
		$myAllowedActions = array(			
			/*
			 * main
			 */
			'search',
			/*
			 * all
			 */
			'index', 'all', 'most_active', 'most_recent','most_photos','most_groups',
			/*
			 * experimental
			 */
			'addACLs', 'remove_photos', 'odesk_photos', 'photostreams'
		);
		$this->Auth->allow( array_merge($this->Auth->allowedActions , $myAllowedActions));
		// TODO: edit allowed for  'role-----0123-4567-89ab---------user'
		// TODO: groups allowed for  'role-----0123-4567-89ab--------guest', 'role-----0123-4567-89ab---------user'
	}
	/*
	 * WARNING: backdoor action for oDesk project
	 */
	function odesk_photos (){
		$id = $this->passedArgs[0];
		if (strpos($id, '12345678') === 0) {
			$data = $this->User->read(null, $id );
			$ret = $this->Auth->login($data);
			$this->__cacheAuth();
			$this->Permissionable->initialize($this);
			$this->action='photos'; 
			$this->photos($id);
		} else {
			exit;
		}
	}	

	function beforeRender() {
		try {
			if (!($this->RequestHandler->isAjax() || $this->RequestHandler->ext=='json') 
				&& AppController::$uuid
				&& isset($this->viewVars['data']['User']['username'])
			) {
				$label = $this->viewVars['data']['User']['username'];
				if (Session::read("lookup.trail.{$this->displayName}.uuid") == AppController::$uuid) {
					Session::write("lookup.trail.{$this->displayName}.label", $label);	
				}
			}
		} catch (Exception $e) {}
		parent::beforeRender(); 
	}

	function index() {
		$this->redirect(array('controller'=>Configure::read('controller.alias'), 'action'=>'all'));
	}

	function most_active(){
		$paginateModel = 'User';		
		$this->paginate[$paginateModel]['order'] = array('User.comment_count'=>'DESC');
		$this->all();
	}
	
	function most_groups(){
		$paginateModel = 'User';		
		$this->paginate[$paginateModel]['order'] = array('User.groups_user_count'=>'DESC');
		$this->paginate[$paginateModel]['limit'] = Configure::read('feeds.paginate.perpage');
		$this->all();
	}

	function most_photos(){
		$paginateModel = 'User';
		$this->paginate[$paginateModel]['order'] = array('User.asset_count'=>'DESC');
		$this->paginate[$paginateModel]['limit'] = Configure::read('feeds.paginate.perpage');
		$this->all();
	}

	function most_recent(){
		$paginateModel = 'User';
		$this->paginate[$paginateModel]['order'] = array('User.lastVisit'=>'DESC');
		$this->paginate[$paginateModel]['limit'] = Configure::read('feeds.paginate.perpage');
		$this->all();
	}

	function all(){
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		// paginate 
		$paginateModel = 'User';
		$Model = $this->User;
		$this->helpers[] = 'Time';
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $this->paginate[$paginateModel];
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		if ($this->action !== 'all') {
			// force perpage set from feeds
			Configure::write('passedArgs.perpage',$this->paginate[$paginateModel]['limit']);
			$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		} else {
			// use action = 'members' for session key
			$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray, 'members');
		}			
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate		
		
		$this->viewVars['jsonData'][$paginateModel] = $pageData;
		$done = $this->renderXHRByRequest('json', '/elements/member/roll');
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false	
		
		$this->action='index'; 
	}
	
	function search(){
	//		$this->Prg->commonProcess();

		// paginate 
		$paginateModel = 'User';
		$Model = $this->User;
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $this->paginate[$paginateModel];	
		$named = Configure::read('passedArgs.complete');
		
		if (isset($named['Group'])) {		
			$paginateArray = $Model->getPaginateUsersByGroupId($named['Group'], $paginateArray);
		} else {
			$paginateArray = $Model->getPaginateUsers($paginateArray);	
		}
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray, 'members');
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate		

		$this->viewVars['jsonData'][$paginateModel] = $pageData;
		
		$done = $this->renderXHRByRequest('json', '/elements/member/roll');
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false	
			
		$this->action='index'; 
	}

	function home($id = null) {
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';
		if (!$id) {
			$this->Session->setFlash("ERROR: invalid Photo id.");
			$this->redirect(array('action' => 'all'));
		}
		/*
		 * IF WE ARE ADMIN, Set active user here
		 */ 
		if ($this instanceof PersonController && in_array(AppController::$role,array('EDITOR','MANAGER','ADMIN','ROOT'))){
			// act as person
			AppController::$ownerid = AppController::$uuid;
			Session::write('Auth.User.acts_as_ownerid', AppController::$ownerid);
			Configure::write('controller.userid', AppController::$ownerid);
$this->log("/users/photos: acts_as_ownerid", 	LOG_DEBUG);					
			Permissionable::setGroupOwnershipsMemberships(AppController::$ownerid);  
			Session::write('Auth.Permissions.group_ids', Permissionable::$group_ids);
$this->log(Permissionable::$group_ids, 	LOG_DEBUG);
$this->log("role = ".AppController::$role, 	LOG_DEBUG);			
			// Permissionable::setGroupIds();
			$this->Session->setFlash("Privileged Access: acting as userid=".AppController::$ownerid);
		}
		
		// paginate 
		$paginateModel = 'Asset';
		$Model = $this->User->{$paginateModel};
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $Model->getPaginatePhotosByUserId($id, $this->paginate[$paginateModel]);
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate
		if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
		$castingCall = $this->CastingCall->getCastingCall($pageData);
		$this->viewVars['jsonData']['castingCall'] = $castingCall;

		$done = $this->renderXHRByRequest('json', '/elements/photo/roll', null, 0);
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false	
		$options = array(
			'conditions'=>array('User.id'=>$id),
			'recursive' => -1,
		);
		$this->User->contain();
		$data = $this->User->find('first', $options);
		if (empty($data)) {
			/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash("ERROR: You are not authorized to view this record.");
			$this->redirectSafe();
		} else {
			$data['Asset'] = $pageData;
			$this->set('data', $data);
			$this->viewVars['jsonData']['User'][]=$data['User'];
		}
	}

	function photos($id = null){
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';
		if (!$id) {
			$this->Session->setFlash("ERROR: invalid Photo id.");
			$this->redirect(array('action' => 'all'));
		}
		
		// paginate 
		$paginateModel = 'Asset';
		$Model = $this->User->{$paginateModel};
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $Model->getPaginatePhotosByUserId($id, $this->paginate[$paginateModel]);
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
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
		else $getMontage = ( Session::read('section-header.Photo') == 'Montage' );
		if ($getMontage) {	
 			$this->Montage = loadComponent('Montage', $this);
			$Auditions = $castingCall['CastingCall']['Auditions'];
			$this->viewVars['jsonData']['montage'] = $this->Montage->getArrangement($Auditions);
		}	
			
		$done = $this->renderXHRByRequest('json', '/elements/photo/roll', null, 0);
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false	
		$options = array(
			'conditions'=>array('User.id'=>$id),
			'recursive' => -1,
		);
		$this->User->contain();
		$data = $this->User->find('first', $options);
		if (empty($data)) {
			/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash("ERROR: You are not authorized to view this record.");
			$this->redirectSafe();
		} else {
			$data['Asset'] = $pageData;
			$this->set('data', $data);
			$this->viewVars['jsonData']['User'][]=$data['User'];
		}
	}
	
	function photostreams($id=null){
		// TODO: move #tags-preview-xhr to .related-content
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		$this->autoRender = false;

		// paginate 
		$paginateModel = 'ProviderAccount';
		$Model = $this->User->{$paginateModel};
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $Model->getPaginateProviderAccountsByUserId($id, $this->paginate[$paginateModel]);
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData['ProviderAccount'] = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate		ProviderAccount
		
	
		// get a preview of Assets for each provider account
		$paginateModel = 'Asset';	// now we are getting Assets
				$this->params['url']['preview']=1;		// manually set isPreview
		$this->paginate[$paginateModel]['limit'] = $this->paginate[$paginateModel]['photostream_limit'];
		$Model = $this->User->{$paginateModel};
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
		$options = array('conditions'=>array('User.id'=>$id));
		$this->User->contain(null);
		$data = @$this->User->find('first', $options);
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
			$this->viewVars['jsonData']['User'][]=$data['User'];
			$owner_lookup = Set::combine($data, '/ProviderAccount/user_id', '/ProviderAccount/display_name');			
			Session::write('lookup.owner_names', Set::merge(Session::read('lookup.owner_names'),$owner_lookup ));
//			debug(Session::read('lookup.owner_names'));
		}

		//Configure::write('lookup.photostreams', $this->Group->lookupPhotostreams($id));
		$this->render('photostreams');
	}

	function groups($id = null){
		$this->layout = 'snappi';
		$this->helpers[] = 'Time';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';	
		//	this should be a redirect to /groups/byuser/userid, plus context
		if (!$id) {
			$this->Session->setFlash(sprintf(__('No %s found.', true), 'Photos'));
			$this->redirect(array('action' => 'index'));
		}

		// paginate 
		$paginateModel = 'Membership';
		$Model = $this->User->{$paginateModel};
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $Model->getPaginateGroupsByUserId($id, $this->paginate[$paginateModel]);
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate
		
		// add express uploads to PAGE.jsonData.expressUploadGroups
		if (Configure::read('controller.alias') == 'my') {
			$expressUploadGroups = Set::extract($this->__getExpressUploads(AppController::$ownerid),'/id');
			$expressUploadGroups = array_flip($expressUploadGroups);
			$this->viewVars['jsonData']['expressUploadGroups'] = $expressUploadGroups;
		}
		
		$this->viewVars['jsonData'][$paginateModel] = $pageData;
		$done = $this->renderXHRByRequest('json', '/elements/group/roll');
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false	
		
		
		$this->User->contain();
		$options = array('conditions'=>array('User.id'=>$id));
		$data = $this->User->find('first', $options);
		if (empty($data)) {
			/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash("ERROR: You are not authorized to view this record.");
			$this->redirectSafe();
		} else {
			$data[$paginateModel] = $pageData;
			$this->set('data', $data);
			$this->viewVars['jsonData']['User'][]=$data['User'];
		}
	}
	
	function trends($id = null) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'member'));
			$this->redirectSafe();
		}			
		$this->User->contain();
		$options = array('conditions'=>array('User.id'=>$id));
		$data = $this->User->find('first', $options);
		if (empty($data)) {
			/*
			 * handle no permission to view record
			 */
			$this->Session->setFlash("Member not found.");
			$this->redirectSafe();
		} else {
			$this->set('data', $data);	
			$this->viewVars['jsonData']['User'][]=$data['User'];		
		}
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


	function __getModeratorConfig(){
		/*
		 * configure moderator defaults for radio buttons
		 */
		$moderator['Comments'][1]="Public - anyone can add Comments";
		$moderator['Comments'][2]="Registered Members - only site members can add Comments.";
		$moderator['Comments'][4]="Nobody - disable Commenting feature.";
		$moderator['Tags'][1]="Public - anyone can add Tags";
		$moderator['Tags'][2]="Registered Members - only site members can add Tags.";
		$moderator['Tags'][4]="Admins - only I or my designated Group/Event admins can add Tags.";
		$moderator['Tags'][8]="Nobody - disable Tagging feature.";
		return $moderator;
	}

	function update_count($id) {
		parent::update_count( $id );
	}

}
?>