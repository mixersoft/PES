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
<title>Snaphappi PageMaker for oDesk Projects</title>
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
<input id='url' type="text" size="120" value="<?php echo $url ?>"></input>
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