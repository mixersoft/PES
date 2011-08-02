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
 * Tagged model
 *
 * @package tags
 * @subpackage tags.models
 */
class Tagged extends TagsAppModel {

/**
 * Name
 *
 * @var string
 */
	public $name = 'Tagged';

/**
 * Table that is used
 *
 * @var string
 */
	public $useTable = 'tagged';

/**
 * Find methodes
 *
 * @var array
 */
	public $_findMethods = array(
		'cloud' => true,
		'tagged' => true);

	public $recursive = 0;	// don't know why I cannot set this to -1 and use $this->contain()
	
/**
 * belongsTo associations
 *
 * @var string
 */
	public $belongsTo = array(
		'Tag' => array(
			'className' => 'Tags.Tag',
			'counterCache' => true
		),
	);

/**
 * Returns a tag cloud
 *
 * The result contains a "weight" field which has a normalized size of the tag
 * occurrence set. The min and max size can be set by passing 'minSize" and
 * 'maxSize' to the query. This value can be used in the view to controll the
 * size of the tag font.
 *
 * @todo Ideas to improve this are welcome
 * @param string
 * @param array
 * @param array
 * @return array
 */
	public function _findCloud($state, $query, $results = array()) {
		if ($state == 'before') {
			$options = array(
				'minSize' => 10,
				'maxSize' => 20,
				'page' => null,
				'limit' => null,
				'order' => "occurrence DESC",  	// snappi:
				'joins' => null,
				'offset' => null,
				'contain' => 'Tag',
				'conditions' => array(),
				'fields' => 'SQL_CALC_FOUND_ROWS Tag.*, Tagged.tag_id, COUNT(Tagged.tag_id) AS occurrence',
				'context'=> 'show',				// snappi: get context
				'group' => 'Tagged.tag_id');
			
			foreach ($query as $key => $value) {
				if ($key=='fields') continue;		// force default fields
				if (!empty($value)) {
					$options[$key] = $value;
				}
			}
			
			/*
			 * add cloud conditions
			 */
			if ($options['context'] !== 'skip') {
				// add context, except when $options['context']=='skip' for /tags/all
				$options['conditions'] = @mergeAsArray($options['conditions'], $this->getCloudConditions($options));
			}
			return $options;
			
			
		} elseif ($state == 'after') {
			if (!empty($results) && isset($results[0][0]['occurrence'])) {
				$weights = Set::extract($results, '{n}.0.occurrence');
				$maxWeight = max($weights);
				$minWeight = min($weights);

				$spread = $maxWeight - $minWeight;
				if (0 == $spread) {
					$spread = 1;
				}

				foreach ($results as $key => $result) {
					$size = $query['minSize'] + (($result[0]['occurrence'] - $minWeight) * (($query['maxSize'] - $query['minSize']) / ($spread)));
					$results[$key]['Tag']['occurrence'] = $result[0]['occurrence'];
					$results[$key]['Tag']['weight'] = ceil($size);
				}
				
			}
			return $results;
		}
	}

	
	/*
	 * -- default tag cloud
	 SELECT Tag.*, Tagged.tag_id, COUNT(*) AS occurrence, `Tag`.`id`
	 FROM `tagged` AS `Tagged`
	 LEFT JOIN `tags` AS `Tag` ON (`Tagged`.`tag_id` = `Tag`.`id`)
	 -- WHERE `Tagged`.`model` = 'Asset'
	 GROUP BY `Tagged`.`tag_id` LIMIT 10
	 */
	function getCloudConditions($query){
		// get Conditions for current page (XHR parent)
		$xhrFrom = Configure::read('controller.xhrFrom');
		if ($xhrFrom) {
			// filter tagCloud by Model
			$keyName = $xhrFrom['keyName']; $uuid = $xhrFrom['uuid'];
			$model = Configure::read("lookup.xfr.{$keyName}.Model");
			$getConditionsFor = array($model=>$uuid);
		} else {
			$getConditionsFor = array();
			$model = $query['model'];
		}
		// add Conditions for context, skip if keyName == contextClass
		$context = Session::read('lookup.context');
		if ( Configure::read('controller.alias') != 'all' && $context['uuid'] ){
			$getConditionsFor[ Configure::read("lookup.xfr.{$context['keyName']}.Model")]=$context['uuid'];
		}
		$contextCloudConditions = null;
//debug($getConditionsFor);			
		foreach ($getConditionsFor as $model=>$uuid) {
			if ($model=='*') {
				// tags for all Models
				// WARNING: this doesn't seem to work
				$contextCloudConditions[] = null;
				continue;
			}
			if (empty($uuid)) {
				// tags for given model, context = all
//				$contextCloudConditions[] =  array('Tagged.model' => $model);
				$contextCloudConditions[] =  null;
				continue;
			}
			switch($model){
				case 'User':
					/*
					 * -- tag cloud for user=12345678-1111-0000-0000-sardinia----
					 where 1=1
					 and (
					 tagged.foreign_key in (select ag.group_id from groups_users ag where ag.user_id = '12345678-1111-0000-0000-sardinia----')
					 or tagged.foreign_key in (select a.id from assets a where a.owner_id = '12345678-1111-0000-0000-sardinia----')
					 )
					 */
					$group_SubSelect = "select ag.group_id from groups_users ag where ag.user_id = '{$uuid}'";
					$asset_SubSelect = "select a.id from assets a where a.owner_id = '{$uuid}'";
					switch (Configure::read('passedArgs.filter')) {
						case 'Asset':
							// TODO: should this be all assets the user has permissions on?
							$cloudConditions = array( 
								$this->getDataSource()->expression("`Tagged`.`foreign_key` IN ({$asset_SubSelect})") 
							);							
							break;
						case 'Group':
							// TODO: should this be all groups the user has permissions on?
							$cloudConditions = array(
								$this->getDataSource()->expression("`Tagged`.`foreign_key` IN ({$group_SubSelect})"),
							);							
							break;
						default:
							$cloudConditions = array(
								'OR'=>array(
									$this->getDataSource()->expression("`Tagged`.`foreign_key` IN ({$group_SubSelect})"),
									$this->getDataSource()->expression("`Tagged`.`foreign_key` IN ({$asset_SubSelect})")
									)
							);							
							break;
					}

					break;
				case 'Group':
					/*
					 * -- tag cloud for group = member---0123-4567-89ab-000000000002
					 where 1=1
					 and (
	     tagged.foreign_key='member---0123-4567-89ab-000000000002'
	     or tagged.foreign_key in (select ag.asset_id from assets_groups ag where ag.group_id = 'member---0123-4567-89ab-000000000002')
	     )
					 */
					$asset_SubSelect = "select ag.asset_id from assets_groups ag where ag.group_id = '{$uuid}'";
					$cloudConditions = array('OR'=>array(
							"`Tagged`.`foreign_key`"=>$uuid,
					$this->getDataSource()->expression("`Tagged`.`foreign_key` IN ({$asset_SubSelect})")
					)
					);
					break;
				case 'Tag':
					/*
					 * -- tag cloud for tag=italy
					 where 1=1
					 and (
					 tagged.foreign_key in (
					 SELECT DISTINCT `fkTagged`.foreign_key FROM `tagged` AS `fkTagged` left JOIN `tags` AS `fkTag` ON (`fkTagged`.`tag_id` = `fkTag`.`id`)
					 where `fkTag`.`keyname` = 'italy'
					 )
					 )
					 */
					$tag_SubSelect = "SELECT DISTINCT `fkTagged`.foreign_key FROM `tagged` AS `fkTagged` INNER JOIN `tags` AS `fkTag` ON (`fkTagged`.`tag_id` = `fkTag`.`id`) where `fkTag`.`keyname` = '{$uuid}'";
					$cloudConditions = array(
						$this->getDataSource()->expression("`Tagged`.`foreign_key` IN ({$tag_SubSelect})")
					);
					break;
				case 'Asset':
					// do nothing
					$cloudConditions = array('Tagged.foreign_key' => $uuid);
					break;
				default:
					// this should not happen
					break;
			}
			$contextCloudConditions[] = $cloudConditions;
		}
		return $contextCloudConditions;
	}
	
	
	
/**
 * Find all the Model entries tagged with a given tag
 * 
 * The query must contain a Model name, and can contain a 'by' key with the Tag keyname to filter the results
 * <code>
 * $this->Article->Tagged->find('tagged', array(
 *		'by' => 'cakephp',
 *		'model' => 'Article'));
 * </code
 *
 * @TODO Find a way to populate the "magic" field Article.tags
 * @param string $state
 * @param array $query
 * @param array $results
 * @return mixed Query array if state is before, array of results or integer (count) if state is after
 */
 
 
 
 
 
/**
 * Find all the Model entries tagged with a given tag
 * 
 * 
 * NOTE(!):
 * @deprecated for Assets,Groups
 * USE Asset->getPaginatePhotosByTagId(), Group->getPaginateGroupsByTagId()
 * which joins to Tagged/Tag manually 
 * 
 * 
 * 
 * The query must contain a Model name, and can contain a 'by' key with the Tag keyname to filter the results
 * <code>
 * $this->Article->Tagged->find('tagged', array(
 *		'by' => 'cakephp',
 *		'model' => 'Article'));
 * </code
 *
 * @TODO Find a way to populate the "magic" field Article.tags
 * @param string $state
 * @param array $query
 * @param array $results
 * @return mixed Query array if state is before, array of results or integer (count) if state is after
 */
	public function _findTagged($state, $query, $results = array()) {
		if ($state == 'before') {
			if (isset($query['model']) && $Model = ClassRegistry::init($query['model'])) {
				$belongsTo = array(
					$Model->alias => array(
						'className' => $Model->name,
						'foreignKey' => 'foreign_key',
						'conditions' => array(
							$this->alias . '.model' => $Model->alias
						),
					)
				);
				$this->bindModel(compact('belongsTo'));

				if (isset($query['operation']) && $query['operation'] == 'count') {
					$query['fields'][] = "COUNT(DISTINCT $Model->alias.$Model->primaryKey)";
				} else {
					$query['fields'][] = "DISTINCT $Model->alias.*";
				}

				if (!empty($query['by'])) {
					$query['conditions'] = array(
						$this->Tag->alias . '.keyname' => $query['by']);
				}
			}
			return $query;
		} elseif ($state == 'after') {
			if (isset($query['operation']) && $query['operation'] == 'count') {
				return array_shift($results[0][0]);
			}
			return $results;
		}
	}
	
	
	/**************************************************************************
	 * Overridde PaginateCount/Paginate
	 *
	 */
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		return call_user_func_array('AppModel::paginateCount', func_get_args());
//		return AppModel::paginateCount($conditions, $recursive, $extra);
	}
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		return call_user_func_array('AppModel::paginate', func_get_args());
//		return AppModel::paginate($conditions, $fields, $order, $limit, $page, $recursive, $extra);
	}
	
}
