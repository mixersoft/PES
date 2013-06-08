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

</script>

