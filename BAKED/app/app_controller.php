<?php

class AppController extends Controller {

	public $scaffold = false;
//	public $layout = 'default';
	public $layout = 'snappi';
	public $components = array(
		'Cookie',
		'Session',
		'RequestHandler',
		'Auth',
		'Permissionable.Permissionable',
	);
	static $http_static = null;
	static $uuid = null;
	static $userid = null;
	static $ownerid = null;
	static $role = null;
	static $writeOk = false;

	/**
	 * beforeFilter
	 *
	 * Application hook which runs prior to each controller action
	 * @access public
	 */
	function beforeFilter() {
		if (!isset($this->helpers['Layout'])) $this->helpers['Layout'] = null;
		$this->__check_browserRedirect();
		//Set application wide actions which do not require authentication

		//		$this->Auth->allow(array('home', 'photos'));
		//		$this->Auth->allow('*');	// disable permissions, allow everything
		//		debug($this->Auth->allowedActions);

		//Set the default redirect for users who login
		$this->Auth->loginRedirect = '/my/home';
		// login page
		if (empty($this->Auth->loginAction)) $this->Auth->loginAction = '/users/signin' . (Configure::read('AAA.allow_guest_login') ? '?optional' : '');
		//Set the default redirect for users who logout
		$this->Auth->logoutRedirect = $this->Auth->loginAction;  // ?optional
		
		// call login() after auth for additional processing
		$this->Auth->autoRedirect = false;
		
		// assume $this->Auth->user has been set
		$this->prepareRequest();
		
		
		// setup for Comments plugin
		if ($this->action=='discussion' && isset($this->Comments)) {
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
			$this->User->id = AppController::$userid;
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
		if (in_array($this->name, array('Users', 'Groups', 'Assets', 'Collections', 'Tags'))) {
			unset($this->passedArgs['comment_view_type']);
			$this->__setPageTitle();
			$this->__addFiltersAsJsonData();
		} else if($this->name == 'CakeError') {
			$this->layout = 'plain';
		}
	}
	
	function __check_browserRedirect() {
		if (empty($_SERVER['HTTP_USER_AGENT'])) return true;
		$u_agent = $_SERVER['HTTP_USER_AGENT']; 
	    $unsupported = false; 
	    if(preg_match('/MSIE/i',$u_agent)) 
	    {
	    	$unsupported = true;
	    	
	    } 
		
		if (in_array($this->name, array('Pages', 'Gallery', 'Combo', 'Snappi'))) {
			return $unsupported;
		} else if ($this->name == 'Collections' && $this->action == 'story') {
			return true;
		} else if ($unsupported) {
			Session::write('browser_unsupported_redirect', $this->here);
			$this->redirect('/pages/browser_unsupported', null, true);
		}
		return true;
	}
	function __redirectIfTouchDevice(){
		// Story controller checks for touch device
		$options = array('Android', 'iPod', 'iPhone', 'iPad','Opera Mobi','webOS', 'Windows Phone OS');			
		$pattern = '/' . implode('|', $options) . '/i';
		$isTouch = (bool)preg_match($pattern, env('HTTP_USER_AGENT'));
		
		$SUBDOMAIN = array('touch'=>"touch", 'desktop'=>'preview');
		$exclude = array('git3', 'dev', 'touch-debug');
		$pattern = '/' . implode('|', $exclude) . '/i';
		$isExclude = (bool)preg_match($pattern, env('HTTP_HOST'));
    	if ($isTouch && !$isExclude && strpos(env('HTTP_HOST'),$SUBDOMAIN['touch'])!=0 ) {
			$next = "http://{$SUBDOMAIN['touch']}.snaphappi.com".env('REQUEST_URI');
			$this->redirect($next, null, true);
		} else if (!$isTouch && !$isExclude && strpos(env('HTTP_HOST'),$SUBDOMAIN['touch'])===0 ){
			$next = "http://{$SUBDOMAIN['desktop']}.snaphappi.com".env('REQUEST_URI');
			$this->redirect($next, null, true);
		}
	}
	function __setPageTitle() {
		if ($this->RequestHandler->isAjax() || $this->RequestHandler->ext=='json') {
			return; // skip page titles for AJAX requests.
		}
		if (!empty($this->viewVars['title_for_layout'])) return;
		
		if (AppController::$uuid) {
			if (isset($this->displayName)) {
				$label = Session::read("lookup.trail.{$this->displayName}.label");
			}  
			$title = !empty($label) ? "{$label} ({$this->displayName})" : isset($this->titleName) ? $this->titleName : 'Snaphappi' ;
		} else {
			$title = isset($this->titleName) ? $this->titleName : '';
		}
		$this->set('title_for_layout', $title);
	}

	/*
	 * set XHR source params to pass to XHR requests 
	 * parses $this->params['url']['xhrfrom' or 'xhrview'], and removes from $this->params['url']
	 * 	?xhrfrom=[controller alias]~[uuid]~[action]~[view/element] 
	 *  ?xhrview=[view/element]
	 * saves to Configure::write('controller.xhrFrom')
	 *  Configure::read('controller.xhrFrom.alias')
	 *  Configure::read('controller.xhrFrom.uuid') 
	 *  Configure::read('controller.xhrFrom.action')
	 *  Configure::read('controller.xhrFrom.view')
	 */
	function __setXhrFrom() {
		if (empty($this->params['url']['xhrfrom'])) {
			/*
			 * init if empty. for dependent XHR calls
			 */ 
			$xhrFrom['alias'] = Configure::read("controller.alias"); // controller alias
			$xhrFrom['uuid'] = AppController::$uuid;  // add as ? 'src'=>implode('~',$xhrRoot)				// uuid
			$xhrFrom['action'] = $this->action; // action (optional)
			$xhrFrom['view'] = null; 
		} else {
			/*
			 * use if set, do NOT overwrite
			 */ 
			// example: /tags/show?xhrfrom=tags~europe~home~
			$xhrFromParts = explode('~', $this->params['url']['xhrfrom']);
			$xhrFrom['alias'] = isset($xhrFromParts[0]) ? $xhrFromParts[0] :  Configure::read("controller.alias"); // controller alias
			$xhrFrom['uuid']= isset($xhrFromParts[1]) ? $xhrFromParts[1] : null;
			$xhrFrom['action']= isset($xhrFromParts[2]) ? $xhrFromParts[2] : null;
			$xhrFrom['view']= isset($xhrFromParts[3]) ? $xhrFromParts[3] : null;
			unset($this->params['url']['xhrfrom']);
		}
		// TODO: move to a better place. used for settings/edit
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
		if (in_array($controllerAttr['name'], array('Users', 'Groups', 'Tags', 'Collections', 'Assets'))) {
			$filter=array();
			$context = Session::read('lookup.context');
			if ($context['keyName']) {
				$contextControllerAlias = Configure::read("lookup.xfr.{$context['keyName']}.ControllerAlias");
				$labelHref = array('controller'=>$contextControllerAlias,'action'=>'home', $context['uuid']);
				$removeHref  = $this->passedArgs + array('plugin'=>'', 'context'=>'remove');
				$extras['label'] = !empty($context['label']) ? $context['label'] : $context['uuid'];
				$extras['labelHref'] = Router::url($labelHref);
				$extras['removeHref'] = Router::url($removeHref);
				$context['class'] = $context['keyName'];	// deprecate keyName
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
			if (isset($this->passedArgs['rating'])) {	// search
				$remove = $this->passedArgs;
				unset($remove['rating']);
				$filter[] = array(
					'class'=>'Rating',	
					'label'=>"at least {$this->passedArgs['rating']}", 
					'value'=>$this->passedArgs['rating'], 
					'removeHref'=>Router::url($remove)
				);
				if ($this->passedArgs['rating']==0) $filter['label'] = 'unrated Snaps'; 
			}
			$this->viewVars['jsonData']['filter'] = @mergeAsArray($this->viewVars['jsonData']['filter'],$filter);
			/*
			 * end filter JSON
			 */
		}
	}
	
	function __updateExif($uuid) {
		if (!Permissionable::isRoot() && $uuid===null) {
			echo "Root permission required.";
			exit;
		}		
		$this->autoRender = false;
		Configure::write('debug',2);
// debug("AppController::__updateExif");	
		if (!isset($this->Asset)) $this->Asset = ClassRegistry::init('Asset');	
		$ret = $this->Asset->updateExif(array('name'=>$this->name,'uuid'=>$uuid));
		$this->render('/elements/dumpSQL', 'plain');
		return;
	}


	/**
	 * read cookie with prefix 'SNAPPI_' from $_COOKIE
	 * and save json_encode(data) to $this->viewVars['cData']
	 * read in javascript from PAGE.Cookie 
	 * use $this->layout='markup', or 
	 * 		if (isset($cData)) echo $this->element('script/cookieData'); 
	 */ 
	function __setCookies(){
		// scan $_COOKIE for prefix 'SNAPPI_'
		foreach ($_COOKIE as $name=>$data) {
			if (strpos($name, 'SNAPPI_') !== 0) continue;
			
			// only read cookie with prefix 'SNAPPI_' from $_COOKIE
			if (substr($data, 0, 1)=='{'){	
				// guess its already JSON data
				$this->viewVars['cData'][str_replace('SNAPPI_', '', $name)] = $data;
			} else {
				// parse into Array for json_encode() on output
				$cookie = $this->Cookie->read("{$name}.");	// returns array of kv pairs
				foreach ($cookie as $kv){
					list($k, $v) = explode('=',$kv);
					$aa[$k] = $v;
				}
				$this->viewVars['cData'][str_replace('SNAPPI_', '', $name)] = $aa;	
			}			
		}
	}
	/**
	 * allows easy rendering of json/xml requests in browser for debugging
	 */
	function renderXHRByRequest($json=null, $xhr=null, $xml=null, $forceXHR = 0){
		// TODO: use Configure::read('debugXhr');
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
			if ($forceXHR) 	{
				$this->render(null, null,'/elements/dumpSQL');
			}			
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
			if (Configure::read('debug')) $this->render('/elements/dumpSQL');
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
		AppController::$http_static = Configure::read('http_static');
		$this->__cacheAuth();
		$this->__cacheControllerAttrs();	
		$this->__cacheClickStream();
		$this->__setXhrFrom();			
		$this->__moveParamToNamed();
		$this->__loadProfile();
		$this->__checkForContextChange();	
		
		$this->passedArgs['plugin'] = '';  // suppress plugin on Router::url() generation
		if (!empty($this->passedArgs['debug'])) Configure::write('debug' , $this->passedArgs['debug']);
		Configure::write('passedArgs.complete', $this->passedArgs);
		Configure::write('passedArgs.min', array_diff_key($this->passedArgs, array_flip(array('perpage', 'page', 'sort', 'direction', 'filter'))));
		// use Stagehand Static class to manage staged content and object badges
		Stagehand::$default_badges = Configure::read('path.default_badges');
		$root = Configure::read('path.stageroot');
		Stagehand::$stage_baseurl =  "/{$root['httpAlias']}/";
		Stagehand::$stage_basepath = $root['basepath'];
		
		// $this->viewVars['jsonData']['named'] = $this->params['named'];
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
			AppController::$role = $role;
			$this->Session->write('Auth.User.displayname', $displayName);
			// $this->Session->write('Auth.User.role', $role);		// deprecated
			AppController::$userid = $auth['User']['id'];	
			//Pass auth component data over to view files
			if ($this->Auth->user()) $this->set('Auth', $this->Auth->user());
		}
	}
	
	
	/** 
	 * Controller/Request attributes that need to be available to Model/View classes
	 */
	function __cacheControllerAttrs() {
		// min, required for /nav/header
		$controllerAttr = array(
			'name'=>$this->name,
			'alias'=>$this->getControllerAlias(),
			'action'=>$this->action,
			'here'=>$this->here,
			'userid'=>AppController::$userid,
			'isXhr'=>$this->RequestHandler->isAjax(),			
		);
		$extended_controllerAttr = $extras = array();	
		if (in_array($this->name, array('Assets', 'Groups', 'Users', 'Collections', 'Tags'))) {
			AppController::$uuid = isset($this->passedArgs[0]) ? $this->passedArgs[0] : null;
			/*
			 * sluggable processing. this method requires 2 DB calls. (cached)
			 */
//			$slug = isset($this->passedArgs[0]) ? $this->passedArgs[0] : null;
//			AppController::$uuid = $this->__getFromSlug($slug);
//			$this->passedArgs[0] = AppController::$uuid;
			$extended_controllerAttr = array(
				// deprecate keyName, use label/displayName instead 
				'keyName'=>Configure::read("lookup.keyName.{$this->name}"), 
				'label'=>$this->displayName, 
				'titleName'=>isset($this->titleName) ? $this->titleName : '',
//				'uuid'=>AppController::$uuid, // see ['xhrFrom']['uuid'] or PAGE.jsonData.controller.xhrFrom.uuid
			);
		}			
		$extras = array( 
			'class'=>Inflector::singularize($this->name),		 
			// 'label'=>isset($this->displayName) ? $this->displayName : '', 
			'isWide'=>!empty($this->params['named']['wide']),
		);
		if (!empty($this->params['url']['forcexhr'])) $controllerAttr['isXhr'] = 1; 
		$controllerAttr = array_merge( $controllerAttr , $extended_controllerAttr, $extras);

		if (in_array(AppController::$role, array('EDITOR','MANAGER','ADMIN','ROOT'))) {
			// set in /person/photos for now
			AppController::$ownerid = Session::read('Auth.User.acts_as_ownerid');
			if (!AppController::$ownerid) AppController::$ownerid =  AppController::$userid;
			$controllerAttr['userid'] = AppController::$ownerid;
			$controllerAttr['ROLE'] =  AppController::$role;
		} else AppController::$ownerid = AppController::$userid; 	
		Configure::write('controller', $controllerAttr);
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
		if ($this->RequestHandler->isAjax()) return; // don't update ajax requests
		
		$controllerAttr = Configure::read('controller');
		
		if (in_array($controllerAttr['name'], array('Users', 'Groups', 'Assets', 'Tags', 'Collections'))){
			$trail_uuid = Session::read("lookup.trail.{$this->displayName}.uuid");
			if ($trail_uuid !== AppController::$uuid) 
			{
				// update trail with new breadcrumb
				// note: $classLabel==$this->displayName, but used by lookup.trail
				$newCrumb = array('uuid'=>AppController::$uuid, 'classLabel'=>$controllerAttr['label']);
				Session::write("lookup.trail.{$controllerAttr['label']}", $newCrumb);
			}
		}
		// cache nav location in session
		// displayName == $controllerAttr['label'] == Me, Person, Group, Photo/Snap, Tag (section header)
		// titleName = Me, People, Groups, Tags
		$navPrimary = (isset($this->displayName ) && $controllerAttr['action'] != 'all') ? $this->displayName : 'Explore';
		if ($navPrimary == "Tag") {
			if ($context = Session::read("lookup.context")) {
				$context = array_shift($context);
			} else {
				$context = array();
			}
			$navPrimary = isset($context['classLabel']) ? $context['classLabel'] : 'Tag';	
		}
		switch ($navPrimary) {
			case 'Me':
				// if ($controllerAttr['action'] == 'home') $focus = 'Home'; 
				$focus = 'Home'; 
				break;
			case 'Group':
			case 'Event':
			case 'Wedding':
				$focus = 'Circles'; break;
			case 'Snap':
			case 'Photo':
			case 'Story':	
				$focus = 'Snaps'; break;
			case 'Person':
				$focus = 'People'; break;
			case 'Tag':
			case 'Explore':
			default: 
				$focus = ''; break;				
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
	 * set Context explicitly to add as a filter condition for subsequent pageviews
	 * 		called by $this->__checkForContextChange()
	 * 		to set context, add named param, '/context:{$controllerAttr['label']}' 
	 * 		keyName values: Me, Person, Group, Photo/Snap, Tag (section header)
	 * @param $keyName mixed. array or String, Controller::$displayName, aka $controllerAttr['label']
	 * @param $uuid
	 * @param $extras, additional keys, ['classLabel']
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
			return $new_context;
		}
	}
	/**
	 * check passedArgs to set/clear Context
	 * 		to set context, add named param, '/context:{$controllerAttr['label']}~{$uuid}' 
	 * 		keyName values: Me, Person, Group, Photo/Snap, Tag (section header)
	 */
	function __checkForContextChange() {
			// check for explicit Context change, update and redirect if found
		if (isset($this->passedArgs['context'])) {
			list($context['keyName'], $context['uuid'] ) = explode('~',$this->passedArgs['context']);
			
			if ($this->action == 'search') $this->__setContext(null);
			else if ($context['keyName']=='Asset') $this->__setContext(null);
			else if ($context['keyName'] == 'remove') $this->__setContext(null);
			else {
				$this->__setContext($context['keyName'], $context['uuid']); 
			}
			// unset($this->params['named']['context']);
			unset($this->passedArgs['context']);
				
			//			$this->passedArgs['plugin'] = '';
			if ($this instanceof MyController) unset($this->passedArgs[0]);
			$here = Router::url($this->passedArgs);
			// redirect, to remove context from url
			$this->redirect($here, null, true);
		}		
	}


	function getProfile($id=null) {
		$id= $id? $id : AppController::$userid;
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
	function XXXgetPaginateOptionsForCount($model, $options=array()) {
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
	/**
	 * Initializes the view type for comments widget
	 *
	 * @return string [flat|threaded|tree]
	 * @access public
	 */
    public function callback_commentsInitType($class='discussion') {
    	$type = $this->Session->read("comments.viewType.{$class}");
		if (empty($type)) {
	    	switch ($class) {
				case 'discussion': $type = 'tree'; break;
				case 'help': $type =  'flat'; break;
				default: $type =  'flat'; break;
	    	}
		}
		return $type;
    }
/**
 * Flat representaion. Paginable
 *
 * @param array $options
 * @return array
 */
	public function callback_commentsfetchDataFlat($options) {
		/*
		 * same as: $conditions = $this->Comments->_prepareModel($options);
		 */ 
		$CommentsComponent = $this->Comments;
		$UserModel = $CommentsComponent->userModel;
		$params = array(
			'isAdmin' => $this->Auth->user('is_admin') == true,
			'userModel' => $UserModel,
			'userData' => $this->Auth->user());
		$conditions = $this->{$CommentsComponent->modelName}->commentBeforeFind(array_merge($params, $options));
		/*
		 * append UserModel fields for belongsTo Association
		 */
		$UserModelFields = & $this->{$CommentsComponent->modelName}->Comment->belongsTo[$CommentsComponent->userModel]['fields'];  
		$UserModelFields = array_merge($UserModelFields, array( "{$UserModel}.src_thumbnail", "{$UserModel}.asset_count", "{$UserModel}.groups_user_count", "{$UserModel}.last_login"));		
		return $this->paginate($CommentsComponent->assocName, $conditions);
	}

/**
 * Threaded method - non paginable, whole data is fetched
 *
 * @param array $options
 * @return array
 */
	public function callback_commentsFetchDataThreaded($options) {
		$CommentsComponent = $this->Comments;
		$UserModel = $CommentsComponent->userModel;
		$Comment =& $this->{$CommentsComponent->modelName}->Comment;
		/*
		 * same as: $conditions = $this->Comments->_prepareModel($options);
		 */ 
		$params = array(
			'isAdmin' => $this->Auth->user('is_admin') == true,
			'userModel' => $UserModel,
			'userData' => $this->Auth->user());
		$conditions = $this->{$CommentsComponent->modelName}->commentBeforeFind(array_merge($params, $options));
		$fields = array(
			'Comment.id', 'Comment.user_id', 'Comment.foreign_key', 'Comment.parent_id', 'Comment.approved',
			'Comment.title', 'Comment.body', 'Comment.slug', 'Comment.created',
			$CommentsComponent->modelName . '.id',
			$CommentsComponent->userModel . '.id',
			$CommentsComponent->userModel . '.' . $Comment->{$CommentsComponent->userModel}->displayField,
			$CommentsComponent->userModel . '.slug',
			"{$UserModel}.src_thumbnail", "{$UserModel}.asset_count", "{$UserModel}.groups_user_count", "{$UserModel}.last_login"
			);
		$order = array(
			// 'Comment.parent_id' => 'asc',
			'Comment.created' => 'desc');
		$data = $Comment->find('threaded', compact('conditions', 'fields', 'order'));
		return $data;
	}	

/**
 * Tree representaion. Paginable.
 *
 * @param array $options
 * @return array
 */
	public function callback_commentsFetchDataTree($options) {
		$COMMENTS_TREE_VIEW_LIMIT = 10;
		
		$CommentsComponent = $this->Comments;
		$UserModel = $CommentsComponent->userModel;
		/*
		 * same as: $conditions = $this->Comments->_prepareModel($options);
		 */ 
		$params = array(
			'isAdmin' => $this->Auth->user('is_admin') == true,
			'userModel' => $UserModel,
			'userData' => $this->Auth->user());
		$conditions = $this->{$CommentsComponent->modelName}->commentBeforeFind(array_merge($params, $options));
		
		$order = array('Comment.lft' => 'asc');
		$limit = $COMMENTS_TREE_VIEW_LIMIT;
		$fields = array(
			'Comment.id', 'Comment.user_id', 'Comment.foreign_key', 'Comment.parent_id', 'Comment.approved',
			'Comment.lft', 'Comment.rght',
			'Comment.title', 'Comment.body', 'Comment.slug', 'Comment.created',
			$CommentsComponent->modelName . '.id',
			$CommentsComponent->userModel . '.id',
			$CommentsComponent->userModel . '.username',
			$CommentsComponent->userModel . '.slug',
			"{$UserModel}.src_thumbnail", "{$UserModel}.asset_count", "{$UserModel}.groups_user_count", "{$UserModel}.last_login"
		);
		$this->paginate['Comment'] = compact('order', 'conditions', 'limit', 'fields');
		$data = $this->paginate('Comment');
		
		
		$parents = array();
		if (isset($data[0]['Comment'])) {
			$rec = $data[0]['Comment'];
			$conditions[] = array('Comment.lft <' => $rec['lft']);
			$conditions[] = array('Comment.rght >' => $rec['rght']);
			$parents = $this->{$CommentsComponent->modelName}->Comment->find('all', compact('conditions', 'order', 'fields'));
		}
		return array_merge($parents, $data);
	}



		
}

?>