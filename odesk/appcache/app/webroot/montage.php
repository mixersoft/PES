<?php echo header('Pragma: no-cache');
	  echo header('Cache-control: no-cache');
	  echo header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<!--[if IE]><style> img {behavior: url(/app/pagemaker/js/fixnaturalwh.htc)}</style><![endif]-->
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="http://snaphappi.com/img/favicon.ico" type="image/x-icon"
	rel="icon" />
<title>Snaphappi Page Layout Maker</title>
<link media="screen" type="text/css"
	href="http://dev.snaphappi.com/app/pagemaker/css/pageGallery.css"
	rel="stylesheet">
<script type="text/javascript"
	src="http://yui.yahooapis.com/combo?3.3.0/build/yui/yui-min.js"> 
        </script>
<script type="text/javascript"
	src="http://dev.snaphappi.com/app/pagemaker/js/play/pageGallery.js"> 
        </script>
</head>
<body>
<div id="glass" class="hide">
<div id="centerbox" class="hide">
<div id="closeBox" class="hidden"></div>
<span id="prevPhoto"></span> <span id="nextPhoto"></span> <img
	id="lightBoxPhoto" /></div>
<div class='loading'></div>
<div id='bottom'></div>
</div>
<div id="paging" class="">
<div id="prevPage"></div>
<span id="pagenum"></span>
<div id="nextPage"></div>
</div>
<?php
/*****************************************************************
 * Get input photos from JSON request
 * 
 * 
 * request params
 */
$userid 	= '12345678-1111-0000-0000-venice------'; 	// "12345678-1111-0000-0000-paris-------", "12345678-1111-0000-0000-sardinia----"
$userid		= '12345678-1111-0000-0000-paris-------';
// $userid 	= '4d567c33-bed4-4f15-8503-21ff0a803b63'; 
$page 		= isset($_REQUEST['pg']) ? $_REQUEST['pg'] : 2;		// 
$perpage 	= isset($_REQUEST['perpage']) ? $_REQUEST['perpage'] : 12;		// becomes SQL "LIMIT 0, 24"
$rating		= isset($_REQUEST['rating']) ? $_REQUEST['rating'] : 1;		// becomes SQL "LIMIT 0, 24"
$COUNT 		= 5;		// count of photos to use in the arrangement, taken from the final, sorted array

/*
 * get JSON from url
 */
$url = "http://dev.snaphappi.com/photos/all/rating:{$rating}/page:{$page}/perpage:{$perpage}/.json?debug=0";
$rawJson = file_get_contents($url);
$json = json_decode($rawJson, true);
$json = $json['response'];
$photos = $json['castingCall']['CastingCall']['Auditions']['Audition'];
$baseurl = 'http://dev.snaphappi.com'.$json['castingCall']['CastingCall']['Auditions']['Baseurl'];


/*
 * extract key properties from photos
 */
function getPhotos($photos, $baseurl, $format = null){
	$output = array();
	foreach ($photos as $photo) {
		$p = array();
		$p['id'] = $photo['id'];
		$p['caption'] = $photo['Photo']['Caption'];
		$p['unixtime'] = $photo['Photo']['TS'];
		$p['dateTaken'] = $photo['Photo']['DateTaken'];
		$p['rating'] = $photo['Photo']['Fix']['Rating'];
		$p['width'] = $photo['Photo']['Img']['Src']['W'];
		$p['height'] = $photo['Photo']['Img']['Src']['H'];
		$p['src'] = $baseurl . $photo['Photo']['Img']['Src']['rootSrc'];
		
		if ($format == 'landscape') {	// only use wide photos, for static arrangement
			if ($p['width'] < $p['height']) continue;
		}
		
		$output[] = $p;
	}
//	sort($output, );
	return $output;
}

function getPhotoById($photos, $id){
	foreach ($photos as $photo) {
		if ($photo['id'] == $id) return $photo;
	}
	return false;
}
/*
 * sort by Rating DESC, then unixtime ASC
 */
function sortPhotos($photos, $count = 24){
	// Obtain a list of columns
	foreach ($photos as $key => $row) {
	    $rating[$key]  = $row['rating'];
	    $time[$key] = $row['unixtime'];
	}
	array_multisort($rating, SORT_DESC, $time, SORT_ASC, $photos);
	if ($count) $photos = array_slice($photos, 0, $count);
	return $photos;
}

/*
 * end INPUT
 **********************************************************************************/


/**************************************************************************************
 * output arrangement to HTML template
 * 	NOTE: this example arrangement is just a static template sorted by: W*H DESC, Y ASC, X ASC 
 * 			it DOES NOT consider cropVariance in photo placement
 */

	/*
	 * sample arrangements, json_encoded
	 */ 
	$static_arrangement['7wide'] = '{"H":7.03125,"W":12.5,"Roles":[{"H":0.69164265129683,"W":0.51058623646998,"X":0.48941376353002,"Y":0},{"H":0.59114139693356,"W":0.48941376353002,"X":0,"Y":0.40885860306644},{"H":0.40885860306644,"W":0.33849943298906,"X":0.15091433054096,"Y":0},{"H":0.30835734870317,"W":0.25529311823499,"X":0.74470688176501,"Y":0.69164265129683},{"H":0.30835734870317,"W":0.25529311823499,"X":0.48941376353002,"Y":0.69164265129683},{"H":0.20442930153322,"W":0.15091433054096,"X":0,"Y":0.20442930153322},{"H":0.20442930153322,"W":0.15091433054096,"X":0,"Y":0}],"way":"(((h-h)|h)-h)|(h-(h|h)), init: v|v","quality":6.6,"Scale":72}';
	$static_arrangement['4wide'] = '{"H":12.5,"W":10.158150851582,"Roles":[{"H":0.60948905109489,"W":1,"X":0,"Y":0},{"H":0.39051094890511,"W":0.64071856287425,"X":0.35928143712575,"Y":0.60948905109489},{"H":0.19525547445255,"W":0.35928143712575,"X":0,"Y":0.60948905109489},{"H":0.19525547445255,"W":0.35928143712575,"X":0,"Y":0.80474452554745}],"way":"h-((h-h)|h), init: h-h","quality":6.6,"Scale":72}';
	$static_arrangement['3wide'] = '{"H":12.5,"W":12.227638772927,"Roles":[{"H":0.65417867435159,"W":1,"X":0,"Y":0.34582132564841},{"H":0.34582132564841,"W":0.52863436123348,"X":0.47136563876652,"Y":0},{"H":0.34582132564841,"W":0.47136563876652,"X":0,"Y":0}],"way":"(h|h)-h, init: h-h","quality":8.1,"Scale":72}';
	
	function getArrangementFromPOST($photos) {
		$postData = array();
		foreach ($photos as $row) {
			$post['id'] = $row['id'];
			// $post['Caption'] = $row['caption'];
			$post['TS'] = $row['unixtime'];
			// $post['DateTaken'] = $row['dateTaken'];
			$post['Rating'] = $row['rating'];
			$post['W'] = $row['width'];
			$post['H'] = $row['height'];
			$postData[] = $post;
		};
		$auditionsAsJson = json_encode(array('Audition'=>$postData));
		$count = count($postData);
		$url = "http://dev.snaphappi.com/pagemaker/arrangement/.json?data[role_count]={$count}&forcexhr=1&debug=0&data[CastingCall][Auditions]={$auditionsAsJson}";
		$rawJson = file_get_contents($url);
		$json = json_decode($rawJson, true);
		$json = $json['response'];
		$arrangement = $json['arrangement'];
		return $arrangement;
	}
	function scaleArrangement(&$arrangement){
		$scale = $arrangement['Scale'];
		$arrangement['H'] *= $scale;
		$arrangement['W'] *= $scale;
		foreach ($arrangement['Roles'] as & $r) {
			$r['X'] *= $arrangement['W']; 		
			$r['Y'] *= $arrangement['H'];
			$r['W'] *= $arrangement['W'];
			$r['H'] *= $arrangement['H'];
		}
	}

 	function sortRoles($arrangement){
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
 	function exportMontage($arrangement, $sortedPhotos) {
	 	$arrangementTemplate = "<div style='background-color: lightgray; margin: 2px auto; height: %fpx; width: %fpx;' class='pageGallery'>";
	 	$photoTemplate = "<img title='%s/5 : %s : %s' src='%s' style='height: %fpx; width: %fpx; left: %fpx; top: %fpx; position: absolute; border: 3px solid lightgray; cursor: pointer;'>";
	 	$role_count = count($arrangement['Roles']);
	 	
	 	$outputHTML = sprintf($arrangementTemplate, $arrangement['H'], $arrangement['W']);
	 	for	($i = 0; $i < $role_count ; $i++) {
	 		$r = $arrangement['Roles'][$i];
			if (isset($r['photo_id'])) {
				$p = getPhotoById($sortedPhotos, $r['photo_id']);
				if ($p == false) break;
			} else {
		 		if (!isset($sortedPhotos[$i])) break;
				$p = $sortedPhotos[$i];
			}
	 		$outputHTML .= sprintf($photoTemplate, $p['rating'], $p['dateTaken'], $p['caption'], $p['src'], $r['H'], $r['W'], $r['X'], $r['Y'] );
	 	}
	 	$outputHTML .= '</div>';
	 	return $outputHTML;
 	}
 	
 	/****************************************************
 	 * output
 	 */
 	if (1) {	// use sample/static arrangements	
 		// static arrangements use only landscape photos
 		$layoutPhotos = sortPhotos(getPhotos($photos, $baseurl, 'landscape'),24);
		shuffle($static_arrangement);
 		$rawJson = array_shift($static_arrangement);
		$arrangement = json_decode($rawJson, true);
		scaleArrangement($arrangement);
		do {
			$slice = array_splice($layoutPhotos,0,count($arrangement['Roles']));
	 		$montage[] = exportMontage($arrangement, $slice);
	 	} while (!empty($layoutPhotos));
 	} else {
 		$layoutPhotos = sortPhotos(getPhotos($photos, $baseurl),3);
		if ($single_page = 1) {
			$arrangement = getArrangementFromPOST($layoutPhotos);
	 		scaleArrangement($arrangement);
			$montage[] = exportMontage($arrangement, $layoutPhotos);
		} else {
			do {
				$slice = array_splice($layoutPhotos,0,5);
				$arrangement = getArrangementFromPOST($slice);
	 			scaleArrangement($arrangement);
		 		$montage[] = exportMontage($arrangement, $slice);
		 	} while (!empty($layoutPhotos));
		}
 	}
?>

<input id='url' type="text" size="120" value="<?php echo $url ?>"></input>
<div id='check-data' class='hide' > 
	<div id='json' style="color: white;"><?php print_r ($layoutPhotos); ?></div>
	<img src='<?php echo $sortedPhotos[0]['src']?>'></img>
</div>
<div id='content' style='border:1px solid red;'>
<?php foreach ($montage as $page) {
	echo $page ; 
}
?>
</div>
<script type="text/javascript">
/*
 * use Firebug to inspect output arrays
 */
	json = {};
	json.raw=<?php echo json_encode($json);?>;
	json.photos = <?php echo json_encode($photos);?>;
	json.arrangement = <?php echo json_encode($arrangement); ?>;
</script>
</body>
</html>
