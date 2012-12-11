<?php
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['Owner']['src_thumbnail'], 'sq', 'Person');
		echo $this->element('nav/section', compact('badge_src'));  
	$this->Layout->blockEnd();	
?>	
<?php 

	$this->Layout->blockStart('HEAD');
		echo $this->Html->css('uploader/thrift-uploader');
		
	$authToken64 = base64_encode($taskID['AuthToken']);
	$sessionId64 = base64_encode($taskID['Session']);
	$baseurl64 =  base64_encode("http://{$_SERVER['HTTP_HOST']}/files/TopLevelFolder");
	$launch_SnappiUploader = "snaphappi://{$authToken64}_{$sessionId64}_ur";
	$launch_SnappiUploader_watched = "snaphappi://{$authToken64}_{$sessionId64}_sw";		
	$this->Layout->blockEnd();	
?>
<div class="grid_16">
	<h2>Upload Devices</h2>
	<div id='checking-config' class="alpha prefix_4 grid_8 suffix_4 omega">
		<div id="top-level-folder-wrap"  class='blue rounded-5'>
			<div class="wrap">
				<h1>Checking configuration</h1>
				<div class="progress meter pending"><span class="fill"></span></div>
			</div>
		</div>
	</div>	
	<div class='thrift-devices'>
		<p>These are the devices from which you have already uploaded photos.</p>
		<p>If no device is selected, please manually identify the current device or create a new one.</p>
		<div class="wrap">
		<?php echo $this->element('thrift/devices'); ?>	
		</div>
	</div>
	
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

