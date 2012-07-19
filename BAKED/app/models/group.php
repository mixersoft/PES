<?php
class Group extends AppModel {
	var $name = 'Group';
	
	/*
	 * GroupPermissions
	 * uid = group owner
	 * gid = group id
	 * default permissions for 'user' Groups:
	 * perm = 567 (rwd/-wd/---/r--) - public listing, hide content, do NOT add to groupIds, content visible only by upgrading Asset perms to public
	 * perm = 631 (rwd/-wd/r--/r--) - public listing, content is member only, for members, add to groupIds
	 * perm = 119 (rwd/-wd/r--/--) 	- listing AND content is member only, for members, add to groupIds 
	 * perm = 63 (rwd/rwd/---/--) 	- listing AND content is owner/admin only, for admins, add to groupIds 
	 * 
	 */
	public $actsAs = array(
		'Tags.Taggable',	// TODO: why can't I load this on the fly????
		'Search.Searchable',
		'Comments.Sluggable' => array('label' => 'title'),	
		'Permissionable.Permissionable' => array(
			'defaultBits'	=> 631, 
			'userModel'		=> 'User',
			'groupModel'	=> 'Group',
		),
	);	
	

	/*
	 * Search plugin
	 */
	public $filterArgs = array(
		array('name' => 'title', 'type' => 'like', 'field' => 'title'),
		array('name' => 'description', 'type' => 'like', 'field' => 'description'),
		array('name' => 'keyword', 'type' => 'like', 'field' => 'keyword'),
		array('name' => 'tags', 'type' => 'subquery', 'method' => 'findByTags', 'field' => 'id'),
		array('name' => 'type', 'type' => 'value', 'field' => 'type'),
	);

	public function findByTags($data = array()) {
		$this->Tagged->Behaviors->attach('Containable', array('autoFields' => false));
		$this->Tagged->Behaviors->attach('Search.Searchable');
		$query = $this->Tagged->getQuery('all', array(
			'conditions' => "(`Tag`.name) LIKE '%{$data['tags']}%'",
			'fields' => array('foreign_key'),
			'contain' => array('Tag')
		));
		return $query;
	}
				
	
	public function beforeFind($queryData) {
		return true;
	}
	
	public function afterFind($results, $primary) {
		$model = $this->alias;
		$permAlias = $this->getPermissionAlias();
		// merge permissions
		try {
			if ($primary && isset($results[0][$model]) && class_exists('Permissionable')) {
				$member_owner_group_ids = Permissionable::getGroupIds();
				foreach ($results as $i => & $data) {
					if (isset($data[$permAlias]['perms'])) $data[$model]['perms'] = $data[$permAlias]['perms'];
					$data[$model]['isMember'] = in_array($data[$model]['id'], $member_owner_group_ids );  
					if (!empty($data[$model]['owner_id'])) $data[$model]['isOwner'] = $data[$model]['owner_id'] == AppController::$ownerid;
				}
			}
		} catch (Exception $e) {}

		if ($primary && !Configure::read('controller.isXhr')  && isset($results[0]['Group']['owner_id'])){
			if ($results[0]['Group']['id'] == Configure::read('controller.xhrFrom.uuid')) {
				Configure::write('controller.isOwner', $results[0]['Group']['owner_id'] == AppController::$ownerid);	
			}
		}
		return $results;
	}

	public function afterSave($created) {
		/*
		 * for Permissionable
		 * 		"Member" created groups must have Permission.gid = Group.id
		 */
		
		if($created && (!isset($this->isCREATE) || $this->isCREATE==false)) {
			$perms = $this->getPermission();
			if ($perms['gid'] != $this->id){
				$perms['gid'] = $this->id;
				ClassRegistry::init('Permissionable.Permission')->save($perms, array('gid'));
			}
		}
	}
	
	var $validate = array(
		'isSystem' => array(
			'boolean' => array(
				'rule' => array('boolean'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
		'title' => array(
			'notempty' => array(
				'rule' => array('notempty'),
				//'message' => 'Your custom message here',
				//'allowEmpty' => false,
				//'required' => false,
				//'last' => false, // Stop validation after this rule
				//'on' => 'create', // Limit validation to 'create' or 'update' operations
			),
		),
	);
	
	/*
	 * update Group counters for assets, users
	 * 		works with counterCache, 
	 * NOTE: remember to +1 user for owner before display
	 */
	function updateCounter($gid) {
		$time = time();
		$sql = "/* do not cache @{$time} */ SELECT COUNT(DISTINCT ag.asset_id) AS `assets_group_count`, COUNT(DISTINCT gu.user_id) AS `groups_user_count` 
FROM `groups` g
LEFT JOIN assets_groups ag ON g.id = ag.group_id
LEFT JOIN groups_users gu ON g.id = gu.group_id 
WHERE g.id='{$gid}'		
		;";
		$result = $this->query($sql);
		if ($result) {
			$data[$this->alias] = array_shift(array_shift($result));
			$data[$this->alias]['groups_user_count'] +=1; 		// add 1 for owner
			$this->id = $gid;
			$this->disablePermissionable(true);
			$ret = $this->save($data, false, array('assets_group_count','groups_user_count'));
			$this->disablePermissionable(false);	
			return $ret != false;
		}
		return false;
	}
	
	
	function lookupPhotostreams($id) {
		$lookup = "SELECT -- DISTINCT
  ProviderAccount.id as id,
  Asset.batchId,
  concat( ProviderAccount.display_name,'@',ProviderAccount.provider_name) AS provider_account_name,
  Owner.id, Owner.username as owner,
  count(*) as photos
FROM assets_groups `Group`
 JOIN assets AS Asset ON Asset.id = `Group`.asset_id
 JOIN `provider_accounts` AS `ProviderAccount` ON `Asset`.`provider_account_id` = `ProviderAccount`.`id`
 JOIN `users` AS `Owner` ON (`ProviderAccount`.`user_id` = `Owner`.`id`)
WHERE 1 = 1
  AND `Group`.group_id ='{$id}'
GROUP BY ProviderAccount.id, Asset.batchId
ORDER BY photos DESC;";
		$data=$this->query($lookup);
//		debug($data);
		return $data;
	}
	
	//The Associations below have been created with all possible keys, those that are not needed can be removed
	public $belongsTo = array(
		'Owner' => array(			// TODO: possible duplicate. ownership also stored in Permissions
			'className' => 'User',
			'foreignKey' => 'owner_id',
			'counterCache' => true,
//			'fields'=>array('Owner.id', 'Owner.username'),  // or add via Containable
			'type'=>'INNER',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
	
/*
 * 	primary group id is only used for Roles. this relationship is not frequently used.
 */
	public $hasMany = array(
//		'AssetsGroup' => array(
//			'className' => 'AssetsGroup',
//			'foreignKey' => 'group_id',
//			'dependent' => false,
//			'conditions' => '',
//			'fields' => '',
//			'order' => '',
//			'limit' => '',
//			'offset' => '',
//			'exclusive' => '',
//			'finderQuery' => '',
//			'counterQuery' => ''
//		),
//		'PrimaryUser' => array(
//			'className' => 'User',
//			'foreignKey' => 'primary_group_id',
//			'dependent' => false,
//			'conditions' => '',
//			'fields' => '',
//			'order' => '',
//			'limit' => '',
//			'offset' => '',
//			'exclusive' => '',
//			'finderQuery' => '',
//			'counterQuery' => ''
//		),	
		'Groupshot' => array(					// GroupShot belongsTo Group
			'className' => 'Groupshot',
			'foreignKey' => 'group_id',
			'dependent' => true,
		),		
	);


	var $hasAndBelongsToMany = array(
		'Asset' => array(
//			'with' => 'assets_groups',
			'with' => 'AssetsGroup',
		),
		'Collection' => array(
			'with' => 'CollectionsGroup',
		),
		'Member' => array(
//			'with' => 'groups_users',
			'with' => 'GroupsUser',
			'className' => 'User',
		),
		'ProviderAccount' => array(
			'with' => 'GroupsProviderAccount',
			'className' => 'ProviderAccount',
		),
//		'Tag' => array('with'=> 'Tagged'),
	);
	function appendFilterConditions($options, $conditions) {
		/*
		 * add filters, from $this->params['named']
		 */
		$filterConditions = array();
		if (isset($options['q'])) {
			// text search, 
			$searchKeys = array('title', 'description', 'tags');
			$filterConditions[] = $this->appendSearchConditions($options, $searchKeys );
		}	
		if (isset($options['filter-type'])) {
			$filterConditions[] = array("`{$this->alias}`.type"=>$options['filter-type']);
		}
		if (isset($options['filter-me'])) {
			$filterConditions[] = array("`{$this->alias}`.owner_id"=>AppController::$ownerid);
		}
		return @mergeAsArray( $conditions, $filterConditions);
	}
	
	
	function getPaginateGroups ( $paginate = array(), $skipContext = true) {
		$paginateModel = 'Group';
//debug($paginateModel);		
		// refactor
		$context = Session::read('lookup.context');
		$controller = Configure::read('controller.alias');
		
		// add conditions for photo/asset_id
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

	function getPaginateGroupsByPhotoId ($assetid , $paginate = array()) {
		$paginateModel = 'Group';
//debug($paginateModel);		
		// refactor
		$context = Session::read('lookup.context');
		$controller = Configure::read('controller.alias');
		
		// add conditions for photo/asset_id
		$conditions = $joins = array();
		$joins[] = array(				
			'table'=>'assets_groups',
			'alias'=>'AssetsGroup',
			'type'=>'INNER',
			'conditions'=>array('`AssetsGroup`.`group_id` = `Group`.id'),
		);
		$conditions[] = "AssetsGroup.asset_id='{$assetid}'";
		
		// check of context == controller
		$skip = $context['keyName'] == Configure::read('controller.label');
		// add context
		if (!$skip) {
			if (in_array($context['keyName'], array('Me', 'Person'))) {
				$assocModel = 'Group';
				//photos/groups
				if ($context['uuid'] != AppController::$ownerid) {
					// HERE. CONTEXT USER != $current_userid USER
					$joins[] =  array(
								'table'=>'groups_users',
								'alias'=>'HABTM_2',
								'type'=>'LEFT',
								'conditions'=>array("HABTM_2.group_id"=> "{$assocModel}.id",  "HABTM_2.user_id"=>$context['uuid'] ),
					);
				}
			}
			if (in_array($context['keyName'], array('Group','Event','Wedding'))) {
				// skip
			}
			if ($context['keyName'] == 'Tag') {
				$joins[] =	array(
							'table'=>'tagged',
							'alias'=>'Tagged',
							'type'=>'INNER',
							'conditions'=>array("`Tagged`.`foreign_key` = `Group`.id AND `Tagged`.`model` = 'Group'"),
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

	function getPaginateGroupsByCollectionId ($collectionid , $paginate = array()) {
		$paginateModel = 'Group';
//debug($paginateModel);		
		// refactor
		$context = Session::read('lookup.context');
		$controller = Configure::read('controller.alias');
		
		// add conditions for photo/asset_id
		$conditions = $joins = array();
		$joins[] = array(				
			'table'=>'collections_groups',
			'alias'=>'CollectionsGroup',
			'type'=>'INNER',
			'conditions'=>array('`CollectionsGroup`.`group_id` = `Group`.id'),
		);
		$conditions[] = "CollectionsGroup.collection_id='{$collectionid}'";
		
		// check of context == controller
		$skip = $context['keyName'] == Configure::read('controller.label');
		// add context
		if (!$skip) {
			if (in_array($context['keyName'], array('Me', 'Person'))) {
				$assocModel = 'Group';
				//photos/groups
				if ($context['uuid'] != AppController::$ownerid) {
					// HERE. CONTEXT USER != $current_userid USER
					$joins[] =  array(
								'table'=>'groups_users',
								'alias'=>'HABTM_2',
								'type'=>'LEFT',
								'conditions'=>array("HABTM_2.group_id"=> "{$assocModel}.id",  "HABTM_2.user_id"=>$context['uuid'] ),
					);
				}
			}
			if (in_array($context['keyName'], array('Group','Event','Wedding'))) {
				// skip
			}
			if ($context['keyName'] == 'Tag') {
				$joins[] =	array(
							'table'=>'tagged',
							'alias'=>'Tagged',
							'type'=>'INNER',
							'conditions'=>array("`Tagged`.`foreign_key` = `Group`.id AND `Tagged`.`model` = 'Group'"),
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
	/**
	 *  /groups/search/User:[not userid]
	 * @param $userid
	 * @param $paginate
	 * @param $paginateModel
	 * @return unknown_type
	 */
	function getPaginateGroupsByUserId ($userid , $paginate = array(), $paginateModel = null) {
		$controllerClass = Configure::read('controller.class');
		$paginateModel = $this->alias;		
		// add context, refactor
		$context = Session::read('lookup.context');
		$controller = Configure::read('controller.alias');
		
		// add conditions for UserId
		$conditions = $joins = array();

		if ( $userid != AppController::$ownerid) {
			// if we are not looking at the Permissionable user, we have to add join
			// unless we are superuser
			$joins[] = array(
				'table'=>'groups_users',
				'alias'=>'HABTM',
				'type'=>'INNER',
				'conditions'=>array("`HABTM`.group_id =`{$paginateModel}`.id"),
			);
			if ($userid == Permissionable::getRootUserId() ) $conditions[] = array();
			else {
				$permissionableAlias = $this->Behaviors->Permissionable->getPermissionAlias($this);
				$conditions[] = array(
					"`{$permissionableAlias}`.`perms` <> 567",
					'OR'=>array(
						"`{$paginateModel}`.`owner_id`" => $userid ,
						"`HABTM`.`user_id`"=>$userid
					)
				);
			}
			
			$paginate['fields'] ='DISTINCT '.$paginate['fields'];
		} else {
			$conditions[] = array("`{$paginateModel}`.`id`" => Permissionable::getGroupIds());
		}	
		
		// check of context == controller
		$skip = $context['keyName'] == Configure::read('controller.label');
		// add context
		if (!$skip) {
			if (in_array($context['keyName'], array('Me', 'Person'))) {
				//	skip. no context required for /users/groups and context=Users
			}
			if (in_array($context['keyName'], array('Group','Event','Wedding'))) {
				// skip /groups/groups == /groups/home	
				// but NOT /users/groups	
				$conditions[] = array("`{$paginateModel}`.id"=>$context['uuid']);		
			}
			if ($context['keyName'] == 'Tag') {
				$joins[] = 	array(
							'table'=>'tagged',
							'alias'=>'Tagged',
							'type'=>'INNER',
							'conditions'=>array("`Tagged`.`foreign_key` = `{$paginateModel}`.id AND `Tagged`.`model` = 'Group'"),
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
	
	function getPaginateGroupsByTagId ($tagid , $paginate = array()) {
		$paginateModel = 'Group';
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
				'conditions'=>array("`Tagged`.`foreign_key` = `Group`.id AND `Tagged`.`model` = 'Group'"),
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
				$conditions[] = "Group.owner_id='{$context['uuid']}'";	// groups OWNED by user
				// TODO: should we also include group memberships of $context['uuid'] ???
			}
			if (in_array($context['keyName'], array('Group','Event','Wedding'))) {
				// skip
			}
			if ($context['keyName'] == 'Tag') {
				// skip
			}
		}
		if (!empty($joins)) $paginate['joins'] = @mergeAsArray($paginate['joins'], $joins);
		if (!empty($conditions)) $paginate['conditions'] = @mergeAsArray($paginate['conditions'], $conditions);
		return $paginate;
	}
	
	function getPublicPaginateGroups ($paginate = array(), $paginateModel = null) {
		$controllerClass = Configure::read('controller.class');
		$paginateModel = ($controllerClass == 'User') ? 'Membership' : 'Group';		
//debug($paginateModel);				
		// add context, refactor
		$context = Session::read('lookup.context');
		$controller = Configure::read('controller.alias');
		
		// add conditions for UserId
		$conditions = $joins = array();
		$this->Asset->disablePermissionable(true);
		
//		$joins[] = 	array(
//			'table'=>'Permissions',
//			'alias'=>"{$paginateModel}Permission",
//			'type'=>'INNER',
//			'conditions'=>array("`{$paginateModel}Permission`.`model` = '{$paginateModel}' 
//				AND `{$paginateModel}Permission`.`foreignId` = `{$paginateModel}`.`id` 
//				AND `{$paginateModel}Permission`.`perms` = 0567"),
//		);
		
		$joins[] = 	array(
			'table'=>'permissions',
			'alias'=>'Public',
			'type'=>'INNER',
			'conditions'=>array("`Public`.`model` = '{$paginateModel}' 
				AND `Public`.`foreignId` = `{$paginateModel}`.`id` 
				AND `Public`.`perms` = 0567"),
		);
		
		// check of context == controller
		$skip = $context['keyName'] == Configure::read('controller.label');
		// add context
		if (!$skip) {
			if (in_array($context['keyName'], array('Me', 'Person'))) {
				//	skip. no context required for /users/groups and context=Users
			}
			if (in_array($context['keyName'], array('Group','Event','Wedding'))) {
				// skip /groups/groups == /groups/home	
				// but NOT /users/groups	
				$conditions[] = array("`{$paginateModel}`.id"=>$context['uuid']);		
			}
			if ($context['keyName'] == 'Tag') {
				$joins[] = 	array(
							'table'=>'tagged',
							'alias'=>'Tagged',
							'type'=>'INNER',
							'conditions'=>array("`Tagged`.`foreign_key` = `Membership`.id AND `Tagged`.`model` = 'Group'"),
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
		$this->Asset->disablePermissionable(false);	
		return $paginate;
	}
	function submissionPolicy($id) {
		$this->id=$id;
		$submission_policy = $this->field('submission_policy');
		return $submission_policy;		
	}

	function canPublish($id) {
		$submission_policy = (int)$this->submissionPolicy($id);
		return $submission_policy === 1;		// 1=publish, 2=queue for Admin approval
	}
	
	function canSubmit($id) {
		return $this->hasPermission('read', $id);		// for now, assume if we have READ access, we have SHARE/CONTRIBUTE permission		
	}
	function membershipPolicy($id) {
		$this->id=$id;
		$policy = $this->field('membership_policy');
		// TODO: use placeholder for Group membershipPolicy for now.
		return true;
	}
	function getUserIdsByRole($id, $role='admin'){
		$sql = "
SELECT `Group`.`owner_id`, GroupsUser.user_id, GroupsUser.role
FROM `groups` AS `Group`
JOIN groups_users AS GroupsUser ON GroupsUser.group_id=`Group`.`id` AND GroupsUser.isActive=1
WHERE `Group`.`id` = '{$id}' AND GroupsUser.role='{$role}'";
		$result = $this->query($sql);
		$userids = Set::extract($result, '/GroupsUser/user_id');
		return $userids;
	}
	/**
	 * add array of asset_ids to group using assets_groups 
	 * @params $groupId, Group uuid
	 * @params $assets mixed, array or comma delim string of Asset uuids
	 * @params $isApproved boolean, optional initial approved status
	 * @params $providerAccountId, optional, avoids extra lookup query if provided
	 */
	function contributePhoto($groupId, $assets, $isApproved=null, $providerAccountId=null) {
		if ($isApproved === null) $isApproved = $this->canPublish($groupId);
		if (is_string($assets))  $assets = explode(',', $assets );
// $this->log($assets, LOG_DEBUG);
// $this->log(Debugger::trace(), LOG_DEBUG);				
		$userid = AppController::$ownerid;
		$saveAll_assets = array();
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
		
		// add ProviderAccount to GroupsProviderAccount for photostreams
		if ($providerAccountId) {
			$this->insertIntoGroupsProviderAccount($groupId, null, $providerAccountId);
		} else {
			$this->insertIntoGroupsProviderAccount($groupId, $assets);
		}
		// copy any Usershots to Groupshots
		ClassRegistry::init('Usershot')->copyToGroupshots($groupId, $assets);
		// update Counts
		
		$resp2 = $this->updateCounter($groupId);
		// $response['shared'] = count($VALUES_assets_groups);
		// $response['count'] = 
		return count($VALUES_assets_groups);	
	}
	/**
	 * add ProviderAccount to GroupsProviderAccount for photostreams
	 * @params $groupId, Group uuid
	 * @params $assets mixed, array or comma delim string of Asset uuids
	 * @params $providerAccountIds mixed, Array or Comma delim String of uuids, optional
	 */
	function insertIntoGroupsProviderAccount($groupId, $assets, $providerAccountIds=null){
		if ($providerAccountIds === null) {
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
		} else if (is_string($providerAccountIds)) $providerAccountIds = explode(',',$providerAccountIds);
		
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
		return true;
	}
	
}
?>