<?php 

	$basepath = Configure::read('path.topLevelFolder.basepath');
	$path_RunSwf = "{$basepath}/fb_run_test_app.swf";
	$path_InstallSwf = "{$basepath}/expressInstall.swf";
	
	// start native background uploader
	$authToken = base64_encode($authToken);
	$sessionId = base64_encode($sessionId);
	// $authToken = base64_encode("aHR0cDovL3d3dy5zbmFwaGFwcGkuY29t");
	// $sessionId = base64_encode("Session-".String::uuid());
	$launch_SnappiUploader = "window.location.href='snaphappi://{$authToken}_{$sessionId}_ur'";
	$launch_SnappiUploader_watched = "window.location.href='snaphappi://{$authToken}_{$sessionId}_sw'";
	
	
	$this->Layout->blockStart('HEAD');
		echo $this->Html->script($basepath.'/js/swfobject.js');
?>
	<script type="text/javascript">
		var flashvars = { 
			sessionID: <?php echo "'{$sessionId}'"; ?>, 
			authToken: <?php echo "'{$authToken}'"; ?>, };
		var params = {
			menu: "false",
			scale: "noScale",
			allowFullscreen: "true",
			allowScriptAccess: "always",
			bgcolor: "",
			wmode: "direct" // can cause issues with FP settings & webcam
		};
		var attributes = {
			id:"top-level-folder"
		};
		swfobject.embedSWF(
			<?php echo  "'{$path_RunSwf}'"; ?>, 
			"top-level-folder-alt", "300", "30", "10.0.0", 
			<?php echo  "'{$path_InstallSwf}'"; ?>,
			flashvars, params, attributes);
	</script>
<?php		
	$this->Layout->blockEnd();		

	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['Owner']['src_thumbnail'], 'sq', 'Person');
		echo $this->element('nav/section', compact('badge_src'));  
	$this->Layout->blockEnd();	
	
	/*
	 * experimental: replace mode to find/replace existing photo with original
	 */ 
	$replace = isset($this->params['url']['replace']);  	
?>

<div class="grid_16 upload">
	<h1>Upload a LOT of Photos from your Desktop</h1>
	<div class="alpha grid_8">
		<div class="wrap">
			<?php echo $this->element('group/express-upload'); ?>	
		</div>
		<div id="top-level-folder-wrap" class='blue rounded-5'>
			<h1>Choose a Folder to Import</h1>
			<div id="top-level-folder-alt" >
				<h1>Get the TopLevelFolder App</h1>
				<p><a href="http://www.adobe.com/go/getflashplayer">Get Adobe Flash player</a></p>
			</div>
		</div>
		<br />
		<div id='snappi-uploader-wrap' class='blue rounded-5'>
			<h1>Snaphappi Desktop Uploader</h1>
			<ul class="inline">
				<li class='btn orange rounded-5'>
					<a onclick=<?php echo $launch_SnappiUploader ?>>Start Uploading</a>
				</li>
			</ul>
			<ul class="inline">
				<li class='btn orange rounded-5'>
					<a onclick=<?php echo $launch_SnappiUploader_watched ?>>Watch Folders</a>
				</li>
			</ul>			
		</div>
	</div>
	<div class="grid_8 omega">
		<aside class="related-content blue rounded-5">
		<?php echo $this->element('downloads')?>
		</aside>
	</div>
</div>
<script type="text/javascript">
var initOnce = function() {
	// init xhr paging & fetch xhr-gets
	// NOTE: any xhr-gets will bind own PAGE.init() method
	var Y = SNAPPI.Y;
	if (Y.one('#top-level-folder')) {
		Y.one('#snappi-uploader-wrap').removeClass('hide');
	}
	/**
	 * TODO: change to DialogAlert()
	 * get group_ids of groups for express upload/sharing
	 * @return String, comma delim string of group_ids
	 */
	var getExpressUploads = function(){
		var Y = SNAPPI.Y;
		var gids = [];
		Y.all('#express-upload-options input[type=checkbox]').each(function(n,i,l){
			if (n.get('checked')) gids.push(n.getAttribute('uuid'));
		});
		return gids.join(',');
	}

};
try {
	initOnce(); 
}			// run now for XHR request, or
catch (e) {
	PAGE.init.push(initOnce); 
	var check;
}	// run from Y.on('domready') for HTTP request
</script>	

