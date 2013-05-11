<?php
	$isIFrame = !empty($this->params['url']['iframe']);
	if ($isIFrame) {	// iframe
		$href = "parent.window.location.href='{$link}';";		
		$fullscreen = "<div class='right' id='fullscreen' onclick=\"{$href}\" title='show fullscreen' ><img src='/static/img/css-pm/full_screen.png' /></div>";
	} else {	// fullscreen
		$href = $link;
		$fullscreen = '';
	}	
?> 
<div id="glass">
	<div id="centerbox" class="hide">
		<div id="closeBox" class="hidden"></div>
		<span id="prevPhoto" ></span>
		<span id="nextPhoto" ></span> 
		<img id="lightBoxPhoto" />
	</div>
	<div class='loading'></div>
	<div id='bottom'></div>
</div>
<div id="header" class='cf' >
	<div id="paging" class="hidden<?php if ($isPreview) echo " hide"; ?>" >
			<div class="prev" onclick=''></div> <!-- onclick is an ipad hack  -->
			<div id="pagenum"></div>
			<div class="next" onclick=''></div>
	</div>
	<?php if (!$isIFrame && !$isPreview) { ?>
	<div class="sharethis inline right hide">
		<span class='st_facebook_large' displayText='Facebook'></span>
		<span class='st_twitter_large' displayText='Tweet'></span>
		<span class='st_plusone_large' displayText='Google +1'></span>
		<span class='st_email_large' displayText='Email'></span>
		<span class='st_sharethis_large' displayText='ShareThis'></span>
	</div>
	<?php } 
		if ($isIFrame) echo $fullscreen; 
	?>
</div>
<div id='story-content' class='cf hidden'>
 	<?php foreach ($page_gallery as $row) {
		echo $row;
	} ?> 
</div>
<div id="footer"> 
	<?php if (!$isIFrame && !$isPreview) {
		echo '<div id="share">Share this story: <a href="" id="share-link">http://'.env('HTTP_HOST').'/stories/story/4f82af26-66e4-407f-aa75-66f70afc480d</a></div>';
		echo '<a target="_new" href="http://snaphappi.com"><img src="'.AppController::$http_static[0].'/static/img/css-gui/snappi-top.png"></a>';
	} ?>
</div>
<?php if ($isIFrame) {
	$this->Layout->blockStart('javascript'); ?>
<script type="text/javascript">
	var initOnce = function() {
// console.log('fire snappi:xhr-story-complete');
		SNAPPI.Y.fire('snappi:xhr-story-complete');
	};
	try {SNAPPI.xhrFetch.fetchXhr; initOnce(); }			// run now for XHR request, or
	catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>
<?php 
	$this->Layout->blockEnd();
}
?>	
