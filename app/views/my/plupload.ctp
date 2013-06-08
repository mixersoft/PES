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
	}
	.plupload_droptext > span {
		color: #666;
		font-size: 2em;
		font-weight: normal;
		line-height: 120px;
	}
	.plupload_droptext > div {
		line-height: 1;
	}
	.copy-paste {
		background-color: #EEEEEE;
		border: 1px dotted black;
		padding: 2px;
    	text-align: center;
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
<script type="text/javascript">

</script>

