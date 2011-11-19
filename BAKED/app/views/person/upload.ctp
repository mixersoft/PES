<?php 
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['User']['src_thumbnail'], 'sq', 'person');
		echo $this->element('nav/section', array('badge_src'=>$badge_src));  
	$this->Layout->blockEnd();		
?>
<style type="text/css">
#valums-file-uploader {
	border: 1px dotted gray;
	margin: 10px;
	padding: 4px;
	-webkit-border-radius: 5px;
	-moz-border-radius: 5px;	
}
h1 {
	font-size: 20pt;
}
</style>

<div class="grid_16">
	<h1>Upload Photos to Snaphappi</h1>
	<section class="alpha grid_8">
		<h2>Upload a Few Photos</h2>
		<p>If you plan to upload just a few photos, you can drag-drop the photos onto the button below. (Only JPG files, please.) 
			But, if you have a lot of photos, we strongly suggest you use the Desktop Uploader</p>
		<div class="wrap">
		<?php echo $this->element('group/express-upload'); ?>	
		</div>	
		
		<div id="valums-file-uploader" >       
		    <noscript>          
		        <p>Please enable JavaScript to use file uploader. </p>
		        <!-- or put a simple form for upload here -->
		    </noscript>         
		</div>
	</section>	
	<div class="grid_8 omega">
		<aside class="related-content blue">
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
	var uploader = new qq.FileUploader({
	    // pass the dom node (ex. $(selector)[0] for jQuery users)
	    element: document.getElementById('valums-file-uploader'),
	    // path to server-side upload script
	    action: '/my/upload',
	    allowedExtensions:['jpg', 'jpeg'],
//	    sizeLimit: // 10Mb is the default in vender file,
	    debug: false,
	    onSubmit:function(id, fileName) {
	    	var gids = getExpressUploads();
			uploader.setParams({
				'batchId': timestamp,
				'groupIds': gids,
			});  
			var check;  
		},
//		onComplete:null,
		end: null
	}); 
	/**
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


<?php echo $this->element('sqldump') ?>
