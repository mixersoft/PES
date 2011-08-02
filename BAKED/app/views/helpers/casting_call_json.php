<?php
class CastingCallJsonHelper extends Helper {
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
	 [shot_id]=>
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
		$exif = (array)json_decode($row['json_exif']);

		if (!@empty($row[0]['FocusCenter'])) {
			$x=$row[0]['FocusCenter']['X'];
			$y=$row[0]['FocusCenter']['Y'];
			$scale=$row[0]['FocusCenter']['Scale'];
		} else {
			if (!@empty($exif['ExifImageWidth'])){
				$x = $exif['ExifImageWidth']/2;
				$y = $exif['ExifImageLength']/2;
			} else {
				$x = $exif['imageWidth']/2;
				$y = $exif['imageHeight']/2;
			}
			$scale = 2*max(array($x,$y));
		}
		$FocusCenter = array('Scale'=>$scale, 'X'=>$x, 'Y'=>$y);
		$FocusVector = array('Direction'=>0, 'Magnitude'=>0);
		$Rating = @ifed($row['rating'], null);		// owner rating
		$Votes = @ifed($row['votes'], null);
		$LayoutHint = compact('FocusCenter', 'FocusVector', 'Rating', 'Votes');

		// Src
		$W = $exif['imageWidth'];
		$H = $exif['imageHeight'];
		$src = (array)json_decode($row['json_src']);
		$Src = $src['preview'];
		if (isset($src['base64Src'])) $base64Src = $src['base64Src'];
		if (isset($src['root'])) $rootSrc = $src['root'];
		$origSrc = $src['orig'];
		$Src = compact('W', 'H', 'AutoRender', 'Src', 'base64Src','rootSrc');
		$Img = array('Src'=>$Src);

		$DateTaken = @ifed($row['dateTaken'],0);
		$TS = strtotime($DateTaken);
		$ExifColorSpace = $exif['ColorSpace'];
		$ExifFlash = $exif['Flash']==0 ? 0 : 1;
		$ExifOrientation = $exif['Orientation'];
		$W = $exif['ExifImageWidth'];
		$H = $exif['ExifImageLength'];
		$Crops = (!@empty($row['crops'])) ? $this->formatCrops($row['crops']) : '';
		$Rating = (!@empty($row['rating'])) ? $row['rating'] : null;
		$Rotate = (!@empty($row['rotate'])) ? $row['rotate'] : 1;
		$Scrub = (!@empty($row['scrub'])) ? $this->formatScrub($row['scrub'])  : '';
		$Fix = compact('Crops', 'Rating', 'Rotate', 'Scrub');
		$AutoRender = ($row['provider_name']=='snappi');


		$Photo = compact('id','W','H','Fix','Img','DateTaken','TS','ExifColorSpace','ExifFlash','ExifOrientation', 'origSrc');
		$SubstitutionREF = $row['shot_id'];
		$Shot = array('id'=>$row['shot_id'], 'count'=>$row['shot_count']);
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

	function getCastingCall($assets, $cache = true) {
		$class='Asset';
		//debug($this->params['paging'][$class]);
		//debug(($assets));
		$Request = $this->here;
		$Page = @ifed($this->params['paging'][$class]['page'], 1);
		$Pages = @ifed($this->params['paging'][$class]['pageCount'], 1);
		$Perpage = @ifed($this->params['paging'][$class]['options']['limit'], @ifed($this->params['paging'][$class]['defaults']['limit'], null));
//debug($Perpage);		
		$Total = (int)@ifed($this->params['paging'][$class]['count'],@ifed($this->params['paging']['total'][$class], count($assets['Asset'])));
		$Baseurl = Session::read('stagepath_baseurl');
		//debug(compact('Audition','Total','Perpage','Pages','Page','Baseurl'));

		$Audition = array();
		foreach ($assets['Asset'] as $asset) {
			//	debug($asset);
			$Audition[] = $this->formatAudition($asset, array());
		}

		$Auditions = compact('Audition','Total','Perpage','Pages','Page','Baseurl');
		$ID = time();
		$CastingCall = compact('ID','Auditions','Substitutions','Tags','Clusters', 'Request');

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
			Session::write("castingCall.{$ID}",$CastingCall);
		}
//		return json_encode($CastingCall);
		return $castingCall;
	}
}
?>