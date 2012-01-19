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
		'Orientation', 
		'DateTimeOriginal', 
		'Flash', 
		'ColorSpace', 
		'InterOperabilityIndex', 
		'InterOperabilityVersion', 
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
	function augmentExif($exif, $options=array()) {
		if(!empty($exif)) {
			// these values are set by exif_read_data(), but NOT by AIR uploader
			if(isset($exif['COMPUTED']['Width'])) {
				// TODO: find out if this is BEFORE/AFTER autorotate
$this->log("using exif['COMPUTED']", LOG_DEBUG);
$this->log(">> computed width={$exif['COMPUTED']['Width']}, length={$exif['COMPUTED']['Height']}", LOG_DEBUG);
$this->log(">> compared to exif width={$exif['ExifImageWidth']}, length={$exif['ExifImageLength']}", LOG_DEBUG);
$this->log(">> exif_Orientation={$exif['Orientation']}", LOG_DEBUG);
				$exif['imageWidth'] = $exif['COMPUTED']['Width'];
			}
			if(isset($exif['COMPUTED']['Height'])) {
				$exif['imageHeight'] = $exif['COMPUTED']['Height'];
			}
//debug($exif['COMPUTED']);
//debug($options);
//debug('WARNING: test if exif[computed] is before/after autorotate. expecting AFTER');			
		} else {
			// create bare minimum exif array
			$exif = array();
			if (isset($options['width'])) {
				// ORIGINAL values, passed manually, usually by desktop uploader
				$exif['ExifImageWidth']=$options['width'];
				$exif['ExifImageLength']=$options['height'];
			}			
			$exif['Orientation'] = 1;
		}
		// at the very minimum, get image width/height from actual file
		// warning, this could be AFTER autorotate
		if (!empty($options['filepath']) 
			&& (empty($exif['imageWidth']) || empty($exif['imageHeight']))) 
		{
			// look at preview file to get actual size
			// typically used with AIR uploader
			$attrs = getimagesize($options['filepath']);
			$exif['imageWidth']=$attrs[0];
			$exif['imageHeight']=$attrs[1];
		} 
		$exif['isRGB'] = !empty($exif['ColorSpace']) ? ($exif['ColorSpace'] == 1) : 0;
		$exif['isFlash'] =  !empty($exif['Flash']) ? ($exif['Flash'] & 1) : 0;  // checks bit 0
		
		/*
		 * add preview to exif
		 * 	- HANDLED IN casting_call.php
		 */
		$previewExif = array_filter_keys($exif, array('imageWidth','imageHeight', 'isRGB', 'Orientation'));
		if (!empty($options['autoRotate']) 
			&& (empty($previewExif['Orientation']) || $previewExif['Orientation'] > 4)) // null, 6 or 8 
		{	
			// add this if exif[COMPUTED] is NOT autorotated
			// manually change dimension because preview is auto-rotated
//			$previewExif['imageWidth'] = $exif['imageHeight'];
//			$previewExif['imageHeight'] = $exif['imageWidth'];
			$previewExif['Orientation'] = 1;
		}		
		$exif['preview'] = $previewExif;
		
		
		return $exif;	
	}

	/**
	 * 	Get exif and iptc meta data for image file,
	 * 		get imageWidth, imageHeight at the very minimum
	 *
	 * @param string path - path to image file
	 * @return array
	 */
	function getMeta($path)
	{
		$data = array('exif'=>NULL, 'iptc'=>NULL);

		// get EXIF data
		$exif = @exif_read_data($path);
		$data['exif'] = array_filter_keys($exif, ImportComponent::$EXIF_FIELDS);
		$data['exif'] = $this->augmentExif($data['exif'], array('filepath'=>$path));
		
//		if(!empty($exif))
//		{
//			// NOTE: array_filter_keys defined in /lib/php_lib.php
//			$data['exif'] = array_filter_keys($exif, ImportComponent::$EXIF_FIELDS);
//			$exif_data = array();
//			if(isset($exif['COMPUTED']['Width'])) {
//				$data['exif']['imageWidth'] = $exif['COMPUTED']['Width'];
//			}
//			if(isset($exif['COMPUTED']['Height'])) {
//				$data['exif']['imageHeight'] = $exif['COMPUTED']['Height'];
//			}
//		}
//		// at the very minimum, get image width/height from actual file
//		if (@empty($data['exif']['imageWidth']) || @empty($data['exif']['imageHeight'])) {
//			// look at image file to get actual size
//			list($width, $height, $type, $attr) = getimagesize($path, $iptc);
//			$data['exif']['imageWidth']=$width;
//			$data['exif']['imageHeight']=$height;
//		}

		// get IPTC data
		if (!isset($iptc)) getimagesize($path, $iptc);
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