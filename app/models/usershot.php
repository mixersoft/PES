<?php
class Usershot extends AppModel {
	public $name = 'Usershot';
	public $table = 'usershots';
	
	public $belongsTo = array(
		'Owner' => array(								// User hasMany Usershots
			'className' => 'User',
			'foreignKey' => 'owner_id', 	
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),	
	);
	public $hasOne = array(				
		'BestUsershotSystem' => array(					// ?-BestUsershotSystem belongsTo Usershots
			'className' => 'BestUsershot',
			'foreignKey' => 'usershot_id',
			'conditions' => array("`BestUsershotSystem`.user_id IS NULL"),
			'dependent' => false,
		),
		'BestUsershotOwner' => array(					// BestUsershotOwner belongsTo Usershot
			'className' => 'BestUsershot',
			'foreignKey' => 'usershot_id',
//			'conditions' => array("`BestUsershotOwner`.user_id"=>'Asset.owner_id'),
			'dependent' => false,
		),	
				
	);		
	public $hasMany = array(
		'AssetsUsershot' => array(						// AssetsUsershot belongsTo Usershot 
			'className' => 'AssetsUsershot',
			'foreignKey' => 'usershot_id',
			'dependent' => true,
//			'conditions' => '',
//			'fields' => '',
//			'order' => '',
//			'limit' => '',
//			'offset' => '',
//			'exclusive' => '',
//			'finderQuery' => '',
//			'counterQuery' => ''
		),
		'BestUsershotMember' => array(					// BestUsershotMember belongsTo Usershot
			'className' => 'BestUsershot',
			'foreignKey' => 'usershot_id',
			'dependent' => true,
		),		
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
	 * 
	 * TODO:  deprecate, clean up hack, or deprecate
	 * Problem: Workorder (WMS) access is /workorders/photos/[woid], NOT /person/photos/[owner_id]
	 * Hack: check that Assets are from the SAME owner, Asset.owner_id are ALL THE SAME
	 * 		then use Asset.owner_id as $owner_id
	 * TODO: we should really check if Asset.owner_id == Workorder.source_id
	 * @params $assetIds array of Asset ids
	 * @params $owner_id default value
	 */
	private function _getOwnerIdForWorkorderProcessing($assetIds, $owner_id){
		$options = array(
			'fields'=>array('`Asset`.id','`Asset`.owner_id'), 
			'conditions'=>array('`Asset`.id'=>$assetIds),
			'order'=>array('`SharedEdit`.score DESC', '`Asset`.dateTaken ASC'),			
			'extras'=>array(
				'show_edits' => true,
				'join_shots'=>'Usershot', 
				'show_hidden_shots'=>true,
				'join_bestshot'=>false,
			),  //default
		);
		$Asset = $this->AssetsUsershot->Asset;
		if ($Asset->Behaviors->attached('WorkorderPermissionable')) {
			// TODO: match with Asset::joinWithShots()
			$checkdata = $Asset->find('all', $options);
// debug($checkdata);			
			// check that all Assets have same owner_id
			$ownerIds = array_unique(Set::extract('/Asset/owner_id', $checkdata));
			if (count($ownerIds) == 1) {
				return array_pop($ownerIds);
			} else return false;
		} else return $owner_id;	// default value
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
	 * 		group assetIds as a Usershot
	 * 		check Usershot.priority for permission to deactivate/replace existing shots
	 * 		include all hiddenShots (i.e. Assets) in new Usershot
	 * 	NOTES: assumes only bestshots are visible in UI and submitted 
	 * 		only considers score of submitted assetIds for new bestshot
	 * 	WARNING: if user manually sets existing bestshot to a low scoring photo, it may
	 * 		not become a NEW bestshot, despite higher Useredit.rating
	 * @param $assetIds array $assetIds 
	 * @param $isBestshot boolean, default false. if true, then 
	 * 		we assume the user was adding a bestshot to new Shot, find/add hiddenshots	
	 * 
	 * @return standard JSON response format
	 */
	public function groupAsShot ($assetIds, $isBestshot=false) {
		$success = false; $message=array(); $response=array();
		$submitted = $assetIds;	// for AssetPermission check
		$isActive = true;						// set to false if needed for $force=true
		$Asset = $this->AssetsUsershot->Asset;
		
		$options = array(
			'fields'=>array('DISTINCT `Asset`.id','`Asset`.owner_id'), 
			'conditions'=>array('`Asset`.id'=>$assetIds),
			'order'=>array('`SharedEdit`.score DESC', '`Asset`.dateTaken ASC'),		
			'extras'=>array(
				'show_edits' => true,
				'join_shots'=>'Usershot', 
				'show_hidden_shots'=>true,		// Asset.id != Bestshot.asset_id
				'show_inactive_shots'=>false,	//do NOT change inactive shots, i.e. dupe, removeFrom, etc.
				'join_bestshot'=>false,
			),  //default
		);
		
		$data = $Asset->find('all', $options);	
		$existing_shots = array_filter(Set::combine($data, '/Shot/shot_id', '/Shot/shot_priority'));
		$permitted_AssetIds = array_unique(Set::extract('/Asset/id', $data)); // aids sorted by SharedEdit.score DESC in bestshot order
// debug($options);		
// debug($data);		
// debug("FOUND EXISTING SHOTS:  ".print_r($existing_shots,true));		
// debug($permitted_AssetIds);
		$no_permissions = array_diff($assetIds, $permitted_AssetIds);
		// check permissions on submitted Assets, unless role=SCRIPT
		if (AppController::$role !=='SCRIPT' && count($no_permissions)) {
			// throw new Exception('Error: No Permission on the following assetIds, aids='.print_r(array_diff($assetIds,$permitted_AssetIds),true)); 		
			$message[] = 'Error: No Permission on the following assetIds';
			$response['asset_ids'] = $no_permissions;
			return compact('success', 'message', 'response');
		}
		// check for same Asset.owner_id
		$owner_ids = array_unique(Set::extract('/Asset/owner_id', $data));
		if (count($owner_ids)!=1) {
			$message[] = 'Error: group Photos do not belong to the same owner';
			$response['Asset.owner_ids'] = $owner_ids;
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
debug("relative priority={$relative_priority}");		 	
		 	if ($relative_priority > 0) {  // new shot HIGHER priority
				if ($isBestshot) {
					// find & add hiddenShots, active only
					$merge_hiddenshots[] = $shot_id;
					$cleanup['deactivateShots'][] = $shot_id;
				} else {
					// show_hidden_shots: add remainder as new shot at SAME priority 
					// TODO: should use same logic on removeFromShot
					// 		or just remove from lower priority shot
					$cleanup['duplicateShots'][$shot_priority] = $shot_id;		// at $shot_priority == new priority
				}
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
					'fields'=>array('`Usershot`.id', '`Usershot`.active'),
					'conditions'=>array(
						'`Usershot`.id'=>$merge_hiddenshots,
						'`Usershot`.active'=>1,
					),
					'contain'=>array('AssetsUsershot.asset_id'),
				);
				$hiddenShot_data = $this->find('all', $hiddenShot_options);
				$hiddenShot_assetIds = Set::extract($hiddenShot_data, '/AssetsUsershot/asset_id');
// debug($merge_hiddenshots); 				
// debug($hiddenShot_options); 
// debug($hiddenShot_data);
// debug($hiddenShot_assetIds); 		// NOTE: this array is NOT unique		
				$permitted_AssetIds = array_unique(array_merge($permitted_AssetIds, $hiddenShot_assetIds));
		} 

		// create Usershot
		// add assetIds to AssetsUsershot
		$insert = $this->create();
		$insert['Usershot']['owner_id'] = AppController::$userid; 
		$insert['Usershot']['priority'] = $shot_priority; 
		$insert['Usershot']['active'] = $isActive; 
		foreach ($permitted_AssetIds as $assetId) {
			$insert['AssetsUsershot'][]['asset_id'] = $assetId;
		}		
// debug($permitted_AssetIds);
		if (count($permitted_AssetIds)) {
			// set Bestshot for NEW group
			// set BestShotSystem by sort order, sort=='`SharedEdit`.score DESC, `Asset`.dateTaken ASC', 
			// for *visible* assets only, ignores hiddenShot assets 
			// EDITORS will set only BestShotSystem by default
			$insert['BestUsershotSystem']['asset_id'] = $permitted_AssetIds[0];
			if (false == $Asset->Behaviors->attached('WorkorderPermissionable')) {
				// now sort by UserEdit.rating, then SharedEdit.score DESC
				if (in_array(AppController::$userid, $owner_ids))
				{
					// set BestUsershotOwner by UserEdit.rating	
					$bestshotAlias='BestUsershotOwner';
				} else {
					// set BestUsershotMember by UserEdit rating
					$bestshotAlias='BestUsershotMember';
				}
				$insert[$bestshotAlias]['asset_id'] = $this->_getTopRatedByRatingScore($data);
				$insert[$bestshotAlias]['user_id'] = AppController::$userid;
			}
			
debug($cleanup);
debug($insert); 
// exit;		// stop before actual insert
			
			
			// save to AssetsUsershot, BestUsershot, etc.
			$ret = $this->saveAll($insert, array('validate'=>'first'));
		} 
		if (isset($ret)) {
			$success = $ret != false;
			$message[] = 'Usershot->groupAsShot: OK';
			$response['groupAsShot']['shotId'] = $this->id;
			$response['groupAsShot']['check_existing_shotIds'] = $existing_shots;
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
			if (!empty($cleanup['duplicateShots'])) {
				// TODO: duplicate lower priority shot at new priority AND remove assets from duplicates
				// original shot is deactivated
				$resp1 = $this->_duplicateShot($cleanup['duplicateShots'], $isActive);
debug($resp1);				
				if ($resp1['success']) {
					// queue remove groupAsShot assets from duplicateShots
					$cleanup['removeFromShots'] = Set::merge($cleanup['removeFromShots'], $resp1['response']['duplicateShot']['newShotIds']);
				}
				$success = $success && $resp1['success'];
				$resp0 = Set::merge($resp0, $resp1);
				
debug($cleanup);	
			
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
	 * Delete Shot and related AssetsShots, BestShots
	 * @param array $deleteShotIds array of UUIDs
	 * @return false on error
	 */
	public function unGroupShot ($deleteShotIds) {
		if (!is_array($deleteShotIds)) throw new Exception('Error: $deleteShotIds should be an Array()');
		$success = false; $message=array(); $response=array();
		if (!empty($deleteShotIds)) {
			
			/*
			 * Confirm unGroup Shot privilege and priority, do we have permission to edit existing Shot?
			 * 	higher priority can deactivate lower priority shots
			 * 	equal priority will replace shots
			 */ 
			 $shot_priority = $this->_get_ShotPriority();
			 $data = $this->find('all', array('conditions'=>array('`Usershot`.id'=>$deleteShotIds)));
debug($data);			 
			 foreach ($data as $row) {
			 	$shot_id = $row['Usershot']['id'];
			 	$old_priority = $row['Usershot']['priority'];
			 	if ($old_priority < $shot_priority) {
			 		// current role has lower priority, no privilege to change existing Shot
			 		// no privilege or save as "lower" privilege???
			 		$message = "Error: Current role has lower priority, no privilege to change existing Shot, role=".AppController::$role;
					$response['existing_shots'][] = array_filter_keys($row['Usershot'], array('id', 'owner_id', 'priority'));				
			 		return compact('success', 'message', 'response'); 
			 	}
			 	if ($old_priority == $shot_priority) $cleanup['unGroupShots'][] = $shot_id;
				if ($old_priority > $shot_priority) $cleanup['deactivateShots'][] = $shot_id;
				
				// only owner_id can remove from a USER created Usershot
				if ($old_priority == $this->_get_ShotPriority('USER')
					&& AppController::$userid!=$row['Usershot']['owner_id']) 
				{
					$message = "Error: no privilege to remove from this Shot, Shot.owner_id={$row['Usershot']['owner_id']}";
					$response['Usershot'] = array_filter_keys($row['Usershot'], array('id', 'owner_id', 'priority'));
					return compact('success', 'message', 'response');
				}
				
			 } 
debug($cleanup);
			// TODO: delete old/orphaned Shots using QUEUE
			if (!empty($cleanup['unGroupShots'])) {
				$sql_deleteCascadeShots = "
	DELETE FROM `Shot`, `Best`, `AssetsShots`
	USING `usershots` AS `Shot`
	INNER JOIN `best_usershots` AS `Best` ON (`Best`.usershot_id = `Shot`.id)
	INNER JOIN `assets_usershots` AS `AssetsShots` ON (`AssetsShots`.usershot_id = `Shot`.id)
	WHERE `Shot`.id IN ";
				$sql_deleteCascadeShots .= "('".implode("','",$cleanup['unGroupShots'])."')";
				$ret = $this->query($sql_deleteCascadeShots); // always true
				$message[] = "Usershot->unGroupShot: OK";
				$response['unGroupShot']['shotIds'] = $deleteShotIds;
			}
			$success = true;
			$resp0 = compact('success', 'message', 'response');
			if (!empty($cleanup['deactivateShots'])) {
				// TODO: deactivate old Shots using QUEUE
				$resp1 = $this->_deactivateShot($cleanup['deactivateShots']);
				$success = $success && $resp1['success'];
				$resp0 = Set::merge($resp0, $resp1);
			}	
		} 
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
			$sql_deactivateShots = "UPDATE `usershots` AS `Shot` SET `Shot`.active=0 WHERE `Shot`.id IN ('".implode("','",$deactivateShotIds)."')";
			$ret = $this->query($sql_deactivateShots); // always true
		} 
		$success = true;
		$message[] = "Usershot->deactivateShot: OK";
		$response['deactivateShot']['shotIds'] = $deactivateShotIds;
		return compact('success', 'message', 'response');
	}
	/**
	 * Duplicate Shots, 
	 * 	duplicate shot with new owner/priority 
	 *  set usershots.active=0 on old shot,
	 * @param array $duplicateShotIds array of $priority=>UUIDs
	 * @return standard JSON response 
	 */
	private function _duplicateShot ($duplicateShotIds, $isActive = false) {
		if (!is_array($duplicateShotIds)) throw new Exception('Error: $deactivateShotIds should be an Array()');
		$success = true; $message=array(); $response=array();
		if (!empty($duplicateShotIds)) {
debug($duplicateShotIds);			
			$Asset = $this->AssetsUsershot->Asset;
			foreach($duplicateShotIds as $shot_priority=>$old_shotId) {
				$options = array(
					'permisssionable'=>false,
					'fields'=>array('DISTINCT `Asset`.id','`Asset`.owner_id'), 
					'conditions'=>array('`AssetsUsershot`.usershot_id'=>$old_shotId),
					'order'=>array('`SharedEdit`.score DESC', '`Asset`.dateTaken ASC'),		
					'extras'=>array(
						'show_edits' => true,
						'join_shots'=>'Usershot', 
						'show_hidden_shots'=>true,		// Asset.id != Bestshot.asset_id
						'show_inactive_shots'=>true,	// process for ALL shots, including inactive
						'join_bestshot'=>false,
					),  //default
				);
				
				$data = $Asset->find('all', $options);	
				// $existing_shots = array_filter(Set::combine($data, '/Shot/shot_id', '/Shot/shot_priority'));
				$permitted_AssetIds = array_unique(Set::extract('/Asset/id', $data)); // aids sorted by SharedEdit.score DESC in bestshot order
	
				// add assetIds to AssetsUsershot
				$insert = $this->create();
				$insert['Usershot']['owner_id'] = AppController::$userid; 
				$insert['Usershot']['priority'] = $shot_priority; 
				$insert['Usershot']['active'] = $isActive; 
				foreach ($permitted_AssetIds as $assetId) {
					$insert['AssetsUsershot'][]['asset_id'] = $assetId;
				}		
		// debug($permitted_AssetIds);
				if (count($permitted_AssetIds)) {
					// set Bestshot for NEW group
					// set BestShotSystem by sort order, sort=='`SharedEdit`.score DESC, `Asset`.dateTaken ASC', 
					// for *visible* assets only, ignores hiddenShot assets 
					// EDITORS will set only BestShotSystem by default
					$insert['BestUsershotSystem']['asset_id'] = $permitted_AssetIds[0];
					if (false == $Asset->Behaviors->attached('WorkorderPermissionable')) {
						// now sort by UserEdit.rating, then SharedEdit.score DESC
						if (in_array(AppController::$userid, $owner_ids))
						{
							// set BestUsershotOwner by UserEdit.rating	
							$bestshotAlias='BestUsershotOwner';
						} else {
							// set BestUsershotMember by UserEdit rating
							$bestshotAlias='BestUsershotMember';
						}
						$insert[$bestshotAlias]['asset_id'] = $this->_getTopRatedByRatingScore($data);
						$insert[$bestshotAlias]['user_id'] = AppController::$userid;
					}
					
					// save to AssetsUsershot, BestUsershot, etc.
					$success = $success && $this->saveAll($insert, array('validate'=>'first'));
					if ($success) {
						$newShotIds[] = $this->getLastInsertId();
					} else {
						// insert failed
						throw new Exception("Error saving _duplicate Shot", 1);
					}
					
				} 
			}
		} 
		$message[] = "Usershot->duplicateShot: OK";
		$response['duplicateShot']['newShotIds'] = $newShotIds;
		$resp0 = compact('success', 'message', 'response');
		if ($isActive) {
			// deactivate original shots
			$resp1 = $this->_deactivateShot($duplicateShotIds);
			$success = $success && $resp1['success'];
			$resp0 = Set::merge($resp0, $resp1);
		}
		// NOTE: remove from NEW shots, not original in calling method
		return $resp0;
	}	
		
	/**
	 * remove Asset from Shot, but keep shot
	 * 		check Usershot.priority for privileges
	 * 		if role=USER, only Usershot.owner_id can remove shots
	 * 	WARNING: if you call from $cleanup['removeFromShots'], 
	 * 		then you may be left with a Shot with just 1 item
	 * 		this case is usually detected in JS and ungroupShot called instead
	 * @param array $assetIds, uuids should belong to shot 
	 * @param mixed, uuid or array of uuids, $shotId
	 * @return standard JSON response 
	 */
	public function removeFromShot ($assetIds, $shotIds) {
		if (!is_array($assetIds)) throw new Exception('Error: $assetIds should be an Array()');
		if (!is_array($shotIds)) $shotIds = array($shotIds);
		$success = false; $message=array(); $response=array();
		if (!empty($assetIds)) {
			
			// TODO: check Permissionable or WorkorderPermissionable on submitted Assets
					
			/*
			 * Confirm edit Shot priority, do we have permission to edit existing Shot?
			 * 	higher priority can deactivate lower priority shots
			 * 	equal priority will replace shots
			 */ 
			$shot_priority = $this->_get_ShotPriority();
			$cleanup['removeFromShots'] = array();
			foreach ($shotIds as $shot_id) {
				$existing = $this->read(array('priority', 'owner_id'), $shot_id);
				$old_priority = $existing['Usershot']['priority'];
				$relative_priority = $old_priority - $shot_priority;  // lower number is higher priority
			 	if ($relative_priority > 0) {  // new shot HIGHER priority
			 		// duplicate lower priority shot at new priority THEN remove assets from duplicates
					// original, lower priority shot is deactivated
					$cleanup['duplicateShots'][$shot_priority] = $shot_id;		// at new priority
				} else if ($relative_priority == 0){
					$cleanup['removeFromShots'][] = $shot_id;
				} else {
					// no privilege to remove from higher priority shot
					$response['removeFromShots']['noPrivilege'][] = $shot_id;
					continue;
				}
			}
			$success = true;
			$resp0 = compact('success','message', 'response');
			if (!empty($cleanup['duplicateShots'])) {
				$resp1 = $this->_duplicateShot($cleanup['duplicateShots'], $isActive=true);
				if ($resp1['success']) {
					// queue remove assets from duplicateShots
					$cleanup['removeFromShots'] = Set::merge($cleanup['removeFromShots'], $resp1['response']['duplicateShot']['newShotIds']);
				}
				$success = $success && $resp1['success'];
				$resp0 = Set::merge($resp0, $resp1);
			}
			if (empty($cleanup['removeFromShots'])) {
				$success = false;
				$message[] = "No privilege to removeFromShot";
				return compact('success', 'message', 'response');
			}
			
			// TODO: delete old/orphaned Shots using QUEUE
			$assetIds_IN = "('" . join("','", $assetIds)  ."')";
			$sql_removeFromShot = "
DELETE FROM `Best`, `AssetsShots`
USING `usershots` AS `Shot`
INNER JOIN `assets_usershots` AS `AssetsShots` ON (`AssetsShots`.usershot_id = `Shot`.id 
	AND `AssetsShots`.asset_id IN {$assetIds_IN} )
LEFT JOIN `best_usershots` AS `Best` ON (`Best`.usershot_id = `Shot`.id
	AND `Best`.asset_id = `AssetsShots`.asset_id  )
";
			$shotIds_IN = "('" . join("','", $cleanup['removeFromShots'])  ."')";
			$WHERE = "WHERE `Shot`.id IN {$shotIds_IN}";
			$sql_removeFromShot .= ' '.$WHERE;
			
			$ret = $this->query($sql_removeFromShot); // always true
			$response['removeFromShot']['shotIds'] = $cleanup['removeFromShots'];
			$response['removeFromShot']['assetIds'] = $assetIds;
			$message[] = 'Usershot->removeFromShot: OK';
			$success = true;
			$resp1 = compact('success', 'message', 'response');
			$resp0 = Set::merge($resp0, $resp1);
						
			/*
			 *  update Shot.assets_usershot_count
			 */
			$resp1 = $this->updateShotCounterCache($cleanup['removeFromShots']);
			$success = $success && $resp1['success'];
			
			/*
			 *  update Best, if best was removed
			 */
			$resp2 = $this->updateBestShotSystem($cleanup['removeFromShots']);
			$success = $success && $resp2['success'];
			
			$resp0 = Set::merge($resp0, $resp1, $resp2);
			$resp0['success'] = $success;
		} else {
			$message[] = 'Usershot->removeFromShot: no asset_ids provided';
			$resp0 = compact('success', 'message', 'response'); 
		}
		return 	$resp0;	
	}
	/**
	 * update bestShotSystem for $shotIds using top score
	 * 	called from removeFromShot
	 * 	NOTE: does NOT update bestShotMember or bestShotUser. this
	 * 		- also check email for script: "SQL: update BestShotSystem using TEMP table to mark Bestshot and TopRated"" 
	 * @param $shotIds array of Shot.id
	 * @return aa  array('asset_id', 'changed') 
	 */
	public function updateBestShotSystem($shotIds = array()) {
		$success = false; $message=array(); $response=array();
		$options = array(
			'permissionable'=>false,
			'fields'=>array('`Asset`.id','`BestShotSystem`.`id`','`BestShotSystem`.`asset_id`','`BestShotSystem`.`id`'),
			'conditions'=>array('`Shot`.id'=>$shotIds),
			'order'=>array('`SharedEdit`.score DESC', '`Asset`.dateTaken ASC'),
			'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>'Usershot', 
				'join_bestshot'=>true, 
				'show_hidden_shots'=>true, 
			),
		);
		$Asset = $this->AssetsUsershot->Asset;
		$data = $Asset->find('all',$options);
		if (empty($data)) {
			// Usershot contains no assets, probably everything was removed
			$message[] = 'Usershot->updateBestShotSystem: Shot was empty';
			$success = true;
			$response['Usershot.id'] = $shotIds;
			return compact('success', 'message', 'response');
		}
		$topScoreAsset = $data[0];
		$model = 'BestUsershotSystem';
		if (empty($topScoreAsset['BestShotSystem']['asset_id'])) {
			// set new BestShotSystem
			$insert[$model] = array(
				'usershot_id'=>$topScoreAsset['Shot']['shot_id'],
				'asset_id' => $topScoreAsset['Asset']['id']
			);
// debug($insert);			
			$ret = $this->{$model}->save($insert);
			if ($ret) {
				// return asset_id of new bestShot
				$response['updateBestShotSystem']['asset_id'] = $ret[$model]['asset_id'];
				$response['updateBestShotSystem']['changed'] = true;
				$message[] = 'Usershot->updateBestShotSystem: OK. bestShotSystem changed';
				$success = true;
			} else {
				$message[] = 'Usershot->updateBestShotSystem: error saving NEW bestShot';
				$success = false;
			}
		} else 	{
			// see if we should update bestShotSystem based on new top score
			if ($topScoreAsset['BestShotSystem']['asset_id'] != $topScoreAsset['Asset']['id']) {
				// new top score, update bestShotSystem
				// WARNING: not sure this code path is used.
				// removeFromShot>unShare seems to follow: Usershot->updateBestShotSystem: OK. bestShotSystem changed
				$this->{$model}->id = $topScoreAsset['BestShotSystem']['id'] ;
				$ret = $this->{$model}->savefield('asset_id', $topScoreAsset['Asset']['id']);
				if ($ret) {
					$response['updateBestShotSystem']['asset_id'] = $topScoreAsset['Asset']['id'];
					$response['updateBestShotSystem']['updateBestShotSystem']['changed'] = true;
					$message[] = 'Usershot->updateBestShotSystem: OK. bestShotSystem UPDATED with new best score';
					$success = true;
				} else {
					$message[] = 'Usershot->updateBestShotSystem: error UPDATING bestShotSystem with new best score';
					$success = false;
				}
			} else {
				// bestShot unchanged
				$response['updateBestShotSystem']['asset_id'] = $topScoreAsset['BestShotSystem']['asset_id'];
				$response['updateBestShotSystem']['changed'] = false;
				$message[] = 'Usershot->updateBestShotSystem: OK. bestShotSystem NOT changed';
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
				'fields'=>array('`AssetsUsershot`.usershot_id'),
				'conditions'=>array('`AssetsUsershot`.asset_id'=>$assetIds),
			);
			$data = $this->AssetsUsershot->find('all', $options);
			$new_shotIds = Set::extract('/AssetsUsershot/usershot_id', $data);				
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
					'join_shots'=>'Usershot',
					'join_bestshot'=>true,  		
					'show_hidden_shots'=>true, 
				),
				'permissionable'=>true,	
			);
			$Asset = $this->AssetsUsershot->Asset;
			$data = $Asset->find('all',$options);
			
			foreach ($shotIds as $shotId) {
				$best = Set::extract("/Asset[shot_id={$shotId}]/.[:first]", $data);
				// insert/update into best_usershots
				$bestshot['usershot_id']=$best[0]['shot_id'];
				$bestshot['user_id']=$userId;
				$bestshot['asset_id']=$best[0]['id'];
				$batch[] = $bestshot;
			}
			foreach ($batch as $bestshot) {
				$VALUES[]= "( UUID(), '".implode("','", $bestshot)."', now() )";
			}
			$TABLE = 'best_usershots';
			$INSERT_SQL="
INSERT INTO `{$TABLE}` (`id`, `usershot_id`, `user_id`, `asset_id`, `modified`) 
VALUES :chunk:
ON DUPLICATE KEY UPDATE `asset_id`=VALUES(`asset_id`),  `modified`=VALUES(`modified`);
";			
			$VALUES = insertByChunks($INSERT_SQL, $VALUES);		
			foreach ($VALUES as $chunk) {
				$INSERT = str_replace(':chunk:', $chunk, $INSERT_SQL);
				$this->query($INSERT);	
			}			
			return $batch;
		}
	}
	
	public function setBestshot($userId, $shotId, $assetId) {
		$TABLE = 'best_usershots';
		$VALUES = "(  UUID(), '{$shotId}', '{$userId}', '{$assetId}', now() )";
		$INSERT_SQL="
INSERT INTO `{$TABLE}` (`id`, `usershot_id`, `user_id`, `asset_id`, `modified`) VALUES 
{$VALUES}
ON DUPLICATE KEY UPDATE `asset_id`=VALUES(`asset_id`),  `modified`=VALUES(`modified`);
";			
		$this->query($INSERT_SQL);		
		return 1;
	}
	
	/**
	 * updates counterCache for usershots
	 * @param mixed array of uuids or string uuid, 1 shotId
	 */
	public function updateShotCounterCache($shotIds = array()) {
		$success = false; $message=array(); $response=array();
		if (!empty($shotIds)) {
			if (is_array($shotIds))	$WHERE = "WHERE  `InnerShot`.id IN ('" . join("','", $shotIds)  ."')";
			else  $WHERE = "WHERE  `InnerShot`.id ='{$shotIds}'";
		} else $WHERE = '';
		$updateSQL = "
UPDATE `usershots` AS `Shot`
INNER JOIN (
	SELECT `InnerShot`.id AS shot_id, COUNT(`AssetsShots`.id ) as assets_usershot_count
	FROM `usershots` AS `InnerShot`
	LEFT JOIN `assets_usershots` AS `AssetsShots` ON (`AssetsShots`.usershot_id = `InnerShot`.id)
	{$WHERE}
	GROUP BY shot_id
) AS `Count` ON (`Count`.shot_id = `Shot`.id)
SET `Shot`.assets_usershot_count = `Count`.assets_usershot_count;";
		$this->query($updateSQL);
		
		$success = true;
		$message[] = "Usershot->updateShotCounterCache: OK";
		return compact('success', 'message', 'response');
	}
	
	/**
	 * Copy usershots/best_usershots to groupshots/best_groupshots by assetIds
	 * NOTE: $replace==false
	 * 		by default, will only create groupshots for assets that are NOT already in a groupshot
	 * 		assets that are already in a groupshot will be left alone
	 * 
	 * @param string UUID of group
	 * @param array of $asset_id UUIDs
	 * @param boolean $replace default false
	 * 
	 * 
	 */
	public function copyToGroupshots($groupId, $assetIds, $replace = false) {
		if (!is_array($assetIds)) $assetIds = array($assetIds);
		$lookup = $newAssetIds = $VALUES_groupshots = $VALUES_assets_groupshots = $VALUES_best_groupshots = array();
		
		// NOTE: permission are active on SELECTs
		
		// get usershot_ids and asset_ids from that are NOT in assets_groupshots 
		$options = array(
			'conditions'=>array('`AssetsUsershot`.asset_id'=>$assetIds, 
				'`AssetsGroupshot`.asset_id IS NULL'
			),
			'fields'=> array('`AssetsUsershot`.usershot_id', '`AssetsUsershot`.asset_id'),
			'joins'=>array(
				array(
					'table'=>'assets_groupshots',
					'alias'=>'AssetsGroupshot',
					'type'=>'LEFT',
					'conditions'=>array(
						'`AssetsGroupshot`.asset_id = `AssetsUsershot`.asset_id'
					)
				)
			),
		);
		$data = $this->AssetsUsershot->find('all', $options);
		foreach ($data as $row) {
			$usershotId = $row['AssetsUsershot']['usershot_id'];
			if (!isset($lookup[$usershotId])) {
				$groupshotId = String::uuid();
				$lookup[$usershotId] = $groupshotId;
				$VALUES_groupshots[] = "( '{$groupshotId}', '{$groupId}', now() )";
			}
			$newAssetIds[] = $row['AssetsUsershot']['asset_id'];
		}
		
//$this->log($lookup, LOG_DEBUG);		
		if (count($VALUES_groupshots)) {
			$INSERT_groupshots = "INSERT INTO `groupshots` (id, group_id, modified) VALUES :chunk: ;";
			$VALUES = insertByChunks($INSERT_groupshots, $VALUES_groupshots);		
			foreach ($VALUES as $chunk) {
				$INSERT = str_replace(':chunk:', $chunk, $INSERT_groupshots);
				$this->query($INSERT);	
			}		
//$this->log($INSERT_groupshots, LOG_DEBUG);
		}		
		
		// get assets from assets_usershots
		$usershotIds = array_keys($lookup);
		$options = array(
			'conditions'=>array(
//				'`AssetsUsershot`.usershot_id'=>$usershotIds,
				'`AssetsUsershot`.asset_id'=>$newAssetIds, 
			),
			'fields'=> array('`AssetsUsershot`.asset_id', '`AssetsUsershot`.usershot_id'),
		);
		$data = $this->AssetsUsershot->find('all', $options);
		foreach ($data as $row) {
			$groupshotId = $lookup[$row['AssetsUsershot']['usershot_id']];
			$VALUES_assets_groupshots[] = "( UUID(), '{$row['AssetsUsershot']['asset_id']}', '{$groupshotId}' )";
		}
		if (count($VALUES_assets_groupshots)) {
			// TODO: what about duplicate keys?		
			$INSERT_assets_groupshots = "INSERT INTO `assets_groupshots` (id, asset_id, groupshot_id) VALUES :chunk: ;";
			$VALUES = insertByChunks($INSERT_assets_groupshots, $VALUES_assets_groupshots);		
			foreach ($VALUES as $chunk) {
				$INSERT = str_replace(':chunk:', $chunk, $INSERT_assets_groupshots);
				$this->query($INSERT);	
			}		
	//$this->log($INSERT_assets_groupshots, LOG_DEBUG);
		}
		// copy bestshots
		$options = array(
			'conditions'=>array(
				'`BestUsershot`.asset_id'=>$newAssetIds,
//				'`BestUsershot`.usershot_id'=>$usershotIds,		// required for BestGroupshots
			),
			'fields'=> array('`BestUsershot`.asset_id', '`BestUsershot`.usershot_id', '`BestUsershot`.user_id'),
		);
		$BestUsershot = ClassRegistry::init('BestUsershot');
		$data = $BestUsershot->find('all', $options);	
		foreach ($data as $row) {
			$groupshotId = $lookup[$row['BestUsershot']['usershot_id']];
			$userid = "'{$row['BestUsershot']['user_id']}'";
			if ($userid=="''") $userid = "null";
			$VALUES_best_groupshots[] = "( UUID(), '{$row['BestUsershot']['asset_id']}', '{$groupshotId}', {$userid}, now() )";
		}		
		if (count($VALUES_best_groupshots)) {	
			$INSERT_best_groupshots = "INSERT INTO `best_groupshots` (id, asset_id, groupshot_id, user_id, modified) VALUES :chunk: ;";
			$VALUES = insertByChunks($INSERT_best_groupshots, $VALUES_best_groupshots);		
			foreach ($VALUES as $chunk) {
				$INSERT = str_replace(':chunk:', $chunk, $INSERT_best_groupshots);
				$this->query($INSERT);	
			}
	//$this->log($INSERT_best_groupshots, LOG_DEBUG);
		}		
		
		$this->updateGroupshotCounterByGroupId($groupId);
		return 1;
	}

	/*
	 * returned data in the form of $data['Shot']['shot_id], $data['Shot']['count'], $data['AssetsShot']['asset_id]
	 * @params $aids, array of uuids
	 * */
	public function findShotsByAssetId($aids){
		$in_aids = implode("','", $aids);
		$SQL = "
SELECT Shot.id AS shot_id, Shot.assets_usershot_count AS count, `AssetsShot`.asset_id
FROM usershots as Shot
JOIN assets_usershots as `AssetsShot` ON Shot.id = `AssetsShot`.usershot_id
WHERE `AssetsShot`.asset_id IN ('{$in_aids}')
-- ORDER BY Shot.id
;";
		$shot_data = $this->query($SQL);
		return $shot_data;
	}
	
	function updateGroupshotCounterByGroupId($groupId) {
		$UPDATE = "
UPDATE `groupshots`
LEFT JOIN (
  SELECT gs.id, gs.assets_groupshot_count AS old_count, COUNT(ags.asset_id) AS assets_groupshot_count
  FROM `groupshots` gs
  LEFT JOIN assets_groupshots ags ON gs.id = ags.groupshot_id
  WHERE gs.group_id='{$groupId}'
  GROUP BY gs.id
  HAVING (old_count <> COUNT(ags.asset_id) OR old_count IS NULL)
) AS ags ON groupshots.id = ags.id
SET groupshots.assets_groupshot_count=ags.assets_groupshot_count
WHERE groupshots.id = ags.id;		
		";
		$this->query($UPDATE);
		return 1;
	}	
	
}
?>