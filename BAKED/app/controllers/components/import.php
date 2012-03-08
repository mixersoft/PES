<?php
App::import('lib', 'ShardLib');


class ImportComponent extends Object
{
	var $controller;
	public static $importpath; //  Configure::read('path.local.original.basepath');
	public static $stagepath;
	protected $shard;

	static $EXIF_FIELDS = array(
		'ExifImageWidth',
		'ExifImageLength', 
		// 'PixelXDimension', 'PixelYDimension',		
		'Orientation', 
		'DateTimeOriginal', 
		'Flash', 
		'ColorSpace', 
		'InterOperabilityIndex', 
		'InterOperabilityVersion', 
// extra fields
		'Make', 'Model', 
		'ISOSpeedRatings', 'FNumber', 'ApertureFNumber',
		'ExposureTime', 
		'ImageUniqueID',
		'ExifVersion',
		'GPSVersion', 'GPSLatitudeRef', 'GPSLatitude', 'GPSLongitudeRef', 'GPSLongitude', 
		'GPSDateStamp', 'GPSTimeStamp',
		'GPSAreaInformation',
		'COMPUTED',
		'preview',		// preserve rotates in updateExif
	);
	static $EXIF_COMPUTED_FIELDS = array(
		'Height',
		'Width',
	);
	static $EXIF_DO_NOT_CHANGE = array(	// do not overwrite this fields on updateExif
		'Orientation', 
		'InterOperabilityIndex', 
		'InterOperabilityVersion',
		'ColorSpace',
		// also, UserEdit.rotate, 
		// $exif['preview']['Orientation']
	);
	static $IPTC_FIELDS = array(
		'Caption'=>'2#120', 
		'SpecialInstructions' => '2#040', 
		'Keyword' => '2#025', 
		'Category' => '2#015', 
		'ByLine' => '2#080', 
		'ByLineTitle' => '2#085',
	);

	static $IPTC_MAX_SIZE = 5120;  // db column varchar(8192), but adjust for UTF-8


	function startup(& $controller)
	{
		$this->controller = & $controller;
		ImportComponent::$stagepath = Configure::read('path.stageroot');
		ImportComponent::$importpath = Configure::read('path.local.original');
	}

	/**
	 * findPhotos - find JPG files inside folder
	 *
	 * @param mixed $pathOrFolder - path to file, or Folder object
	 * @param boolean $recursive - look into subfolders
	 * @param int $limit -  limit return set
	 * @return array
	 */
	function findPhotos($pathOrFolder, $recursive=false, $limit=null)
	{

		if (is_string($pathOrFolder)) {
			$pathOrFolder = cleanPath($pathOrFolder);
			if (!file_exists($pathOrFolder)) {
				$BASEPATH = Configure::read('path.local.original.basepath');
				$pathOrFolder = $BASEPATH.DS.$pathOrFolder;
			}
			$folder = new Folder($pathOrFolder);
		} else $folder = $pathOrFolder;

		if ($recursive) $photos = $folder->findRecursive('(?![a-z]{2}~).*\.jpe?g');
		else {
			list($subfolders,$photos,) = $folder->read(true,true,true);
			for ($i=0;$i<count($photos);$i++)
			{
				if (preg_match( '/\.jpe?g$/i', $photos[$i])===0)  {
					unset($photos[$i]);
				}
			}
		}

		if ($limit)	return array_slice($photos, 0, $limit);
		else return $photos;
	}

	/**
	 * extract imageWidth/imageHeight from exif, file or POST
	 * 		imageWidth/Height == actual dimensions of the local file
	 * 		ExifImageWidth/Length == original dimensions of photo
	 * @params exif array - from exif_read_data()
	 * @params $options array, 
	 * 		$options['filepath], - actualy filepath of photo for getimagesize 
	 * 		$options['width'], $options['height'] - original w/h of photo, not included in exif
	 * 		$options['autoRotate'] - preview was autoRotated, may not be in sync with original exif 
	 * @return array
	 */
	
	/**
	 * hack for incorrect getMeta call
	 */
	function fixRootImagesize($src){
		$path = Stagehand::$stage_basepath.DS.$src['root'];
		if (!file_exists($path)) {
			$this->log("WARNING: missing img file, (note: >1M not copied to dev) path={$path}");
			return array('imageWidth'=>null, 'imageHeight'=>null);
		}
$this->log("WARNING: missing exif[root][imageSize], (see castingCall), path={$path}");		
		$getimagesize = getimagesize($path);
		list($imageWidth, $imageHeight, $type, $attr) = $getimagesize;
		return compact('imageWidth', 'imageHeight');
	}
	/**
	 * 	Get exif and iptc meta data for image file,
	 * 		get imageWidth, imageHeight at the very minimum
	 * 	will NOT overwrite Orientation
	 *
	 * @param $path string path - path to image file, src['root']
	 * @param $isOriginal boolean, is the (root) file on server the Original, or an autoRotated Preview 
	 * @param $exif_0 array, POST or DB json_exif field to be updated
	 * @param $autoRotate boolean, manually set autoRotate if known, otherwise assume Previews are autoRotated
	 * @return array data[exif], data [iptc] 
	 */
	function getMeta($path, $isOriginal=null, $exif_0=array(), $autoRotate=null)
	{
		if (!file_exists($path)) return false;
		
		// guess isOriginal from root filesize
		$getimagesize = getimagesize($path, $iptc);
		list($width, $height, $type, $attr) = $getimagesize;
		if ($isOriginal === null) $isOriginal = (max($width, $height) > 640);		
				
		if (is_string($exif_0)) {	
			// process json_exif from v1.8.3 snappi-uploader via POST
			$exif_0 = json_decode($exif_0, true);
		} 
		
		/*
		 * get EXIF data
		 * 1. array_filter_keys($exif_0, ImportComponent::$EXIF_DO_NOT_CHANGE) fields take priority, i.e. original exif[Orientation]
		 * 2. use file exif data before POST or DB exif 
		 */ 
		$exif = @exif_read_data($path);
		if (empty($exif)) $exif = $exif_0;
		else if ($exif && $exif_0) $exif = array_merge($exif, array_filter_keys($exif_0, ImportComponent::$EXIF_DO_NOT_CHANGE));
		
		// this is a fix to preserve manual rotates, 
		// currently saved in json_exif, NOT UserEdit.rotate
		if (isset($exif_0['preview']['Orientation'])) {
			$exif['preview'] = $exif_0['preview'];
		};
		
		
		$data = array('exif'=>NULL, 'iptc'=>NULL);
// debug($exif);		
		if (!empty($exif)) {	// jpg exif data take priority
			$data['exif'] = array_filter_keys($exif, ImportComponent::$EXIF_FIELDS);
			if ($isOriginal) {
				$autoRotate = false; $isPreview = false;
			} else {
				$autoRotate = true; $isPreview = true;
			}
			$data['exif'] = $this->_augmentFromExif($data['exif'], $autoRotate, $isPreview); 
		}
		if (!$data['exif']) {		// use actual imagesize from filepath
			$data['exif'] = $this->_augmentNoExif($exif, $getimagesize, $path);
		}
// debug($data['exif']);		
		/*
		 * get IPTC data
		 */ 
		if(!empty($iptc['APP13']))
		{
			App::import('Sanitize');
			$iptc_data = array();
			$app13 = iptcparse($iptc['APP13']);
			$total = 0; $sizes = array();
			foreach(ImportComponent::$IPTC_FIELDS as $field => $index) {
				if(isset($app13[$index]))
				{
					$value = $app13[$index];
					if(is_array($value))
					{
						$value = join(',', $value);
					}
					$iptc_data[$field] = Sanitize::html($value, array('remove'=>true));
					$sizes[$field] = strlen($iptc_data[$field]);
					$total += $sizes[$field];
				}
			}
			if ($total> ImportComponent::$IPTC_MAX_SIZE) {
				// truncate iptc fields
				asort($sizes);
				foreach ($sizes as $field => $size) {
					if ($size > $total-ImportComponent::$IPTC_MAX_SIZE) {
						$iptc_data[$field] = substr($iptc_data[$field],0, ($size - ($total - ImportComponent::$IPTC_MAX_SIZE-3))).'...';
						break;
					} else {
						$iptc_data[$field] = substr($iptc_data[$field],0, (1024-3)).'...';
						$total -= ($size - strlen($iptc_data[$field]));
					}
				}
				if ($total>ImportComponent::$IPTC_MAX_SIZE) $iptc_data = NULL;
			}
			$data['iptc'] = $iptc_data;
		}
		return $data;
	}
	function _autoRotateExifDim($exif_0, $rootExif, & $exif){
		if (isset($exif['Orientation']) && $exif['Orientation'] !== 1) return; // not autoRotated
		
		// correct ExifImagewidth, ExifImageLength, Orientation 
		// assume original ExifOrientation has be 'autorotated' & set =1
		if (
			(($rootExif['imageWidth']<$rootExif['imageHeight']) && ($exif_0['ExifImageWidth']>$exif_0['ExifImageLength'])) 
			|| (($rootExif['imageWidth']>$rootExif['imageHeight']) && ($exif_0['ExifImageWidth']<$exif_0['ExifImageLength']))
		){	// flip values
			$exif['ExifImageLength'] = $exif_0['ExifImageWidth'];
			$exif['ExifImageWidth'] = $exif_0['ExifImageLength'];
		}
		// later we will merge: array_merge($exif_0, $exif);
	}

	function _augmentFromExif($exif_0, $autoRotate, $isPreview=false){
		// may have been autoRotated, if so $exif_0 ImageWidth/Length needs work
		$exif = $rootAttr = array();
		if (!empty($exif_0['COMPUTED'])) {
			$rootAttr['imageWidth'] = $exif_0['COMPUTED']['Width'];	// preview
			$rootAttr['imageHeight'] = $exif_0['COMPUTED']['Height'];	// preview
			$rootAttr['isRGB'] = !empty($exif_0['ColorSpace']) ? ($exif_0['ColorSpace'] == 1) : 0;
			if ($autoRotate) $rootAttr['Orientation']=1;
			$exif['ApertureFNumber'] = $exif_0['COMPUTED']['ApertureFNumber']; 
			$exif['isFlash'] =  !empty($exif_0['Flash']) ? ($exif_0['Flash'] & 1) : 0;  // checks bit 0
		} else {
			// $rootAttr['isRGB'] = !empty($exif_0['ColorSpace']) ? ($exif_0['ColorSpace'] == 1) : 0;
			$this->log("ERROR: _augmentFromPreview(): no exif['COMPUTED'] data, use _augmentNoExif()", LOG_DEBUG);
		}
		
		if ($isPreview && max($rootAttr['imageWidth'], $rootAttr['imageHeight']) > 640) {
			debug("ERROR: _augmentFromPreview(): exif[COMPUTED] dimensions > 640px, use _augmentFromOriginal()??? ");
			$this->log("ERROR: _augmentFromPreview(): exif[COMPUTED] dimensions > 640px, use _augmentFromOriginal()??? ", LOG_DEBUG);
		}
		if ($autoRotate) $this->_autoRotateExifDim($exif_0, $rootAttr, $exif);
		
		unset ($exif_0['imageWidth']);
		unset ($exif_0['imageHeight']);
		unset ($exif_0['COMPUTED']);
		/*
		 * add Exif attributes for root Audition.Photo.Img on Server
		 * 	- HANDLED IN casting_call.php
		 */
		$exif['root'] = $rootAttr;
		// safety check
		$exif = array_diff_key($exif, array_flip(ImportComponent::$EXIF_DO_NOT_CHANGE));
		return array_merge($exif_0, $exif);
	}
	function _augmentNoExif($exif_0, $getimagesize, $filepath) {
		if (!empty($getimagesize[0]) && !empty($getimagesize[1]) )
		{
			$getimagesize = getimagesize($filepath);
		}
		list($width, $height, $type, $attr) = $getimagesize;
		$exif['ExifImageWidth']=$rootAttr['imageWidth']=$width;
		$exif['ExifImageLength']=$rootAttr['imageHeight']=$height;
		$exif['root']=$rootAttr;
		$exif = array_diff_key($exif, array_flip(ImportComponent::$EXIF_DO_NOT_CHANGE));
		return array_merge($exif_0, $exif);
	}
	
	function getImageSrcBySize($relpath, $size){
		return Stagehand::getImageSrcBySize($relpath, $size); // from php_lib.php

	}

	function getBasepath()
	{
		if (empty($this->basepath)) {
			$this->basepath = cleanPath(ImportComponent::$importpath['basepath']);
			//			$this->basepath = cleanPath(Configure::read('path.local.preview.basepath'));
		}
		return $this->basepath;
	}

	function getRelpath($filepath, $os=null) {
		$relpath = str_replace($this->getBasepath().DS,'',cleanPath($filepath));
		if ($os) {
			$relpath = cleanpath($relpath, $os);
		}
		return $relpath;
	}
	
	/*
	 * get batchId for import. 
	 * 		reuse batchId if import session is < 1 hour old
	 */
	function getBatchId($timestamp=null){
		$timestamp = $timestamp ? $timestamp : time();
		$batchId = isset($this->controller->current['batchId']) ? $this->controller->current['batchId'] : $timestamp;
//		if ($timestamp-$batchId > 60*60) {
		if ($timestamp-$batchId > 10) {
			// $batchId is older than 1 hour don't reusue;
			$batchId = $timestamp;
		}
		$this->controller->setCurrent('batchId', $batchId);
		return $batchId;
	}
	
	
	function shardKey($key, $uuid=null, $ext='jpg'){
		if ($uuid) {
			return Shard::getSlot($key)."/{$uuid}.{$ext}";
		} else {
			return Shard::getSlot($key);
		}
	}
	
	function import2stage($src, $dest, $stagepath=null, $move = false){
		$stagepath = $stagepath ? $stagepath : ImportComponent::$stagepath['basepath'];
		/*
		 * wrapper for copy (or move)
		 */
		if (!file_exists(dirname($stagepath.DS.$dest))) mkdir(dirname($stagepath.DS.$dest), 2775, true);
		$OS = Configure::read('Config.os');
		$src = cleanPath($src, $OS);
		$dest = cleanPath($stagepath.DS.$dest, $OS);		
//$this->log("Import->import2stage, src={$src}, dest={$dest}", LOG_DEBUG);		
		$result = copy($src, $dest);
//$this->log("result={$result}", LOG_DEBUG);		
		if ($move && $result) unlink($src);
		return $result;
	}


	function __resetExifTime($filepath, $dbTime, $exifTime, $Jhead=null)
	{
		$error = null;
		$createNewExif = ($exifTime==null);
		if ($dbTime != $exifTime)
		{
			if (empty($Jhead)) $Jhead = loadComponent('Jhead');
			$error = $Jhead->setExifTime($filepath, $dbTime, $createNewExif);
			// doublecheck result
			$meta = $this->getMeta($filepath);
			$dbTime = str_replace('-',':',$dbTime);
			if ($dbTime != $meta['exif']['DateTimeOriginal']) {
				$error = "ERROR: problem resetting Exif time to DB stored value. filepath=$filepath";
			}
		}
		return $error;
	}
	
	/*
	 * NOTE: when do we autoRotate?
	 * 	- if we autoRotate BEFORE import Src.orientation==1 in the DB, 
	 * 		- we will not know if we need to autoRotate when we fetch src for HQ processing 
	 * 	- autoRotate in StageXXX, 
	 * 		- can't use dir wildcard for jQuery
	 * 		- DECISION (for now): autoRotate staging photos, in chunks
	 * 
	 * params photos Array of relpaths for newly staged files
	 * 
	 */
	function autoRotate($photos, $stagepath=null){
		$stagepath = $stagepath ? $stagepath : ImportComponent::$stagepath['basepath'];
		
		$errors = array();
		
		/*
		 * Autorotate Staged by file (spool = true);
		 */
		if (empty($Jhead)) $Jhead = loadComponent('Jhead');
		foreach ($photos as $photo){
			$stdin[] = $Jhead->autorotate($stagepath.DS.$photo,true);
		}
		/*
		 * autorotate in chunks
		 */
		$Exec = loadComponent('Exec');
		$options = array('path'=>'W:\\usr\\bin\\jhead');
		$stdin_chunks = array_chunk($stdin,20);
		foreach($stdin_chunks as $stdin_batch)
		{
//			debug(strlen(print_r($stdin,true)));
			$errors[] = $Exec->shell($stdin_batch, $options);
		}
//debug($errors);		
		return $errors;
	}
	
	

	/*
	 * *****************************     LEGACY     *****************************
	 */



	function updateAssetExifIptcs($idOrBasepath)
	{
		App::import('model', 'Workorder');
		$Workorder = new Workorder();

		/*
		 * create workorder if BasePath
		 */
		if(is_numeric($idOrBasepath))
		{
			$workorder = $Workorder->find('first', array(
				'conditions' => array('id' => $idOrBasepath)
			, 'recursive' => -1
			));
		}
		else
		{
			$workorder = $Workorder->find('first', array(
				'conditions' => array('folder_basepath' => $idOrBasepath)
			, 'recursive' => -1
			));
		}
		extract($workorder['Workorder']);
		$errors = $Workorder->Asset->updateAssetExifIptc($workorder['Workorder']['id'], ImportComponent::$importpath['basepath'].DS);
		if ($errors) return $errors;
	}



	/**
	 * Adjust exif_DateOriginalTaken times for
	 * ALL PHOTOS FOR SAME PHOTOGRAPHER/WORKORDER, and optionally, camera
	 * using a single dateTime offset calculated by new-time - old-time
	 *
	 * @param aa $data
	 * @param String $filter, 	'photog' limits adjustment to photos by the same photog (default),
	 * 							'camera' limits adjustment to photos from the same camera
	 * return array $errors
	 *
	 Array(
	 [asset_id] => Array
	 (
	 [asset_id] => 15593
	 [new-time] => 2008-08-23 17:54:19
	 [old-time] => 2008-08-23 17:54:19
	 )
	 )
	 */
	function syncExifTime($data, $filter='camera')
	{
		if (empty($data)) return;
		$errors = array();
		App::import('model', 'Asset');
		$Asset = new Asset();

		// get data for from asset_id so we can check workorder_id
		$asset_ids = array_keys($data);
		$photos = $Asset->find('all', array(
			'conditions' => array('id' => $asset_ids)
		, 'fields' => array('id', 'workorder_id', 'iptc_ByLine', 'iptc_Category')
		, 'recursive' => -1
		));

		// check workorder_id from data
		foreach ($photos as $photo)
		{
			if (@empty($workorder_id)) $workorder_id = $photo['Asset']['workorder_id'];
			else if ($workorder_id!=$photo['Asset']['workorder_id']) {
				$errors[]="ERROR: first kiss photos are not from the same workorder. please flag.";
			}
		}
		if (!@empty($errors)) return $errors;

		/*
		 * set up Adjustments to sync photos
		 */
		$adjustments=array();
		foreach ($data as $asset_id=>$photo)
		{
			$photo['asset_id'] = $asset_id;
			$photo['workorder_id'] = $workorder_id;
			$adjustments[] = $photo;
		}
		//debug($adjustments); exit;
		/*
		 *  Save adjusted time to DB, Assets.sync_DateTimeOriginal
		 */
		$errors = $Asset->syncExifTime($adjustments, $filter);

		//debug($errors);
		//exit;

		/*
		 * copy Assets.sync_DateTimeOriginal to files using JHead
		 */
		//		debug($adjustments);
		set_time_limit(0);
		if (!isset($Jhead)) $Jhead = loadComponent('JHead');
		foreach ($adjustments as $adjustment)
		{
			$conditions = array('iptc_ByLine' => $adjustment['iptc_ByLine']
			, 'iptc_Category' => $adjustment['iptc_Category']
			, 'workorder_id'=>$workorder_id
			, 'sync_DateTimeOriginal is NOT NULL');
			$photos = $Asset->find('all', array(
				'conditions' => $conditions
			, 'fields' => array('relpath', 'exif_DateTimeOriginal', 'sync_DateTimeOriginal')
			, 'recursive' => -1
			));

			//			debug($photos);
			// format as array of jhead commands, then execute
			foreach ($photos as $photo)
			{
				if (!isset($photo['Asset']['relpath'])) debug($photo);
				$filepath = cleanPath($importpath['basepath'].DS.$photo['Asset']['relpath']);
				$meta = @$this->getMeta($filepath);
				$createNewExif = !isset($meta['exif']['DateTimeOriginal']);
				$error = $Jhead->setExifTime($filepath, $photo['Asset']['sync_DateTimeOriginal'], $createNewExif);
				if ($error) $errors[] = $error;
			}
		}
		if ($errors) return $errors;
	}

}
?>