<?php 
class PageableBehavior extends ModelBehavior {
	
/**
 * Settings array
 *
 * @var array
 */
	
// *	TODO:  refactor, use 'limit' and 'preview', use behavior settings for keys
	public $settings = array(

	);	
	
/**
 * controller reference
 *
 * @var Controller
 */
	public $controller = null;
	
/**
 * named params array, defaults to Configure::read('passedArgs.complete');
 *
 * @var Controller
 */
	public $named = null;	
	
/**
 * Default settings
 *
 * @var array
 */
	protected $_defaults = array(
		'preview_limit' => 'preview_limit',
		'paging_limit' => 'paging_limit',	
		'use_configure_passedArgs' => true,
	);	
	

/**
 * Setup
 *
 * @param AppModel $Model
 * @param array $settings
 */
	public function setup(Model $Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = $this->_defaults;
		}
		if (!empty($settings['controller'])) $this->controller = $this->settings['controller'];
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], $settings);
	}


	
/**
 * Gets properly constructed $controller->paginate[$Model->name] array 
 * from the following inputs, in order of priority
 * 	1) named params, including /page:/perpage:/sort:/direction: and filter conditions
 * 	2) user profile overrides
 *		check user profile, Session::read("profile.{$action}")
 *	3) querystring params, including &preview for preview perpage limit
 * 	4) Controller->paginate array 
 *		check $controller->paginate[$Model->name]['paging_limit'] or ['preview_limit']
 * 
 * to Override final values, change in Configure::write("paginate.Options.{$pageableAlias}");
 *  TODO:  refactor, use 'limit' and 'preview' in settings
 *  
 *  saves total count in $controller->params['paging']['total'][$Model->name]
 *  
 * @param AppModel $Model
 * @param Controller $controller originating controller
 * @param array $paginateArray, from $controller->paginate[$Model->name]
 * @param string action, for Session::read("profile.{$action}.perpage")
 * @return array $controller->paginate[$Model->name]
 */	
	public function getPageablePaginateArray(Model $Model, $controller = null, $paginateArray = null, $action = null) {
		if ($controller !== null) $this->controller = $controller;
		if ($paginateArray === null) $paginateArray = $this->controller->paginate[$Model->name];
		
		$this->named = (!empty($this->settings['use_configure_passedArgs'])) 
			? Configure::read('passedArgs.complete') 
			: $controller->params['named'];		
		
		
		$pageableAlias = isset($paginateArray['PageableAlias']) ? $paginateArray['PageableAlias'] : $Model->alias; 
		Configure::write("paginate.Model", $pageableAlias);
		
		// move appendFilterConditions outside pageable behavior
//		$paginateArray['conditions'] = @$Model->appendFilterConditions($this->named, $paginateArray['conditions']);
		/*
		 * get paginate sort options, /sort:/direction:
		 */ 
		$paginateArray = $this->mergePaginateOptions($Model, $paginateArray, $action);
		Configure::write("paginate.Options.{$pageableAlias}", $paginateArray);
		$this->controller->paginate[$Model->name] = $paginateArray;
		return $paginateArray;
	}
	
/**
 * merge paginate options, including:
 * 	- name: /page:/perpage:, plus any filters passed as named params
 * 		merges /perpage: -> paginateArray['limit']
 * 	- sort: /sort:/direction:
 * 
 * merge options from passedArgs > $options > default, passes all additional fields in paginateArray 
 * uses Configure::read('passedArgs.complete') which is written in AppController::beforeFilter() somewhere
 * 
 * @param aa $options - options, most commonly from Controller->paginate[$Model->name]
 * @param string $alias - model alias
 * @return array $controller->paginate[Model->name] for use by $controller->paginate($Model->name)
 */
	public function mergePaginateOptions(Model $Model, $paginateArray, $action = null) {
		$default = array_fill_keys(array('fields', 'order', 'limit', 'page', 'sort', 'direction', 'conditions'),'');
		$default['page'] = 1;
		
		$passedArgs = Configure::read('passedArgs.complete');
		$paginateArray = $passedArgs + $paginateArray + $default;		// left to right copy
		if (!empty($paginateArray['perpage'])) {
			$paginateArray['limit'] = $paginateArray['perpage'];
		} else {
			$paginateArray['limit'] = $this->getPerpageLimit($Model, $action, false);	
		}
		if (!empty($paginateArray['sort'])) {
			$paginateArray['order'] = $this->getSqlOrderFromOptions($Model, $paginateArray);
		}
		return $paginateArray;	
	}	
	
/**
 * get perpage limits from the following, in order of priority 
 *		(optional) check for explicit named param in passedArgs, /perpage:
 *		check user profile, Session::read("profile.{$action}.perpage")
 *		check querystring &preview=1 for
 *		check $controller->paginate[$Model->name]['paging_limit'] or ['preview_limit']
 * @param $Model
 * @param string action, lookup key in profile array
 * @param boolean $checkPerpage, check Configure::read('passedArgs.perpage') 
 * @return int
 */
	public function getPerpageLimit(Model $Model, $action = null, $checkPerpage = true) {
		if ($action===null) $action = $this->controller->action;
		if ($checkPerpage) {
			$limit = Configure::read('passedArgs.perpage');
		} else {
			$perpageProfileData = Session::read("profile.{$action}.perpage");
			if($perpageProfileData){
				$limit = $perpageProfileData;
			} else if (empty($this->controller->params['url']['preview']) ) {
				// get a full page, NOT a preview page
				$limit = $this->controller->paginate[$Model->alias][$this->settings[$Model->alias]['paging_limit']];
			} else {
				// preview page
				$limit = $this->controller->paginate[$Model->alias][$this->settings[$Model->alias]['preview_limit']];
			}		
		}
		return $limit;
	}
		

/**
 * used by paginateCount
 * 
 * get SQL order by from cakephp /sort:/direction: directives
 * 	allows sorting by derived columns using pattern: "0.{column alias}"
 * @param $paginateArray $paginateArray
 * @return string to be used by $paginateArray['order']
 */
	public function getSqlOrderFromOptions(Model $Model, $paginateArray) {
		$order = $direction = $sort = null;
		if (!empty($paginateArray['sort'])) {
			$sort = $paginateArray['sort'];
			if (!empty($paginateArray['direction'])) $direction = $paginateArray['direction'];
			if (strpos($sort,'.') > 0) { 
				// model name prefixed to /sort:
				if (strpos($sort,'0.') === 0) {
					// '0.' marker indicates sort on derived column, strip marker from order clause
					$sort = substr($sort,2);  
				}
				$order = array("{$sort}" => "{$direction}");
			} else {
				// model name NOT prefixed to /sort:, use default
				$order = array("`{$Model->alias}`.{$sort}" => "{$direction}");
			}
		}
		return $order; 	
	}

/**
 * add backticks to Alias names to correct SQL Syntax in $fields
 * @params string $paginateOptions['fields']
 */	
	protected function backtickAlias($fields) {
		return preg_replace('/\b(\w+)\./i', '`${1}`.',$fields);
	}	
	
/**
 * beforeFind Callback
 *
 * @param AppModel $Model 
 * @param array $queryData 
 * @return array
 */
	public function beforeFind(Model $Model, $queryData) {
//		extract($this->settings[$Model->alias]);
		return $queryData;
	}	
	
/**
 * afterFind Callback
 *
 * @param AppModel $Model 
 * @param array $results 
 * @param boolean $primary 
 * @return array
 */
	public function afterFind(Model $Model, $results, $primary) {
//		extract($this->settings[$Model->alias]);
//		$this->controller->params['paging']['total'][$Model->name] = $this->controller->params['paging'][$Model->name]['count'];
		return $results;
	}	
	
}
?>