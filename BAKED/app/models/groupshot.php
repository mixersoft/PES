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
	/**
	 * groupAsShot
	 * @param array $assetIds 
	 * @param $group_id - uuid of group, FK for groupshots
	 * @param string $owner_id - uuid of group owner, 
	 * 		sets BestShotOwner when same as AppController::$ownerid 
	 * @return array('shotId', 'bestshotId')  or FALSE on error
	 */
	public function groupAsShot ($assetIds, $group_id, $owner_id = null) {
		$success = false; $message=array(); $response=array();
		
		$owner_id = $owner_id ? $owner_id : AppController::$ownerid;
		$Asset = $this->AssetsGroupshot->Asset;
		$Asset->Behaviors->detach('Taggable');
//		$Asset->contain('AssetsGroup.group_id');
		// filter $assetIds to check group_id;
		$options = array(
			'fields'=>'`Asset`.id', 
			'conditions'=>array('`Asset`.id'=>$assetIds),
			'contain'=>array(
				'AssetsGroup'=>array('fields'=>'`AssetsGroup`.group_id',
					'conditions'=>array('`AssetsGroup`.group_id'=>$group_id)),
			),
			'recursive' => 2,
			'order'=>'`SharedEdit`.score DESC, `Asset`.dateTaken ASC',		
//			'hideHiddenShots'=>false,	// deprecated
//			'showEdits'=>true,
			'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>'Groupshot', 		
				'show_hidden_shots'=>true,
				'join_bestshot'=>false,
			),
			// TODO: permissionable does NOT obey recursive or containable()
			// TODO: not sure if we can ignore permissionable in Groups?
			'permissionable'=>false,	
		);
		$data = $Asset->find('all', $options);
		$assetIds = Set::extract($data, '/Asset/id');
	
		/*
		 *  remove $assetIds from any existing shots
		 */
		$deleteShotIds = array_filter(Set::extract($data, '/Asset/shot_id'));
//debug($deleteShotIds); 
		if (count($deleteShotIds))	{
			$hiddenShot_options = array(
				'fields'=>"`AssetsGroupshot`.asset_id",
				'conditions'=>array('`AssetsGroupshot`.groupshot_id'=>$deleteShotIds),
			); 
			$hiddenShot_data = $Asset->AssetsGroupshot->find('all', $hiddenShot_options);
			$hiddenShotIds = Set::extract($hiddenShot_data, '/AssetsGroupshot/asset_id');
			$assetIds = array_unique(array_merge($assetIds, $hiddenShotIds));
//debug($assetIds);
		}
		
		// create Groupshot
		// add assetIds to AssetsGroupshot
		$insert = array();
		$insert['Groupshot']['group_id'] = $group_id;
		foreach ($assetIds as $assetId) {
			$insert['AssetsGroupshot'][]['asset_id'] = $assetId;
		}		
		if (count($assetIds)) {
			// set BestGroupshotSystem by sort order, sort=='`SharedEdit`.score DESC, `Asset`.dateTaken ASC',
			$insert['BestGroupshotSystem']['asset_id'] = $assetIds[0];
			// now sort by UserEdit.rating
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
			
			// save to DB
			$ret = $this->saveAll($insert, array('validate'=>'first'));
		}
//debug($insert); 
		if (isset($ret)) {
			$success = $ret != false;
			$message[] = 'Groupshot->groupAsShot: OK';
			$response['groupAsShot']['shotId'] = $this->id;
			$response['groupAsShot']['bestshotId'] = $insert[$bestshotAlias]['asset_id'];
			$resp0 = compact('success', 'message', 'response');
			// after saving NEW shot, delete old shots
			if (!empty($deleteShotIds)) {
				// TODO: delete old/orphaned Shots using QUEUE
				$resp1 = $this->unGroupShot($deleteShotIds);
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
	 * TODO: should we check owner/permissions here? require either moderator/admin or group membership
	 */
	public function unGroupShot ($deleteShotIds) {
		$success = false; $message=array(); $response=array();
		if (!empty($deleteShotIds)) {
			// TODO: delete old/orphaned Shots using QUEUE
			$sql_deleteCascadeShots = "
DELETE FROM `Shot`, `Best`, `AssetsShots`
USING `groupshots` AS `Shot`
INNER JOIN `best_groupshots` AS `Best` ON (`Best`.groupshot_id = `Shot`.id)
INNER JOIN `assets_groupshots` AS `AssetsShots` ON (`AssetsShots`.groupshot_id = `Shot`.id)
WHERE Shot.id IN ";
			$sql_deleteCascadeShots .= "('".implode("','",$deleteShotIds)."')";
			$ret = $this->query($sql_deleteCascadeShots);
		} 
		$success = true;
		$message[] = "Groupshot->unGroupShot: OK";
		$response['unGroupShot']['shotIds'] = $deleteShotIds;
		return compact('success', 'message', 'response');
	}
	
	/**
	 * remove Asset from Shot, but keep shot
	 * @param array $assetIds 
	 * @param uuid $shotId
	 * @return aa array('success','bestShot')
	 */
	public function removeFromShot ($assetIds, $shotId) {
		$success = false; $message=array(); $response=array();
		if (!empty($assetIds)) {
			$assetIds_IN = "('" . join("','", $assetIds)  ."')"; 
			// TODO: delete old/orphaned Shots using QUEUE
			$sql_removeFromShot = "
DELETE FROM `Best`, `AssetsShots`
USING `groupshots` AS `Shot`
INNER JOIN `assets_groupshots` AS `AssetsShots` ON (`AssetsShots`.groupshot_id = `Shot`.id 
	AND `AssetsShots`.asset_id IN {$assetIds_IN} )
LEFT JOIN `best_groupshots` AS `Best` ON (`Best`.groupshot_id = `Shot`.id
	AND `Best`.asset_id = `AssetsShots`.asset_id  )
WHERE `Shot`.id = '{$shotId}'";
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
			'order'=>'`SharedEdit`.score DESC, `Asset`.dateTaken ASC',	
//			'showEdits'=>true,
			'extras'=>array(
				'show_edits'=>true,
				'join_shots'=>'Groupshot', 
				'show_hidden_shots'=>true, 
			),
			'permissionable'=>false,	
		);
		$Asset = $this->AssetsGroupshot->Asset;
		$Asset->Behaviors->detach('Taggable');
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
				'order'=>'`Shot`.id, rating DESC, `SharedEdit`.score DESC, `Asset`.dateTaken ASC',	
				'extras'=>array(
					'show_edits'=>true,
					'join_shots'=>'Groupshot', 
					'show_hidden_shots'=>true, 
				),
				'permissionable'=>true,	
			);
			$Asset = $this->AssetsGroupshot->Asset;
			$Asset->Behaviors->detach('Taggable');
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