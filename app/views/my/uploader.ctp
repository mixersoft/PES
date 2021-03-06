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
	$baseurl64 =  base64_encode("http://{$_SERVER['HTTP_HOST']}/files/TopLevelFolder");
	$launch_SnappiUploader = "snaphappi://{$authToken64}_{$sessionId64}_ur";
	$launch_SnappiUploader_watched = "snaphappi://{$authToken64}_{$sessionId64}_sw";		
?>
	<script type="text/javascript">
      // For version detection, set to min. required Flash Player version, or 0 (or 0.0.0), for no version detection. 
        var swfVersionStr = "10.2.0";
        // To use express install, set to playerProductInstall.swf, otherwise the empty string. 
        var launchSwfUrlStr = <?php echo  "'{$path_RunSwf}'"; ?>;
        var xiSwfUrlStr = <?php echo  "'{$path_InstallSwf}'"; ?>;
        var flashvars = {
        	baseurl: <?php echo  "'{$baseurl64}'"; ?>,
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
            "top-level-folder-alt", 		// visible if no Adobe AIR
            "268", "70", 
            swfVersionStr, xiSwfUrlStr, 
            flashvars, params, attributes
        );
		PAGE.jsonData.nativeUploader = {
				authToken64: <?php  echo "'{$authToken64}'";  ?>,
				sessionId64: <?php  echo "'{$sessionId64}'";  ?>,
		}	
	</script>
<?php		
	$this->Layout->blockEnd();	
?>
<div class="grid_16">
	<h2>Snaphappi Desktop Uploader</h2>
</div>
<div id='checking-config' class="prefix_4 grid_8 suffix_4">
	<div id="top-level-folder-wrap"  class='blue rounded-5'>
		<div class="wrap">
			<h1>Checking configuration</h1>
			<div class="progress meter pending"><span class="fill"></span></div>
		</div>
	</div>
</div>
<div id='download-wrap' class="grid_16 hide">
	<h2>Uploading Photos</h2>
	<div class="alpha prefix_4 grid_8 suffix_4 omega">
		<aside class="blue rounded-5">
			<div class="wrap">
			<h1>Upload a LOT of Photos from your Desktop</h1>	
			<hr>
			<?php echo $this->element('downloads', array('uploader_type'=>'Thrift'))?>
			</div>
		</aside>
	</div>
</div>
<div id='snappi-uploader-wrap' class="grid_16 offscreen">
	<div class="alpha grid_5" >
		<div id="top-level-folder-wrap"  class='blue rounded-5'>
			<div class="wrap">
			<h1>Select a Folder to Import</h1>
			<div id="top-level-folder-alt" >
				<h1>Get the TopLevelFolder App</h1>
				<p><a href="http://www.adobe.com/go/getflashplayer">Get Adobe Flash player</a></p>
			</div>
		</div></div>
	</div>
	<div class="grid_11 omega" >
		<div id="thrift-uploader-wrap"  class='blue rounded-5'>
			<div class="wrap">
			<h1>Control Panel</h1>
			<ul class="actions inline">
				<li class='btn orange rounded-5'>
					<a onclick='SNAPPI.ThriftUploader.action.launchTask("ur");'>Start Uploading</a>
				</li>
				<li class='btn orange rounded-5'>
					<a onclick='SNAPPI.ThriftUploader.action.launchTask("uo");'>Upload Origs</a>
				</li>
				<li class='btn red rounded-5' onclick='SNAPPI.ThriftUploader.action.refresh(false);'>
					Stop
				</li>
				<li class='label device-label'>Device: <?php  echo !empty($device) ? $device['label'] : 'unknown';  ?></li>
			</ul>	
			<?php
				if (!empty($folders)) {
					echo "<div id='uploader-ui-xhr' class='device-id-found'>";
					echo $this->element('thrift/folders');
					echo "</div>";
				}  else {
					$ajaxSrc = Router::url(array('action'=>'uploader_folders'));
					echo "<div id='uploader-ui-xhr' class='xhr-get-disabled' xhrSrc='{$ajaxSrc}' delay='0'></div>";
				}
			?>			
			<hr>
		</div></div>
		<ul style="margin:20px 40px;">
			<li><input type="field" size='80' value='<?php echo "snaphappi://{$authToken64}_{$sessionId64}_ur" ?>'</input></li>
			<li><?php echo "authToken={$taskID['AuthToken']}"; ?></li>
			<li><?php echo "sessionId={$taskID['Session']}"; ?></li>	
		</ul>
	</div>
</div>
<div class='markup hide'>
	<div id='restart-markup' >
		<div class='restart'>
			<span>Umm, the Uploader seems to have stopped. Please click </span>
			<ul class="actions inline">
				<li class='btn orange rounded-5'>
					<a action=<?php echo $launch_SnappiUploader ?> onclick='SNAPPI.ThriftUploader.action.launchTask("ur")'>Restart</a>
					</li>
			</ul>
		</div>
	</div>
	<table><tbody>
		<tr id='folder-row-markup' class='row folder inline'>
			<td class="status"><div class="progress meter pending">
				<span class="fill">
				</span></div></td>
			<td class="is-watched"><input type="checkbox" action="watch" name="data[ThriftFolder][native_path_hash]"></td>
			<td class="label"></td>
			<td class="remove"><input type="button" class="orange" value="remove" action="remove" name="data[ThriftFolder][native_path_hash]"></td>
		</tr>					
	</tbody></table>
</div>
<script type="text/javascript">
var bootstrapReady = function(value) {
	try {
		SNAPPI.ThriftUploader.action.bootstrapReady(value);
	} catch (e) {
		// will run on SNAPPI.onYready.ThriftUploader
		SNAPPI.is_TopLevelFolder_installed = value;
	}
}
</script>	

