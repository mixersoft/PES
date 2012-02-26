<?php
class Asset extends AppModel {
	var $name = 'Asset';
	var $displayField = 'src_thumbnail';

	/*
	 * AssetPermissions
	 * uid = group owner
	 * gid = group id => user_id
	 * default permissions for Assets:
	 * perm = 519 (rwd/---/---/r--) - public listing,
	 * perm = 71 (rwd/---/r--/--) 	- members only
	 * perm = 7 (rwd/---/---/--) 	- private
	 */
	public $actsAs = array(
		'Tags.Taggable',
		'Search.Searchable',
		'Permissionable.Permissionable' => array(
			'defaultBits'	=> 71,  
			'userModel'		=> 'User',
			'groupModel'	=> 'Group',
		),
	);


	/*
	 * Search plugin
	 */
	public $filterArgs = array(
		array('name' => 'caption', 'type' => 'like', 'field' => 'Asset.caption'),
		array('name' => 'keyword', 'type' => 'like', 'field' => 'Asset.keyword'),
		array('name' => 'tags', 'type' => 'subquery', 'method' => 'findByTags', 'field' => 'Asset.id'),
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
	
	/**
	 * @return string, ['Usershot' | 'Groupshot' | false ]
	 */
	public function hasGroupAsShotPerm($class, $uuid) {
		switch($class){
			case 'User':
				if (AppController::$ownerid== $uuid) return 'Usershot';	// ownership
				if (in_array(AppController::$role,array('EDITOR','MANAGER','ADMIN','ROOT'))) return 'Usershot'; // backoffice editor
				// TODO: check public
				break;
			case 'Group':
				// if you can see Group Assets, you have permission to groupAsShot
				return 'Groupshot';
				break;
			case 'Tag':
				// check for context of Group or Owner
				$context = Session::read('lookup.context');
				if (!empty($context['keyName'])) {
					if (in_array($context['keyName'], array('Group','Event','Wedding'))) return 'Groupshot';
					if (in_array($context['keyName'], array('Me','Person'))) {
						if (AppController::$ownerid == $context['uuid']) return 'Usershot';	// person is current user
						if (in_array(AppController::$role,array('EDITOR','MANAGER'))) return 'Usershot';
					}  
				}
				break;
		}
		return false;
	}
	
	/**
	 * looks at the following keys from Asset->find() or Asset->paginate()
	 * $queryData['extras']['join_shots'] = [null | 'Usershot' | 'Groupshot' ], default null
	 * $queryData['extras']['show_hidden_shots'] boolean, default false
	 * $queryData['extras']['show_edits'] boolean, default false, join Shared/UserEdits for rating/score
	 */
	public function beforeFind($queryData) {
//debug("Model:Asset beforeFind()");
//debug($queryData); // use extras for shot options
			        // add belongsTo fields???
		if (!empty($queryData['extras']['join_shots'])) {
			// default is to NOT show hidden shots
			$showHidden = !empty($queryData['extras']['show_hidden_shots']) ? true : false;
			$queryData = $this->joinWithShots($queryData, $showHidden);			// uses user/groupshots table
		} 
		if (!empty($queryData['extras']['show_edits']) || !empty($queryData['showEdits']) ) {
			$queryData = $this->joinWithEdits($queryData);
		}
		return $queryData;
	}
	
	/**
	 * join with Shared/UserEdits to add score, rating from User/Shared edits
	 * 
	 */
	public function joinWithEdits($queryData) {
		// add manual joins for recursive=-1
//		unset($this->belongsTo['SharedEdit']);
//		unset($this->hasMany['UserEdit']);
		$this->unbindModel(array(
            'belongsTo' => array('SharedEdit'),
			'hasMany' => array('UserEdit'),
        ));
		$joins =  array(
			array(
				'table'=>'shared_edits',
				'alias'=>'SharedEdit',
				'type'=>'LEFT',
				'conditions'=>array("SharedEdit.asset_hash = Asset.asset_hash"),
			),
			array(
				'table'=>'user_edits',
				'alias'=>'UserEdit',
				'type'=>'LEFT',
				'conditions'=>array("UserEdit.asset_hash=Asset.asset_hash",
					'UserEdit.owner_id'=>AppController::$userid),
			),
		);
		$queryData['joins'] = @mergeAsArray($queryData['joins'], $joins );
		$queryData['fields'] = @mergeAsArray($queryData['fields'], 
			array(
				'SharedEdit.score, SharedEdit.votes',
				// coalese takes first non-null value
				'coalesce(UserEdit.rating, SharedEdit.score) AS rating',		// puts results in $results[0]
				'coalesce(UserEdit.rotate, SharedEdit.rotate) AS rotate'
		));
		return $queryData;
	}

	/**
	 * joinWithShots
	 * 	- currently required for $data['Asset']['shot_count']
	 *  - use 'showHidden'==true && 'join_bestshot'=>false for groupAsShot
	 * @param $queryData aa, from beforeFind 
	 * $queryData['extras'] = array(
	 * 			'join_shots'=>['Groupshot'|'Usershot' (default)], 
	 * 			'show_hidden_shots'=>boolean, default false, 
	 * 			'join_bestshot'=>boolean, default true, join/shot bestShot
	 * 			'only_shots'=>boolean, default false
	 * 	)
	 * @param $showHidden boolean, default false. use true to show Hidden shots
	 */
	public function joinWithShots($queryData, $show_hidden_shots=false){
		$shotType = $queryData['extras']['join_shots'];	// Groupshot or Usershot
		$join_bestshot = !isset($queryData['extras']['join_bestshot']) || ($queryData['extras']['join_bestshot'] == true); // default true
		$only_shots = !empty($queryData['extras']['only_shots']); // default false
		if ($shotType == 'Groupshot') {
			// join with Groupshots
			$joins[] = array(
					'table'=>'assets_groups',
					'alias'=>'AssetsGroup',
					'type'=>'INNER',
					'conditions'=>array('`AssetsGroup`.asset_id = `Asset`.id'),
				);
			$joins[] =  array(
					'table'=>'groups',
					'alias'=>'Group',
					'type'=>'INNER',
					'conditions'=>array('`Group`.id = `AssetsGroup`.group_id'),
				);			
			$joins[] =  array(
					'table'=>'assets_groupshots',
					'alias'=>'AssetsGroupshot',
					'type'=>'LEFT',
					'conditions'=>array('`AssetsGroupshot`.asset_id = `Asset`.id'),
				);			
			$joins[] =  array(
					'table'=>'groupshots',
					'alias'=>'Shot',		// use Shot instead of Groupshot
					'type'=>'LEFT',
					'conditions'=>array('`Shot`.id = `AssetsGroupshot`.groupshot_id'),
				);	
			if ($join_bestshot) {					
				$joins[] =  array(
						'table'=>'best_groupshots',
						'alias'=>'BestShotSystem',
						'type'=>'LEFT',
						'conditions'=>array('`BestShotSystem`.groupshot_id = `Shot`.id','`BestShotSystem`.user_id IS NULL'),
					);	
				$joins[] =  array(
						'table'=>'best_groupshots',
						'alias'=>'BestShotOwner',
						'type'=>'LEFT',
						'conditions'=>array('`BestShotOwner`.groupshot_id = `Shot`.id','`BestShotOwner`.user_id = `Group`.owner_id'),
					);	
				$joins[] =  array(
						'table'=>'best_groupshots',
						'alias'=>'BestShotMember',
						'type'=>'LEFT',
						'conditions'=>array('`BestShotMember`.groupshot_id = `Shot`.id','`BestShotMember`.user_id'=>AppController::$userid),
					);	
			}
			$fields = array('`Shot`.id AS `shot_id`', '`Shot`.assets_groupshot_count AS `shot_count`');
		} else {
			// join with Usershots
			$joins[] =  array(
					'table'=>'assets_usershots',
					'alias'=>'AssetsUsershot',
					'type'=>'LEFT',
					'conditions'=>array('`AssetsUsershot`.asset_id = `Asset`.id'),
				);			
			$joins[] =  array(
					'table'=>'usershots',
					'alias'=>'Shot',		// use Shot instead of Usershot
					'type'=>'LEFT',
					'conditions'=>array(' `Shot`.id = `AssetsUsershot`.usershot_id','`Shot`.owner_id = `Asset`.owner_id'),
				);			
			if ($join_bestshot) {				
				$joins[] =  array(
						'table'=>'best_usershots',
						'alias'=>'BestShotSystem',
						'type'=>'LEFT',
						'conditions'=>array('`BestShotSystem`.usershot_id = `Shot`.id','`BestShotSystem`.user_id IS NULL'),
					);	
				$joins[] =  array(
						'table'=>'best_usershots',
						'alias'=>'BestShotOwner',
						'type'=>'LEFT',
						'conditions'=>array('`BestShotOwner`.usershot_id = `Shot`.id','`BestShotOwner`.user_id = `Asset`.owner_id'),
					);	
				$joins[] =  array(
						'table'=>'best_usershots',
						'alias'=>'BestShotMember',
						'type'=>'LEFT',
						'conditions'=>array('`BestShotMember`.usershot_id = `Shot`.id','`BestShotMember`.user_id'=>AppController::$userid),
					);
			};
			$fields = array('`Shot`.id AS `shot_id`', '`Shot`.assets_usershot_count AS `shot_count`');
		}
	
		// show or hide hidden shots
		Configure::write('afterFind.Asset.showHiddenShots', $show_hidden_shots);
		// join_shots=1
		// join_bestshots=0,1
		// show_hidden_shots=0,1 
		if ($show_hidden_shots && !$join_bestshot) {
			// show hidden shots, but do NOT care which ones are best
			$conditions = array();
		} else if ($show_hidden_shots && $join_bestshot) {
			// show hidden shots, and get bestShots
			$conditions = array();
			$fields[] = "COALESCE(`BestShotMember`.`asset_id`, `BestShotOwner`.`asset_id`, `BestShotSystem`.`asset_id`) = `Asset`.`id` AS `best_shot`";
		} else if (!$show_hidden_shots && !$only_shots) {
			// show photos and bestShots, but HIDE hidden shots
			$conditions = array('OR'=>array(
				'`Shot`.id IS NULL', 	// no photo with hidden shots, or bestShot 
				'COALESCE(`BestShotMember`.`asset_id`, `BestShotOwner`.`asset_id`, `BestShotSystem`.`asset_id`) = `Asset`.`id`'
				)
			);
		} else if (!$show_hidden_shots && $only_shots) {		
			// show only bestShots, but HIDE hidden shots and single photos	
			$conditions = 'COALESCE(`BestShotMember`.`asset_id`, `BestShotOwner`.`asset_id`, `BestShotSystem`.`asset_id`) = `Asset`.`id`';
		}	
			
		$queryData['joins'] = @mergeAsArray($queryData['joins'], $joins);
		$queryData['conditions'] = @mergeAsArray($queryData['conditions'], $conditions);
		$queryData['fields'] = @mergeAsArray($queryData['fields'], $fields);
		return $queryData;
	}
	
	public function afterFind($results, $primary){
		if ($primary && isset($results[0]['Asset'])) {
//			$start = microtime(1);				
			array_walk($results, 'Asset::mergeResults');
//			debug("elapsed=".(microtime(1) - $start));		
		}
		if ($primary && !Configure::read('controller.isXhr') && isset($results[0]['Asset']['owner_id'])){
			if ($results[0]['Asset']['id'] == Configure::read('controller.xhrFrom.uuid')) {
				// establish ownership of this particular Asset
				Configure::write('controller.isOwner', $results[0]['Asset']['owner_id'] == AppController::$ownerid);
				Configure::write('controller.owner', $results[0]['ProviderAccount']['display_name']);
				Configure::write('controller.photostream', $results[0]['ProviderAccount']['display_name'].'@'.$results[0]['Asset']['provider_name']);
			}
		}
		return $results;
	}
		
	
	/**
	 * callback function for array_walk(), merges additional fields from query results into $results[]['Asset'] 
	 * @param $results by reference
	 * @param $options array [mergeEdits,mergeShots]
	 * @return unknown_type
	 */
	private function mergeResults(& $results, $i) {
		$merged = array();
		if (!empty($results['SharedEdit']))  {
			array_push($merged, $results['SharedEdit']);
		}
		if (!empty($results['Shot']))  {
			array_push($merged, $results['Shot']);
		}
		if (!empty($results['0'])) {
			array_push($merged, $results['0']);
		}
 		if (count($merged)) {
 			array_unshift($merged, $results['Asset']);	//merge into 'Asset'
 			$results['Asset'] = call_user_func_array('array_merge', $merged);
 		}
	}	
		

	//	public function XXXafterSave($created) {
	//		/*
	//		 * for Assets
	//		 * 		if provider is snappi, provider_key = id, save asset_hash
	//		 */
	//		if($created) {
	//			$provider = $this->data['Asset']['provider_name'];
	//			if ( $provider == 'snappi') {
	//				$uuid = $this->id;
	//				$asset = array('provider_key' => $uuid, 'updated'=>false);
	//				$asset['asset_hash'] = md5($provider."-".$uuid, true);
	//				$ret = $this->save($asset, false, array('provider_key', 'asset_hash'));
	//			} else {
	//				$uuid = $this->data['Asset']['provider_key'];
	//				$asset['asset_hash'] = md5($provider."-".$uuid, true);
	//				$ret = $this->save($asset, false, array('asset_hash'));
	//			}
	//			return true;
	//		}
	//	}

	var $validate = array(
		'provider_name' => array(
			'notempty' => array(
				'rule' => array('notempty'),
	//'message' => 'Your custom message here',
	//'allowEmpty' => false,
	//'required' => false,
	//'last' => false, // Stop validation after this rule
	//'on' => 'create', // Limit validation to 'create' or 'update' operations
	),
	),
		'provider_key' => array(
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

	//The Associations below have been created with all possible keys, those that are not needed can be removed

	public $belongsTo = array(
		'ProviderAccount' => array(
			'className' => 'ProviderAccount',
			'foreignKey' => 'provider_account_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
			),
		'Owner' => array(
			'className' => 'User',
			'foreignKey' => 'owner_id',
			'counterCache' => true,
//			'fields'=>array('Owner.id', 'Owner.username'),  // or add via Containable
			'type'=>'INNER',
			'conditions' => '',
			'fields' => '',
			'order' => ''
			),
//		'AssetsUsershot' => array(					// AssetsUsershot belongsTo Asset 
//			'className' => 'AssetsUsershot',
//			'foreignKey' => 'asset_id',
//			'dependent' => true,
//		),					
		'SharedEdit' => array(
			'className' => 'SharedEdit',
			'foreignKey' => 'asset_hash',
			'conditions' => '',
			'fields' => '',
			'order' => ''
			)
		);
	public $hasOne = array(				
//		'AssetsUsershot' => array(					// AssetsUsershot belongsTo Asset 
//			'className' => 'AssetsUsershot',
//			'foreignKey' => 'asset_id',
//			'dependent' => true,
//		),
	);
	public $hasMany = array(
		'UserEdit' => array(
			'className' => 'UserEdit',
			'foreignKey' => 'asset_hash',
			'dependent' => false,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		),		
		'BestGroupshot' => array(					// BestGroupshot belongsTo Asset 
			'className' => 'BestGroupshot',
			'foreignKey' => 'asset_id',
			'dependent' => true,					// delete Asset deletes all related BestGroupshots
		),			
		'BestUsershot' => array(					// BestUsershot belongsTo Asset 
			'className' => 'BestUsershot',
			'foreignKey' => 'asset_id',
			'dependent' => true,					// delete Asset deletes all related BestUsershots
		),			
//		'AssetsGroupshot' => array(					// implied, via Asset habtm Groupshot 
//			'className' => 'AssetsGroupshot',
//			'foreignKey' => 'groupshot_id',
//			'dependent' => true,					// delete Asset deletes all related BestUsershots
//		),			
		
	);


	var $hasAndBelongsToMany = array(
		'Collection' => array(
			'className' => 'Collection',
			'joinTable' => 'assets_collections',
			'foreignKey' => 'asset_id',
			'associationForeignKey' => 'collection_id',
			'unique' => true,
			'conditions' => '',
			'fields' => '',
			'order' => '',
			'limit' => '',
			'offset' => '',
			'finderQuery' => '',
			'deleteQuery' => '',
			'insertQuery' => ''
		),
		'Group' => array(
			'with' => 'AssetsGroup',		
		),
		'Groupshot' => array(					// GroupShot habtm Asset
			'with' => 'AssetsGroupshot',		
		),
		'Tag' => array(
			'with'=> 'Tagged',			
		)
	);

	function addIfNew($asset, $providerAccount, $baseurl, $photoPath, & $response){
		$ret = true;
		$timestamp = time();
		$userid = AppController::$userid;
		$Import = loadComponent('Import', $this);
		
		/****************************************************
		 * setup data['Asset'] to create new Asset
		 */

		$file_relpath = cleanPath(substr($photoPath,strlen($baseurl)),'http');
		$filename_no_counter = preg_replace('/(.*)(~\d+)(\.jpg)/i','${1}${3}',$asset['rel_path']);	
// $this->log('$filename_no_counter =>'.$filename_no_counter,LOG_DEBUG);		
		$uuid = !empty($asset['id']) ? $asset['id'] : String::uuid();
		$shardPath = $Import->shardKey($uuid, $uuid);
		$src['orig']= cleanPath($providerAccount['baseurl'].DS.$asset['rel_path'], 'http');		// original relpath in the clear
		$src['root']= $shardPath;
		$src['preview']= $Import->getImageSrcBySize($shardPath, 'bp');
		$src['thumb']= $Import->getImageSrcBySize($shardPath, 'tn');

		/*
		 * get extended exif from asset
		 */
		if (empty($asset['json_exif'])) {
			$meta = $Import->getMeta($photoPath);
			$asset['json_exif'] = $meta['exif'];
			$asset['json_iptc'] = $meta['iptc'];
			// process json_exif from v1.8.3 snappi-uploader
			$options = array('filepath'=>$photoPath, 'autoRotate'=>0);	// js upload not rotated
		} else {	
			// json_exif from desktop-uploader
			if (is_string($asset['json_exif'])) {	
				// process json_exif from v1.8.3 snappi-uploader
				$asset['json_exif'] = json_decode($asset['json_exif'], true);
			} 
			// process json_exif from v1.8.3 snappi-uploader
			$options = array('filepath'=>$photoPath, 'autoRotate'=>1);
		}
		$exif = $Import->augmentExif($asset['json_exif'], $options);
		$asset['json_exif'] = Set::merge($asset['json_exif'], $exif);
		if (empty($asset['json_exif'])) {	
			//  if exif STILL not available, make an exif array to capture ORIGINAL dimensions
			$options['filepath'] = $photoPath;
			$options['width'] = $asset['width'];
			$options['height'] = $asset['height'];
			$asset['json_exif'] = $Import->augmentExif(null, $options);
		}

		// add provider_account_id for generating asset_hash
		$asset['provider_account_id'] = $providerAccount['id'];
		$asset_hash = getAssetHash($asset, $photoPath, $filename_no_counter );
		/*
		 *  check if asset already exists, by asset.id OR asset_hash 
		 */
		$this->Behaviors->detach('Taggable');
		$checkDupes_options = array(
			'recursive' => -1,
			'conditions' => array( 'Asset.owner_id' => $userid,
				'OR'=>array(
					'Asset.id'=>$asset['id'], 
					'Asset.asset_hash'=>array($asset_hash, $asset['asset_hash']),
				)
			),
			'extras'=>array(
				'show_edits'=>false,
				'join_shots'=>false, 
				'show_hidden_shots'=>false		
			),
			'permissionable'=>false,
		);
		$data = $this->find('first', $checkDupes_options);
// $this->log( "checkDupes_options data=".print_r($data, true), LOG_DEBUG);		
		if (!empty($data['Asset']['id'])) {
			// found
			$response['message'][]="photo already imported";
			return array('Asset'=>$data['Asset']);
		}
		
		$assetTemplate = array(
			'owner_id'=>$userid,						// owner, might be redundant
			'provider_account_id' => $providerAccount['id'],
			'provider_name'=>$providerAccount['provider_name'],
			'batchId' => $asset['batchId'],
			'uploadId' => $timestamp,
		);		
		$newAsset = array(
			'id' => $uuid,
			'provider_key' => $uuid,
			'asset_hash' =>  $asset_hash,	
			'json_src' => $src,
			'src_thumbnail' => $src['thumb'],
			'json_exif' => $asset['json_exif'],
			'json_preview_exif' => !empty($asset['json_exif']['preview']) ? $asset['json_exif']['preview'] : null,	// deprecate?
//			'json_iptc' => $meta['iptc'],
			'dateTaken' => !empty($asset['json_exif']['DateTimeOriginal']) ? $asset['json_exif']['DateTimeOriginal'] : null,
			'isFlash' => $asset['json_exif']['isFlash'],
			'isRGB' => !empty($asset['json_exif']['isRGB']) ? $asset['json_exif']['isRGB']: null,
		);
		if (isset($asset['json_iptc']['Keyword'])) $newAsset['keyword']= $asset['json_iptc']['Keyword'];
		if (isset($asset['json_iptc']['Caption'])) $newAsset['caption']= $asset['json_iptc']['Caption'];
		if (empty($asset['caption']))   {
			$newAsset['caption'] = pathinfo($filename_no_counter, PATHINFO_FILENAME);
		}

		/*
		 * set default Asset perms from Profile
		 *		override Plugin default set in Asset model
		 */
		pack_json_keys($newAsset);		// from php_lib
		$newAsset = array_merge($assetTemplate, $newAsset);
		$data = array('Asset'=>$newAsset);
$this->log("insert newAsset", LOG_DEBUG);		
$this->log($newAsset, LOG_DEBUG);
		/*
		 * $asset is correct
		 *************************************************************/
		
		/*
		 * insert row in assets table
		 */
		// config Permissionable for CREATE to skip 'write' permission check
		$this->isCREATE = true;
		$this->create();
		$this->Behaviors->detach('Taggable');
/*
 * TODO: BUG: SQL Error: 1052: Column 'id' in where clause is ambiguous
 * 		for counterCache on a permissionable
 */			
		$this->belongsTo['Owner']['counterCache']=false;
		if ($ret = $this->save($data)) {
// $this->log( "save asset, data=".print_r($data, true), LOG_DEBUG);				
			$response['message'][]="photo imported successfully";
		} else 	$response['message'][]="Error creating asset, id={$asset['id']}";
		$response['success'] = isset($response['success']) ? $response['success'] && $ret : $ret;
		$data = $this->read();
// $this->log( "AFTER save asset, data=".print_r($data, true), LOG_DEBUG);			
		return $data; 			
	}

	/*
	 *  update json_exif from $src['root'] file and delete any derived assets
	 * 	$options['name'] Controller->name
	 *  $options['uuid']
	 */ 
	function updateExif($options=array()) {
		$os = Configure::read('os');
		$find_options = array(
			'fields'=>"Asset.id, Asset.json_src, Asset.json_exif",
			'recursive'=>-1,
			'permissionable'=>false
		);
		extract($options); 	// $name; $uuid;
		switch($name){
			case "Users": 
				$find_options['conditions'] = array(
					'Asset.owner_id'=>$uuid,
					// 'Asset.owner_id'=>AppController::$ownerid,
				); break;
			case "Assets":
				$find_options['conditions'] = array(
					'Asset.id'=>$uuid,
				); break;
			case "Groups":	
			default:
				return false;
		}
		$this->Behaviors->detach('Taggable');
		$this->belongsTo['Owner']['counterCache']=false;
		$data = $this->find('all',$find_options);
		// debug(Set::extract('/Asset/id', $data)); 
		
		if ($data) {
			$ret = true;
			$this->disablePermissionable(true);
			foreach ($data as $row) {
				$Import = loadComponent('Import', $this);
				$src = json_decode($row['Asset']['json_src'], true);
				$basepath = Configure::read('path.stageroot.basepath');
				$rootpath = cleanpath($basepath.DS.$src['root'], $os);
		debug("{$rootpath} from {$src['orig']}"); // continue;
				$meta = $Import->getMeta($rootpath);
				if (!empty($meta['exif'])){
		// debug($meta['exif']);
					// debug(json_decode($data['Asset']['json_exif'],true));
					$json_exif = json_encode($meta['exif']);
		// debug($data['Asset']['json_exif']);
		// debug($json_exif);
					$this->id = $row['Asset']['id'];
					$retval = $this->saveField('json_exif', $json_exif);
					if ($retval) {
						// delete all derived assets and re-render
						$filename = pathinfo($rootpath, PATHINFO_FILENAME);
						$derived = dirname($rootpath).DS.'.thumbs'.DS.'*'.$filename.'*';
						foreach (GLOB($derived) AS $delete) {
							// debug("delete, path={$delete}");
							unlink($delete);
						}
					}
					$ret = $ret && $retval;
		// debug($ret);
				}				
			}
			return $ret;
		}
		return $false;
	}	
			
	function getByPhotostream($provider_account_id, $options){
		
		

	}

	function appendFilterConditions($options, $conditions) {
		/*
		 * add filters, from $this->params['named']
		 */
		$filterConditions = array();
		if (isset($options['rating'])) {
			if ($options['rating']==='0'){ 
				$filterConditions[] = "SharedEdit.score IS NULL";
			} else 
				$filterConditions[] = "IF(UserEdit.rating, UserEdit.rating, SharedEdit.score)>={$options['rating']}";
		}
		if (isset($options['q'])) {
			// text search, 
			$searchKeys = array('caption', 'keyword', 'tags');
			$filterConditions[] = $this->appendSearchConditions($options, $searchKeys );
		}		
		return @mergeAsArray( $conditions, $filterConditions);
	}
	

	function appendFilterJoins($options, $joins) {
		/*
		 * add filters, from $this->params['named']
		 */
		$filterJoins = array();
		if (isset($options['rating'])) {
		}
		if (isset($options['User'])) {
		}		
		if (isset($options['Group'])) {
			$filterJoins[] = array(
				'table'=>'assets_groups',
				'alias'=>'AssetsGroup',
				'type'=>'INNER',
				'conditions'=>array('`AssetsGroup`.asset_id = `Asset`.id'),
			);
		}		
		return empty( $filterJoins) ? $joins : @mergeAsArray( $joins, $filterJoins);
	}
		
	function getPaginatePhotos ( $paginate = array(), $skipContext = true) {
		$paginateModel = 'Asset';
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

	function getPaginatePhotosByShotId ($shotId, $paginate = array(), $shotType = 'Usershot',  $skipContext = true) {
		ClassRegistry::init('Asset')->disablePermissionable();
		$paginateModel = 'Asset';
		
		$context = Session::read('lookup.context');
		$controller = Configure::read('controller.alias');
		
		$conditions = $joins = array();
		$conditions = array('`Shot`.`id`'=> $shotId);	// Shot alias for Usershot or Groupshot
		
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
		$paginate['order'] = array('rating'=>'DESC', 'Asset.dateTaken'=>'ASC');
		$paginate['extras']['join_shots']=$shotType;		// override default 'Usershot'
		$paginate['extras']['show_hidden_shots']=true;
		// TODO: can we group/ungroup shots, just because we have read perm???
		$paginate['extras']['group_as_shot_permission'] = $shotType;
		return $paginate;
	}				
		
	
	function getPaginatePhotosByGroupId ($groupid , $paginate = array()) {
		if (!$this->Group->hasPermission('read',$groupid) ){
			// no read permission, likely non-member
			$paginate = array('conditions'=>"1=0");
			return $paginate;
		}
//		ClassRegistry::init('Asset')->disablePermissionable();
		$paginateModel = 'Asset';
//debug($paginateModel);	 
		// refactor
		$context = Session::read('lookup.context');
		$controller = Configure::read('controller.alias');
		
		// add conditions for GroupId
		$conditions = $joins = array();
		// moved to joinWithShots()
//		$joins[] = array(
//			'table'=>'assets_groups',
//			'alias'=>'AssetsGroup',
//			'type'=>'INNER',
//			'conditions'=>array('`AssetsGroup`.asset_id = `Asset`.id'),
//		);
		
		$conditions[] = "AssetsGroup.group_id='{$groupid}'";
		// check of context == controller
		$skip = $context['keyName'] == Configure::read('controller.label');
		// add context
		if (!$skip) {
			if (in_array($context['keyName'], array('Me', 'Person'))) {
				//groups/photos
				$conditions[] = "`Asset`.owner_id='{$context['uuid']}'";
			}
			if (in_array($context['keyName'], array('Group','Event','Wedding'))) {
				// skip
			}
			if ($context['keyName'] == 'Tag') {
				$joins[] =	array(
							'table'=>'tagged',
							'alias'=>'Tagged',
							'type'=>'INNER',
							'conditions'=>array("`Tagged`.`foreign_key` = `Asset`.id AND `Tagged`.`model` = 'Asset'"),
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
		$paginate['extras']['join_shots']='Groupshot';		// override default 'Usershot'
		$paginate['extras']['group_as_shot_permission'] = $this->hasGroupAsShotPerm('Group', $groupid);
		return $paginate;
	}			
	
	function getPaginatePhotosByUserId ($userid , $paginate = array()) {
		$paginateModel = 'Asset';
//debug($paginateModel);			
		// add context, refactor
		$context = Session::read('lookup.context');
		$controller = Configure::read('controller.alias');
		
		// add conditions for UserId
		$conditions = $joins = array();
		$conditions[] = "Asset.owner_id='{$userid}'";
		
		// check of context == controller
		$skip = $context['keyName'] == Configure::read('controller.label');
		// add context
		if (!$skip) {
			if (in_array($context['keyName'], array('Me', 'Person'))) {
				// skip
			}
			if (in_array($context['keyName'], array('Group','Event','Wedding'))) {
				// Asset habtm Group
				$joins[] =  array(
						'table'=>'assets_groups',
						'alias'=>'AssetsGroup',
						'type'=>'INNER',
						'conditions'=>array("AssetsGroup.asset_id=`Asset`.id", "AssetsGroup.group_id"=>$context['uuid']),
				);						
			}
			if ($context['keyName'] == 'Tag') {
				$joins[] = 	array(
							'table'=>'tagged',
							'alias'=>'Tagged',
							'type'=>'INNER',
							'conditions'=>array("`Tagged`.`foreign_key` = `Asset`.id AND `Tagged`.`model` = 'Asset'"),
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
		if (AppController::$ownerid == $userid) $paginate['extras']['group_as_shot_permission']='Usershot';
		$paginate['extras']['group_as_shot_permission'] = $this->hasGroupAsShotPerm('User', $userid);
		return $paginate;
	}
	
	function getPaginatePhotosByTagId ($tagid , $paginate = array()) {
		$paginateModel = 'Asset';
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
				'conditions'=>array("`Tagged`.`foreign_key` = `Asset`.id AND `Tagged`.`model` = 'Asset'"),
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
				$conditions[] = "Asset.owner_id='{$context['uuid']}'";
			}
			if (in_array($context['keyName'], array('Group','Event','Wedding'))) {
				$joins[] =  array(
					'table'=>'assets_groups',
					'alias'=>'AssetsGroup',
					'type'=>'INNER',
					'conditions'=>array("AssetsGroup.asset_id = `Asset`.`id`", "AssetsGroup.group_id"=>$context['uuid']),
				);
			}
			if ($context['keyName'] == 'Tag') {
				// skip
			}
		}
		if (!empty($joins)) $paginate['joins'] = @mergeAsArray($paginate['joins'], $joins);
		if (!empty($conditions)) $paginate['conditions'] = @mergeAsArray($paginate['conditions'], $conditions);
		$paginate['extras']['group_as_shot_permission'] = $this->hasGroupAsShotPerm('Tag', $tagid);
		return $paginate;
	}	
	
	function getPaginatePhotosByProviderAccountId ($providerAccountId , $paginate = array(), $shotType = 'Usershot') {
		// TODO: when do we want to show 'Groupshots' by providerAccountId???
//		if (!$shotType) {
//			$shotType = Configure::read('controller.name') == 'Groups' ? 'Groupshot' : 'Usershot'; 
//		}

		$paginateModel = 'Asset';
//debug($paginateModel);	 
		// refactor
		$context = Session::read('lookup.context');
		$controller = Configure::read('controller.alias');
		
		// add conditions for GroupId
		$conditions = $joins = array();
		$conditions[] = array("`Asset`.provider_account_id" => $providerAccountId);
		$groupId = AppController::$uuid;
		if (Configure::read('controller.name')=='Groups') {		
			$joins[] = array(
				'table'=>'assets_groups',
				'alias'=>'AssetsGroup',
				'type'=>'LEFT',
						'conditions'=>array("`AssetsGroup`.`asset_id` = `Asset`.id AND `AssetsGroup`.`group_id` = '$groupId'"),
			);
		}
		// check of context == controller
		$skip = $context['keyName']  == Configure::read('controller.label');
		// add context
		if (!$skip) {
			if ($context['keyName']  == 'Person') {
				//groups/photos
				$conditions[] = "`Asset`.owner_id='{$context['uuid']}'";
				$paginate['extras']['group_as_shot_permission'] = $this->hasGroupAsShotPerm('User', $context['uuid']);
			}
			if ($context['keyName']  == 'Group') {
				// skip
				$paginate['extras']['group_as_shot_permission'] = $this->hasGroupAsShotPerm('Group', $context['uuid']);
			}
			if ($context['keyName']  == 'Tag') {
				$joins[] =	array(
							'table'=>'tagged',
							'alias'=>'Tagged',
							'type'=>'INNER',
							'conditions'=>array("`Tagged`.`foreign_key` = `Asset`.id AND `Tagged`.`model` = 'Asset'"),
					);
				$joins[] =	array(
							'table'=>'tags',
							'alias'=>'Tag',
							'type'=>'INNER',
							'conditions'=>array("`Tagged`.`tag_id` = `Tag`.id AND `Tag`.`keyname` = '{$context['uuid']}'"),
					);
				$paginate['extras']['group_as_shot_permission'] = $this->hasGroupAsShotPerm('Tag', $context['uuid']);
			}
		}
		$paginate['joins'] = Array();
		$paginate['conditions'] = Array();
		if (!empty($joins)) $paginate['joins'] = @mergeAsArray($paginate['joins'], $joins);
		if (!empty($conditions)) $paginate['conditions'] = @mergeAsArray($paginate['conditions'], $conditions);
		$paginate['extras']['join_shots']=$shotType;		// usually, 'Usershot', not sure how 'Groupshot would work
		
		return $paginate;
	}
}
?>