<?php
/**
 * Copyright 2009-2010, Cake Development Corporation (http://cakedc.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2009-2010, Cake Development Corporation (http://cakedc.com)
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/**
 * Tags Controller
 *
 * @package tags
 * @subpackage tags.controllers
 */
class TagsController extends TagsAppController {

/**
 * Name
 *
 * @var string
 */
	public $name = 'Tags';
	
/**
 * Helpers
 *
 * @var array
 */
//	public $helpers = array('Html', 'Form');
	public $helpers  = array(
		'Tags.TagCloud',
		'Html', 'Form', 'Text', 'Time',
		'Paginator' => array('model'=>'Tagged'),
		'CastingCallJson',
	);	

/**
 * Index action
 *
 * @return void
 */
	public function index() {
		$this->redirect(array('controller'=>Configure::read('controller.alias'), 'action'=>'all'), null, true);
		
//		$this->Tag->recursive = 0;
//		$this->set('tags', $this->paginate());
	}

/**
 * View
 *
 * @param string
 * @return void
 */
	public function view($keyName = null) {
		try {
			$this->set('tag', $this->Tag->view($keyName));
		} catch (Exception $e) {
			$this->Session->setFlash($e->getMessage());
			$this->redirect('/');
		}
	}

/**
 * Admin Index
 *
 * @return void
 */
	public function admin_index() {
		$this->Tag->recursive = 0;
		$this->set('tags', $this->paginate());
	}

/**
 * Views a single tag
 *
 * @param string tag UUID
 * @return void
 */
	public function admin_view($keyName) {
		try {
			$this->set('tag', $this->Tag->view($keyName));
		} catch (Exception $e) {
			$this->Session->setFlash($e->getMessage());
			$this->redirect('/');
		}
	}

/**
 * Adds one or more tags
 *
 * @return void
 */
	public function admin_add() {
		if (!empty($this->data)) {
			if ($this->Tag->add($this->data)) {
				$this->Session->setFlash(__d('tags', 'The Tags has been saved.', true));
				$this->redirect(array('action' => 'index'));
			}
		}
	}

/**
 * Edits a tag
 *
 * @param string tag UUID
 * @return void
 */
	public function admin_edit($tagId = null) {
		try {
			$result = $this->Tag->edit($tagId, $this->data);
			if ($result === true) {
				$this->Session->setFlash(__d('tags', 'Tag saved.', true));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->data = $result;
			}
		} catch (Exception $e) {
			$this->Session->setFlash($e->getMessage());
			$this->redirect(array('action' => 'index'));
		}

		if (empty($this->data)) {
			$this->data = $this->Tag->data;
		}
	}

/**
 * Deletes a tag
 *
 * @param string tag UUID
 * @return void
 */
	public function admin_delete($id = null) {
		if ($this->Tag->delete($id)) {
			$this->Session->setFlash(__d('tags', 'Tag deleted.', true));
		} else {
			$this->Session->setFlash(__d('tags', 'Invalid Tag.', true));
		}
		$this->redirect(array('action' => 'index'));
	}
	
	
	
	
	
	/******************************************************************************
	 * Snappi overrides to CakeDC-tags-1.1-0-ge48bb37
	 */
	public $titleName = 'Tags';
	public $displayName = 'Tag';
	public $components = array(
		'Comments.Comments' => array(  'userModelClass' => 'User'	),
		'Search.Prg',
	);

	
//	public $helpers  = array(
//		'Tags.TagCloud',
//		'Html', 'Form', 'Text', 'Time',
//		'Paginator' => array('model'=>'Tagged'),
//		'CastingCallJson',
//	);	
	
	public $paginate = array(
		'limit'=>10,
		'big_limit'=>64,
		'Tagged'=>array(
			'preview_limit'=>10,
			'paging_limit' =>64,
			// deprecate limit, big_limit
			// set limit in PageableBehavior->getPerpageLimit()				
			'limit'=>10,
			'big_limit'=>64,
		),
		'Asset'=>array(
			'preview_limit'=>6,
			'paging_limit' =>24,
			// deprecate limit, big_limit
			// set limit in PageableBehavior->getPerpageLimit()				
			'limit' => 6,
			'big_limit' =>48,
			'order'=>array('Asset.dateTaken'=>'ASC'),
			'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>'Usershot', 
				'show_hidden_shots'=>false
			),			
		),
		'Collection'=>array(				
			'preview_limit'=>4,
			'paging_limit' =>16,
			'order'=>array('Collection.modified'=>'DESC'),
			'recursive'=> -1,
			'fields' =>'Collection.*',
		),
		'Group'=>array(	
			'preview_limit'=>3,
			'paging_limit' =>8,
			// deprecate limit, big_limit
			// set limit in PageableBehavior->getPerpageLimit()			
			'limit' => 8,
			'big_limit' =>36,
			'order'=>array('Group.title'=>'ASC'),
		),
		'Comment' =>array(
			'limit'=>3,
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
		'home', 'photos', 'groups', 'discussion', 'trends', 'search', 
		/*
		 * all
		 */
		'all', 'most_active', 'most_recent', 'most_common', 
		/*
		 * actions
		 */
		'show', 'show_more',
		/*
		 * experimental
		 */
		'admin_index');  
	}

	function beforeRender(){
		if (!$this->RequestHandler->isAjax() && AppController::$uuid) {
			$label = @ifed($this->viewVars['data']['Tag']['name'], null);
			if (Session::read("lookup.trail.{$this->displayName}.uuid") == AppController::$uuid) {
				Session::write("lookup.trail.{$this->displayName}.label", $label);	
			}
		}
		parent::beforeRender(); 		
	}	

	
	/**
	 * Adds one or more tags, called from Taggables, returns to Referrer with message
	 *
	 * @return void
	 */
	public function add() {
		if (!empty($this->data)) {
			if (isset($this->data['Tag'])){
				// initialize vars
				$replace = false;
				$strTags = '';
				$foreignKey = '';
				$class = '';
				extract($this->data['Tag']);
				$next = env('HTTP_REFERER');
				if ($class=='Asset') {
					$q = strpos($next,'?');
					if ($q!==false) {
						$next = substr_replace($next,$foreignKey, $q-36).substr($next,$q);
					}  
				}
				try {
					$ret = ClassRegistry::init($class)->saveTags($strTags, $foreignKey, $replace);
					if ($ret) {
						$this->Session->setFlash(__d('tags', 'Tags saved.', true));
					}
					else {
						$this->Session->setFlash(__d('tags', 'Error. There was a problem saving these tags. Please try again.', true));
					}
				} catch(Exception $e) {
					$this->Session->setFlash(__d('tags', 'Error. There was a problem saving these tags. Please try again.', true));
				}
				$this->redirect($next, null, true);
			}
		}
	}

	function most_active(){
		$paginateModel = 'Tagged';
		$this->paginate[$paginateModel]['order'] = array('`Tag`.comment_count'=>'DESC');
		$this->paginate[$paginateModel]['limit'] = Configure::read('feeds.paginate.perpage');
		$this->action='all';
		$this->all();	
	}
	
	function most_recent(){
		$paginateModel = 'Tagged';
		$this->paginate[$paginateModel]['order'] = array('`Tag`.created'=>'DESC');
		$this->paginate[$paginateModel]['limit'] = Configure::read('feeds.paginate.perpage');
		$this->action='all';
		$this->all();	
	}

	function most_common(){
		$paginateModel = 'Tagged';
		$this->paginate[$paginateModel]['order'] = array('`Tag`.tagged_count'=>'DESC');
		$this->paginate[$paginateModel]['limit'] = Configure::read('feeds.paginate.perpage');
		$this->action='all';
		$this->all();	
	}
	
	/**
	 * all
	 * 		show all tags in paging view. 
	 * 		NOTE: '/tags/all' skips context when retrieving tags 
	 *
	 * @param string
	 * @return void
	 * @access public
	 */

	function all(){
		$this->layout = 'snappi';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';
		// paginate 
		$paginateModel = 'Tagged';
		$Model = isset($this->{$paginateModel}) ? $this->{$paginateModel} : ClassRegistry::init('Tagged');
		$this->{$paginateModel} = $Model;	// add model to controller for paginate()
		$Model->Behaviors->attach('Pageable');
		
		$paginateArray = $this->paginate[$paginateModel];
		if (!empty($this->passedArgs['filter'])) {
			// filter tagCloud by Model
			$paginateArray['conditions'] = array('Tagged.model'=>$this->passedArgs['filter']);
		}		
		$paginateArray['conditions'] = @$Model->Tag->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		
		// additional options, 
		// TODO: move to $options['extras']['context']
		$options=array();
		$xhrFrom = Configure::read('controller.xhrFrom');
		if (!$xhrFrom) {
			$options['context'] = 'skip';		// skip/ignore context when compiling list of tags
		}
		$options['operation'] = 'cloud';
		$paginateArray = array_merge($paginateArray, $options);		
		
		if ($this->action !== 'all') {
			// force perpage set from feeds
			Configure::write('passedArgs.perpage',$this->paginate[$paginateModel]['limit']);	// Configure::read('feeds.paginate.perpage')
			$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		} else {
			$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray, 'trends');
		}			
		// paginate Tagged	
		$data['cloud'] = $this->paginate($paginateModel);		
		// end paginate		
		
		$this->set('cloudTags', $data['cloud']);  // tagCloud
		$this->set('isPreview',0);				
		$done = $this->renderXHRByRequest(null, '/elements/tags/tagCloud', null);
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false	
	}


	
	function trends($keyname = null) {
		$this->layout = 'snappi';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';
		$data = $this->__getTag($keyname);
		$this->paginate['Tagged']['context'] = 'show';
		//$this->action='all';
		$this->all();
	}
	
	
	
	function search(){
//		$this->Prg->commonProcess();

		// paginate 
		$paginateModel = 'Tagged';
		$Model = isset($this->{$paginateModel}) ? $this->{$paginateModel} : ClassRegistry::init('Tagged');
		$this->{$paginateModel} = $Model;	// add model to controller for paginate()
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $this->paginate[$paginateModel];
			
		if (!empty($this->passedArgs['filter'])) {
			// filter tagCloud by Model
			$paginateArray['conditions'] = array('Tagged.model'=>$this->passedArgs['filter']);
		}		
		$paginateArray['conditions'] = @$Model->Tag->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		
		// additional options, 
		// TODO: move to $options['extras']['context']
		$options=array();
		$xhrFrom = Configure::read('controller.xhrFrom');
		if (!$xhrFrom) {
			$options['context'] = 'skip';		// skip/ignore context when compiling list of tags
		}
		$options['operation'] = 'cloud';
		$paginateArray = array_merge($paginateArray, $options);		
		
		if ($this->action !== 'all') {
			// force perpage set from feeds
			Configure::write('passedArgs.perpage',$this->paginate[$paginateModel]['limit']);	// Configure::read('feeds.paginate.perpage')
			$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		} else {
			$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray, 'trends');
		}			
		// paginate Tagged	
		$data['cloud'] = $this->paginate($paginateModel);		
		// end paginate			
		
		
		$this->set('cloudTags', $data['cloud']);  // tagCloud
		$this->set('isPreview',0);				
		$done = $this->renderXHRByRequest(null, '/elements/tags/tagCloud');
		if ($done) return;	// stop for JSON/XHR requests, $this->autoRender==false	
		$this->action = 'all';
	}	
	
	/**
	 * Show - shows tagCloud
	 * - called via XHR to show tagCloud for a given Taggable
	 * - example: $xhrSrc = Router::url(array('plugin'=>'', 'controller'=>'tags','action'=>'show', '?' => array( 'from'=>), 'filter'=>'Group'));
	 */
	function show(){

		// paginate 
		$paginateModel = 'Tagged';
		$Model = isset($this->{$paginateModel}) ? $this->{$paginateModel} : ClassRegistry::init('Tagged');
		$this->{$paginateModel} = $Model;	// add model to controller for paginate()
		$Model->Behaviors->attach('Pageable');
		
		$paginateArray = $this->paginate[$paginateModel];
		if (!empty($this->passedArgs['filter'])) {
			// filter tagCloud by Model
			$paginateArray['conditions'] = array('Tagged.model'=>$this->passedArgs['filter']);
		}		
		$paginateArray['conditions'] = @$Model->Tag->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		
		// additional options, 
		// TODO: move to $options['extras']['context']
		$options=array();
		$xhrFrom = Configure::read('controller.xhrFrom');
		if (!$xhrFrom) {
			$options['context'] = 'skip';		// skip/ignore context when compiling list of tags
		}
		$options['operation'] = 'cloud';
		$paginateArray = array_merge($paginateArray, $options);		
		
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		// paginate Tagged	
		$data['cloud'] = $this->paginate($paginateModel);		
		// end paginate
				
		$this->set('cloudTags', $data['cloud']);  // tagCloud
		if ($this->action == 'show_more') {
			// deprecate show_more, use ?preview=0|1, test paging		
			// from parent action=trends
			if (isset($this->passedArgs['page'])) {
				// XHR paging loads paging-inner only
				$xhrView = '/elements/tags/tagCloud'; 
			} else {
				// first XHR page load includes headers, etc.
				$xhrView = '/elements/tags/paging-tags'; 
			}
		} else {
			// action = "show", XHR load from div#tags-preview-xhr
			$isPreview = $this->params['url']['preview'];
			$this->set(compact('isPreview'));
			$xhrView = '/elements/tags/tagCloud'; 
			// $xhrView = '/elements/tags/preview';
		}
		$done = $this->renderXHRByRequest(null, $xhrView, null, 0);
		if ($done) return;
		$this->render('all');

	}	
	/*
	 * 
	 * deprecate. use &preview=0&gallery=1
	 * show a "big" page. 
	 * - used for showing cloud from /[users|groups|tags]/trends
	 */
	function show_more() {
		$paginateModel = 'Tagged';	
		$this->paginate[$paginateModel]['preview_limit'] = $this->getPerpageLimit($paginateModel, 'trends');
		$this->show();
	}
	
	/**
	 * __getTag
	 * 
	 *  get tag string from URL, 
	 *  - checks /by:[tagString] if keyname is null
	 */
	private function __getTag($keyname = null) {
		/*
		 * extract keyname from named params
		 */
		if (empty($keyname) && isset($this->passedArgs['by'])) {
			$this->passedArgs[0] = $this->passedArgs['by'];
			unset($this->passedArgs['by']);
			unset($this->passedArgs['comment_view_type']);
			// convert named param to passedArgs[0]
			$this->redirect($this->passedArgs, null,  true);
		}
		
		
		/*
		 * confirm tag is valid
		 */
		try {
			return $this->Tag->view($keyname);
		} catch (Exception $e) {
			$this->Session->setFlash($e->getMessage());
			$redirect = env('HTTP_REFERER') ? env('HTTP_REFERER') : '/welcome/home';
			$this->redirect($redirect);
		}	
	}

	public function home($keyname = null) {
		$this->layout = 'snappi';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';
		if (!$keyname) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'tag'));
			$this->redirectSafe();
		}		
		
		
		$data = $this->__getTag($keyname);
		$this->set('data', $data);
	}	
	
	function photos($keyname = null){
		$this->layout = 'snappi';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';
		if (!$keyname) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'tag'));
			$this->redirectSafe();
		}		
		
		$data = $this->__getTag($keyname);
		$isPreview = (!empty($this->params['url']['preview']));

		// paginate 
		$paginateModel = 'Asset';
		$Model = isset($this->{$paginateModel}) ? $this->{$paginateModel} : ClassRegistry::init('Asset');
		$this->{$paginateModel} = $Model;	// add model to controller for paginate()
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $Model->getPaginatePhotosByTagId($keyname, $this->paginate[$paginateModel]);
			
		if ($isPreview) 1;
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate		
		$this->CastingCall = loadComponent('CastingCall', $this);
		$castingCall = $this->CastingCall->getCastingCall($pageData);
		$this->viewVars['jsonData']['castingCall'] = $castingCall;
		$done = $this->renderXHRByRequest('json', '/elements/photo/roll', null, 0);	
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false	
		
		$taggable = $paginateModel;
		$data[$taggable] = $pageData;
		$this->set(compact('data','keyname', 'isPreview'));
		
		if (@empty($this->params['paging'][$paginateModel]['count'])) {
			/*
			 * handle no tags, no permission to view record
			 */
			$this->Session->setFlash("There are no photos tagged {$keyname}.");
		}
		if ($this->RequestHandler->ext == 'json') {
			$this->set(array('assets'=>$data,'class'=>'Tagged'));	// ???
		}
	}
	
	function stories($keyname = null){
		$this->layout = 'snappi';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';
		if (!$keyname) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'tag'));
			$this->redirectSafe();
		}	
				
		$data = $this->__getTag($keyname);
			
		// paginate 
		$paginateModel = 'Collection';
		$Model = isset($this->{$paginateModel}) ? $this->{$paginateModel} : ClassRegistry::init('Collection');
		$this->{$paginateModel} = $Model;	// add model to controller for paginate()
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $Model->getPaginateCollectionsByTagId($keyname, $this->paginate[$paginateModel]);
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate	
		$this->viewVars['jsonData'][$paginateModel] = $pageData;
		$done = $this->renderXHRByRequest('json', '/elements/collections/roll');
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false	
						
		$taggable = $paginateModel;
		$data[$taggable] = $pageData;
		$this->set(compact('data','keyname'));
				
		if (@empty($this->params['paging'][$paginateModel]['count'])) {
			/*
			 * handle no tags, no permission to view record
			 */
			$this->Session->setFlash("There are no Stories tagged {$keyname}.");
		}
	}

	function groups($keyname = null){
		$this->layout = 'snappi';
		if (!empty($this->params['named']['wide'])) $this->layout .= '-wide';
		if (!$keyname) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'tag'));
			$this->redirectSafe();
		}	
				
		$data = $this->__getTag($keyname);
			
		// paginate 
		$paginateModel = 'Group';
		$Model = isset($this->{$paginateModel}) ? $this->{$paginateModel} : ClassRegistry::init('Group');
		$this->{$paginateModel} = $Model;	// add model to controller for paginate()
		$Model->Behaviors->attach('Pageable');
		$paginateArray = $Model->getPaginateGroupsByTagId($keyname, $this->paginate[$paginateModel]);
		$paginateArray['conditions'] = @$Model->appendFilterConditions(Configure::read('passedArgs.complete'), $paginateArray['conditions']);
		$this->paginate[$paginateModel] = $Model->getPageablePaginateArray($this, $paginateArray);
		$pageData = Set::extract($this->paginate($paginateModel), "{n}.{$paginateModel}");
		// end paginate	
		$this->viewVars['jsonData'][$paginateModel] = $pageData;
		$done = $this->renderXHRByRequest('json', '/elements/group/roll');
		if ($done) return; // stop for JSON/XHR requests, $this->autoRender==false	
						
		$taggable = $paginateModel;
		$data[$taggable] = $pageData;
		$this->set(compact('data','keyname'));
				
		if (@empty($this->params['paging'][$paginateModel]['count'])) {
			/*
			 * handle no tags, no permission to view record
			 */
			$this->Session->setFlash("There are no Groups tagged {$keyname}.");
		}
	}


	function events($keyname = null){
		$LIMIT = isset($this->passedArgs['perpage']) ? array('limit'=> $this->passedArgs['perpage']) : null;
		$this->__getTaggable($keyname, 'Event', $LIMIT);
		if (empty($data['Group'])) {
			/*
			 * handle no tags, no permission to view record
			 */
			$this->Session->setFlash("There are no Events tagged {$keyname}.");
		}
	}		

	function discussion($id) {
		if (!$id) {
			$this->Session->setFlash(sprintf(__('Invalid %s', true), 'group'));
			$this->redirectSafe();
		}
		if (isset($this->params['url']['view'])) {
			$this->Session->write("comments.viewType.discussion", $this->params['url']['view']);
			$this->redirect($this->here, null, true);
		}
		$BIG_LIMIT = 10;
		$this->Tag->contain('Comment');
		$options = array('conditions'=>array('Tag.keyname'=>$id));
		$data = $this->Tag->find('first', $options);
		$this->set('data', $data);
		
		//TODO: deprecate fragment		
		$from = @ifed($this->passedArgs['f'],$this->action);
		if ($from=='discussion') $this->paginate['Comment']['limit'] = $BIG_LIMIT;
		
		if (0 || $this->RequestHandler->isAjax()) {
			if ($this->autoRender === false) return;
			// fragment will set $this->autoRender == false and control manually
			//					Configure::write('debug', 0);
			$this->render('/elements/comments/discussion','ajax');
			return;
		}
	}
	// INCOMPLETE, COPIED FROM GROUP
	function __setRandomCoverPhoto($id = null){
		$wwwroot = Configure::read('path.wwwroot');
		$srcs = Set::extract('/Asset/src_thumbnail', $asset);
		shuffle($srcs);
		Stagehand::getSrc($row['Group']['src_thumbnail'], '');
	}
	function __setCounts($keyname= null){
		if ($keyname) {
			$where = "WHERE `Tag`.keyname = '{$keyname}'";
		} else $where='';	
		$sql = "SELECT `Tag`.id, `Tag`.name, count(t.id) as tagged_count
FROM tagged t
JOIN tags as `Tag` ON Tag.id = t.tag_id
{$where}
GROUP BY Tag.id, Tag.name;";
		
		$data = $this->Tag->query($sql);
		// $gids = Set::extract('/Group/id', $data);
		foreach ($data as $row) {
			$this->Tag->id = $row['Tag']['id'];
			$this->Tag->saveField('tagged_count', $row[0]['tagged_count']);
		}		
		return;
	}
	function update_count($keyname=null){
		if (!Permissionable::isRoot() && $keyname===null) {
			echo "Root permission required.";
			exit;
		}
		$this->autoRender = false;
		// $this->__setRandomCoverPhoto($id);
		$this->__setCounts($keyname);
		if ($keyname) $this->redirect(array('action'=>'home', $keyname));
		else $this->redirect(array('action'=>'all'));
	}		
}
