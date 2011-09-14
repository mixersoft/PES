<?php

class AppController extends Controller {

	public $scaffold;
//	public $layout = 'default';
	public $layout = 'snappi-aui-960';
	public $components = array(
		'Cookie',
		'Session',
		'RequestHandler',
		'Auth',
		'Permissionable.Permissionable',
	);

	static $uuid = null;
	static $userid = null;
	static $writeOk = false;

	/**
	 * beforeFilter
	 *
	 * Application hook which runs prior to each controller action
	 * @access public
	 */
	function beforeFilter() {
		//Override default fields used by Auth component
		$this->Auth->userModel = 'User';
		$this->Auth->fields = array('username'=>'username', 'password'=>'password');
		//Set application wide actions which do not require authentication

		//		$this->Auth->allow(array('home', 'photos'));
		//		$this->Auth->allow('*');	// disable permissions, allow everything
		//		debug($this->Auth->allowedActions);

		//Set the default redirect for users who logout
		$this->Auth->logoutRedirect = '/users/login?optional';
		//Set the default redirect for users who login
		$this->Auth->loginRedirect = '/my/home';
		// login page
		$this->Auth->loginAction = '/users/login' . (Configure::read('AAA.allow_guest_login') ? '?optional' : '');
		//Extend auth component to include authorisation via isAuthorized action
		$this->Auth->authorize = 'controller';
		//Restrict access to only users with an active account
		$this->Auth->userScope = array('User.active = 1');
		//Pass auth component data over to view files
		$this->set('Auth', $this->Auth->user());		// deprecate
		// call login() after auth for additional processing
		$this->Auth->autoRedirect = false;
			
		// assume $this->Auth->user has been set
		$this->prepareRequest();
		
		// setup for Comments plugin
		if ($this->action=='discussion' && isset($this->Comments)) {
			$this->passedArgs['comment_view_type'] = 'flat';
			$this->Comments->actionNames = array('discussion');
			$this->Comments->viewVariable = 'data';
		}	
		
//		ClassRegistry::init('Asset')->disablePermissionable();
//		ClassRegistry::init('Group')->disablePermissionable();	
	}
	
	/*
	 * load profile data 
	 */
	function __loadProfile(){
		if(!$this->Session->check('profile')){
			$this->User = ClassRegistry::init('User');
			$this->User->id = Session::read('Auth.User.id');
			$getMetaData = $this->User->getMeta('profile');
			if($getMetaData){
				$this->Session->write('profile', $getMetaData);
			}
		}
		$this->viewVars['jsonData']['profile'] = $this->Session->read('profile');
	}

	/**
	 * beforeRender
	 *
	 * Application hook which runs after each action but, before the view file is
	 * rendered
	 *
	 * @access public
	 */
	function beforeRender() {
		unset($this->passedArgs['comment_view_type']);
		$this->__setPageTitle();
		$this->__addFiltersAsJsonData();
	}
	
	function __setPageTitle() {
		if ($this->RequestHandler->isAjax() || $this->RequestHandler->ext=='json') {
			return; // skip page titles for AJAX requests.
		}
		 
		if (AppController::$uuid) {
			if (isset($this->keyName)) {
				$label = Session::read("lookup.trail.{$this->keyName}.label");
			}  
			$title = !empty($label) ? "{$label} ({$this->displayName})" : $this->titleName ;
		} else {
			$title = isset($this->titleName) ? $this->titleName : '';
		}
		$this->set('title_for_layout', $title);
	}

	/*
	 * set XHR source params to pass to XHR requests 
	 * parses $this->params['url']['xhrfrom' or 'xhrview'], and removes from $this->params['url']
	 * 	?xhrfrom=[controller keyname]~[uuid]~[action]~[view/element] 
	 *  ?xhrview=[view/element]
	 * saves to Configure::write('controller.xhrFrom')
	 *  Configure::read('controller.xhrFrom.keyName')
	 *  Configure::read('controller.xhrFrom.uuid') 
	 *  Configure::read('controller.xhrFrom.action')
	 *  Configure::read('controller.xhrFrom.view')
	 */
	function __setXhrFrom() {
		if (!empty($this->params['url']['xhrfrom'])) {
			// move from cgi param to Configure
			$xhrFromParts = explode('~', $this->params['url']['xhrfrom']);
			$xhrFrom['keyName']= isset($xhrFromParts[0]) ? $xhrFromParts[0] : null;
			$xhrFrom['uuid']= isset($xhrFromParts[1]) ? $xhrFromParts[1] : null;
			$xhrFrom['action']= isset($xhrFromParts[2]) ? $xhrFromParts[2] : null;
			$xhrFrom['view']= isset($xhrFromParts[3]) ? $xhrFromParts[3] : null;
			unset($this->params['url']['xhrfrom']);
		} else {
			$xhrFrom['keyName'] = Configure::read("lookup.keyName.".Configure::read('controller.name')); // keyName
			$xhrFrom['uuid'] = AppController::$uuid;  // add as ? 'src'=>implode('~',$xhrRoot)				// uuid
			$xhrFrom['action'] = null; // action (optional)
			$xhrFrom['view'] = null; 
		}
		if (!empty($this->params['url']['xhrview'])) {
			$xhrFrom['view'] = $this->params['url']['xhrview']; // view element(optional)
			unset($this->params['url']['xhrview']);
		}
		Configure::write('controller.xhrFrom', $xhrFrom);																	
	}
	
	function __addFiltersAsJsonData() {
		if ($this->RequestHandler->isAjax()) {
			return; // skip filters for AJAX requests.
		}
		/*
		 * build filter config as jsonData
		 */
		$controllerAttr = Configure::read('controller');

		$filter=array();
		$context = Session::read('lookup.context');
		if ($context['keyName']) {
			$contextControllerAlias = Configure::read("lookup.xfr.{$context['keyName']}.ControllerAlias");
			$labelHref = array('controller'=>$contextControllerAlias,'action'=>'home', $context['uuid']);
			$removeHref  = $this->passedArgs + array('plugin'=>'', 'context'=>'remove');
			$extras['label'] = $context['label'];
			$extras['labelHref'] = Router::url($labelHref);
			$extras['removeHref'] = Router::url($removeHref);
			$filter[] = array_merge($context , $extras);
		}
		if (!empty($this->passedArgs['q'])) {	// search
			$remove = $this->passedArgs;
			unset($remove['q']);
			$filter[] = array(
				'class'=>'Search',
				'label'=>$this->passedArgs['q'],
//				'classLabel'=>'text', 
				'removeHref'=>Router::url($remove)
			);
		}
		if (!empty($this->passedArgs['rating'])) {	// search
			$remove = $this->passedArgs;
			unset($remove['rating']);
			$filter[] = array(
				'class'=>'Rating', 
				'label'=>"at least {$this->passedArgs['rating']}", 
				'value'=>$this->passedArgs['rating'], 
				'removeHref'=>Router::url($remove)
			);
		}
		$this->viewVars['jsonData']['filter'] = @mergeAsArray($this->viewVars['jsonData']['filter'],$filter);
		/*
		 * end filter JSON
		 */
	}

	/**
	 * allows easy rendering of json/xml requests in browser for debugging
	 */
	function renderXHRByRequest($json=null, $xhr=null, $xml=null, $forceXHR = 0){
		$forceXHR = setXHRDebug($this, $forceXHR);
		$renderComplete = false;
		if ($this->RequestHandler->isAjax() || $forceXHR) {
			if ($forceXHR) header('Content-Type: text/html');		// force text/html to view in browser
			else Configure::write('debug',0);
			
			if ($this->RequestHandler->ext == 'json') {
				if (empty($json) || $json == 'json') {
					if (!$forceXHR) header('Content-type: application/json');
					$this->view = 'Json';
					$this->render(); 
				} else {
					// XHR && json
					header('Content-Type: application/json');
					$this->render($json);
				}  	
				$renderComplete = true;
			} else if ($this->RequestHandler->ext == 'xml' && $xml) {
				// XHR && xml
				if (!$forceXHR) header('Content-Type: application/xml');
				$this->render($xml);
				$renderComplete = true;
			} else if ($xhr){
				// XHR && !json or !xml
				if (!$forceXHR) header('Content-Type: text/html');
				$this->render($xhr, 'ajax');
				$renderComplete = true;
			}
			if ($forceXHR) 	$this->render('/elements/sqldump', false);
			
		} else if ($this->RequestHandler->ext !=='html') {
			// NOTE: THIS IS FOR DEBUGGING XHR REQUESTS IN THE BROWSER
			/*
			* NOTE: http content-type headers don't get sent by
			* 		RequestHandlerComponent when debug=2
			*/
			$setContentType = !Configure::read('debug');
			if (!$setContentType) header('Content-Type: text/html');		// force text/html to view in browser
			
			if ($this->RequestHandler->ext == 'json' && $json) {
				if (empty($json) || $json = 'json') {
					if ($setContentType) header('Content-type: application/json');
					$this->view = 'Json';
					$this->render($json);
				} else {
					// XHR && json
					if ($setContentType) header('Content-Type: application/json');
					$this->render($json);
				}  	
				$renderComplete = true;
			} else 	if ($this->RequestHandler->ext == 'xml' && $xml) {
				// XHR && xml
				if ($setContentType) header('Content-Type: application/xml');
				$this->render($xml);
				$renderComplete = true;
			}
			if (Configure::read('debug')) $this->render('/elements/sqldump');
		}
		if ($renderComplete && $this->autoRender) $this->autoRender = false;
		return $renderComplete;
	}

	/**
	 * isAuthorized
	 *
	 * Called by Auth component for establishing whether the current authenticated
	 * user has authorization to access the current controller:action
	 *
	 * @return true if authorised/false if not authorized
	 * @access public
	 */
	function isAuthorized() {
		$retval = $this->__permittedActions($this->name, $this->action);
		return $retval;
	}
	/**
	 * __permittedActions
	 *
	 * Helper function returns true if the currently authenticated user has permission
	 * to access the controller:action specified by $controllerName:$actionName
	 * @return
	 * @param $controllerName Object
	 * @param $actionName Object
	 */
	function __permittedActions($controllerName, $actionName) {
		//		debug($this->Auth->allowedActions);
		// first check Auth->allow
		if (in_array(low($actionName), $this->Auth->allowedActions)) {
			return true;
		}

		//Ensure checks are all made lower case
		$controllerName = low($controllerName);
		$actionName = low($actionName);

		//If permissions have not been cached to session...
		if (!$this->Session->check('Auth.Permissions')) {
			$this->Permissionable->initialize($this);
			// should be done in Permissionable.Permissionable::initialize()
		}
		//...they have been cached already, so retrieve them
		$permissions = $this->Session->read('Auth.Permissions.actions');

		//Now iterate through permissions for a positive match
		foreach ((array)$permissions as $permission) {
			// ! prefix to $permission == deny
			$ok = strpos($permission, '!')===false;
			if (!$ok) $permission = substr($permission, 1);

			if ($permission == '*') {
				return $ok;//Super Admin Bypass Found
			}
			if (low($permission) == $controllerName.':*') {
				return $ok;//Controller Wide Bypass Found
			}
			if (low($permission) == '*:'.$actionName) {
				return $ok;//system Wide action Bypass Found
			}
			if (low($permission) == $controllerName.':'.$actionName) {
				return $ok;//Specific permission found
			}
		}
		$this->Session->setFlash("Permission Denied from isAuthorized");
		$this->log("WARNING: Permission Denied from isAuthorized for ACL={$controllerName}:{$actionName}", LOG_DEBUG);
		return false;
	}


	/**
	 * loads $this->Session->read('Current') plus additional key values into static member variables for class Cookie
	 * class Cookie is defined in /app/config/bootstrap.php
	 */
	function getCurrent() {	// deprecate
		(array)$restored = $this->Session->read('Current');
	}
	
	function prepareRequest() {
		$this->__cacheAuth();
		$this->__cacheControllerAttrs();
		$this->__cacheClickStream();
		$this->__setXhrFrom();			
		$this->__moveParamToNamed();
		$this->__loadProfile();
		
		$this->__checkForContextChange();	
		
		$this->passedArgs['plugin'] = '';  // suppress plugin on Router::url() generation
		if (!empty($this->passedArgs['debug'])) Configure::write('debug' , $this->passedArgs['debug']);
		Configure::write('passedArgs', $this->passedArgs);
		if (!$this->Session->check('stagepath_baseurl')) Session::write('stagepath_baseurl', '/'.Configure::read('path.stageroot.httpAlias').'/');
		
		$this->helpers[] = 'Layout';
	}
	
	/*
	 * deprecate. should use Config class for request only caching
	 */
//	function setCurrent($key, $value) { // deprecate
//		$this->Session->write("Current.$key", $value);
//	}
	

	function getControllerAlias(){
		if (isset($this->params['url']['url'])) {
			$urlparts = explode('/', $this->params['url']['url']);
			return $urlparts[0];
		} else return null;
	}

	function __cacheAuth() {
		$auth = $this->Auth->user();	// comes from Session::read('Auth.User')
		if (!empty($auth)) {
			$displayName = ($auth['User']['username']==$auth['User']['id']) ? 'Guest' : $auth['User']['username'];
			$role = array_search($auth['User']['primary_group_id'], Configure::read('lookup.roles'), true);
			$this->Session->write('Auth.User.displayname', $displayName);
			$this->Session->write('Auth.User.role', $role);
			// TODO: refactor to use AppController::$userid instead of Session::read('Auth.User.id');
			AppController::$userid = $auth['User']['id'];	
		}
	}
	
	
	/** 
	 * Controller/Request attributes that need to be available to Model/View classes
	 */
	function __cacheControllerAttrs() {
		if (in_array($this->name, array('Assets', 'Groups', 'Users', 'Tags'))) {
			
			AppController::$uuid = isset($this->passedArgs[0]) ? $this->passedArgs[0] : null;
			/*
			 * sluggable processing. this method requires 2 DB calls. (cached)
			 */
//			$slug = isset($this->passedArgs[0]) ? $this->passedArgs[0] : null;
//			AppController::$uuid = $this->__getFromSlug($slug);
//			$this->passedArgs[0] = AppController::$uuid;
			
			$controllerAttr = array(
				'name'=>$this->name, 
				'keyName'=>Configure::read("lookup.keyName.{$this->name}"), 
				'alias'=>$this->getControllerAlias(),
				'titleName'=>isset($this->titleName) ? $this->titleName : '',
				'action'=>$this->action,
				'here'=>$this->here,
//				'uuid'=>AppController::$uuid, // see ['xhrFrom']['uuid'] or PAGE.jsonData.controller.xhrFrom.uuid
				'userid'=>AppController::$userid,
				'isXhr'=>$this->RequestHandler->isAjax(),			
			);
			if (!empty($this->params['url']['forcexhr'])) $controllerAttr['isXhr'] = 1; 
			
			$extras = array( 
				'class'=>Inflector::singularize($this->name),		// ?? is this still used? replaced by keyName? 
				'label'=>isset($this->displayName) ? $this->displayName : '', 
				'isWide'=>!empty($this->params['named']['wide']),
			);
			$controllerAttr = array_merge($controllerAttr, $extras);
			
			Configure::write('controller', $controllerAttr);	
			$this->keyName = $controllerAttr['keyName'];	// save for easy usage inside controller 
//			$this->viewVars['jsonData']['controller'] = $controllerAttr;	// moved to layout		
		}	
	}
	
	function __getFromSlug($slug = null) {
		// how do I know if this is NOT a uuid?
		if ($this->{$this->modelClass}->Behaviors->enabled('Sluggable')){
			$id = $this->{$this->modelClass}->getFromSlug($slug);
			if ($id) return $id;
		} 
		return $slug;
	}
	/**
	 * any breadcrumb related values
	 */
	function __cacheClickStream() {
		if (!AppController::$uuid) return;  // all page, no clickstream update
		$controllerAttr = Configure::read('controller');
		$trail_uuid = Session::read("lookup.trail.{$controllerAttr['keyName']}.uuid");
		if (in_array($controllerAttr['name'], array('Users', 'Groups', 'Assets', 'Tags', 'Events', 'Collections'))){
			if ($trail_uuid !== AppController::$uuid) 
			{
				// update trail with new breadcrumb
				$newCrumb = array('uuid'=>AppController::$uuid, 'classLabel'=>$this->displayName);
				Session::write("lookup.trail.{$controllerAttr['keyName']}", $newCrumb);
			}
		}
		// cache nav location in session
		$titleName = ($controllerAttr['action'] != 'all') ? $controllerAttr['titleName'] : 'Explore';
		switch ($titleName) {
			case 'Me':
				if ($controllerAttr['action'] == 'home') $focus = 'Home'; 
				// else if ($controllerAttr['action'] == 'groups') $focus = 'Circles';
				// else if ($controllerAttr['action'] == 'photos') $focus = 'Snaps';
				else $focus = 'Home'; 
				break;
			case 'Groups':
			case 'Events':
			case 'Weddings':
			case 'Circles':	
				$focus = 'Circles'; break;
			case 'Photos':
				$focus = 'Snaps'; break;
			case 'People':
				$focus = 'People'; break;
			case 'Explore':
				$focus = 'Explore'; break;
			default: 
				$focus = null;
		}
		Session::write("nav.primary", $focus);
	}
	
	/*
	 * move various $this->params['url'] params to $this->named params for downstream URL creations
	 */
	function __moveParamToNamed() {
		// convert $this->params['url']['q'] to $this->named['q'] so 'q' can be used by XHR
		if (!empty($this->params['url']['q'])) {
			$this->params['named']['q'] = $this->params['url']['q'];
			$this->passedArgs['q'] = $this->params['url']['q'];
			unset($this->params['url']['q']);
		}		
	}
	
	
	/**
	 * persist Context into Session for subsequent pageviews
	 * @param $keyName mixed. array or $keyName value of uuid
	 * @param $uuid
	 * @param $extras
	 * @return void
	 */	
	function __setContext($keyName, $uuid=null, $extras=null ) {
		$current_context = Session::read('lookup.context');
		if (is_array($keyName)) {
			Session::write('lookup.context', Set::merge($current_context, $keyName));
		} else {
			if ($keyName ===null || $keyName ==='remove') {
				$new_context = array('keyName'=>null, 'uuid'=>null);
			} else if ($uuid) {	
				$new_context = array('keyName'=>$keyName, 'uuid'=>$uuid);
			} else {
				$new_context = Session::read("lookup.trail.{$keyName}");
				$new_context['keyName'] = $keyName;
			}
			if (is_array($extras)) {
				$new_context = array_merge($extras, $new_context);
			}
			Session::write('lookup.context', $new_context);
		}
	}
	
	function __checkForContextChange() {
			// check for explicit Context change, update and redirect if found
		if (isset($this->params['named']['context'])) {
			if ($this->action == 'search') $this->__setContext(null);
			else if ($this->params['named']=='Asset') $this->__setContext(null);
			else if ($this->params['named']['context'] == 'remove') $this->__setContext(null);
			else {
				$this->__setContext($this->params['named']['context']); 
			}
			unset($this->params['named']['context']);
			unset($this->passedArgs['context']);
				
			//			$this->passedArgs['plugin'] = '';
			$here = Router::url($this->passedArgs);
			// redirect, to remove context from url
			$this->redirect($here, null, true);
		}		
	}


	function getProfile($id=null) {
		$id= $id? $id : Session::read('Auth.User.id');
		$options=array('recursive'=>-1, 'conditions'=>array('Profile.user_id'=>$id));
		return ClassRegistry::init('Profile')->find('first',$options);
	}

	/*
	 * get lookups for thumbnails, 
	 */
	function getLookups($request) {
		foreach ($request as $class=>$ids) {
			$Model = ClassRegistry::init(Inflector::singularize($class));
			$options = array('recursive'=>-1, 'conditions'=>array('id'=>$ids));
			switch ($class) {
				case "Users":
					// get owner names from query
					$options['fields'] = 'User.id, User.username';
					$ownerLookup = @Set::combine($Model->find('all', $options), '/User/id', '/User/username');
					Session::write('lookup.owner_names', Set::merge(Session::read('lookup.owner_names'), $ownerLookup));
					break;
			}
		}
	}

	/**
	 * cascading options for paginate
	 * @deprecated ??? 
	 */
	function getPaginateOptionsForCount($model, $options=array()) {
		$limit = ''; $page = 1; $perpage=null;
		$order = ''; $sort = ''; $direction = '';
		$fields=''; $conditions='';
		// TODO:add paginate processing for these fields $recursive='';
		//  $recursive='';
		extract($this->paginate[$model], EXTR_IF_EXISTS);
		extract($options, EXTR_IF_EXISTS);
		extract($this->passedArgs, EXTR_IF_EXISTS);
		if ($perpage) $limit = $perpage;
		$this->paginate[$model]['limit'] = $limit;		// for proper pageCount
		return  compact('fields', 'order', 'limit', 'page', 'sort', 'direction', 'conditions');
	}


	function redirectSafe(){
		$redirect = env('HTTP_REFERER') ? env('HTTP_REFERER') : substr($this->here, 0, strpos($this->here,'/',1));
		$this->redirect($redirect, null, true);
	}

	/**
	 * @deprecated, moved to pageable behavior
	 * @param $paginateModel
	 * @param $action
	 * @return unknown_type
	 */
	function getPerpageLimit($paginateModel, $action = null) {
		if ($action===null) $action = $this->action;
		$perpageProfileData = $this->Session->read("profile.{$action}.perpage");
		if($perpageProfileData){
			$limit = $perpageProfileData;
		} else if (empty($this->params['url']['preview']) ) {
			// get a full page, NOT a preview page
			$limit = $this->paginate[$paginateModel]['big_limit'];
		} else {
			// preview page
			$limit = $this->paginate[$paginateModel]['limit'];
		}		
		return $limit;
	}


	/**
	 * override Comments View
	 */
	public function callback_commentsView($displayType, $processActions = true) {
		return $this->Comments->callback_view($displayType, $processActions);
	}
}

?>