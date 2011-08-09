<?php 
	echo $this->element('nav/section', array('icon_src'=>$data['User']['src_thumbnail']));
?>
<div >
<h2>Upload Photos</h2>
<div class="placeholder">
	<?php echo $this->element('downloads')?>
</div>
<br />
<div class="placeholder">
<h3>Upload a few Photos</h3>
<p>If you plan to upload just a few photos, you can drag-drop the photos onto the button below. Only JPG files are allowed.</p>
<div id="file-uploader" class='center' style="padding:21px;margin:0 auto;width: 50%;">       
    <noscript>          
        <p>Please enable JavaScript to use file uploader. </p>
        <!-- or put a simple form for upload here -->
    </noscript>         
</div>
</div>

</div>
<script type="text/javascript">
var initOnce = function() {
	// init xhr paging & fetch fragments
	// NOTE: any fragments will bind own PAGE.init() method
//	SNAPPI.ajax.init(); 


	// USE default look and feel. 
	// if you want to customize uploader, use new qq.FileUploaderBasic();
	var timestamp = Math.round(new Date().getTime() / 1000);
	var uploader = new qq.FileUploader({
	    // pass the dom node (ex. $(selector)[0] for jQuery users)
	    element: document.getElementById('file-uploader'),
	    // path to server-side upload script
	    action: '/my/upload',
	    allowedExtensions:['jpg', 'jpeg'],
//	    sizeLimit: // 10Mb is the default in vender file,
	    debug: false,
	    onSubmit:function(id, fileName) {
			uploader.setParams({
				batchId: timestamp
			});  
			var check;  
		},
//		onComplete:null,
		end: null
	}); 
};
try {initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>	
