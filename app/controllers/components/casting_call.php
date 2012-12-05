<?php
class CastingCallComponent extends Object {
	
	public $controller;
	
	function startup(& $controller)
	{
		$this->controller = & $controller;
	}
	/*
	 Array
	 (
	 [a] => Array
	 (
	 [pid] => snappi
	 [provider_key] => 8973
	 [imageWidth] => 4000
	 [imageHeight] => 2672
	 [exif_ExifImageWidth] => 4000
	 [exif_ExifImageLength] => 2672
	 [exif_Orientation] => 1
	 [exif_Flash] => 16
	 [exif_ColorSpace] => 1
	 [relpath] => Summer2009/P1010577.JPG
	 [src_preview] => Summer2009/P1010577.JPG
	 [src_root] => Summer2009/P1010577.JPG
	 [id] => 8973~snappi
	 [src] => Summer2009/P1010577.JPG
	 [shot_id]=>  // deprecate
	 [chunk]=>
	 )

	 [0] => Array
	 (
	 [dateTaken] => 2009-09-09 15:23:59
	 [rating] => 1.0
	 [scrub] =>
	 [crops] =>
	 [rotate] => 1
	 [ts] => 1252481039
	 )

	 [f] => Array
	 (
	 [rating_points] => 1
	 [rating_votes] => 1
	 )

	 [oper] => Array
	 (
	 [finished_path] =>
	 [workorder_id] => 15
	 )

	 )
	 */

	function formatSubstitutions($container) {
		//		debug($container);
		$Substitute = array();
		foreach ($container['contents'] as $groupId => $group ) {
			$AuditionREF = array();
			foreach ($group['Auditions'] as $idref) {
				$AuditionREF[] = array('idref'=> "snappi-audition-".$idref);
			}
			$Label = $group['Label'];
			$Type = $group['Type'];
			$id = "snappi-{$Type}-{$groupId}";
			$Substitute[] = compact('id', 'Label', 'AuditionREF');
		}
		//		return compact('Substitute');
		return array('Substitution'=>$Substitute);
	}
	function formatClusters($container) {
		//debug($container);
		$Cluster = array();
		foreach ($container['contents'] as $groupId => $group ) {
			$AuditionREF = array();
			foreach ($group['Auditions'] as $idref) {
				$AuditionREF[] = array('idref'=> "snappi-audition-".$idref);
			}
			$Label = $group['Label'];
			$Type = $group['Type'];
			$id = "snappi-{$Type}-{$groupId}";
			$Cluster[] = compact('id', 'Label', 'AuditionREF');
		}
		return compact('Cluster');
	}
	function formatTags($container) {
		//debug($container);
		$Tag = array();
		foreach ($container['contents'] as $groupId => $group ) {
			$AuditionREF = array();
			foreach ($group['Auditions'] as $idref) {
				$AuditionREF[] = array('idref'=> "snappi-audition-".$idref);
			}
			$Label = $group['Label'];
			$id = "snappi-tag-{$groupId}";
			$Tag[] = compact('id', 'Label', 'AuditionREF');
		}
		return compact('Tag');
	}
	function formatAudition($row, $local) {
		$Clusters = '';
		$Credits = '';
		$IsCast = @ifed($local['isCast'], false);

		$lastPerformed = @ifed($local['lastPerformed'], null);
		//		$id = "snappi-audition-{$row['id']}";
		$id = $row['id'];
		$isOwner = $row['owner_id'] == AppController::$ownerid;
		// Src Root
		$src = (array)json_decode($row['json_src'], true);
		// $Src = $src['preview']; 	// TODO: deprecate. refactor to use $src['root'] here
		if (isset($src['base64Src'])) $base64Src = $src['base64Src'];
		if (isset($src['root'])) $rootSrc = $src['root'];
		// if (isset($src['preview']))	$previewSrc= $src['preview'];
		if (isset($src['orig']))	$origSrc= $src['orig'];
		
		$exif = json_decode($row['json_exif'], true);
		$ExifOrientation = !empty($exif['Orientation']) ? $exif['Orientation'] : 1;
if (!isset($exif['root']['imageWidth'])) {
	if (!isset($this->Import)) $this->Import = loadComponent('Import', $this);
	// TODO: save $exif['root'] to $row['json_exif']
	$exif['root'] = @mergeAsArray( $exif['root'], $this->Import->fixRootImagesize($src));	
}	
		if (!@empty($row[0]['FocusCenter'])) {
			$x=$row[0]['FocusCenter']['X'];
			$y=$row[0]['FocusCenter']['Y'];
			$scale=$row[0]['FocusCenter']['Scale'];
		} else {
			if (!empty($exif['ExifImageWidth'])){
				// BEFORE autorotate, adjust in pagemaker/.../audition.js:202
				$x = $exif['ExifImageWidth']/2;
				$y = $exif['ExifImageLength']/2;					
			} else {
				$x = $exif['root']['imageWidth']/2;
				$y = $exif['root']['imageHeight']/2;
			}
			$scale = 2*max(array($x,$y));
		}
		$FocusCenter = array('Scale'=>$scale, 'X'=>$x, 'Y'=>$y);
		$FocusVector = array('Direction'=>0, 'Magnitude'=>0);
		// $Rating = @ifed($row['rating'], null);		// owner rating
		$LayoutHint = compact('FocusCenter', 'FocusVector');
		if (isset($exif['root'])) { 	// $exif['root'] set in component/import.php
		
			$W = $exif['root']['imageWidth'];
			$H = $exif['root']['imageHeight'];
			$Orientation  = isset($exif['root']['Orientation']) ? $exif['root']['Orientation'] : $ExifOrientation;
			if ( isset($exif['root']['isRGB'])) $isRGB  = $exif['root']['isRGB'];
		} else {
			$W = $exif['ExifImageWidth'];
			$H = $exif['ExifImageLength'];
			$Orientation = (isset($exif['Orientation'])) ? $exif['Orientation'] : 1;
		}
		$Src = compact('W', 'H', 'base64Src', 'rootSrc', 'Orientation', 'isRGB');
		$Img = compact('Src');
		
		if (!empty($row['dateTaken_syncd'])) {
			$DateTaken = $row['dateTaken_syncd'];
		} else if (!empty($row['dateTaken'])) {
			$DateTaken = $row['dateTaken'];
		} else $DateTaken = '';
		
//		$DateTaken = (!empty($row['dateTaken']))  ? $row['dateTaken'] : '';
		$TS = strtotime($DateTaken);
		$ExifColorSpace = !empty($exif['ColorSpace']) ? $exif['ColorSpace'] : null;
		$ExifFlash = !empty($exif['Flash']) ? $exif['Flash'] & 1 : '';	// check bit 0
		$W = !empty($exif['ExifImageWidth']) ? $exif['ExifImageWidth'] : null;
		$H = !empty($exif['ExifImageLength']) ? $exif['ExifImageLength'] : null;
		$Crops = (!empty($row['crops'])) ? $this->formatCrops($row['crops']) : '';
		$Rating = (!empty($row['rating'])) ? $row['rating'] : null;
		$Score = number_format(round($row['score'] , 1),1);
		$Votes = @ifed($row['votes'], null);
		$Flagged = isset($row['flag_status']) ? $row['flag_status'] : null;
//TODO: hack until json_exif[preview][Orientation] saved to rotate
$previewOrientation = 	isset($exif['preview']['Orientation']) ? $exif['preview']['Orientation'] : 1;	
		$Rotate = (!empty($row['rotate'])) ? $row['rotate'] : $previewOrientation;
		$Scrub = (!empty($row['scrub'])) ? $this->formatScrub($row['scrub'])  : '';
		$Fix = compact('Crops', 'Rating', 'Rotate', 'Scrub', 'Score', 'Votes');
		$AutoRender = ($row['provider_name']=='snappi');
		$Caption = (!empty($row['caption'])) ? $row['caption'] : '';
		$OwnerId = $row['owner_id'];
		$ProviderAccountId = $row['provider_account_id'];
		$CameraId = (!empty($row['cameraId'])) ? $row['cameraId'] : '';
//		$IsFlash = (!empty($row['isFlash'])) ? $row['isFlash'] : ''; // same as $ExifFlash
//		$IsRGB = (!empty($row['isRGB'])) ? $row['isRGB'] : '';		 // same as ExifOrientation
//		$UploadId = (!empty($row['uploadId'])) ? $row['uploadId'] : '';
		$BatchId = (!empty($row['batchId'])) ? $row['batchId'] : '';
		$Keyword = (!empty($row['keyword'])) ? $row['keyword'] : '';
		$Created = (!empty($row['created'])) ? $row['created'] : '';
		$Photo = compact('id','W','H','Fix','Img','isOwner','DateTaken','TS','ExifColorSpace','ExifFlash','ExifOrientation', 'Caption','origSrc','CameraId',
			/*
			 * extended properties
			 */
	//		'IsFlash','IsRGB','UploadId',
			'OwnerId', 'ProviderAccountId',
			'BatchId','Keyword','Created',
			'Flagged'
		);
		if (isset($row['asset_count'])) {
			// use join with shots table, only add $SubstitutionREF when asset_count > 1
			$SubstitutionREF = ($row['asset_count'] > 1) ? $row['shot_id'] : null;
		} else {
			// use subselect
			$SubstitutionREF = $row['shot_id'];
		}
		$Shot = isset($row['shot_id']) ? array('id'=>$row['shot_id'], 'count'=>$row['shot_count']) : array('id'=>'', 'count'=>'') ;
		$Tags = array();
		
		return compact('id','Photo','LayoutHint','IsCast','lastPerformed','SubstitutionREF','Shot','Tags','Clusters','Credits');
	}

	function formatCrops(& $crops) {
		$formatted = array();
		foreach ($crops as & $crop) {
			$formatted[] = array('Label'=>$crop['Label'], 'Format'=>$crop['Format'], 'Rect'=> & $crop);
			unset($crop['Format']);
			unset($crop['Label']);
		}
		// for now, assume client can only read 1st crop for each asset
		// fleegxml limits parses single XML element as Photo.Fix.Crops.Crop, not Photo.Fix.Crops.Crop[]
		return array('Crop'=>$formatted[0]);
	}

	function formatScrub($scrub) {
		debug($scrub);
		return "***********   PLACEHOLDER FOR SCRUBS *********************";
	}

	/**
	 * @params $options assoc array
	 * 	$options['ProviderName] = 'snappi'
	 *  $options['request'] = cache_Refresh Request
	 *  $options['cache_key'] = cache_Refresh cache_key
	 *  also, $this->paginate[$paginateModel]['extras']:
	 *     [show_edits] => 1
	 *     [join_shots] => Usershot
	 *     [show_hidden_shots] => 1
	 *     [shot_id] => 4ff90f01-bef8-49d1-969c-0725f67883f5
	 *     [group_as_shot_permission] => Usershot
	 */
	function getCastingCall($assets, $cache = true, $options=array()) {
		$options = array_merge(array('ProviderName'=>'snappi'), $options);
		$class='Asset';
		if (isset($assets['Asset'])) $assets = & $assets['Asset'];
		
		$Request = !empty($request) ? $request : $this->controller->here;
		
		$Page = @ifed($this->controller->params['paging'][$class]['page'], 1);
		$Pages = @ifed($this->controller->params['paging'][$class]['pageCount'], 1);
		$Perpage = @ifed($this->controller->params['paging'][$class]['options']['limit'], @ifed($this->controller->params['paging'][$class]['defaults']['limit'], null));
		$Total = (int)@ifed($this->controller->params['paging'][$class]['count'],@ifed($this->controller->params['paging']['total'][$class], count($assets)));
		$Baseurl = Stagehand::$stage_baseurl;
		//debug(compact('Audition','Total','Perpage','Pages','Page','Baseurl'));
		// check permissions for groupAsShot 
		$extras = Configure::read("paginate.Options.".Configure::read('paginate.Model').".extras");
		$ShotType = !empty($extras['join_shots']) ? $extras['join_shots'] : false;
		$GroupAsShotPerm = !empty($extras['group_as_shot_permission']) ? $ShotType : false;
		$ShowHidden = !empty($extras['show_hidden_shots']);
		//

		$Audition = array(); $Bestshot = array();
		foreach ($assets as $asset) {
			$Audition[] = $this->formatAudition($asset, array());
			if (!empty($asset['best_shot'])) $Bestshot[$asset['shot_id']]=$asset['id'];
		}

		$Auditions = compact('Audition','Bestshot','Total','Perpage','Pages','Page','Baseurl', 'ShotType');
		$Timestamp = time();
		$ID = empty($cache_key) ? $Timestamp : $cache_key;
		$CastingCall = compact('ID', 'Timestamp', 'ProviderName','Auditions','Substitutions','Tags','Clusters', 'Request', 'GroupAsShotPerm', 'ShowHidden', 'ShowInactiveShots');

		/*
		 * add Groupings, i.e. Subsitutions, Tags, Clusters
		 */
		if (isset($assets['groupings'])){
			foreach ($assets['groupings'] as $class=>$containers) {
				if (count($containers['contents'])) {
					$CastingCall[$class] = $this->{"format{$class}"}($containers);
				}
			}
		}

		$lookups = Session::read('lookups');	// deprecate???
		unset($lookups['roles']);	// keep these ids private
		
		
		/*
		 * return as stringify json 
		 */
		$castingCall = compact('CastingCall', 'lookups');
		if ($cache) {
			// if (empty($cache_key)) $cache_key = $ID;
			$cache_Entry[$ID] = $CastingCall;
			$this->cache_Push($cache_Entry);
		}
		return $castingCall;
	}
	
	/*******************************************************************
	 * castingCall cache methods
	 * WARNING: I'm not sure these methods are valid. they may not catch 
	 * things like updated ratings
	 * @param newCC array($ID=>$castingCall)
	 */
	static $MAX_AGE = 600; // 10 minutes
	static $MAX_ENTRIES = 10; // LAST 10 castingCalls 
	static function most_recent($a, $b) {
	    if ($a['Timestamp'] == $b['Timestamp']) {
	        return 0;
	    }
	    return ($a['Timestamp'] > $b['Timestamp'] ) ? -1 : 1;
	}
	function cache_Clear(){
		Session::write('castingCall', null); 
	}
	function cache_Push($newCC){
		// example: $newCC = array (
    		// [1317144348] => Array (
		            // [ID] => 1317144348
		            // [ProviderName] => snappi
		            // [Auditions] => Array()
				// ))
		$this->cache_Age($newCC);	// saves a write to Session this way
	}
	/*
	 * summary output of CC for debugging
	 */ 
	function printr_CC($cc) {
		$output = array();
		if (isset($cc['ID'])) $cc = array($cc);
		foreach ($cc as $key=>$row) {
			$output_row['ID'] = $row['ID'];
			$output_row['Timestamp'] = $row['Timestamp'];
			$output_row['Request'] = $row['Request'];
			$output_row['Stale'] = isset($row['Stale']) ? $row['Stale'] : 0;
			$output_row['Total'] = isset($row['Auditions']['Total']) ? $row['Auditions']['Total'] : null;
			$output[$key] = $output_row;
		}
		return $output;
	}
	/**
	 * age entries from cache, just keep Request for old cache keys
	 */
	function cache_Age($newCC = array(), $MAX_AGE = null, $MAX_ENTRIES = null) {
		if ($MAX_AGE == null) $MAX_AGE = CastingCallComponent::$MAX_AGE;
		if ($MAX_ENTRIES == null) $MAX_ENTRIES = CastingCallComponent::$MAX_ENTRIES;			
		$cc = (array)Session::read('castingCall');
		$changed = false;
		if ($cc) {
			$now = time(); 
			// Comparison function, most recent by castingCall timestamp
			// function mostRecent($a, $b) {
			    // if ($a['Timestamp'] == $b['Timestamp']) {
			        // return 0;
			    // }
			    // return ($a['Timestamp'] > $b['Timestamp'] ) ? -1 : 1;
			// }
			uasort($cc, 'CastingCallComponent::most_recent');
			// max entries
			$count = 0;
			foreach ($cc as $key => $CastingCall ) {
				$timestamp = $CastingCall['Timestamp'];
				if ($timestamp=='Lightbox') continue;

				$age = $now - $timestamp;
				if (++$count >= $MAX_ENTRIES) {
					$this->cache_MarkStale($cc, $key);
					$changed = true;
				} else if ($age > $MAX_AGE) {
					$this->cache_MarkStale($cc, $key);
					// unset($cc[$timestamp]);	// delete old ccids from Session, but not the last one
					$changed = true;
				}
			}
		}
		if ($newCC) {
			$cc = $newCC + $cc;
	
			$changed = true;
		}
		if ($changed) {
			$ret = Session::write('castingCall', $cc);
// $this->log($this->printr_CC($cc),LOG_DEBUG);		
			if (!$ret) {
				Session::write('castingCall', $newCC);
			}
		} 
		return $changed;
	}	
	function cache_MostRecent($cc = null) {
		// use most recent ccid from Session if incorrect or missing
		if ($cc === null) $cc = Session::read('castingCall');
		$ccIds = array_keys($cc);
		unset($ccIds['Lightbox']);	// don't include Lightbox in this method
		if ($ccIds) {
			sort($ccIds);
			return $cc[array_pop($ccIds)];
		} else return null;
	}
	function cache_MarkStale(& $castingCall, $ccid = null){
		// mark castingCall as Stale
		// if (!$ccid) $ccid = array_shift(array_keys(Session::read('castingCall')));
		if (!$ccid) return false;
		$request = $castingCall[$ccid]['Request'];
		$timestamp = $castingCall[$ccid]['Timestamp'];
		if ($request) {
			$castingCall[$ccid] = array('ID'=>$ccid, 'Request'=>$request, 'Timestamp'=>$timestamp, 'Stale'=>true);
		} else unset($castingCall[$ccid]);
	}
	/**
	 * @params $mixed int or aa, ccid or CastingCall
	 * @params $options['perpage'] int (optional), override perpage defaults to get more rows
	 * @params $options['page'] int (optional)
	 * @params $options['perpage_on_cache_stale'] int (optional)
	 * @return false if cache miss, otherwise CastingCall
	 */
	function cache_Refresh($mixed, $options = array()){
		extract($options);	// $perpage, $page, $perpage_on_cache_miss
		$cacheKey = null;
		if (is_array($mixed) && isset($mixed['Request'])) {
			// example: /photos/home/4bbb3976-42b8-4d23-b9c1-11a0f67883f5
			$request = $mixed['Request'];
		} else if (is_numeric($mixed)) {
			$request = Session::read("castingCall.{$mixed}.Request"); 
			$cacheKey = $mixed;	// reuse this cacheKey, but update ['CastingCall']['Timestamp']
		} else {
			// example: http://git:88/my/photos
			$request = env('HTTP_REFERER');
			$strip = "http://".env('HTTP_HOST');
			$request = str_replace($strip, '', $request);			
		}
		if (empty($request)) return false;		// cache miss

// debug($request);		
		$route = Router::parse($request);
// debug($route);		
		/*
		 * get a new CastingCall using the $request URL
		 */
		// test for stale cache entry, compare paging
		$passedArgs = $route['named'];
		// check for overrides
		if (!empty($page)) $passedArgs['page'] = $page;
		if (!empty($perpage)) $passedArgs['perpage'] = $perpage;
		if ($cacheKey)	 {
// debug($passedArgs);			
			// check for cacheHit
			$cc = Session::read("castingCall.{$mixed}"); 
// debug(array_diff_key($cc['Auditions'], array('Audition'=>1)));			
			if (
				empty($cc['Stale'])
				&& isset($cc['Auditions']['Perpage']) 
				&& (empty($passedArgs['perpage']) || $cc['Auditions']['Perpage'] == $passedArgs['perpage'])
				&& (empty($passedArgs['page']) || $cc['Auditions']['Page'] == $passedArgs['page'])
				&& (time() - $cc['Timestamp'] < CastingCallComponent::$MAX_AGE) 
			) {
// debug("cache HIT!!!");
// debug(array_diff_key($cc['Auditions'], array('Audition'=>1)));	
// debug($passedArgs);
// debug($cc['Auditions']['Perpage']);
// exit;
				$castingCall['CastingCall'] = $cc; 	// format as CastingCall 
				return $castingCall;
			}
		}
		if (!empty($perpage_on_cache_stale)) $passedArgs['perpage'] = $perpage_on_cache_stale;		
		Configure::write('passedArgs.complete', $passedArgs); 
// debug("******************* cache MISS **********************");		
// debug($passedArgs);		
		$id = isset($route['pass'][0]) ? $route['pass'][0] : null;
		$paginateModel = 'Asset';
		$Model = ClassRegistry::init($paginateModel);
		$Model->Behaviors->attach('Pageable');
		switch ($route['controller']) {
			case 'groups':
			case 'circles':
			case 'events':
			case 'weddings':
				if (!$id) return false;
				// paginate 
				App::Import('Controller', 'GroupsController', null, null, 'groups_controller.php');
				$PaginateController = new GroupsController;
				$paginateArray = $PaginateController->paginate[$paginateModel];
				$paginateArray = $Model->getPaginatePhotosByGroupId($id, $paginateArray);
				$paginateArray['conditions'] = @$Model->appendFilterConditions($passedArgs, $paginateArray['conditions']);
				$this->controller->paginate[$paginateModel] = $Model->getPageablePaginateArray($this->controller, $paginateArray);
				$pageData = Set::extract($this->controller->paginate($paginateModel), "{n}.{$paginateModel}");
					// end paginate
				break;						
			case 'my':	
				$id = AppController::$ownerid;
			case 'person':
			case 'people':
			case 'users':	
				if (!$id) return;
				// paginate
				App::Import('Controller', 'PersonController', null, null, 'person_controller.php');
				$PaginateController = new PersonController;
				$paginateArray = $PaginateController->paginate[$paginateModel];
				$paginateArray = $Model->getPaginatePhotosByUserId($id, $paginateArray);
				$paginateArray['conditions'] = @$Model->appendFilterConditions($passedArgs, $paginateArray['conditions']);
				$this->controller->paginate[$paginateModel] = $Model->getPageablePaginateArray($this->controller, $paginateArray);
				$pageData = Set::extract($this->controller->paginate($paginateModel), "{n}.{$paginateModel}");
				// end paginate
				break;
			case 'tags':
				App::Import('Controller', 'Tags.TagsController', true, null, 'tags_controller.php');
				$PaginateController = new TagsController;
				$paginateArray = $PaginateController->paginate[$paginateModel];
				$paginateArray = $Model->getPaginatePhotosByTagId($id, $paginateArray);
				$paginateArray['conditions'] = @$Model->appendFilterConditions($passedArgs, $paginateArray['conditions']);
				$this->controller->paginate[$paginateModel] = $Model->getPageablePaginateArray($this->controller, $paginateArray);
				$pageData = Set::extract($this->controller->paginate($paginateModel), "{n}.{$paginateModel}");
				// end paginate
				break;
			default:				
				return false;
				break;	
		}		
// debug($pageData); exit;
		$options['cache_key'] = $cacheKey;
		// get updated request
		$route['named'] = array_merge($route['named'], $passedArgs);
		unset($route['url']['ext']);
		$options['request'] = Router::reverse($route);;
		$castingCall = $this->getCastingCall($pageData, true, $options);		// cache=true
		return $castingCall;
	}
	function cache_Lightbox($aids, $MAX_AGE = null) {
		if ($MAX_AGE == null) $MAX_AGE = CastingCallComponent::$MAX_AGE;		
		if (Session::read('castingCall.Lightbox.key', $aids)) {
			$ccLightbox = Session::read('castingCall.Lightbox.castingCall');
			$ccLightbox['CastingCall']['Cached'] = true;
			$timestamp = $ccLightbox['CastingCall']['Timestamp'];
			if (time() - $timestamp <= $MAX_AGE) {
				return $ccLightbox;
			} 
		}
		// cache miss or expired, delete key
		Session::delete('castingCall.Lightbox');
		return false;
	}

}
?>