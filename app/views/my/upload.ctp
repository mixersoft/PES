<?php 
	$this->Layout->blockStart('HEAD');
		$basepath = Configure::read('path.fileUploader.basepath');
		echo $this->Html->css($basepath.'/client/fileuploader.css');
		echo $this->Html->script($basepath.'/client/fileuploader.js');
	$this->Layout->blockEnd();		

	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['User']['src_thumbnail'], 'sq', 'Person');
		echo $this->element('nav/section', compact('badge_src'));  
	$this->Layout->blockEnd();	
	
	/*
	 * experimental: replace mode to find/replace existing photo with original
	 */ 
	$replace = isset($this->params['url']['replace']);  	
?>

<div class="grid_16 upload">
	<h1>Upload Photos to Snaphappi</h1>
	<section class="alpha grid_8">
		<h2>Upload a Few Photos</h2>
		<p>If you plan to upload just a few photos, you can drag-drop the photos onto the button below. (Only JPG files, please.)</p>
		<br /> 
		<p>But, if you plan to upload 10+ photos, you can finish up to <b>50x faster</b> by using the <b>Snaphappi Desktop Uploader</b>.</p>
		<div class="wrap">
		<?php echo $this->element('group/express-upload'); ?>	
		</div>	
		
		<div id="valums-file-uploader" class='rounded-5'>       
		    <noscript>          
		        <p>Please enable JavaScript to use file uploader. </p>
		        <!-- or put a simple form for upload here -->
		    </noscript>         
		</div>
	</section>	
	<div class="grid_8 omega">
		<aside class="related-content blue rounded-5">
			<h1>Upload a LOT of Photos</h1>
			<?php echo $this->element('downloads')?>
		</aside>		
	</div>
	

</div>
<script type="text/javascript">
var initOnce = function() {
	// init xhr paging & fetch xhr-gets
	// NOTE: any xhr-gets will bind own PAGE.init() method
//	SNAPPI.xhrFetch.init(); 


	// USE default look and feel. 
	// if you want to customize uploader, use new qq.FileUploaderBasic();
	var timestamp = Math.round(new Date().getTime() / 1000);
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
try {initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>	

