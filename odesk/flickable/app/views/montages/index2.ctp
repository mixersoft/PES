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
$url = "http://aws.snaphappi.com/users/odesk_photos/{$userid}/rating:{$rating}/page:{$page}/perpage:{$perpage}/.json?debug=0";
$rawJson = file_get_contents($url);
$json = json_decode($rawJson, true);
$photos = $json['castingCall']['CastingCall']['Auditions']['Audition'];
$baseurl = 'http://aws.snaphappi.com'.$json['castingCall']['CastingCall']['Auditions']['Baseurl'];

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
		$p['src'] = $baseurl . $photo['Photo']['Img']['Src']['Src'];
		$output[] = $p;
	}
//	sort($output, );
	return $output;
}

/*
 * sort by Rating DESC, then unixtime ASC
 */
function sortPhotos($photos, $count = null){
	// Obtain a list of columns
	foreach ($photos as $key => $row) {
	    $rating[$key]  = $row['rating'];
	    $time[$key] = $row['unixtime'];
	}
	array_multisort($rating, SORT_DESC, $time, SORT_ASC, $photos);
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
	 	$arrangementTemplate = "<div style='background-color: lightgray; margin: 6px auto; height: %fpx; width: %fpx;' class='pageGallery'>";
	 	$photoTemplate = "<img title='%s/5 : %s : %s' src='%s' style='height: %fpx; width: %fpx; left: %fpx; top: %fpx; position: absolute; border: 3px solid lightgray; cursor: pointer;'>";
	 	$role_count = count($arrangement['Roles']);
	 	
	 	$outputHTML = sprintf($arrangementTemplate, $arrangement['H'], $arrangement['W']);
	 	for	($i = 0; $i < $role_count ; $i++) {
	 		$r = $arrangement['Roles'][$i];
	 		if (!isset($sortedPhotos[$i])) break;
			$p = $sortedPhotos[$i];
	 		$outputHTML .= sprintf($photoTemplate, $p['rating'], $p['dateTaken'], $p['caption'], $p['src'], $r['H'], $r['W'], $r['X'], $r['Y'] );
	 	}
	 	$outputHTML .= '</div>';
	 	return $outputHTML;
 	}
	
	
	function exportMontage2($arrangement, $sortedPhotos) {
	 	$arrangementTemplate = "<div id='scrollview-content' class='yui3-scrollview-loading'><ul>";
	 	$photoTemplate = "<li><img title='%s/5 : %s : %s' src='%s'></li>";
	 	$role_count = count($arrangement['Roles']);
	 	
	 	$outputHTML = sprintf($arrangementTemplate, $arrangement['H'], $arrangement['W']);
	 	for	($i = 0; $i < $role_count ; $i++) {
	 		$r = $arrangement['Roles'][$i];
	 		if (!isset($sortedPhotos[$i])) break;
			$p = $sortedPhotos[$i];
	 		$outputHTML .= sprintf($photoTemplate, $p['rating'], $p['dateTaken'], $p['caption'], $p['src'], $r['H'], $r['W'], $r['X'], $r['Y'] );
	 	}
	 	$outputHTML .= '</ul></div>';
	 	return $outputHTML;
 	}
 	
 	/****************************************************
 	 * output
 	 */
 	$arrangement = sortRoles($example);
 	$layoutPhotos = $sortedPhotos;
 	do {
 		$montage[] = exportMontage($arrangement, $layoutPhotos);
 		$montage2[] = exportMontage2($arrangement, $layoutPhotos);
 		array_splice($layoutPhotos,0,count($example['Roles']));
 	} while (!empty($layoutPhotos));
?>

<input id='url' type="text" size="120" value="<?php echo $url ?>"></input>
<div id='check-data' class='hide' > 
	<div id='json' style="color: white;"><?php print_r ($sortedPhotos); ?></div>
	<img src='<?php echo $sortedPhotos[0]['src']?>'></img>
</div>
<!--div id='content' style='border:1px solid red;'>
<?php 
//foreach ($montage as $page) {
	//echo $page ; 
//}
?>
</div-->

<div id='content' style='border:1px solid red;'>
<div id='scrollview-content' class='yui3-scrollview-loading'>
<ul>
<?php 

foreach ($sortedPhotos as $photos) {
?>
<li><img src="<?php echo $photos['src'];?>"></li>
<?php
}

?>
</ul>
</div>
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
<script type="text/javascript" charset="utf-8">
            YUI().use('scrollview', function(Y) {

                var scrollView = new Y.ScrollView({
                    id: 'scrollview',
                    srcNode: '#scrollview-content',
                    width: 320,
                    flick: {
                        minDistance:10,
                        minVelocity:0.3,
                        axis: "x"
                    }
                });

                scrollView.render();

                // Prevent default image drag behavior
                scrollView.get("contentBox").delegate("mousedown", function(e) {
                    e.preventDefault();
                }, "img");
            });
        </script>
