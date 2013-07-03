<?php
class Groupshot extends AppModel {
	public $name = 'Groupshot';
	public $table = 'groupshots';
	
	public $belongsTo = array(	
		'Group' => array(								// Group hasMany Groupshots
			'className' => 'Group',
			'foreignKey' => 'group_id',
			'counterCache' => true,						// o-add groups.groupshot_count
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);	
	public $hasOne = array(				
		'BestGroupshotSystem' => array(					// ?-BestGroupshotSystem belongsTo Usershots
			'className' => 'BestGroupshot',
			'foreignKey' => 'groupshot_id',
			'conditions' => array("`BestGroupshotSystem`.user_id IS NULL"),
			'dependent' => true,
		),
		'BestGroupshotOwner' => array(					// BestGroupshotOwner belongsTo Groupshot
			'className' => 'BestGroupshot',
			'foreignKey' => 'groupshot_id',
//			'conditions' => array("`BestGroupshotOwner`.user_id"=>'Group.owner_id'),
			'dependent' => true,
		),		
		// TODO: should be hasMany, but it doesn't saveAll() misses groupshot_id.
		'BestGroupshotMember' => array(					// BestGroupshotMember belongsTo Groupshot
			'className' => 'BestGroupshot',
			'foreignKey' => 'groupshot_id',
//			'conditions' => array("`BestGroupshotOwner`.user_id"=>AppController::$userid),		
			'dependent' => true,
		),				
	);			
	public $hasMany = array(
		'AssetsGroupshot' => array(						// AssetsUsershot belongsTo Usershot 
			'className' => 'AssetsGroupshot',
			'foreignKey' => 'groupshot_id',
			'dependent' => true,			
		),	
		'BestGroupshot' => array(						// BestGroupshot belongsTo Groupshot 
			'className' => 'BestGroupshot',
			'foreignKey' => 'groupshot_id',
			'dependent' => true,			// delete Groupshot deletes all related BestGroupshots
//			'conditions' => '',
//			'fields' => '',
//			'order' => '',
//			'limit' => '',
//			'offset' => '',
//			'exclusive' => '',
//			'finderQuery' => '',
//			'counterQuery' => ''
		),
	);	
	public $hasAndBelongsToMany = array(
		'Asset' => array(								// Asset habtm Groupshot
			'with' => 'AssetsGroupshot',				// AssetsGroupshot belongsTo Groupshot
//			'className' => 'Asset',
		)
	);

/**
	 * return top rated Asset.id, sorted by rating DESC, score DESC
	 * NOTE: assumes data is order by 'order'=>'`SharedEdit`.score DESC, `Asset`.dateTaken ASC',
	 */
	private function _getTopRatedByRatingScore(& $data){
		$top_rated = null;
		foreach ($data as $row) {
			if (!$top_rated) {
				$top_rated = $row;
				continue;
			}
			if ($row[0]['rating'] > $top_rated[0]['rating']) {
				$top_rated = $row;
				continue;
			}
			if ($row[0]['rating'] == $top_rated[0]['rating'] 
				&& $row['SharedEdit']['score'] > $top_rated['SharedEdit']['score'] ) 
			{
				$top_rated = $row;
				continue;
			}
		}
		return $top_rated['Asset']['id'];
	}	
	/*
	 * TODO: clean up hack
	 * Problem: Workorder (WMS) access is /workorders/photos/[woid], NOT /groups/photos/[group_id]
	 * Hack: check that Assets are from the SAME owner, Asset.owner_id are ALL THE SAME
	 * 		then use AssetsGroup.group_id as $group_id
	 * TODO: we should really check if Asset.owner_id == Workorder.source_id
	 * @params $assetIds array of Asset ids
	 * @params $group_id default value
	 */
	private function _getGroupIdForWorkorderProcessing($assetIds, $group_id){
		$options = array(
			'fields'=>array('`Asset`.id'), 
			'conditions'=>array('`Asset`.id'=>$assetIds),
			'contain'=>array(
				'AssetsGroup'=>array('fields'=>'`AssetsGroup`.group_id',
					'conditions'=>array('`AssetsGroup`.group_id'=>$group_id)),
			),
			'recursive' => 2,
			'order'=>array('`SharedEdit`.score DESC', '`Asset`.dateTaken ASC'),		
			'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>'Groupshot', 		
				'show_hidden_shots'=>true,
				'join_bestshot'=>false,
			),
		);
		$Asset = $this->AssetsGroupshot->Asset;
		if (in_array('WorkorderPermissionable', $Asset->Behaviors->attached())) {
			// TODO: match with Asset::joinWithShots()
			$checkdata = $Asset->find('all', $options);
			// check that all Assets have same owner_id
debug($checkdata);			
			$groupIds = array_unique(Set::extract('/AssetsGroup/group_id', $checkdata));
			if (count($groupIds) == 1) {
				return array_pop($groupIds);
			} else return false;
		} else return $group_id;	// default value
	}
	
	/**
	 * Get Usershot.priority based on AppController::$role, 
	 * use priority in GroupAsShot to determine privilege
	 * 	USER = 10
	 * 	EDITOR/MANAGER/OPERATOR = 20
	 *  SCRIPT = 30, i.e. asset-group script
	 * 
	 * higher priority = lower Usershot.priority number
	 * higher priority can *deactivate* lower priority shots
	 * equal priority will *replace* existing shots
	 */
	public function _get_ShotPriority($role=null){
		if (!$role) $role = AppController::$role;
		$role_priority_lookup['USER'] = 10;
		$role_priority_lookup['MANAGER'] = 20;
		$role_priority_lookup['OPERATOR'] = 20;
		$role_priority_lookup['EDITOR'] = 20;		// TODO: DEPRECATE EDITOR ROLE
		$role_priority_lookup['GUEST'] = 40;
		$role_priority_lookup['SCRIPT'] = 30;
		return $role_priority_lookup[$role];
	}
	
	/**
	 * groupAsShot
	 * @param array $assetIds 
	 * @param $group_id - uuid of group, FK for groupshots
	 * @param $isBestshot boolean, default false. if true, then 
	 * 		we assume the user was adding a bestshot to new Shot, find/add hiddenshots	
	 * @param string $owner_id - uuid of group owner, 
	 * 		sets BestShotOwner when same as AppController::$ownerid 
	 * @return array('shotId', 'bestshotId')  or FALSE on error
	 */
	public function groupAsShot ($assetIds, $group_id, $isBestshot=false, $owner_id = null) {
		$success = false; $message=array(); $response=array();
		
		$owner_id = $owner_id ? $owner_id : AppController::$ownerid;
		$Asset = $this->AssetsGroupshot->Asset;
//		$Asset->contain('AssetsGroup.group_id');
		$group_id = $this->_getGroupIdForWorkorderProcessing($assetIds, $group_id);
		$options = array(
			'fields'=>'DISTINCT `Asset`.id', 
			'conditions'=>array('`Asset`.id'=>$assetIds),
			// NOTE: must use join instead of containable w/ condition for Permissionable
			// 'contain'=>array(
				// 'AssetsGroup'=>array('fields'=>'`AssetsGroup`.group_id',
					// 'conditions'=>array('`AssetsGroup`.group_id'=>$group_id)),
			// ),
			'recursive' => 2,
			'order'=>array('`SharedEdit`.score DESC', '`Asset`.dateTaken ASC'),		
			'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>'Groupshot', 		
				'show_hidden_shots'=>true,
				'show_inactive_shots'=>true,	// process for ALL shots, including inactive
				'join_bestshot'=>false,
			),
		);
		// NOTE: must use join instead of containable w/ condition for Permissionable
		$options['joins'][] = array(
			'table'=>'assets_groups',
			'alias'=>'AssetsGroup',
			'type'=>'INNER',
			'conditions'=>array(
				'AssetsGroup.asset_id = Asset.id', 
				'AssetsGroup.group_id'=>$group_id, 
			),
		);
		if ($owner_id === false) {
			$success = false;
			$message[] = 'Groupshot->groupAsShot: Error saving shot, Group ownerid missing';
			$resp0 = compact('success', 'message', 'response');
			return $resp0;
		} 
		
		$data = $Asset->find('all', $options);
		$assetIds = Set::extract($data, '/Asset/id');
debug($assetIds);
		
		
		
		$existing_shots = array_filter(Set::combine($data, '/Shot/shot_id', '/Shot/shot_priority'));
		$permitted_AssetIds = array_unique(Set::extract('/Asset/id', $data)); // aids sorted by SharedEdit.score DESC in bestshot order
// debug($options);		
debug($existing_shots);	
debug($permitted_AssetIds);
// debug($data);		

// exit;
		$no_permissions = array_diff($assetIds, $permitted_AssetIds);
debug($no_permissions);		
		// check permissions on submitted Assets, unless role=SCRIPT
		if (AppController::$role !=='SCRIPT' && count($no_permissions)) {
			// throw new Exception('Error: No Permission on the following assetIds, aids='.print_r(array_diff($assetIds,$permitted_AssetIds),true)); 		
			$message[] = 'Error: No Permission on the following assetIds';
			$response['asset_ids'] = $no_permissions;
			return compact('success', 'message', 'response');
		}
				
		 /**
		  * 3 tasks:
		  * 	1) check new vs existing shot priority for EACH asset
		  * 	2) check if we need to include hiddenshots in new shot
		  * 	3) cleanup old shot(s) [updateShot, deactivateShot, unGroupShot]
		  * 
		  * Existing Shots - 3 scenarios (lower number == HIGHER priority)
		  * 3) new shot priority HIGHER THAN old shot priority,
		  * 	deactivate old shot,  $cleanup['deactivateShots'][] 
		  * 1) new shot priority LOWER THAN old shot(s) 
		  * 	do NOT change old shot, 
		  * 	create new shot with $isActive=0
		  * 		PEOPLE: return error message to user
		  * 2) new shot priority SAME AS old shot priority, find correct cleanup strategy
		  * 	a) SCRIPT shot
		  * 		SCRIPT shots should NOT overlap??? 
		  * 		just use $cleanup['removeFromShots'][], but respond with WARNING message
		  * 	b) PEOPLE shot
		  * 		if added from show_hidden_shots==0, find/add all hiddenShots to new Shot, unGroup old Shot
		  * 			$cleanup['unGroupShots'][] = $shot_id;
		  * 			Add to $merge_hiddenshots, include hiddenshots in group
		  * 		if added from show_hidden_shots==1, remove from oldShot, reset bestshot on oldShot
		  * 			$cleanup['removeFromShots'][] = $shot_id;
		  *
* 		 * 	1) show_hidden_shots=0 && ShotPriority==USER, from photoRollContextMenu: 
		 * 		the asset is the Bestshot, get hiddenShots and add to the NEW Shot
		 * 		TODO: we need to know if the asset is a Bestshot
		 * 	2) show_hidden_shots=1, i.e. from hiddenShotContextMenu
		 * 		remove selected assets from $existing_shots, do NOT add hiddenShots
		  * 
		  */ 
		 $shot_priority = $this->_get_ShotPriority();
		 $cleanup = $merge_hiddenshots = array();
		 foreach ($existing_shots as $shot_id=>$old_priority) {
		 	if (!$shot_id) continue;	// should be fixed by array_filter()
		 	
		 	$relative_priority = $old_priority - $shot_priority;  // lower number is higher priority
		 	if ($relative_priority > 0) {  // new shot HIGHER priority
				$cleanup['deactivateShots'][] = $shot_id;
				if ($isBestshot) {
					// find & add hiddenShots, active only
					$merge_hiddenshots[] = $shot_id;
				}
				//???: also remove from deactivated shots? 
			}
		 	if ($relative_priority < 0) { // new shot LOWER priority  
				 	$isActive = false;  // NEW shot is active=0
					// no cleanup applied to existing shot with HIGHER priority
					if ($isBestshot) {
						// find & add hiddenShots, active only
						$merge_hiddenshots[] = $shot_id;
					}
					continue;		
		 	}
		 	if ($relative_priority == 0) {	// SAME priority, find the correct cleanup strategy
		 		if ( $shot_priority == $this->_get_ShotPriority('SCRIPT') ) {
		 			// SCRIPT shots should NOT overlap, right?
		 			// for SCRIPT shots, REMOVE asset from old Shot 
		 			$cleanup['removeFromShots'][$shot_id] = $permitted_AssetIds;
					$message[] = "WARNING: new SCRIPT shot overlaps with existing SCRIPT shot. Removing assets from existing shot. shot_id={$shot_id}";
				} else {
					// TODO: how do we determine $show_hidden_shots for this request???
// debug("isBestshot={$isBestshot}");					
					if ($isBestshot) {
						// find & add hiddenShots, active only
						$merge_hiddenshots[] = $shot_id;
						$cleanup['unGroupShots'][] = $shot_id;
					} else {
						$cleanup['removeFromShots'][] = $shot_id;
						// removeFromShot() does a USER=owner_id check, but not MANAGER/OPERATOR
					}
				}
			}
			
		 } 
		 /*
		  * merge hiddenshots as necessary
		  */
		 if (count($merge_hiddenshots)) {
				// find hiddenshots from ACTIVE shots, and add to the NEW shot using $permitted_AssetIds
				// destroy existing shot
				$hiddenShot_options = array(
					'fields'=>array('`Groupshot`.id', '`Groupshot`.active'),
					'conditions'=>array(
						'`Groupshot`.id'=>$merge_hiddenshots,
						'`Groupshot`.active'=>1,
					),
					'contain'=>array('AssetsGroupshot.asset_id'),
				);
				$hiddenShot_data = $this->find('all', $hiddenShot_options);
				$hiddenShot_assetIds = Set::extract($hiddenShot_data, '/AssetsGroupshot/asset_id');
// debug($merge_hiddenshots); 				
// debug($hiddenShot_options); 
// debug($hiddenShot_data);
// debug($hiddenShot_assetIds); 		// NOTE: this array is NOT unique		
				$permitted_AssetIds = array_unique(array_merge($permitted_AssetIds, $hiddenShot_assetIds));
		} 
		
		
	
		// create Groupshot
		// add assetIds to AssetsGroupshot
		$insert = array();
		$insert['Groupshot']['group_id'] = $group_id;
		foreach ($permitted_AssetIds as $assetId) {
			$insert['AssetsGroupshot'][]['asset_id'] = $assetId;
		}		
		if (count($permitted_AssetIds)) {
			// set BestGroupshotSystem by sort order, sort=='`SharedEdit`.score DESC, `Asset`.dateTaken ASC',
			$insert['BestGroupshotSystem']['asset_id'] = $permitted_AssetIds[0];
			// now sort by UserEdit.rating
			if (false == $Asset->Behaviors->attached('WorkorderPermissionable')) {
				$admins = $this->Group->getUserIdsByRole($group_id);
				if (in_array(AppController::$userid, $admins)) {
					// set BestGroupshotOwner by UserEdit.rating	
					$bestshotAlias='BestGroupshotOwner';
				} else {
					// set BestGroupshotMember by UserEdit rating
					$bestshotAlias='BestGroupshotMember';
				}
				$insert[$bestshotAlias]['asset_id'] = $this->_getTopRatedByRatingScore($data);
				$insert[$bestshotAlias]['user_id'] = AppController::$userid;
			}
			
debug($cleanup);
debug($insert); 
// exit;		// stop before actual insert			
			
			// save to DB
			$ret = $this->saveAll($insert, array('validate'=>'first'));
		}
//debug($insert); 
		if (isset($ret)) {
			$success = $ret != false;
			$message[] = 'Groupshot->groupAsShot: OK';
			$response['groupAsShot']['shotId'] = $this->id;
			if (isset($bestshotAlias)) $response['groupAsShot']['bestshotId'] = $insert[$bestshotAlias]['asset_id'];
			else $response['groupAsShot']['bestshotId'] = $permitted_AssetIds[0];
			$resp0 = compact('success', 'message', 'response');
			
			// after saving NEW shot, cleanup old shots
			// unGroupShot if same priority, deactivate shot if lower priority (higher number)

			if (!empty($cleanup['unGroupShots'])) {
				// TODO: delete old/orphaned Shots using QUEUE
				$resp1 = $this->unGroupShot($cleanup['unGroupShots']);
				$success = $success && $resp1['success'];
				$resp0 = Set::merge($resp0, $resp1);
			}
			if (!empty($cleanup['removeFromShots'])) {
				// TODO: deactivate old Shots using QUEUE
				$resp1 = $this->removeFromShot($permitted_AssetIds, $cleanup['removeFromShots']);
				$success = $success && $resp1['success'];
				$resp0 = Set::merge($resp0, $resp1);
			}
			if (!empty($cleanup['deactivateShots'])) {
				// TODO: deactivate old Shots using QUEUE
				$resp1 = $this->_deactivateShot($cleanup['deactivateShots']);
				$success = $success && $resp1['success'];
				$resp0 = Set::merge($resp0, $resp1);
			}			
		} else {
			$message[] = 'Usershot->groupAsShot: Error saving shot';
			$resp0 = compact('success', 'message', 'response'); 			
		}
		return $resp0;
	}

	
	
	/**
	 * remove Delete Shot and related AssetsShots, BestShots
	 * @param array $deleteShotIds array of UUIDs
	 * @return false on error
	 * 
	 * TODO: we should check owner/permissions here? require either moderator/admin or group membership
	 */
	public function unGroupShot ($deleteShotIds) {
		$success = false; $message=array(); $response=array();
		
		if (!empty($deleteShotIds)) {
						
			/*
			 * Confirm unGroup Shot privilege and priority, do we have permission to edit existing Shot?
			 * 	higher priority can deactivate lower priority shots
			 * 	equal priority will replace shots
			 */ 
			 $shot_priority = $this->_get_ShotPriority();
			 $options = array('conditions'=>array('`Groupshot`.id'=>$deleteShotIds));
			 $data = $this->find('all', $options);
debug($data);			 
			 foreach ($data as $row) {
			 	$shot_id = $row['Groupshot']['id'];
			 	$old_priority = $row['Groupshot']['priority'];
				$relative_priority = $old_priority - $shot_priority;  // lower number is higher priority
				if ($relative_priority > 0) $cleanup['deactivateShots'][] = $shot_id;  // new shot HIGHER priority  
			 	if ($relative_priority < 0){ // new shot LOWER priority 
			 		// current role has lower priority, no privilege to change existing Shot
			 		// no privilege or save as "lower" privilege???
			 		$message = "Error: Current role has lower priority, no privilege to change existing Shot, role=".AppController::$role;
					$response['existing_shots'][] = array_filter_keys($row['Groupshot'], array('id', 'owner_id', 'priority'));				
			 		return compact('success', 'message', 'response'); 
			 	}
			 	if ($old_priority == $shot_priority) $cleanup['unGroupShots'][] = $shot_id;
				
				
				// only group member can remove from a USER created Groupshot
				if ($old_priority == $this->_get_ShotPriority('USER')
					&& !$this->Group->canSubmit($row['Groupshot']['group_id'])) 
				{
					$message = "Error: no privilege to remove from this Shot, Shot.owner_id={$row['Groupshot']['owner_id']}";
					$response['Groupshot'] = array_filter_keys($row['Groupshot'], array('id', 'owner_id', 'priority'));
					return compact('success', 'message', 'response');
				}
				
			 } 
debug($cleanup);
			// TODO: delete old/orphaned Shots using QUEUE
			if (!empty($cleanup['unGroupShots'])) {
				$sql_deleteCascadeShots = "
	DELETE FROM `Shot`, `Best`, `AssetsShots`
	USING `groupshots` AS `Shot`
	INNER JOIN `best_groupshots` AS `Best` ON (`Best`.groupshot_id = `Shot`.id)
	INNER JOIN `assets_groupshots` AS `AssetsShots` ON (`AssetsShots`.groupshot_id = `Shot`.id)
	WHERE `Shot`.id IN ";
				$sql_deleteCascadeShots .= "('".implode("','",$cleanup['unGroupShots'])."')";
				$ret = $this->query($sql_deleteCascadeShots); // always true
				$message[] = "Usershot->unGroupShot: OK";
				$response['unGroupShot']['shotIds'] = $deleteShotIds;
			}
			if (!empty($cleanup['deactivateShots'])) {
				// TODO: deactivate old Shots using QUEUE
				$resp1 = $this->_deactivateShot($cleanup['deactivateShots']);
				$message = Set::merge($message, $resp1['message']);
				$response = Set::merge($message, $resp1['response']);
			}	
		} 
		$success = true;
		$message[] = "Groupshot->unGroupShot: OK";
		$response['unGroupShot']['shotIds'] = $deleteShotIds;
		return compact('success', 'message', 'response');
	}
	

	/**
	 * Deactivate Shots, set usershots.active=0,
	 * 	Deactivate shots that are lower priority, (higher Usershot.priority number) instead of deleting 
	 * 		* called by groupAsShot, unGroupShot
	 * ???: should we allow public access to this method??
	 * 
	 * @param array $deactivateShotIds array of UUIDs
	 * @return standard JSON response 
	 */
	private function _deactivateShot ($deactivateShotIds) {
		if (!is_array($deactivateShotIds)) throw new Exception('Error: $deactivateShotIds should be an Array()');
		$success = false; $message=array(); $response=array();
		if (!empty($deactivateShotIds)) {
			// TODO: delete old/orphaned Shots using QUEUE
			$sql_deactivateShots = "UPDATE `groupshots` AS `Shot` SET `Shot`.active=0 WHERE `Shot`.id IN ('".implode("','",$deactivateShotIds)."')";
			$ret = $this->query($sql_deactivateShots); // always true
		} 
		$success = true;
		$message[] = "Groupshot->deactivateShot: OK";
		$response['deactivateShot']['shotIds'] = $deactivateShotIds;
		return compact('success', 'message', 'response');
	}
			
	
	/**
	 * remove Asset from Shot, but keep shot
	 * @param array $assetIds 
	 * @param mixed, uuid or array of uuids, $shotId
	 * @return aa array('success','bestShot')
	 */
	public function removeFromShot ($assetIds, $shotId) {
		$success = false; $message=array(); $response=array();
		if (!empty($assetIds)) {
			
			// TODO: check Permissionable or WorkorderPermissionable on submitted Assets
					
			/*
			 * Confirm edit Shot priority, do we have permission to edit existing Shot?
			 * 	higher priority can deactivate lower priority shots
			 * 	equal priority will replace shots
			 */ 
			$shot_priority = $this->_get_ShotPriority();
			$existing = $this->read(array('priority', 'owner_id'), $shotId);
			$old_priority = $existing['Groupshot']['priority'];
			$relative_priority = $old_priority - $shot_priority;  // lower number is higher priority
		 	if ($relative_priority < 0){ // new shot LOWER priority
		 		$message = "Error: Current role has lower priority, no privilege to remove from existing Shot, role=".AppController::$role;
				$response['existing_shot'] =  array($shotId=>$old_priority);				
		 		return compact('success', 'message', 'response'); 
		 	}
			
			// only USER + member can remove from a USER created Groupshot
			if ($existing['Groupshot']['priority'] == $this->_get_ShotPriority('USER')
				&& !$this->Group->canSubmit($existing['Groupshot']['group_id'])) 
			{	
				$message = "Error: no privilege to remove from this Shot, Shot.owner_id={$existing['Groupshot']['owner_id']}";
				$response['Shot.owner_id'] = $existing['Groupshot']['owner_id'];
				return compact('success', 'message', 'response');
			}
			
			$assetIds_IN = "('" . join("','", $assetIds)  ."')"; 
			// TODO: delete old/orphaned Shots using QUEUE
			$sql_removeFromShot = "
DELETE FROM `Best`, `AssetsShots`
USING `groupshots` AS `Shot`
INNER JOIN `assets_groupshots` AS `AssetsShots` ON (`AssetsShots`.groupshot_id = `Shot`.id 
	AND `AssetsShots`.asset_id IN {$assetIds_IN} )
LEFT JOIN `best_groupshots` AS `Best` ON (`Best`.groupshot_id = `Shot`.id
	AND `Best`.asset_id = `AssetsShots`.asset_id  )
";

			if (is_array($shotId)) {
				$shotIds_IN = "('" . join("','", $shotId)  ."')";
				$WHERE = "WHERE `Shot`.id IN ($shotIds_IN)";
			} else $WHERE = "WHERE `Shot`.id = '{$shotId}'"; 
			$sql_removeFromShot .= ' '.$WHERE;
			
			$ret = $this->query($sql_removeFromShot); // always true
			$response['removeFromShot']['assetIds'] = $assetIds;
			$message[] = 'Groupshot->removeFromShot: OK';
			$success = true;
			$resp0 = compact('success', 'message', 'response');
// debug($resp0);			
			/*
			 *  update Shot.assets_groupshot_count
			 */
			$resp1 = $this->updateShotCounterCache($shotId);
			$success = $success && $resp1['success'];
// debug($resp1);			
			/*
			 *  update Best, if best was removed
			 */
			$resp2 = $this->updateBestShotSystem($shotId);
			$success = $success && $resp2['success'];
// debug($resp2);			
			$resp0 = Set::merge(compact('success', 'message', 'response'), $resp1, $resp2);
			$resp0['success'] = $success;
		} else {
			$message[] = 'Groupshot->removeFromShot: no asset_ids provided';
			$resp0 = compact('success', 'message', 'response'); 
		}
		return $resp0;		
	}

	/**
	 * update bestShotSystem for $shotIds using top score
	 * 	NOTE: does NOT update bestShotMember or bestShotUser. this 
	 * @param $shotIds array of Shot.id
	 * @return aa  array('asset_id', 'changed') 
	 */
	public function updateBestShotSystem($shotIds = array()) {
		$success = false; $message=array(); $response=array();
		$options = array(
			'fields'=>array('`Asset`.id','`BestShotSystem`.`asset_id`','`BestShotSystem`.`id`'),
			'conditions'=>array('`Shot`.id'=>$shotIds),
			'order'=>array('`SharedEdit`.score DESC', '`Asset`.dateTaken ASC'),	
			'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>'Groupshot', 
				'show_hidden_shots'=>true, 
			),
		);
		$Asset = $this->AssetsGroupshot->Asset;
		if ($Asset->Behaviors->attached('Permissionable')) {
			// permissionable does NOT obey recursive or containable()
			$options['permissionable'] = false;
		}
		$data = $Asset->find('all',$options);
// debug($data);		
		$topScoreAsset = $data[0];
		$model = 'BestGroupshotSystem';
		if (empty($topScoreAsset['BestShotSystem']['asset_id'])) {
			// set new BestShotSystem
			$insert[$model] = array(
				'groupshot_id'=>$topScoreAsset['Shot']['shot_id'],
				'asset_id' => $topScoreAsset['Asset']['id']
			);
			$ret = $this->{$model}->save($insert);
			if ($ret) {
				// return asset_id of new bestShot
				$response['updateBestShotSystem']['asset_id'] = $ret[$model]['asset_id'];
				$response['updateBestShotSystem']['changed'] = true;
				$message[] = 'Groupshot->updateBestShotSystem: OK. bestShotSystem changed';
				$success = true;
			} else {
				$message[] = 'Groupshot->updateBestShotSystem: error saving NEW bestShot';
				$success = false;
			}
		} else 	{
			// see if we should update bestShotSystem based on new top score
			if ($topScoreAsset['BestShotSystem']['asset_id'] != $topScoreAsset['Asset']['id']) {
				// new top score, update bestShotSystem
				// WARNING: not sure this code path is used.
				// removeFromShot>unShare seems to follow: Groupshot->updateBestShotSystem: OK. bestShotSystem changed
// debug($topScoreAsset);					
// debug("update bestShotSystem");				
				$this->{$model}->id = $topScoreAsset['BestShotSystem']['id'] ;
				$ret = $this->{$model}->savefield('asset_id', $topScoreAsset['Asset']['id']);
				if ($ret) {
					$response['updateBestShotSystem']['asset_id'] = $topScoreAsset['Asset']['id'];
					$response['updateBestShotSystem']['updateBestShotSystem']['changed'] = true;
					$message[] = 'Groupshot->updateBestShotSystem: OK. bestShotSystem UPDATED with new best score';
					$success = true;
				} else {
					$message[] = 'Groupshot->updateBestShotSystem: error UPDATING bestShotSystem with new best score';
					$success = false;
				}
			} else {
				// bestShot unchanged
				$response['updateBestShotSystem']['asset_id'] = $topScoreAsset['BestShotSystem']['asset_id'];
				$response['updateBestShotSystem']['changed'] = false;
				$message[] = 'Groupshot->updateBestShotSystem: OK. bestShotSystem NOT changed';
				$success = true;
			}			
		}
		return compact('success', 'message', 'response');
	}		
	
	
	/**
	 * set Cover photo/bestShot from Top Rated photos for user
	 *  - automatically lookup related shotIds for provided $assetIds;
	 * 	- BestShotMember/BestShotOwner determined on query, not insert
	 * @param $userId UUID - user who owns this bestshot selection, 
	 * @param $shotIds array of UUIDs
	 * @param $assetIds array of asset_ids 
	 * @return array of bestshots
	 */
	public function updateBestShotFromTopRated($userId, $shotIds=null, $assetIds=array()) {
		$shotIds = $shotIds ? $shotIds : array();
		if ($assetIds) {
			// get $shotId from $assetId
			$options = array(
				'fields'=>array('`AssetsGroupshot`.groupshot_id'),
				'conditions'=>array('`AssetsGroupshot`.asset_id'=>$assetIds),
			);
			$data = $this->AssetsGroupshot->find('all', $options);
			$new_shotIds = Set::extract('/AssetsGroupshot/groupshot_id', $data);				
			if ($new_shotIds) $shotIds = array_unique(array_merge($shotIds, $new_shotIds));
		}
		/*
		 * Q: do we just update bestshotMember? A: yes
		 * Q: When do we set bestshotSystem? A: when group is created, or shot removed
		 */
		
		if (!empty($shotIds)) {
			$options = array(
				'fields'=>array('`Asset`.id'),
				'conditions'=>array('`Shot`.id'=>$shotIds),
				'order'=>array('`Shot`.id', 'rating DESC','`SharedEdit`.score DESC', '`Asset`.dateTaken ASC'),
				'extras'=>array(
					'show_edits'=>true,
					'join_shots'=>'Groupshot', 
					'show_hidden_shots'=>true, 
				),
				'permissionable'=>true,	
			);
			$Asset = $this->AssetsGroupshot->Asset;
			$data = $Asset->find('all',$options);
			
			foreach ($shotIds as $shotId) {
				$best = Set::extract("/Asset[shot_id={$shotId}]/.[:first]", $data);
				// insert/update into best_usershots
				$bestshot['groupshot_id']=$best[0]['shot_id'];
				$bestshot['user_id']=$userId;
				$bestshot['asset_id']=$best[0]['id'];
				$batch[] = $bestshot;
			}
			foreach ($batch as $bestshot) {
				$VALUES[]= "( UUID(), '".implode("','", $bestshot)."', now() )";
			}
			$TABLE = 'best_groupshots';
			$VALUES = implode(', ',$VALUES);
			$INSERT_SQL="
INSERT INTO `{$TABLE}` (`id`, `groupshot_id`, `user_id`, `asset_id`, `modified`) VALUES 
{$VALUES}
ON DUPLICATE KEY UPDATE `asset_id`=VALUES(`asset_id`),  `modified`=VALUES(`modified`);			
";			
			$this->query($INSERT_SQL);
			return $batch;
		}
	}
		
	
	public function setBestshot($userId, $shotId, $assetId) {
		$TABLE = 'best_groupshots';
		$VALUES = "(  UUID(), '{$shotId}', '{$userId}', '{$assetId}', now() )";
		$INSERT_SQL="
INSERT INTO `{$TABLE}` (`id`, `groupshot_id`, `user_id`, `asset_id`, `modified`) VALUES 
{$VALUES}
ON DUPLICATE KEY UPDATE `asset_id`=VALUES(`asset_id`),  `modified`=VALUES(`modified`);
";			
		$this->query($INSERT_SQL);		
		return 1;
	}	
	
	
	/*
	 * returned data in the form of $data['Shot']['shot_id], $data['Shot']['count'], $data['AssetsShot']['asset_id]
	 * @params $aids, array of uuids
	 * */
	public function findShotsByAssetId($aids){
		$in_aids = implode("','", $aids);
		$SQL = "
SELECT Shot.id AS shot_id, Shot.assets_groupshot_count AS count, `AssetsShot`.asset_id
FROM groupshots as Shot
JOIN assets_groupshots as `AssetsShot` ON Shot.id = `AssetsShot`.groupshot_id
WHERE `AssetsShot`.asset_id IN ('{$in_aids}')
-- ORDER BY Shot.id
;";
		$shot_data = $this->query($SQL);
		return $shot_data;
	}
		
	
	/**
	 * updates counterCache for groupshots
	 * @param mixed array of uuids or string uuid, 1 shotId
	 */
	public function updateShotCounterCache($shotIds = array()) {
		$success = false; $message=array(); $response=array();
		if (!empty($shotIds)) {
			if (is_array($shotIds))	$WHERE = "WHERE  `InnerShot`.id IN ('" . join("','", $shotIds)  ."')";
			else  $WHERE = "WHERE  `InnerShot`.id ='{$shotIds}'";
		} else $WHERE = '';
		$updateSQL = "
UPDATE `groupshots` AS `Shot`
INNER JOIN (
	SELECT `InnerShot`.id AS shot_id, COUNT(`AssetsShots`.id ) as assets_groupshot_count
	FROM `groupshots` AS `InnerShot`
	INNER JOIN `assets_groupshots` AS `AssetsShots` ON (`AssetsShots`.groupshot_id = `InnerShot`.id)
	{$WHERE}
	GROUP BY shot_id
) AS `Count` ON (`Count`.shot_id = `Shot`.id)
SET `Shot`.assets_groupshot_count = `Count`.assets_groupshot_count;";
		$this->query($updateSQL);
		$success = true;
		$message[] = "Groupshot->updateShotCounterCache: OK";
		return compact('success', 'message', 'response');
	}	
	
	
}
?>