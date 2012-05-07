<?php 
class MontageComponent extends Object
{
	var $name='Montage';
	var $controller;
//	var $components = array('Exec');
	var $uses = array();
	
	static $SIZE_PREVIEW = 640;
	

	/*
	 * Constants
	 */

	// function __construct() {
		// parent::__construct();
	// }

	function startup(& $controller)
	{
		$this->controller = $controller;
		App::import('Vendor', 'pagemaker', array('file'=>'pagemaker'.DS.'cluster-collage.4.php'));
	}
	/**
	 * call by PHP on server only
	 */
	function __getPhotos($photos, $baseurl){
		$output = array();
		// montage from controller will ALWAYS BE preview, isRehearsal=true
		foreach ($photos as $photo) {
			$p = array();
			$p['id'] = $photo['id'];
			$p['caption'] = $photo['Photo']['Caption'];
			$p['unixtime'] = $photo['Photo']['TS'];
			$p['dateTaken'] = $photo['Photo']['DateTaken'];
			$p['rating'] = $photo['Photo']['Fix']['Rating'];
			$p['width'] = $photo['Photo']['Img']['Src']['W'];
			$p['height'] = $photo['Photo']['Img']['Src']['H'];
			// flip dimensions to match orientation+rotate
			$orientation = $photo['Photo']['Img']['Src']['Orientation'];
			$rotate = $photo['Photo']['Fix']['Rotate'];
			$p = array_merge($p, Stagehand::rotate_dimensions($p, $orientation, $rotate));
			if (isset($photo['Photo']['Img']['Src']['previewSrc'])) {
				// deprecate. used by StoryMaker iOS app
				$p['src'] = $baseurl . $photo['Photo']['Img']['Src']['previewSrc'];
			} else $p['src'] = $baseurl . $photo['Photo']['Img']['Src']['rootSrc'];
			$output[] = $p;
		}
	//	sort($output, );
		return $output;
	}	
	/*
	 * sort by Rating DESC, then unixtime ASC
	 */
	function __sortPhotos($photos, $count = null){
		if (empty($photos)) return $photos;
		// Obtain a list of columns
		foreach ($photos as $key => $row) {
		    $rating[$key]  = $row['rating'];
		}
		array_multisort($rating, SORT_DESC, $photos);
		if ($count) $photos = array_slice($photos, 0, $count);
		return $photos;
	}
	/*
	 * sort arrangement roles by 'prominence'
	 */
 	function __sortRoles($arrangement){
 		$roles = $arrangement['Roles'];
		// Obtain a list of columns
		foreach ($roles as $key => $row) {
		    $area[$key]  = $row['H']*$row['W'];
		    $top[$key] = $row['Y'];
		    $left[$key] = $row['Y'];
		}
		array_multisort($area, SORT_DESC, $top, SORT_ASC, $left, SORT_ASC, $roles);
		$arrangement['Roles'] = $roles;
		return $arrangement;
	}
	
	function __normalize(& $arrangement) {
		$w = $arrangement['W'];
		$h = $arrangement['H'];
		$scale = 72;
		$arrangement['Scale'] = $scale;
		foreach ($arrangement['Roles'] as & $role) {
			$role['H'] /= $h;
			$role['Y'] /= $h;
			$role['W'] /= $w;
			$role['X'] /= $w;
		}
		// in "inches" for dpi calculation
		$arrangement['W'] /= $scale;
		$arrangement['H'] /= $scale;
	}
	/*
	 * NOTE: montage component used by /person/photos, etc.
	 * 	NOT used by /pagemaker/arrangement/
	 */
	function getArrangement($Auditions, $options=16){
		if (is_array($options)) {
			extract($options);	// $count, $allowed_ratios
		} 
		if (!isset($count)) $count = 16;
		// config params for montage
		$TIME_LIMIT_SEC = 10;
		$MAX_WIDTH = 940; 	// .container_16 .grid_16
		$MAX_HEIGHT = 940;
		$ALLOWED_RATIOS = array('h'=>'544:960', 'v'=>'7:10');  // default for montage
		$CROP_VARIANCE_MAX = 0.20;
		
		set_time_limit ( $TIME_LIMIT_SEC );
		if (isset($Auditions['CastingCall']['Auditions'])) $Auditions = $Auditions['CastingCall']['Auditions'];
		$photos = $Auditions['Audition'];
		$baseurl = $Auditions['Baseurl'];
		// $count = $count <= 16 ? $count : 16;
		$sortedPhotos = $this->__sortPhotos($this->__getPhotos($photos, $baseurl), null);
		$layoutPhotos = count($sortedPhotos) > $count ? array_slice($sortedPhotos, 0, $count) : $sortedPhotos;
// debug($layoutPhotos); exit;		

		/*
		 * get arrangement from photos
		 * params
		 * 	- cropVarianceMax, maxHeight, maxWidth
		 * 	- count
		 * 	- seed
		 * 	- index
		 */
		App::import('Vendor', 'pagemaker', array('file'=>'pagemaker'.DS.'cluster-collage.4.php'));
		$cropVarianceMax = 0.20; 
Configure::write('debug', 0);
		if (isset($allowed_ratios) && !empty($allowed_ratios)) {
			$ALLOWED_RATIOS = array_merge($ALLOWED_RATIOS, $allowed_ratios);
		}
		$collage = new ClusterCollage($CROP_VARIANCE_MAX, $MAX_HEIGHT, $MAX_WIDTH);
		$collage->setAllowedRatios($ALLOWED_RATIOS);  //H:W
		try {
			$collage->setPhotos($layoutPhotos, 'topRatedCutoff');
			$arrangement = $collage->getArrangement();
			$this->__normalize($arrangement);
			// if ($forceXHR) debug($arrangement);
			return $arrangement;							
		} catch (Exception $e) {
			return false;
		}
	}
}
?>