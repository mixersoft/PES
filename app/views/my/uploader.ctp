<?php
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['Owner']['src_thumbnail'], 'sq', 'Person');
		echo $this->element('nav/section', compact('badge_src'));  
	$this->Layout->blockEnd();	
?>	
<?php 

	$basepath = Configure::read('path.topLevelFolder.basepath');
	$path_RunSwf = "{$basepath}/fb_run_test_app.swf";
	$path_InstallSwf = "{$basepath}/expressInstall.swf";
	
	$this->Layout->blockStart('HEAD');
		echo $this->Html->script($basepath.'/js/swfobject.js');
?>
	<script type="text/javascript">
		var flashvars = { 
			sessionID: <?php echo "'{$taskID['Session']}'"; ?>, 
			authToken: <?php echo "'{$taskID['AuthToken']}'"; ?>, };
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

	// start native background uploader
	/*
	 * WARNING: cannot leave authToken in HTML, it's a security error !!!!!
	 */ 
	$authToken64 = base64_encode($taskID['AuthToken']);
	$sessionId64 = base64_encode($taskID['Session']);
	$launch_SnappiUploader = "window.location.href='snaphappi://{$authToken64}_{$sessionId64}_ur'";
	$launch_SnappiUploader_watched = "window.location.href='snaphappi://{$authToken64}_{$sessionId64}_sw'";

?>
<div class="grid_16 upload">
	<h1>Upload a LOT of Photos from your Desktop</h1>
	<div class="alpha grid_8">
		<div class="wrap">
			<?php echo $this->element('group/express-upload'); ?>	
		</div>
	</div>
	<div class="grid_8 omega">
		<aside class="related-content blue rounded-5">
		<?php echo $this->element('downloads')?>
		</aside>
	</div>
</div>
<div class="grid_16">
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
				<li><input type="field" value='<?php echo "snaphappi://{$authToken64}_{$sessionId64}_ur" ?>'</input></li>
				<li class='btn orange rounded-5'>
					<a onclick=<?php echo $launch_SnappiUploader_watched ?>>Watch Folders</a>
				</li>
			</ul>	
			<hr>
<?php 
	$ajaxSrc = Router::url(array('action'=>'uploader_ui'));
	echo "<div id='uploader-ui-xhr' class='xhr-get' xhrSrc='{$ajaxSrc}' delay='0'></div>";
?>
		</div>
		<div>
			<p><?php echo "authToken={$taskID['AuthToken']}"; ?></p>
			<p><?php echo "sessionId={$taskID['Session']}"; ?></p>
		</div>	
</div>

<script type="text/javascript">
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
var initOnce = function() {
	// init xhr paging & fetch xhr-gets
	// NOTE: any xhr-gets will bind own PAGE.init() method
	var Y = SNAPPI.Y;
	Y.later(5000, SNAPPI.xhrFetch, function(){
			var n = Y.one('#uploader-ui-xhr');
			this.requestFragment(n);
		}, 
		null,
		true
	);
	if (Y.one('#top-level-folder')) {
		Y.one('#snappi-uploader-wrap').removeClass('hide');
	}
	
};
PAGE.init.push(initOnce); 
</script>	

