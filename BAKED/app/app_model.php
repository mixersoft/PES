<?php

class AppModel extends Model {
	
//	public $useDbConfig = 'baked';  // uses mysqli driver  
	
	public $actsAs = array(
		'Containable'
	);
	public $recursive = -1;
	
	/**
	 * @deprecated. keep here for reference
	 * Add permissions inner join to SQL query to enforce record level ACLS
	 *
	 * @param object $Model reference to Model
	 * @param string $assoc_alias association alias, if not Model->name 
	 */
	public function getReadPermJoinAsSQL(&$Model, $assoc_alias=null) {
		$assoc_alias = empty($assoc_alias) ? $Model->name : $assoc_alias; 
		if (!$this->Behaviors->attached('Permissionable')) return '';
		
		$alias = $this->Behaviors->Permissionable->getPermissionAlias($this);
		$gids = is_array(Permissionable::getGroupIds()) ? implode("','",Permissionable::getGroupIds()) : 0;
		$oid = Permissionable::getUserId();
		
		$sql ="INNER JOIN permissions AS `{$alias}` ON 
	(`{$alias}`.`model` = '{$Model->name}' AND `{$alias}`.`foreignId` = `{$assoc_alias}`.`id` 
	AND (
		(`{$alias}`.`perms` & 512 <> 0) 
		OR (((`{$alias}`.`perms` & 64 <> 0) AND (`{$alias}`.`foreignId` IN (
			SELECT DISTINCT `_ag`.`asset_id` FROM `assets_groups` AS `_ag` WHERE `_ag`.`group_id` IN ('{$gids}')
			)))) 
		OR (((`{$alias}`.`perms` & 8 <> 0) AND (`{$alias}`.`gid` IN ('{$gids}')))) 
		OR (((`{$alias}`.`perms` & 1 <> 0) AND (`{$alias}`.`oid` = '{$oid}')))
		)
	)";
		return $sql;
	}
	
	
	/**
	 * @deprecated, moved to pageable behavior
	 * get paginate options 
	 * merge options from $options > passedArgs > default
	 * uses Configure::read('passedArgs.complete') which is written in AppController::beforeFilter() somewhere
	 * @param aa $options - options, most commonly from Controller->paginate[$paginateModel]
	 * @param string $alias - model alias
	 */
	public function getPaginateOptions($options, $alias=null ) {
		$model = $alias ? $alias : $this->alias;
		$default = array_fill_keys(array('fields', 'order', 'limit', 'page', 'sort', 'direction', 'conditions'),'');
		$default['page'] = 1;
		$passedArgs = Configure::read('passedArgs.complete');
		$mergedOptions = $options + $passedArgs + $default;		// left to right copy
		if (!empty($mergedOptions['perpage'])) $mergedOptions['limit'] = $mergedOptions['perpage'];	
		return $mergedOptions;	
	}		

	/*************************************************************************************
	 * 
	 * override paginateCount/paginate methods to use SQL_CALC_FOUND_ROWS 
	 * - works with MySQL, 
	 *  - NOTE: make sure queries with outer joins return the correct count 
	 *  
	 *************************************************************************************/
	/**
	 * static attribute for storing paginate results between paginateCount() and paginate()
	 * @var aa 
	 */
	public static $cached_results_from_paginateCount = array();	
	
	/**
	 * list models where we use SQL_CALC_FOUND_ROWS 
	 */
	public $USE_FOUND_ROWS = true;	// turn on/off SQL_CALC_FOUND_ROWS query
	public $use_FOUND_ROWS_whitelist = array('Asset', 'Collection', 'User', 'Group', 'Tagged');
	

	/**
	 * Override paginateCount method, to eliminate double query
	 * use $extras['paginateCacheKey'] to save cached results under different key  
	 * NOTE: 
	 * 	- USE $Controller->paginate[$this->alias][paginateCacheKey]='CacheKey' to save 
	 * 		$cached_results_from_paginateCount under a different key than $this->alias
	 * - WARNING: $controller->paginate() will overwrite counts in $this->params['paging'][$this->alias]
	 * 		change $this->alias to avoid this problem. attach association on the fly
	 * 		BUT, make sure you update Configure::write('paginate.Model', );
	 * 		See: MyController::___getExpressUploads() for example
	 */
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		$paginateCacheKey = isset($extra['paginateCacheKey']) ? $extra['paginateCacheKey'] : $this->alias;
		if (false == in_array($this->name, $this->use_FOUND_ROWS_whitelist)) {
			// cancel paginate/paginateCount override
			return  $this->find('count', compact('conditions', 'recursive','extra'));
		}
		
		$paginateOptions = Configure::read('paginate.Options.'.$this->alias);
		// does $conditions == $paginateOptions['conditions'] ??
		if ($conditions != $paginateOptions['conditions']) {
			$paginateOptions['conditions'] = @mergeAsArray($paginateOptions['conditions'], $conditions);
		}
		if (!isset($paginateOptions['recursive']) || $recursive !==0) {
			$paginateOptions['recursive'] = $recursive;
		}	
		if ($paginateOptions['sort']) $paginateOptions['order']= @mergeAsArray($this->getSqlOrderFromOptions($paginateOptions),$paginateOptions['order']);
		if ($paginateOptions['fields']) $paginateOptions['fields'] = $this->backtickAlias($paginateOptions['fields']);
		
		/*
		 * turn on/off SQL_CALC_FOUND_ROWS query
		 */
		$firstField = is_array($paginateOptions['fields']) ? $paginateOptions['fields'][0] : $paginateOptions['fields'];		
		if ($this->USE_FOUND_ROWS && (strpos($firstField, 'DISTINCT') === false)) {
			$paginateOptions['fields'] = $this->prepareFoundRows($paginateOptions['fields']);
		} else {
			// $USE_FOUND_ROWS will not work with cake 1.3.6 with DISTINCT field. extra `` problem 
			// change field to COUNT(`[alias]`.id)
			$data = $this->find('all', $paginateOptions);	
			AppModel::$cached_results_from_paginateCount[$paginateCacheKey] = & $data;
			
			if (strpos($firstField, '.*')) $paginateOptions['fields'] = str_replace('.*', '.id', $paginateOptions['fields']);
			return  $this->find('count', $paginateOptions);
		}
	
		$current_operation = isset($paginateOptions['operation']) ? $paginateOptions['operation'] : 'all';
		switch ($current_operation) {
			case 'all':
				$data = $this->find('all', $paginateOptions);
				break;
			case 'cloud':
				$paginateOptions['model'] = 'Tag';
				// NOTE: the query is run by Model and results returned to _findCloud('after')
				// and the count is returned in _findCloud('after')
			
				$data = $this->find('cloud', $paginateOptions);  // $Tagged->_findTagged()			
				break;
			case 'tagged':
				// NOTE: the paginate context query is RUN in beforeFind() ->  getPermissionableQueryData()
				// and the count is returned in _findTagged('after')
				$data = $this->find('tagged', $paginateOptions);  // $Tagged->_findTagged()
				break;
			default:
				$this->log("ERROR: paginateCount find() not supported for operation: {$current_operation}", LOG_DEBUG);
				$data = array();
				break;
		}
		// save Query results for paginate()
		AppModel::$cached_results_from_paginateCount[$paginateCacheKey] = & $data;	
		$count = $this->getFoundRows();
		return $count;
	}
	/**
	 * Overridden paginate method 
	 */	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		$paginateCacheKey = isset($extra['paginateCacheKey']) ? $extra['paginateCacheKey'] : $this->alias;
		$options = array_merge(compact('conditions', 'fields', 'order', 'limit', 'page', 'recursive'), $extra);
		if (false == in_array($this->name, $this->use_FOUND_ROWS_whitelist)) {
			// cancel override
			return $this->find('all', $options);
		}		
		/*
		 * check for cached results from paginateCount()
		 */
		return isset(AppModel::$cached_results_from_paginateCount[$paginateCacheKey])  
			? AppModel::$cached_results_from_paginateCount[$paginateCacheKey]  
			: $this->find('all', $options);
	}		
	
	protected function getFoundRows ($comment=null) {
		// get total count from find
		// make sure 'SELECT FOUND_ROWS();' is not cached by adding a timestamp, comment
		$time = time();
		if ($comment) $comment = ':'.$comment;
		$sqlComment = sprintf("/* paginateCount for {$this->alias}%s @{$time} */", $comment);
		$found_rows =  $this->query("{$sqlComment} SELECT FOUND_ROWS();");
		$count = array_shift($found_rows[0][0]);
		return $count;		
	}	
	
	/*
	 * add backticks to Alias names to correct SQL Syntax in $fields
	 */
	protected function backtickAlias($fields) {
		return preg_replace('/\b(\w+)\./i', '`${1}`.',$fields);
	}
	
	protected function appendSearchConditions($options, $seachKeys, $simpleSearch = true) {
		$conditions = array();
		if (!empty($options['q']) && $this->Behaviors->attached('Searchable')) {
			$searchString = trim($options['q']);
			if ($simpleSearch) {
				// search all fields for same seachString
				foreach($seachKeys as $key) {
					$searchData[$key] = $searchString;	
				}
				//	conditions from Search plugin
				$conditions = array('OR'=>$this->parseCriteria($searchData));
//				Session::setFlash("Search results for: {$searchString}");
			}
		}
		return $conditions;				
	}
	
	/**
	 * @deprecated moved to pageable behavior
	 * cascading options for paginate
	 */
	
	protected function XXXgetSqlOrderFromOptions($options) {
		$order = $direction = $sort = null;
		if (!empty($options['sort'])) {
			$sort = $options['sort'];
			if (!empty($options['direction'])) $direction = $options['direction'];
			if (strpos($sort,'.') > 0) { 
				// '0.' prefix indicates sort on derived column, do not prefix model name
				if (strpos($sort,'0.') === 0) {
					$sort = substr($sort,2);  
				}
				$order = array("{$sort}" => "{$direction}");
				
			} else {
				$alias = !empty($options['model']) ? $options['model'] : $this->alias;
				$order = array("`{$alias}`.{$sort}" => "{$direction}");
			}
		}
		return $order; 	
	}

	protected function prepareFoundRows ($fields) {
		/*******************************************************************************
		 * build SQL stmt and run here
		 * - add SQL_CALC_FOUND_ROWS
		 * - NOTE: latest build of cakephp 1.3.6 puts quotes around `SQL_CALC_FOUND_ROWS Asset.*` 
		 * 			so we need this workaround
		 */		
		$fields = @mergeAsArray(null, $fields);
		if (empty($fields[0])) {
			$fields = array("SQL_CALC_FOUND_ROWS (null)", "{$this->alias}.*");
		} else {
			array_unshift($fields, 'SQL_CALC_FOUND_ROWS (null)');
		}
		return $fields;
		
		
		/*******************************************************************************
		 * DEPRECATE
		 */
//		if (is_array($fields) && !@empty($fields[0])) {
//			$fields[0] = "SQL_CALC_FOUND_ROWS ".$fields[0];
//		} else {
//			$fields = !empty($fields) ? "SQL_CALC_FOUND_ROWS {$fields}" : "SQL_CALC_FOUND_ROWS  `{$this->alias}`.*";
//			$fields = array($fields);
//		}
		return $fields;
	}	
	
	public function sql2queryData($sql){
		return $this->getDataSource()->expression($sql);
	}

	
	public function queryData2Sql(& $Model, $queryData){
		// TODO: find the method to parse a query in $dbo
		$q = array_merge(array('table'=>$Model->useTable, 'alias'=>$Model->alias), $queryData);
		$sql = $this->getDataSource()->buildStatement($q, $Model);
		return $sql;
	}
	
}
?>