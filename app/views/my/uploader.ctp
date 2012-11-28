<?php
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['Owner']['src_thumbnail'], 'sq', 'Person');
		echo $this->element('nav/section', compact('badge_src'));  
	$this->Layout->blockEnd();	
?>	
<?php 

	$basepath = Configure::read('path.topLevelFolder.basepath');
	$path_RunSwf = "{$basepath}/badge/TLFBadge.swf";
	$path_InstallSwf = "{$basepath}/badge/playerProductInstall.swf";
	
	$this->Layout->blockStart('HEAD');
		echo $this->Html->script($basepath.'/badge/swfobject.js');
		
	// set native-uploader launch URIs
	/*
	 * WARNING: cannot leave authToken in HTML, it's a security error !!!!!
	 */ 
	$authToken64 = base64_encode($taskID['AuthToken']);
	$sessionId64 = base64_encode($taskID['Session']);
	$launch_SnappiUploader = "snaphappi://{$authToken64}_{$sessionId64}_ur";
	$launch_SnappiUploader_watched = "snaphappi://{$authToken64}_{$sessionId64}_sw";		
?>
    <style type="text/css" media="screen"> 
    	#top-level-folder-alt {
    		display:block;
    		text-align:left;
    	}
    	#TLFBadge {
    		margin: 20px;
    	}
    	
    </style>
	<script type="text/javascript">
      // For version detection, set to min. required Flash Player version, or 0 (or 0.0.0), for no version detection. 
        var swfVersionStr = "10.2.0";
        // To use express install, set to playerProductInstall.swf, otherwise the empty string. 
        var launchSwfUrlStr = <?php echo  "'{$path_RunSwf}'"; ?>;
        var xiSwfUrlStr = <?php echo  "'{$path_InstallSwf}'"; ?>;
        var flashvars = {
        	baseurl: 'http://'+window.location.hostname+'/files/TopLevelFolder',
        	version: '1.0',            	
        	at: <?php echo "'{$authToken64}'"; ?>,
        	si: <?php echo "'{$sessionId64}'"; ?>,
        };
        var params = {};
        params.quality = "high";
        // params.bgcolor = "#c5e8fa";
        params.allowscriptaccess = "sameDomain";
        params.allowfullscreen = "false";
        var attributes = {};
        attributes.id = "TLFBadge";
        attributes.name = "TLFBadge";
        attributes.align = "middle";
        swfobject.embedSWF(
            launchSwfUrlStr,
            "top-level-folder-alt", 
            "215", "30", 
            swfVersionStr, xiSwfUrlStr, 
            flashvars, params, attributes
        );
		PAGE.jsonData.nativeUploader = {
				authToken64: <?php  echo "'{$authToken64}'";  ?>,
				sessionId64: <?php  echo "'{$sessionId64}'";  ?>,
		}	
		
		function reloadPage(ms)
		{
			ms = ms ? ms : 5000;
			setTimeout(ms, function(){
				window.location.reload();	
			})
		}
	</script>
<?php		
	$this->Layout->blockEnd();	
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
					<a action=<?php echo $launch_SnappiUploader ?> onclick='SNAPPI.ThriftUploader.action.launchTask("ur")'>Start Uploading</a>
				</li>
				<li><input type="field" size='100' value='<?php echo "snaphappi://{$authToken64}_{$sessionId64}_ur" ?>'</input></li>
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
	if (Y.one('#top-level-folder')) {
		Y.one('#snappi-uploader-wrap').removeClass('hide');
	}
	
};
PAGE.init.push(initOnce); 
</script>	

