<?php
class Collection extends AppModel {
	public $name = 'Collection';
	public $displayField = 'src_thumbnail';
	
	// public $validate = array();

	/*
	 * CollectionPermissions
	 * uid = group owner
	 * gid = group id => user_id
	 * default permissions for Collections:
	 * perm = 519 (rwd/---/---/r--) - public listing,
	 * perm = 71 (rwd/---/r--/--) 	- members only
	 * perm = 7 (rwd/---/---/--) 	- private
	 */
	public $actsAs = array(
		'Tags.Taggable',
		'Search.Searchable',
		'Permissionable.Permissionable' => array(
			'defaultBits'	=> '0519', 			// public,    members only=71,  
			'userModel'		=> 'User',
			'groupModel'	=> 'Group',
		),
	);
	public $belongsTo = array(
		'Owner' => array(
			'className' => 'User',
			'foreignKey' => 'owner_id',
			'counterCache' => true,
			'type'=>'INNER',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);

	public $hasAndBelongsToMany = array(
		'Asset' => array(
			'with'=>'AssetsCollection'
		),
		'Group' => array(
			'with'=>'CollectionsGroup'
		),
		// 'Tag' => array('with'=> 'Tagged'),
	);
		
	
	/*
	 * Search plugin
	 */
	public $filterArgs = array(
		array('name' => 'caption', 'type' => 'like', 'field' => 'Collection.title'),
		array('name' => 'keyword', 'type' => 'like', 'field' => 'Collection.description'),
		array('name' => 'tags', 'type' => 'subquery', 'method' => 'findByTags', 'field' => 'Collection.id'),
	);

	public function findByTags($data = array()) {
		$this->Tagged->Behaviors->attach('Containable', array('autoFields' => false));
		$this->Tagged->Behaviors->attach('Search.Searchable');
		$query = $this->Tagged->getQuery('all', array(
			'conditions' => "`Tag`.name LIKE '%{$data['tags']}%'",
			'fields' => array('foreign_key'),
			'contain' => array('Tag')
		));
		return $query;
	}
	
	function appendFilterConditions($options, $conditions) {
		/*
		 * add filters, from $this->params['named']
		 */
		$filterConditions = array();
		if (isset($options['q'])) {
			// text search, 
			$searchKeys = array('title', 'description');
			$filterConditions[] = $this->appendSearchConditions($options, $searchKeys );
		}		
		return @mergeAsArray( $conditions, $filterConditions);
	}
	
	public function afterFind($results, $primary){
		// merge permissions
		try {
			$model = $this->alias;
			$permAlias = $this->getPermissionAlias();
			if ($primary && isset($results[0][$model])) {
				foreach ($results as $i => & $data) {
					if (isset($data[$permAlias]['perms'])) $data[$model]['perms'] = $data[$permAlias]['perms'];
					if (!empty($data[$model]['owner_id'])) $data[$model]['isOwner'] = $data[$model]['owner_id'] == AppController::$ownerid;
				}
			}
		} catch (Exception $e) {}	
		if ($primary && !Configure::read('controller.isXhr') && isset($results[0]['Collection']['owner_id'])){
			if ($results[0]['Collection']['id'] == Configure::read('controller.xhrFrom.uuid')) {
				// establish ownership of this particular Collection
				Configure::write('controller.isOwner', $results[0]['Collection']['owner_id'] == AppController::$ownerid);
			}
		}
		return $results;
	}

	function getPaginateCollections ( $paginate = array(), $skipContext = true) {
		$paginateModel = 'Collection';
//debug($paginateModel);	 
		$context = Session::read('lookup.context');
		$controller = Configure::read('controller.alias');
		
		$conditions = $joins = array();
		
		// no context for this method
		$skip = $skipContext;
		// add context
		if (!$skip) {
			if (in_array($context['keyName'], array('Me', 'Person'))) {
			}
			if (in_array($context['keyName'], array('Group','Event','Wedding'))) {
			}
			if ($context['keyName'] == 'Tag') {
			}
		}
		if (!empty($joins)) $paginate['joins'] = @mergeAsArray($paginate['joins'], $joins);
		if (!empty($conditions)) $paginate['conditions'] = @mergeAsArray($paginate['conditions'], $conditions);
		return $paginate;
	}				
	
	function getPaginateCollectionsByGroupId ($groupid , $paginate = array()) {
		// if (!$this->Group->hasPermission('read',$groupid) ){
			// // no read permission, likely non-member
			// $paginate = array('conditions'=>"1=0");
			// return $paginate;
		// }
//		ClassRegistry::init('Collection')->disablePermissionable();
		$paginateModel = 'Collection';
//debug($paginateModel);	 
		// refactor
		$context = Session::read('lookup.context');
		$controller = Configure::read('controller.alias');
		
		// add conditions for GroupId
		$conditions = $joins = array();
		// moved to joinWithShots()
		$joins[] = array(
			'table'=>'collections_groups',
			'alias'=>'CollectionsGroup',
			'type'=>'INNER',
			'conditions'=>array('`CollectionsGroup`.collection_id = `Collection`.id'),
		);
	
		$conditions[] = "CollectionsGroup.group_id='{$groupid}'";
		// check of context == controller
		$skip = $context['keyName'] == Configure::read('controller.label');
		// add context
		if (!$skip) {
			if (in_array($context['keyName'], array('Me', 'Person'))) {
				//groups/photos
				$conditions[] = "`Story`.owner_id='{$context['uuid']}'";
			}
			if (in_array($context['keyName'], array('Group','Event','Wedding'))) {
				// skip
			}
			if ($context['keyName'] == 'Tag') {
				$joins[] =	array(
							'table'=>'tagged',
							'alias'=>'Tagged',
							'type'=>'INNER',
							'conditions'=>array("`Tagged`.`foreign_key` = `Collection`.id AND `Tagged`.`model` = 'Collection'"),
					);
				$joins[] =	array(
							'table'=>'tags',
							'alias'=>'Tag',
							'type'=>'INNER',
							'conditions'=>array("`Tagged`.`tag_id` = `Tag`.id AND `Tag`.`keyname` = '{$context['uuid']}'"),
					);
			}
		}
		if (!empty($joins)) $paginate['joins'] = @mergeAsArray($paginate['joins'], $joins);
		if (!empty($conditions)) $paginate['conditions'] = @mergeAsArray($paginate['conditions'], $conditions);
		return $paginate;
	}			
	
	function getPaginateCollectionsByUserId ($userid , $paginate = array()) {
		$paginateModel = 'Collection';
//debug($paginateModel);			
		// add context, refactor
		$context = Session::read('lookup.context');
		$controller = Configure::read('controller.alias');
		
		// add conditions for UserId
		$conditions = $joins = array();
		$conditions[] = "Collection.owner_id='{$userid}'";
		
		// check of context == controller
		$skip = $context['keyName'] == Configure::read('controller.label');
		// add context
		if (!$skip) {
			if (in_array($context['keyName'], array('Me', 'Person'))) {
				// skip
			}
			if (in_array($context['keyName'], array('Group','Event','Wedding'))) {
				// Collection habtm Group
				$joins[] =  array(
						'table'=>'collections_groups',
						'alias'=>'CollectionsGroup',
						'type'=>'INNER',
						'conditions'=>array("CollectionsGroup.collection_id=`Collection`.id", "CollectionsGroup.group_id"=>$context['uuid']),
				);						
			}
			if ($context['keyName'] == 'Tag') {
				$joins[] = 	array(
							'table'=>'tagged',
							'alias'=>'Tagged',
							'type'=>'INNER',
							'conditions'=>array("`Tagged`.`foreign_key` = `Collection`.id AND `Tagged`.`model` = 'Collection'"),
					);
				$joins[] = 	array(
							'table'=>'tags',
							'alias'=>'Tag',
							'type'=>'INNER',
							'conditions'=>array("`Tagged`.`tag_id` = `Tag`.id AND `Tag`.`keyname` = '{$context['uuid']}'"),
					);
			}
		}
		if (!empty($joins)) $paginate['joins'] = @mergeAsArray($paginate['joins'], $joins);
		if (!empty($conditions)) $paginate['conditions'] = @mergeAsArray($paginate['conditions'], $conditions);
		return $paginate;
	}
	function getPaginateCollectionsByPhotoId ($assetid , $paginate = array()) {
		$paginateModel = 'Collection';
//debug($paginateModel);		
		// refactor
		$context = Session::read('lookup.context');
		$controller = Configure::read('controller.alias');
		
		// add conditions for photo/asset_id
		$conditions = $joins = array();
		$joins[] = array(				
			'table'=>'assets_collections',
			'alias'=>'AssetsCollection',
			'type'=>'INNER',
			'conditions'=>array('`AssetsCollection`.`collection_id` = `Collection`.id'),
		);
		$conditions[] = "AssetsCollection.asset_id='{$assetid}'";
		
		// check of context == controller
		$skip = $context['keyName'] == Configure::read('controller.label');
		// add context
		if (!$skip) {
			if (in_array($context['keyName'], array('Me', 'Person'))) {
				$conditions = array('`Collections`.owner_id'=>AppController::$ownerid );
			}
			if (in_array($context['keyName'], array('Photo','Asset','Snap'))) {
				// skip
			}
			if (in_array($context['keyName'], array('Group','Event','Wedding'))) {
				// Collection habtm Group
				$joins[] =  array(
						'table'=>'collections_groups',
						'alias'=>'CollectionsGroup',
						'type'=>'INNER',
						'conditions'=>array("CollectionsGroup.collection_id=`Collection`.id", "CollectionsGroup.group_id"=>$context['uuid']),
				);	
			}
			if ($context['keyName'] == 'Tag') {
				$joins[] =	array(
							'table'=>'tagged',
							'alias'=>'Tagged',
							'type'=>'INNER',
							'conditions'=>array("`Tagged`.`foreign_key` = `Collection`.id AND `Tagged`.`model` = 'Collection'"),
					);
				$joins[] =	array(
							'table'=>'tags',
							'alias'=>'Tag',
							'type'=>'INNER',
							'conditions'=>array("`Tagged`.`tag_id` = `Tag`.id AND `Tag`.`keyname` = '{$context['uuid']}'"),
					);
			}
		}
		if (!empty($joins)) $paginate['joins'] = @mergeAsArray($paginate['joins'], $joins);
		if (!empty($conditions)) $paginate['conditions'] = @mergeAsArray($paginate['conditions'], $conditions);
		
		return $paginate;
	}		
	function getPaginateCollectionsByTagId ($tagid , $paginate = array()) {
		$paginateModel = 'Collection';
//debug($paginateModel);			
		// add context, refactor
		$context = Session::read('lookup.context');
		$controller = Configure::read('controller.alias');
		
		// add conditions for UserId
		$conditions = $joins = array();
		$joins[] = 	array(
				'table'=>'tagged',
				'alias'=>'Tagged',
				'type'=>'INNER',
				'conditions'=>array("`Tagged`.`foreign_key` = `Collection`.id AND `Tagged`.`model` = 'Collection'"),
		); 
		$joins[] = 	array(
				'table'=>'tags',
				'alias'=>'Tag',
				'type'=>'INNER',
				'conditions'=>array("`Tag`.id = `Tagged`.tag_id"),
		); 		
		$conditions[] = array('`Tag`.keyname'=>$tagid);
		
		// check of context == controller
		$skip = $context['keyName'] == Configure::read('controller.label');
		// add context
		if (!$skip) {
			if (in_array($context['keyName'], array('Me', 'Person'))) {
				$conditions[] = "Collection.owner_id='{$context['uuid']}'";
			}
			if (in_array($context['keyName'], array('Group','Event','Wedding'))) {
				$joins[] =  array(
					'table'=>'collections_groups',
					'alias'=>'CollectionsGroup',
					'type'=>'INNER',
					'conditions'=>array("CollectionsGroup.collection_id = `Story`.`id`", "CollectionsGroup.group_id"=>$context['uuid']),
				);
			}
			if ($context['keyName'] == 'Tag') {
				// skip
			}
		}
		if (!empty($joins)) $paginate['joins'] = @mergeAsArray($paginate['joins'], $joins);
		if (!empty($conditions)) $paginate['conditions'] = @mergeAsArray($paginate['conditions'], $conditions);
		return $paginate;
	}	
	/**
	 * extract distinct asset UUIDs from html markup
	 * @return array of distinct/unique UUIDs
	 */
	private function _getAssetsFromMarkup($markup){
		$IMG_uuid_pattern = '/([A-F0-9]{8}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{4}-[A-F0-9]{12})\.JPG/i';
		$count = preg_match_all($IMG_uuid_pattern, $markup, $matches);
		return isset($matches[1]) ? array_unique($matches[1]) : array();
	}
	/**
	 * add to AssetsCollection HABTM
	 * @params $collectionId, Group uuid
	 * @params $assets mixed, array or comma delim string of Asset uuids
	 * @params $HABTM string, HABTM model className
	 * @params $replace boolean, replace existing assetIds
	 */
	function insertIntoHABTM($collectionId, $assets, $HABTM = 'AssetsCollection', $replace=false){
		$HABTM_Model = ClassRegistry::init($HABTM);
		$userid = AppController::$userid;
		if (is_string($assets))  $assets = explode(',', $assets );
		foreach ($assets as $aid) {
			$VALUES[]= "( UUID(), '{$collectionId}', '{$aid}', '{$userid}', now() )";
		}	
		switch($HABTM) {
			case 'AssetsCollection': 
				$table = 'assets_collections'; 
				$columns = "`id`, `collection_id`, `asset_id`, `user_id`, `modified`";
				break;
			case 'CollectionsGroup': 
				// TODO: CollectionsGroup is incomplete, $assets -> $groups
				$table = 'collections_groups'; 
				$columns = "`id`, `collection_id`, `group_id`, `user_id`, `modified`";
				break;
		}
		if ($replace) {
			$HABTM_Model->query("DELETE FROM `{$table}` WHERE `collection_id`='{$collectionId}';");	
		}
		
		$INSERT_aids = "INSERT IGNORE INTO `{$table}` ({$columns}) VALUES :chunk: ";
		$VALUES = insertByChunks($INSERT_aids, $VALUES);		
		foreach ($VALUES as $chunk) {
			$INSERT = str_replace(':chunk:', $chunk, $INSERT_aids);
			$HABTM_Model->query($INSERT);	
		}			
		return true;
	}

	function getDefaultPrivacy(){
		$userid = AppController::$ownerid;
		$field = 'privacy_collections';
		$profile = AppController::getProfile($userid);
		if (isset($profile['Profile'][$field])) {
			return $profile['Profile'][$field];	
		}  
		return $this->Behaviors->Permissionable->settings['Collection']['defaultBits'];
	}

	function save_page($data, $uuid = null, $action = null) {
		
		if (!$uuid) {
			// lookup by Collection.title, assumes title is unique by owner_id
			// what about Guests? can we still use App::$owner_id???
			$title = $data['dest'];
			$options = array('conditions'=>array(
				'`Collection`.owner_id' => AppController::$ownerid,
				'`Collection`.title' => $title)
			);
			$dataCollection = $this->find('first', $options);
			if ($dataCollection) $uuid = $dataCollection['Collection']['id'];
		}
		if ($uuid) {			// update
			if (!$this->hasPermission('write',$uuid)) return false;
			
			$this->id = $uuid;	
			if ($action == 'replace') {
				$markup =  $data['content'];		// replace
			} else {			// append
				$markup = $this->field('markup');
				$markup .=  '\n'.$data['content'];	// append
			}
		} else {				// create
			if (!AppController::$ownerid) return false;	
			$this->isCREATE = true;
			$this->create();
			$uuid = String::uuid(); 
			$data['Collection']['id'] = $uuid;
			$data['Collection']['owner_id'] = AppController::$ownerid;
			// TODO: get default perms from profile
			$data['Permission']['perms'] = $this->getDefaultPrivacy();		// public
			$data['Collection']['src_thumbnail'] = false; 
			$markup = $data['content'];
		}

		$aids = (array)$this->_getAssetsFromMarkup($markup);
		// find topRated Snap and set as cover photo
		if (isset($data['Collection']['src_thumbnail']) && count($aids)) {
			$options = array(
				'conditions'=>array('Asset.id'=>$aids),
				'fields'=>'Asset.id, Asset.src_thumbnail',
				'permissionable'=>false,
				'recursive' => -1,
				'showEdits'=>true,
				'join_shots'=>false, 
				'order'=>'UserEdit.rating DESC, SharedEdit.score DESC' 
			);
			$this->Asset->Behaviors->detach('Taggable');
			$topRated = $this->Asset->find('first', $options);
			$data['Collection']['src_thumbnail'] = $topRated['Asset']['src_thumbnail'];
		}
		$data['Collection']['markup'] = $markup;
		$data['Collection']['assets_collection_count'] = count($aids);
		$data['Collection']['title'] = $data['dest'];
		$ret = $this->save($data);
		if ($ret) { // update habtm associations
			$uuid = $this->id;
			$this->insertIntoHABTM($uuid, $aids, 'AssetsCollection', $action == 'replace');
		}
		return $ret ? $uuid : false;
	}
}
?>