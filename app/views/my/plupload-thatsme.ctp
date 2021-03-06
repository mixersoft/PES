<?php
	$uploadHost =  (Configure::read('isLocal')) ? 'thats-me' : 'thats-me.snaphappi.com';
	$this->Layout->blockStart('HEAD');
?>
<link rel="stylesheet" href="//ajax.googleapis.com/ajax/libs/jqueryui/1.8.9/themes/base/jquery-ui.css" type="text/css" />
<link rel="stylesheet" href="/js/plupload/jquery.ui.plupload/css/jquery.ui.plupload.css" type="text/css" />

<script  type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jquery/1.9.0/jquery.min.js"></script>
<script type="text/javascript" src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.2/jquery-ui.min.js"></script>

<!-- production -->

<!-- 
<script type="text/javascript" src="/min/b=js/plupload&amp;f=jquery.cookie.js,jquery.ba-throttle-debounce.min.js,moxie.js,plupload.dev.js,jquery.ui.plupload/lazy_preload.js,jquery.ui.plupload/jquery.ui.plupload.js,plupload-snappi.js&123"></script>
-->
<!--  debug -->
 
<script type="text/javascript" src="/js/plupload/jquery.cookie.js"></script>
<script type="text/javascript" src="/js/plupload/jquery.ba-throttle-debounce.min.js"></script>
<script type="text/javascript" src="/js/plupload/moxie.js"></script>
<script type="text/javascript" src="/js/plupload/plupload.dev.js"></script>
<script type="text/javascript" src="/js/plupload/jquery.ui.plupload/lazy_preload.js"></script>
<script type="text/javascript" src="/js/plupload/jquery.ui.plupload/jquery.ui.plupload.js"></script>
<script type="text/javascript" src="/js/plupload/plupload-snappi.js"></script>

<style type="text/css">
	body {
		background: transparent;
	}
	form#form {
		margin:	0;
	}
	#uploader_container {
	}
	.nowrap {
		white-space: nowrap;
	}
	.plupload_logo {
		background: url(http://<?php  echo $uploadHost; ?>/img/beachfront/icon-sm-05.png) no-repeat scroll center center;
		background-size: 60px 60px;
	}
	.plupload_droptext {
		position:relative;
		font-size: 13px;
	}
	.plupload_droptext .header {
		padding: 20px 4px;
	}
	.plupload_droptext .header > span {
	}
	.plupload_droptext .header > span.strong {
	}
	.plupload_droptext div {
		line-height: 1.2;
	}
	.copy-paste {
		background-color: #EEEEEE;
		border: 1px dotted black;
		padding: 2px;
		font-size: inherit !important;
		margin: 0 !important;
    	text-align: center;
	}
	.dragover {
		-moz-box-shadow: inset 0 0 5px 5px #888;
		-webkit-box-shadow: inset 0 0 5px 5px#888;
		box-shadow: inset 0 0 5px 5px #888;
	}
	.plupload-help {
		background-color: #FFF;
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		z-index: 1000;
		height: 100%;
		padding-top: 1em;
	}
	.plupload-help * {
		background-color: white;
	}
	.plupload-help .header {
		padding: 20px 4px;
	}
	.plupload-help .header > span {
		color: #666;
		font-size: 2em;
		font-weight: normal;
	}
	.plupload-help .header > span.strong {
		color: darkred;
		font-weight: bold;
	}
	.plupload-help .body {
		text-align: left;
	}
	.plupload-help .plupload_button:hover {
		background: url("images/ui-bg_glass_75_dadada_1x400.png") repeat-x scroll 50% 50% #DADADA;
	    border: 1px solid #999999;
	    color: #212121;
	    font-weight: normal;
    }
    
    .plupload-help.confirm-not-chrome .header img, .plupload-help.confirm-prefer-browse .header img {
		height: 36px;
	}
	.plupload-help ul {
		margin: 0 auto;
	}
	.plupload-help ul li {
		list-style: disc inside none;
	}
	.plupload-help.confirm-not-chrome ul {
		width: 360px;
	}
	.plupload-help.confirm-prefer-browse ul {
		width: 420px;
	}
	
</style>

<?php 		
	$this->Layout->blockEnd();		
?>

<form id="form" method="post" action="dump.php"  class='hide'>
	<div id="uploader">
		<p>Your browser doesn't have Flash, Silverlight or HTML5 support.</p>
	</div>
	<div class='fallback hide'>
		<br /> 	<input type="submit" value="Submit" />
	</div>
</form>
<div id="markup-uploader" class="hide">
	<div class="plupload-help is-chrome">
		<div class='header'>
			<span>Works better with </span>
			<img src='/static/img/providers/chrome_logo_2x.png'>
		</div>
		<div class='subhead'>
			<div>It's easy with Chrome &mdash; drag <u>folders</u> here and we'll find all the JPGs.</div>
		</div>
	</div>
	<div class="plupload-help not-chrome">
		<div class='header'>
			<span>Works better with</span>
			<a href="http://www.google.com/chrome" target="_blank" title="Don't have Chrome? Click here to get it.">
				<img src='/static/img/providers/chrome_logo_2x.png'>
				</a>
		</div>
		<div class='subhead'>
			<p>Only the Chrome browser allows you to drag folders into this box.</p>
			<p>If you plan to upload 100s of photos, 
				please open this page in Chrome 
				<input type="text" size="48" value="<?php echo "http://{$uploadHost}/users/upload"; ?>" onclick="this.select();" class="copy-paste">
			</p>
		</div>
	</div>
	<div class="plupload-help confirm-not-chrome">
		<div class='header'>
			<span class='strong'>Are you sure you don't want to use</span>
			<a href="http://www.google.com/chrome" target="_blank" title="Don't have Chrome? Click here to get it.">
				<img src='/static/img/providers/chrome_logo_2x.png'>
			</a>
			<span>?</span>
			<label class="plupload_button ui-button ui-widget ui-state-default ui-button-text-only" 
				role="button" aria-disabled="false" aria-pressed="true">
				<span class="ui-button-text strong">I'm sure</span>
			</label>		
		</div>
		<div class='body'>
			<ul><p>Chrome gives you these key benefits:</p>
				<li>drag folders and we'll find the JPGs</li>
				<li>20x faster uploads with web-sized photos (640px)</li>
				<li>duplicate detection avoids uploading the same file twice</li>
			</ul>
		</div>
		<br />
		<p>Please open this page in Chrome <input type="text" size="32" value="<?php echo Router::url($this->here, true); ?>" onclick="this.select();" class="copy-paste">
	</div>
	<div class="plupload-help confirm-prefer-browse">
		<div class='header'>
			<span class='strong'>Are you sure you don't want to drag folders?</span>
			<label class="plupload_button ui-button ui-widget ui-state-default ui-button-text-only" 
				role="button" aria-disabled="false" aria-pressed="true">
				<span class="ui-button-text strong">I'm sure</span>
			</label>		
		</div>
		<div class='body'>
			<ul><p>Snaphappi works better when you drop entire folders of JPGs here. We:</p>
				<li>automatically scan your folders for JPGs, and</li>
				<li>find duplicates better when you include folder names.</li>
			</ul>
		</div>
	</div>
</div>

