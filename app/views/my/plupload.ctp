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
<script type="text/javascript" src="/js/plupload/snappi.js"></script>
<style type="text/css">
	.plupload_droptext {
		line-height: 120px;
		position:relative;
	}
	.plupload_droptext .header > span {
		color: #666;
		font-size: 2em;
		font-weight: normal;
		line-height: 120px;
	}
	.plupload_droptext .header > span.strong {
		color: darkred;
		font-weight: bold;
	}
	.plupload_droptext div {
		line-height: 1;
	}
	.copy-paste {
		background-color: #EEEEEE;
		border: 1px dotted black;
		padding: 2px;
    	text-align: center;
	}
	.dragover {
		-moz-box-shadow: inset 0 0 5px 5px #888;
		-webkit-box-shadow: inset 0 0 5px 5px#888;
		box-shadow: inset 0 0 5px 5px #888;
	}
	.confirm-not-chrome {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		z-index: 1000;
		background-color: white;
		height: 100%;
	}
	.confirm-not-chrome * {
		background-color: white;
	}
	.confirm-not-chrome .header > span {
		line-height: 60px;
	}
	.confirm-not-chrome .header img {
		height: 36px;
	}
	.confirm-not-chrome .body {
		text-align: left;
	}
	.confirm-not-chrome .plupload_button:hover {
		background: url("images/ui-bg_glass_75_dadada_1x400.png") repeat-x scroll 50% 50% #DADADA;
	    border: 1px solid #999999;
	    color: #212121;
	    font-weight: normal;
    }
	.confirm-not-chrome ul {
		margin: 0 auto;
		width: 340px;
	}
	.confirm-not-chrome ul li {
		list-style: disc inside none;
	}
	
</style>

<?php 		
	$this->Layout->blockEnd();		

	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['User']['src_thumbnail'], 'sq', 'Person');
		echo $this->element('nav/section', compact('badge_src'));  
	$this->Layout->blockEnd();	
?>

<div class="grid_16 upload">
	<h2>Upload Photos to Snaphappi</h2>
	<section class="">
		<noscript>Javascript is required for this action</noscript>
		<form id="form" method="post" action="dump.php"  class='hide'>
			<div id="uploader">
				<p>Your browser doesn't have Flash, Silverlight or HTML5 support.</p>
			</div>
			<div class='fallback hide'>
				<br /> 	<input type="submit" value="Submit" />
			</div>
		</form>
	</section>	
</div>
<div id="markup-uploader" class="hide">
	<div class="help is-chrome">
		<div class='header'>
			<span>Works better with </span>
			<img src='/static/img/providers/chrome_logo_2x.png'>
		</div>
		<div class='subhead'>
			<div>It's easy with Chrome &mdash; drag <u>folders</u> here and we'll find all the JPGs.</div>
		</div>
	</div>
	<div class="help not-chrome">
		<div class='header'>
			<span>Works better with</span>
			<a href="http://www.google.com/chrome" target="_blank" title="Don't have Chrome? Click here to get it.">
				<img src='/static/img/providers/chrome_logo_2x.png'>
				</a>
		</div>
		<div class='subhead'>
			<p>Only the Chrome browser allows you to drag folders into this box.</p>
			<br />
			<p>If you plan to upload 100s of photos, please open this page in Chrome
				<input type="text" size="32" value="<?php echo Router::url($this->here, true); ?>" onclick="this.select();" class="copy-paste">
			</p>
		</div>
	</div>
	<div class="help confirm-not-chrome">
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
			<ul><p>Chrome gives you these key benefits</p>
				<li>drag folders and we'll find the JPGs</li>
				<li>20x faster uploads with web-sized photos (640px)</li>
				<li>duplicate detection avoids uploading the same file twice</li>
			</ul>
		</div>
		<br />
		<p>Please open this page in Chrome <input type="text" size="32" value="<?php echo Router::url($this->here, true); ?>" onclick="this.select();" class="copy-paste">
	</div>
</div>

