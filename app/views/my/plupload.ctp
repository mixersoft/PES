<?php 
	$this->Layout->blockStart('HEAD');
	?>
<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/themes/base/jquery-ui.css" type="text/css" />
<link rel="stylesheet" href="/js/plupload/jquery.ui.plupload/css/jquery.ui.plupload.css" type="text/css" />

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/jquery-ui.min.js"></script>

<!-- production -->
<!-- <script type="text/javascript" src="/js/plupload/plupload.full.min.js"></script>
<script type="text/javascript" src="/js/plupload/jquery.ui.plupload/jquery.ui.plupload.js"></script> -->

<!--  debug --> 
<script type="text/javascript" src="http://thats-me.snaphappi.com/js/vendor/jquery.cookie.js"></script>
<script type="text/javascript" src="/js/plupload/moxie.js"></script>
<script type="text/javascript" src="/js/plupload/plupload.js"></script>
<script type="text/javascript" src="/js/plupload/jquery.ui.plupload/jquery.ui.plupload.js"></script>
<?php 		
	$this->Layout->blockEnd();		

	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['User']['src_thumbnail'], 'sq', 'Person');
		echo $this->element('nav/section', compact('badge_src'));  
	$this->Layout->blockEnd();	
?>

<div class="grid_16 upload">
	<h1>Upload Photos to Snaphappi</h1>
	<section class="">
		<h1>jQuery UI Widget</h1>
		<p>You can see this example with different themes on the <a href="http://plupload.com/example_jquery_ui.php">www.plupload.com</a> website.</p>
		
		<form id="form" method="post" action="dump.php" >
			<div id="uploader">
				<p>Your browser doesn't have Flash, Silverlight or HTML5 support.</p>
			</div>
			<br />
			<input type="submit" value="Submit" />
		</form>
	</section>	
</div>
<script type="text/javascript">
// Initialize the widget when the DOM is ready

$(function() {
	var CFG = CFG || {}; 
	$("#uploader").plupload({
		// General settings
		runtimes : 'html5,flash,silverlight',
		url : '../upload.php',

		// Select multiple files at once
		multi_selection: true, // if set to true, html4 will always fail, since it can't select multiple files

		// Maximum file size
		max_file_size : '1000mb',

		// User can upload no more then 20 files in one go (sets multiple_queues to false)
		max_file_count: 999,
		
		chunks : {
			size: '1mb',
			send_chunk_number: false // set this to true, to send chunk and total chunk numbers instead of offset and total bytes
		},

		// Resize images on clientside if we can
		resize : {
			width : 640, 
			height : 640, 
			quality : 90,
			crop: false, 				// true=crop to exact dimensions, false=max dim
			preserve_headers: true,		// BUT do NOT update EXIF size after resize
		},

		// Specify what files to browse for
		filters : [
			{title : "Image files", extensions : "jpg,gif,png"},
			// {title : "Zip files", extensions : "zip,avi"}
		],

		// Rename files by clicking on their titles
		rename: true,
		
		// Sort files
		sortable: true,

		// Enable ability to drag'n'drop files onto the widget (currently only HTML5 supports that)
		dragdrop: true,

		// Views to activate
		views: {
			list: true,
			thumbs: true, // Show thumbs
			active: 'list',
			remember: false,
		},

		// Flash settings
		flash_swf_url : '../../js/Moxie.swf',

		// Silverlight settings
		silverlight_xap_url : '../../js/Moxie.xap',
		
		
		
		/*
		 * snaphappi customizations
		 */
		multiple_queues : true,
		unique_names: true,
		prevent_duplicates: true,
		autostart: false,
		max_retries: 0,
		multipart: true,
		multipart_params: {},
		preinit: {
			PostInit: function(up, params) {	
				// TODO: up.features is not initialized
				if (up.features.dragdrop) {
					var target = $('#uploader_dropbox');
					target.ondragover = function(event) {
						event.dataTransfer.dropEffect = "copy";
					};
					target.ondragenter = function() {
						this.className = "dragover";
					};
					target.ondragleave = function() {
						this.className = "";
					};
					target.ondrop = function() {
						this.className = "";
					};
				}
				$('form#form input[type=submit]').addClass('hide');
			},
			FilesAdded: function(up, files) {
				var root;
				for (var i in files) {
					if (files[i].relativePath) {
						files[i].fileName = files[i].name;
						files[i].name = files[i].relativePath;
						root = files[i].relativePath.split('/');
						if (root.length > 2 ) files[i].root = '/'+root[1];				
						else files[i].root = '';
					}
					try {
						// TODO: why can I see this value in firebug???
						var fullpath = files[i].getNative()['mozFullPath'];
						if (fullpath) files[i].fullpath = fullpath; 
					} catch (ex) {
					}
				}
				// remove duplicate files using settings.prevent_duplicates = true
			},
			QueueChanged: function(up){
				// refresh the UI widget on 5 sec delay
				// remove duplicates from UI widget view
				var check;
			},
			BeforeUpload: function(up, file) {
				var root, session, 
					utc_now = Math.floor(new Date().getTime()/1000);
				if (!( CFG['session'] && CFG['session'].BatchId) && typeof ($.cookie) != 'undefined') {
					$.cookie.json = true;
					$.cookie.defaults = {expires : 1};		// BatchId good for 1 day
					session = $.cookie("plupload");
					if (!session || !session.BatchId ) {
					 	session = $.extend(session || {}, {'BatchId': utc_now});
					} 
					$.cookie("plupload", session);
					CFG = $.extend(CFG, {session: session});
				} 
				if (CFG['session'] && CFG['session'].LastUpload) {
					 if ( CFG['session'].LastUpload - CFG['session'].BatchId > 4*60*60 ){
						// use cookie expiration to expire BatchId, instead
						// session = $.extend(session || {}, {'BatchId': utc_now});
					}
				}
				CFG['session'].LastUpload = utc_now;
			},
			UploadFile: function(up, file) {
				// up.settings.url = '../dump.php?id=' + file.id;
				up.settings.url = '/my/plupload?name=' + (file.fileName || file.name);
				up.settings.multipart = true;
				// use cakephp $this->data form for POST data
				up.settings.multipart_params = {
					'data[name]': (file.fileName || file.name), 
					'data[Relpath]': (file.relativePath || ''),
					'data[Root]': file.root || '',
					'data[BatchId]': CFG['session'].BatchId,
					'data[IsOriginal]': up.settings.resize ? 0 : 1,
				}
				var check;
			},
			viewchanged: function(event, args){
				var check;
			}
		}, 
	});
	// var uploader = $('#uploader').plupload('getUploader');
	// uploader.trigger('Init');

	// Handle the case when form was submitted before uploading has finished
	$('#form').submit(function(e) {
		// Files in queue upload them first
		if ($('#uploader').plupload('getFiles').length > 0) {

			// When all files are uploaded submit form
			$('#uploader').on('complete', function() {
				$('#form')[0].submit();
			});

			$('#uploader').plupload('start');
		} else {
			alert("You must have at least one file in the queue.");
		}
		return false; // Keep the form from submitting
	});
});
</script>

