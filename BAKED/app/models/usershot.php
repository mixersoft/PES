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
	 * groupAsShot
	 * @param array $assetIds 
	 * @param string $owner_id - uuid of assets' owner
 	 * 		sets BestShotOwner when same as AppController::$userid
	 * @return array('shotId', 'bestshotId')  or FALSE on error
	 */
	public function groupAsShot ($assetIds, $owner_id = null) {
		$success = false; $message=array(); $response=array();
		
		$owner_id = $owner_id ? $owner_id : AppController::$userid;
		$Asset = $this->AssetsUsershot->Asset;
		$Asset->Behaviors->detach('Taggable');
		$Asset->contain();
		// filter $assetIds to check owner_id;
		$options = array(
			'fields'=>'`Asset`.id', 
			'conditions'=>array('`Asset`.id'=>$assetIds, '`Asset`.owner_id'=>$owner_id),
			'order'=>'`SharedEdit`.score DESC, `Asset`.dateTaken ASC',		
			'extras'=>array(
				'show_edits' => true,
				'join_shots'=>'Usershot', 
				'show_hidden_shots'=>true,
				'join_bestshot'=>false,
			),  //default
			// TODO: permissionable does NOT obey recursive or containable()
			'permissionable'=>false,	
		);
		$data = $Asset->find('all', $options);
		$assetIds = Set::extract('/Asset/id', $data);


		/*
		 *  remove $assetIds from any existing shots
		 */
		$deleteShotIds = array_filter(Set::extract($data, '/Asset/shot_id'));
		if (count($deleteShotIds))	{
			$hiddenShot_options = array(
				'fields'=>"`AssetsUsershot`.asset_id",
				'conditions'=>array('`AssetsUsershot`.usershot_id'=>$deleteShotIds),
			); 
			$Asset->AssetsUsershot = ClassRegistry::init('AssetsUsershot');
			$hiddenShot_data = $Asset->AssetsUsershot->find('all', $hiddenShot_options);
			$hiddenShotIds = Set::extract($hiddenShot_data, '/AssetsUsershot/asset_id');
	//debug($hiddenShotIds); 		
			$assetIds = array_unique(array_merge($assetIds, $hiddenShotIds));
//debug($assetIds);
		}


		// create Usershot
		// add assetIds to AssetsUsershot
		$insert = array();
		$insert['Usershot']['owner_id'] = $owner_id;
		foreach ($assetIds as $assetId) {
			$insert['AssetsUsershot'][]['asset_id'] = $assetId;
		}		
// debug($assetIds);
		if (count($assetIds)) {
			// set Bestshot for NEW group
			// set BestShotSystem by sort order, sort=='`SharedEdit`.score DESC, `Asset`.dateTaken ASC',
			// WARNING: assumes hiddenShot assets are ALL lower rated than visible shots
			$insert['BestUsershotSystem']['asset_id'] = $assetIds[0];
			// now sort by UserEdit.rating
			$byRating = Set::sort($data, '/Asset/rating', 'DESC');
			if ($owner_id == AppController::$userid) {
				// set BestUsershotOwner by UserEdit.rating	
				$bestshotAlias='BestUsershotOwner';
			} else {
				// set BestUsershotMember by UserEdit rating
				$bestshotAlias='BestUsershotMember';
			}
			$insert[$bestshotAlias]['asset_id'] = $byRating[0]['Asset']['id'];
			$insert[$bestshotAlias]['user_id'] = $owner_id;
			
			// save to AssetsUsershot, BestUsershot, etc.
			$ret = $this->saveAll($insert, array('validate'=>'first'));
		} 
// debug($insert); 
		if (isset($ret)) {
			$success = $ret != false;
			$message[] = 'Usershot->groupAsShot: OK';
			$response['groupAsShot']['shotId'] = $this->id;
			$response['groupAsShot']['bestshotId'] = $insert[$bestshotAlias]['asset_id'];
			$resp0 = compact('success', 'message', 'response');
			// after saving NEW shot, delete old shots
			if (!empty($deleteShotIds)) {
				// TODO: delete old/orphaned Shots using QUEUE
				$resp1 = $this->unGroupShot($deleteShotIds, $owner_id);
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
	 */
	public function unGroupShot ($deleteShotIds, $owner_id) {
		$success = false; $message=array(); $response=array();
		if (!empty($deleteShotIds)) {
			// TODO: delete old/orphaned Shots using QUEUE
			$sql_deleteCascadeShots = "
DELETE FROM `Shot`, `Best`, `AssetsShots`
USING `usershots` AS `Shot`
INNER JOIN `best_usershots` AS `Best` ON (`Best`.usershot_id = `Shot`.id)
INNER JOIN `assets_usershots` AS `AssetsShots` ON (`AssetsShots`.usershot_id = `Shot`.id)
WHERE `Shot`.owner_id = '{$owner_id}' AND `Shot`.id IN ";
			$sql_deleteCascadeShots .= "('".implode("','",$deleteShotIds)."')";
			$ret = $this->query($sql_deleteCascadeShots); // always true
		} 
		$success = true;
		$message[] = "Usershot->unGroupShot: OK";
		$response['unGroupShot']['shotIds'] = $deleteShotIds;
		return compact('success', 'message', 'response');
	}
	
	/**
	 * remove Asset from Shot, but keep shot
	 * @param array $assetIds, uuids should belong to shot 
	 * @param uuid $shotId
	 * @return aa array('success','bestShot')
	 */
	public function removeFromShot ($assetIds, $shotId, $owner_id) {
		$success = false; $message=array(); $response=array();
		if (!empty($assetIds)) {
			$assetIds_IN = "('" . join("','", $assetIds)  ."')"; 
			// TODO: delete old/orphaned Shots using QUEUE
			$sql_removeFromShot = "
DELETE FROM `Best`, `AssetsShots`
USING `usershots` AS `Shot`
INNER JOIN `assets_usershots` AS `AssetsShots` ON (`AssetsShots`.usershot_id = `Shot`.id 
	AND `AssetsShots`.asset_id IN {$assetIds_IN} )
LEFT JOIN `best_usershots` AS `Best` ON (`Best`.usershot_id = `Shot`.id
	AND `Best`.asset_id = `AssetsShots`.asset_id  )
WHERE `Shot`.id = '{$shotId}' AND `Shot`.owner_id = '{$owner_id}'";
			$ret = $this->query($sql_removeFromShot); // always true
			$response['removeFromShot']['assetIds'] = $assetIds;
			$message[] = 'Usershot->removeFromShot: OK';
			$success = true;
			$resp0 = compact('success', 'message', 'response');
						
			/*
			 *  update Shot.assets_usershot_count
			 */
			$resp1 = $this->updateShotCounterCache($shotId);
			$success = $success && $resp1['success'];
			
			/*
			 *  update Best, if best was removed
			 */
			$resp2 = $this->updateBestShotSystem($shotId);
			$success = $success && $resp2['success'];
			
			$resp0 = Set::merge(compact('success', 'message', 'response'), $resp1, $resp2);
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
	 * @param $shotIds array of Shot.id
	 * @return aa  array('asset_id', 'changed') 
	 */
	public function updateBestShotSystem($shotIds = array()) {
		$success = false; $message=array(); $response=array();
		$options = array(
			'fields'=>array('`Asset`.id','`BestShotSystem`.`id`','`BestShotSystem`.`asset_id`','`BestShotSystem`.`id`'),
			'conditions'=>array('`Shot`.id'=>$shotIds),
			'order'=>'`SharedEdit`.score DESC, `Asset`.dateTaken ASC',	
//			'showEdits'=>true,
			'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>'Usershot', 
				'show_hidden_shots'=>true, 
			),
			'permissionable'=>false,	
		);
		$Asset = $this->AssetsUsershot->Asset;
		$Asset->Behaviors->detach('Taggable');
		$data = $Asset->find('all',$options);
		$topScoreAsset = $data[0];
		$model = 'BestUsershotSystem';
		if (empty($topScoreAsset['BestShotSystem']['asset_id'])) {
			// set new BestShotSystem
			$insert[$model] = array(
				'usershot_id'=>$topScoreAsset['Shot']['shot_id'],
				'asset_id' => $topScoreAsset['Asset']['id']
			);
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
				'order'=>'`Shot`.id, rating DESC, `SharedEdit`.score DESC, `Asset`.dateTaken ASC',	
				'extras'=>array(
					'show_edits'=>true,
					'join_shots'=>'Usershot', 
					'show_hidden_shots'=>true, 
				),
				'permissionable'=>true,	
			);
			$Asset = $this->AssetsUsershot->Asset;
			$Asset->Behaviors->detach('Taggable');
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
	INNER JOIN `assets_usershots` AS `AssetsShots` ON (`AssetsShots`.usershot_id = `InnerShot`.id)
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