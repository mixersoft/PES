<?php
App::import('Controller', 'Users');
class PersonController extends UsersController {
	public $name = 'Users';
	public $modelClass = 'Person';
	public $modelKey = 'users';
	public $viewPath = 'person';
	public $titleName = 'People';
	public $displayName = 'Person';	// section header
	
	// public $layout = 'snappi';
	
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
			'order'=>array('User.last_login'=>'desc'),
		),
		'ProviderAccount'=>array(
			'order' => array('ProviderAccount.created'=>'ASC'),
			'fields' =>'ProviderAccount.*',
		),
		'Asset'=>array(
			'preview_limit'=>6,
			'paging_limit' =>24,
			'photostream_limit' => 4,
			'order' => array('Asset.batchId'=>'DESC', 'Asset.dateTaken'=>'ASC'),	// most recent upload
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
			'preview_limit'=>4,
			'paging_limit' =>16,
			'order'=>array('Collection.modified'=>'DESC'),
			'recursive'=> -1,
			'fields' =>'Collection.*',
		),
		'Membership'=>array(
			'preview_limit'=>4,
			'paging_limit' =>8,
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
			'search','home','photos','groups','trends',
			/*
			 * all
			 */
			'index', 'all', 'most_active', 'most_recent','most_photos','most_groups',
			/*
			 * experimental
			 */
			'stories',  // TODO: move to ACL
			'addACLs', 'remove_photos', 'odesk_photos', 'photostreams',
			'event_group'
		);
		$this->Auth->allow($myAllowedActions);
		// TODO: edit allowed for  'role-----0123-4567-89ab---------user'
		// TODO: groups allowed for  'role-----0123-4567-89ab--------guest', 'role-----0123-4567-89ab---------user'
	}
	/*
	 * WARNING: backdoor action for oDesk project
	 */
	function odesk_photos (){
		/*
		 * allow cross-domain XHR, instead of jsonp
		 * 	from thats-me.snaphappi.com for timeline app
		 *  from anything from snaphappi.com 
		 */ 
		$origin = !empty($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : $_SERVER['HTTP_HOST'];
		if (preg_match('/(snaphappi.com|thats\-me|github)/i', $origin)) {
			echo header("Access-Control-Allow-Origin: {$origin}");
		}
		$ALLOWED_BY_USERNAME = array('newyork', 'paris', 'sardinia', 'venice', 'bali', 'summer-2009');
		$id = $this->passedArgs[0];
		if (strpos($id, '12345678') === 0) {
			$data = $this->User->read(null, $id );
		} else if (in_array($id, $ALLOWED_BY_USERNAME )) {
			$data = $this->User->find('first', array('conditions'=>array('User.username'=>$id)) );	
			$id = $data['User']['id'];
			/*
			 * make public
			 */ 
			echo header("Access-Control-Allow-Origin: {$origin}");
		} else if (strlen($id)==36 && preg_match('/[snaphappi\.com|thats\-me|snappi\-dev]/i', $origin)) {	// passed as UUID 
			/**
			 * WARNING. this is a public, unauthenticated action. 
			 * ONLY accept from http://$origin/story/$id
			 */
			 $verified = "{$origin}/story/{$id}/";
			 if (0 && strpos($_SERVER['HTTP_REFERER'], $verified) === false) throw new Exception("unauthorized access, cross-domain violation. referer={$_SERVER['HTTP_REFERER']}");
			$data = $this->User->find('first', array('conditions'=>array('User.id'=>$id)) );	
			$id = $data['User']['id'];	
		} else {
			throw new Exception("unauthorized access, user={$id}");
		}
		$ret = $this->Auth->login($data);
		$this->__cacheAuth();
		$this->Permissionable->initialize($this);
		
		// $this->action='photos';
		$return = $this->photos($id);
		
		if ($return==='jsonp') {
			$this->viewVars['allow_jsonp'] = true; 
			$done = $this->renderXHRByRequest('json', null, null, 0);
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
	
	function most_stories(){
		$paginateModel = 'User';		
		$this->paginate[$paginateModel]['order'] = array('User.collection_count'=>'DESC');
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

if (!empty($this->passedArgs['raw']) ) {
	$paginateArray['extras']['show_hidden_shots']=1;
	$paginateArray['extras']['hide_SharedEdits']=1;
}
if (!empty($this->passedArgs['hidden'])) {
	$paginateArray['extras']['show_hidden_shots']=1;
	$paginateArray['extras']['hide_SharedEdits']=0;	// TODO: why does show_hidden fail unless we hide edits?
}
if (!empty($this->passedArgs['only-shots'])) {
		$paginateArray['extras']['only_shots']=1;
}
if (!empty($this->passedArgs['all-shots'])) {
	$paginateArray['permissionable']=false;
	$paginateArray['extras']['all_shots']=1;		// includes Shot.inactive=0	
	$paginateArray['extras']['only_shots']=1;
}
// debug($paginateArray['extras']);
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
// debug(Configure::read("paginate.Options.{$paginateModel}")); exit;	
// $this->log(Configure::read("paginate.Options.{$paginateModel}"), LOG_DEBUG);	
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
			$options = array();
			$options['count'] = isset($this->passedArgs['count']) ? $this->passedArgs['count'] :  9;
			if (isset($this->passedArgs['w'])) $options['maxW'] = $this->passedArgs['w'];
			if (isset($this->passedArgs['h'])) $options['maxH'] = $this->passedArgs['h'];
			// $options['count'] = round(count($Auditions['Audition'])/2);
			// $options['allowed_ratios'] = array('h'=>'1:'.round($options['count']/3), 'v'=>'1:'.round($options['count']/4));  // set for Hscroll
			$this->viewVars['jsonData']['montage'] = $this->Montage->getArrangement($Auditions, $options);
		}	
		/*
		 * for access to json from thats-me
		 * 	- enforces same domain policy for iframe access
		 */
		 if (isset($this->params['url']['min'])) {
		 	$this->set('json',$this->viewVars['jsonData']);
			// uses same viewfile as checkauth
			$this->viewPath = 'elements';
		 	$this->render('iframe-json', 'plain');
			return;
		 }
		 /*
		  * jsonp response for /odesk_photos ONLY
		  */
		if ($this->action=='odesk_photos' && !empty($_GET['callback']) ) {
			return 'jsonp';
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
		$paginateArray = $Model->getPaginateCollectionsByUserId($id, $this->paginate[$paginateModel]);
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate
		
		$this->viewVars['jsonData'][$paginateModel] = $pageData;
		$done = $this->renderXHRByRequest('json', '/elements/collections/roll');
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

	/*
	 * TODO: make JSON only
	 */ 
	function batch_ids($id = null) {
		$forceXHR = setXHRDebug($this, 0);
		if (!(($forceXHR || $this->RequestHandler->isAjax()) && $this->RequestHandler->ext=='json') 
		){
			$this->Session->setFlash('WARNING: This action is only valid for XHR JSON requests.');
			$this->redirect(array('action'=>'photos'), null, true);
			return;
		}
		$this->layout='ajax';	
		$this->autoRender = false;
		if (!$id) $id = AppController::$ownerid;
		$options = array(
			'conditions'=>array(
				'Asset.owner_id'=>$id,
			), 
			'fields'=>array('Asset.batchId', "COUNT('id') AS count"),
			'group'=>'Asset.batchId',
			'order'=>array('batchId DESC'),
			'permissionable'=>false,					// is this ok? needed for DISTINCT
			'extras' => array(
				'join_shots'=>false,	// get ALL photos
				'show_edits' => false,
			),							
		);
		$data = $this->User->Asset->find('all',$options);
		$this->viewVars['jsonData']['batchIds'] = Set::combine($data, '/Asset/batchId', '/Asset/count');
		$done = $this->renderXHRByRequest('json', '/elements/dumpSQL', null);
	}

	/*
	 * auditions in the SAME event_group will be place in a Shot group
	 * as seen in PAGE.jsonData 
	 */ 
	function _addEventsAsShots ($event_groups, $cc){
		// var shotId = o.Audition.SubstitutionREF;
		$i=0;
		$Auditions = $cc['CastingCall']['Auditions']['Audition'];
		$event_Auditions = array();
debug($event_groups['Events']);		
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
		$cc['CastingCall']['Auditions']['ShotType'] = 'event_group';
		$this->viewVars['jsonData']['shot_CastingCall'] = $cc;		// copy complete with Shots added
		$cc['CastingCall']['Auditions']['Audition'] = $event_Auditions;	// just Shots
		return $cc;
	}

	/**
	 * event_group: calculate event group at indicated timescale, 
	 * 	- for XHR request from thats-me/timeline/[id]
	 *  - NOTE: /workorders/event_group shows events using Shot View, not Timeline
	 * example
	 * 	http://dev.snaphappi.com/my/event-group/timescale:0.167/perpage:9999
	 * 
	 * same as action=photos options
	 * 		/perpage:9999					// assume everything fetched in 1 page
	 * 		/timescale:1					// in days, float ok.
	 * 		&forcexhr=1						// xhr only
	 * 		&reset=1						// clear view cache
	 * 		JSON output only
	 */
	function event_group($id = null){
		$forceXHR = setXHRDebug($this, 0);
		
		/*
		 * DEBUG event_group on windows with STATIC values
		 */ 
		if (1 && Configure::read('Config.os')==='win') {
			$this->autoRender = false;
			/*
			 * allow cross-domain XHR, instead of jsonp
			 * 	from thats-me.snaphappi.com for timeline app
			 *  from anything from snaphappi.com 
			 * TODO: I use jsonp somewhere else, WMS app(?) replace with this pattern
			 */ 
			$origin = !empty($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : $_SERVER['HTTP_HOST'];
			if (preg_match('/(snaphappi.com|thats\-me)/i', $origin)) {
				echo header("Access-Control-Allow-Origin: {$origin}");
				// echo header('Access-Control-Allow-Origin: http://thats-me.snaphappi.com');
			}			
			// save json String in /app/vendors/jsonData/UUID.txt
			$req = $this->passedArgs[0];
			if (!empty($this->passedArgs['timescale'])) $req .= "::{$this->passedArgs[0]}";
			$file = APP.'vendors'.DS.'jsonData'.DS.$req.".txt";
			header('Content-type: application/json');
			echo file_get_contents($file);	
			return;
		}	// endif for win
		if (!(($forceXHR || $this->RequestHandler->isAjax()) && $this->RequestHandler->ext=='json') 
		){
			$this->Session->setFlash('WARNING: This action is only valid for XHR JSON requests.');
			$this->redirect(array('action'=>'photos', $id), null, true);
			return;
		}
		
		/*
		 * allow cross-domain XHR, instead of jsonp
		 * 	from thats-me.snaphappi.com for timeline app
		 *  from anything from snaphappi.com 
		 * TODO: I use jsonp somewhere else, WMS app(?) replace with this pattern
		 */ 
		$origin = !empty($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : $_SERVER['HTTP_HOST'];
		if (preg_match('/(snaphappi.com|thats\-me)/i', $origin)) {
			echo header("Access-Control-Allow-Origin: {$origin}");
			// echo header('Access-Control-Allow-Origin: http://thats-me.snaphappi.com');
		}
		
		/*
		 * use backdoor access, Timeline XHR has different cookie from normal login
		 * TODO: CLOSE BACKDOOR
		 */ 
		$ALLOWED_BY_USERNAME = array('newyork', 'paris', 'venice', 'bali', 'summer-2009', 'michael');
		$data = $this->User->read(null, $id );
		if (in_array($data['User']['username'], $ALLOWED_BY_USERNAME )) {
			$ret = $this->Auth->login($data);
			$this->__cacheAuth();
			$this->Permissionable->initialize($this);	
		}
				
		if (!$id || !AppController::$userid) {
			$this->viewVars['jsonData'] = array('success'=>false, 'message'=>'User unknown. Please sign in');
			$done = $this->renderXHRByRequest('json', '/elements/photo/roll', null, 0);
			return; // stop for JSON/XHR requests, $this->autoRender==false	
		}		
		$this->layout = 'snappi';
		// cache 
		App::import('Helper', 'Cache');
		$this->Cache = & new CacheHelper();	
		if (!empty($this->params['url']['reset'])) Cache::clearCache();
		$this->cacheAction = '1 hour';
		
		/*
		 * TODO: should only see bestshots in final Timeline, no duplicates
		 * 	- also add minimum rating
		 * 
		 */ 
		$required_options['extras']['join_shots']=0;			
		$required_options['extras']['show_hidden_shots']=1;
		$required_options['extras']['hide_SharedEdits']=0;
		
		// paginate 
		$paginateModel = 'Asset';
		$Model = $this->User->{$paginateModel};
		$Model->Behaviors->attach('Pageable');
		/*
		 *  event_group DEFAULTS
		 */ 
		$DEFAULT_EVENT_GROUP_LIMIT = 999;
		if (!isset($this->passedArgs['perpage'])) $this->paginate[$paginateModel]['perpage'] = $DEFAULT_EVENT_GROUP_LIMIT; 
		/*
		 * end event_group DEFAULTS
		 */ 
		$paginateArray = $Model->getPaginatePhotosByUserId($id, $this->paginate[$paginateModel]);
		$paginateArray = Set::merge($paginateArray, $required_options);
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);

		/*
		 *  force extras & sort=dateTaken for event-group
		 */ 
		$paginateArray['order'] = array('`Asset`.dateTaken');
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		
		$pageData = $this->paginate($paginateModel);
		$pageData = Set::extract($pageData, "{n}.{$paginateModel}");
		// end paginate
// debug(Configure::read("paginate.Options.{$paginateModel}"));
// debug(Set::extract($pageData, '/dateTaken'));
// return;		
		if (!isset($this->CastingCall)) $this->CastingCall = loadComponent('CastingCall', $this);
		if (!isset($this->Gist)) $this->Gist = loadComponent('Gist', $this);
		 
		$castingCall = $this->CastingCall->getCastingCall($pageData);
		$castingCall['CastingCall']['Auditions']['ShotType'] = 'event_group';
	
		//TODO: deprecate, use EventGroup
		$timescale = empty($this->passedArgs['timescale']) ? 1 : $this->passedArgs['timescale'];
		$castingCall['CastingCall']['Auditions']['Timescale'] = $timescale;
		$castingCall['CastingCall']['Auditions']['EventGroup'] = $this->params['named'];
		
		$event_groups = $this->Gist->getEventGroupFromCC($castingCall, $this->passedArgs);
		// event_groups will appear as shots in PAGE.jsonData.castingCall for the given timescale
		$castingCall = $this->_addEventsAsShots($event_groups, $castingCall);
		$this->viewVars['jsonData']['eventGroups'] = $event_groups;
		$this->viewVars['jsonData']['castingCall'] = $castingCall;
		$this->viewVars['jsonData']['userid'] = AppController::$userid;
		
		/*
		 * get montage  
		 * TODO: get a montage for each event, first 12 photos
		 * */
 		if (empty($castingCall['CastingCall']['Auditions'])) $getMontage = false;
		if ( isset($this->passedArgs['montage']) ) $getMontage = !empty($this->passedArgs['montage']);
		else $getMontage = ( Session::read('section-header.Photo') == 'Montage' );
		if ($getMontage) {	
 			$this->Montage = loadComponent('Montage', $this);
			$Auditions = $castingCall['CastingCall']['Auditions'];
			$options = array();
			$options['count'] = isset($this->passedArgs['count']) ? $this->passedArgs['count'] :  9;
			if (isset($this->passedArgs['w'])) $options['maxW'] = $this->passedArgs['w'];
			if (isset($this->passedArgs['h'])) $options['maxH'] = $this->passedArgs['h'];
			// $options['count'] = round(count($Auditions['Audition'])/2);
			// $options['allowed_ratios'] = array('h'=>'1:'.round($options['count']/3), 'v'=>'1:'.round($options['count']/4));  // set for Hscroll
			$this->viewVars['jsonData']['montage'] = $this->Montage->getArrangement($Auditions, $options);
		}	
			
		$done = $this->renderXHRByRequest('json', '/elements/photo/roll', null, 0);
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false	
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
		$this->layout = 'snappi';
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