<?php echo header('Pragma: no-cache');
	  echo header('Cache-control: no-cache');
	  echo header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
      
	  require_once 'cluster-collage.4.php';	 
	  $appHost = 'git3:88'; 
	  $appHost = 'aws.snaphappi.com';
	  ?>
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
$COUNT 		= $perpage;		// count of photos to use in the arrangement, taken from the final, sorted array

/*
 * get JSON from url
 */
$controller = 'person';
$url = "http://{$appHost}/{$controller}/odesk_photos/{$userid}/rating:{$rating}/page:{$page}/perpage:{$perpage}/.json?debug=0";
$rawJson = file_get_contents($url);
$json = json_decode($rawJson, true);
$photos = $json['castingCall']['CastingCall']['Auditions']['Audition'];
$baseurl = "http://{$appHost}".$json['castingCall']['CastingCall']['Auditions']['Baseurl'];
/*
 * extract key properties from photos
 */
function getPhotos($photos, $baseurl){
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
		if (isset($photo['Photo']['Img']['Src']['previewSrc'])) {
			// deprecate. used by StoryMaker iOS app
			$p['src'] = $baseurl . $photo['Photo']['Img']['Src']['previewSrc'];
		} else $p['src'] = $baseurl . $photo['Photo']['Img']['Src']['rootSrc'];
		$output[] = $p;
	}
//	sort($output, );
	return $output;
}

function getPhotosById($id, $photos){
	foreach ($photos as $key => $photo) {
		if ($photo['id'] == $id){
			return $photo;
        }
    }
	return false;
}
/*
 * sort by Rating DESC, then unixtime ASC
 */
function sortPhotos($photos, $count = null){
	// Obtain a list of columns
	foreach ($photos as $key => $row) {
	    $rating[$key]  = $row['rating'];
	}
	array_multisort($rating, SORT_DESC, $photos);
	if ($count) $photos = array_slice($photos, 0, $count);
	return $photos;
}

$sortedPhotos = sortPhotos(getPhotos($photos, $baseurl), null);
/*
 * end INPUT
 **********************************************************************************/

/*******************************************************************************************
 * PROJECT GOAL:
 * 
 * 
 * 		CREATE A SCRIPT WHICH WILL DYNAMICALLY GENERATE AN "arrangement" LIKE $example BELOW
 * 		FROM AN INPUT ARRAY OF PHOTOS LIKE $sortedPhotos ABOVE.
 * 
 * 
 ********************************************************************************************/

/**************************************************************************************
 * output arrangement to HTML template
 * 	NOTE: this example arrangement is just a static template sorted by: W*H DESC, Y ASC, X ASC 
 * 			it DOES NOT consider cropVariance in photo placement
 */

 	$example = array('H'=>718, 'W'=>1002.7026086956522);
 	$example['Roles'][] = array('H'=>441.146484057971, 'W'=>683.6130028985507, 'X'=>3.1217391304347823, 'Y'=>3.1217391304347823);
 	$example['Roles'][] = array('H'=>441.146484057971, 'W'=>300.35917101449274, 'X'=>692.978220289855, 'Y'=>3.1217391304347823);
 	$example['Roles'][] = array('H'=>258.1230811594203, 'W'=>401.3994608695652, 'X'=>184.29603188405798, 'Y'=>450.51170144927534);
 	$example['Roles'][] = array('H'=>258.1230811594203, 'W'=>401.3994608695652, 'X'=>591.9379304347826, 'Y'=>450.51170144927534);
 	$example['Roles'][] = array('H'=>258.1230811594203, 'W'=>174.93081449275363, 'X'=>3.1217391304347823, 'Y'=>450.51170144927534);

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
	 	$arrangementTemplate = "<div>Quality: {$arrangement['quality']}</div>";
	 	$arrangementTemplate .= "<div>Way: {$arrangement['way']}</div>";
	 	$arrangementTemplate .= "<div style='border: 3px solid lightgray; background-color: lightgray; margin: 6px auto; height: %fpx; width: %fpx;' class='pageGallery'>";
	 	$photoTemplate = "<img title='#%s: %s/5 : %s : %s' src='%s' "
                    . "style='height: %fpx; width: %fpx; left: %fpx; top: %fpx; position: absolute; "
                    . "cursor: pointer;'>";
	 	$role_count = count($arrangement['Roles']);
	 	
	 	$outputHTML = sprintf($arrangementTemplate, $arrangement['H'], $arrangement['W']);
	 	for	($i = 0; $i < $role_count ; $i++) {
	 		$r = $arrangement['Roles'][$i];
			$p = getPhotosById($r['photo_id'], $sortedPhotos);
			if ($p === false) break;
            $ratio = $p['width'] / $p['height'];
            $sub = 3;
            $subW = $ratio > 0 ? $sub * $ratio : $sub;
            $subH = $ratio > 0 ? $sub : $sub * $ratio; 
	 		$outputHTML .= sprintf($photoTemplate, $i, $p['rating'], 
                                $p['dateTaken'], $p['caption'], $p['src'], 
                                $r['H'], $r['W'], $r['X'], $r['Y'] );
	 	}
	 	$outputHTML .= '</div>';
	 	return $outputHTML;
 	}
 	
 	/****************************************************
 	 * output
 	 */
 	$layoutPhotos = $sortedPhotos;

        
        $collage = new ClusterCollage(0.2);
    // 	$arrangement = sortRoles($example);
        do {
                $tempPhotos = array_slice($layoutPhotos,0,$COUNT);

//                // For developement:
//                $ratings = array(5, 4, 3 ,2, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1 ,1, 1, 1);
////                $ratings = array(5, 4,3, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1 ,1, 1, 1);
//                $ratings = array_slice($ratings,0,$COUNT);
//                foreach($ratings as $key => $rating) {
//                    $tempPhotos[$key]['rating'] = $rating;
//                }

                array_splice($layoutPhotos,0,$COUNT);
                try {
                    $collage->setPhotos($tempPhotos, $tempPhotos);
                    $arrangement = $collage->getArrangement();
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
                $montage[] = exportMontage($arrangement, $tempPhotos);
        } while (!empty($layoutPhotos));
        unset($collage);
?>	  
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
<head>
<!--[if IE]><style> img {behavior: url(/app/pagemaker/static/js/fixnaturalwh.htc)}</style><![endif]-->
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="/static/img/favicon.ico" type="image/x-icon"
	rel="icon" />
<title>Snaphappi Page Layout Maker</title>
<link media="screen" type="text/css"
	href="http://<?php echo $appHost ?>/app/pagemaker/css/pageGallery.css"
	rel="stylesheet">
<script type="text/javascript"
	src="http://yui.yahooapis.com/combo?3.3.0/build/yui/yui-min.js"> 
        </script>
<script type="text/javascript"
	src="http://<?php echo $appHost ?>/app/pagemaker/static/js/play.js"> 
        </script>
</head>
<?php  
/*
 * json reponse
 */
if (strpos($_SERVER['REQUEST_URI'], '.json') || strpos($_SERVER['REQUEST_URI'], 'json=1')) {
	echo "<body style='color:white'>";
	/*
	 * json POST
	 * */
	echo "<P>JSON POST string can be seen at:  </P>";
	$debug_url = str_replace('debug=0', 'debug=1', $url);
	echo "<blockquote><a href='{$debug_url}'>{$debug_url}<a></blockquote>";
	/*
	 * json response
	 * */
	echo "<P>JSON response string:  </P>";
	echo "<blockquote>".json_encode($arrangement)."</blockquote>"; 
	echo "</body>";  
	exit;
} ?>
<body>
<div id="glass">
<div id="centerbox" class="hide">
<div id="closeBox" class="hidden"></div>
<span id="prevPhoto"></span> <span id="nextPhoto"></span> <img
	id="lightBoxPhoto" /></div>
<div class='loading'></div>
<div id='bottom'></div>
</div>
<div id="paging" class="">
<div id="prevPage"><&lt Prev</div>
<span id="pagenum"></span>
<div id="nextPage">Next &gt</div>
</div>

<input id='url' type="text" size="120" value="<?php echo $url ?>"></input>
<div id='check-data' class='hide' > 
	<div id='json' style="color: white;"><?php print_r ($sortedPhotos); ?></div>
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
	json.sortedPhotos = <?php echo json_encode($sortedPhotos);?>;
	json.arrangement = <?php echo json_encode($example); ?>;
</script>
</body>
</html>
