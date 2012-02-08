<?php
/**
 * @param array $photos - usually $data['Asset'] from $Model->find()
 */
$context_class = Session::read('lookup.context.keyName');	
$passedArgs = Configure::read('passedArgs.min');
$isPreview = !empty($this->params['url']['preview']);	// no paginate
$isGallery = !empty($this->params['url']['gallery']);	// init lightbox
$isWide = !empty($this->params['named']['wide']);		// fluid layout
$isXhr = Configure::read('controller.isXhr');
$paginateModel = Configure::read('paginate.Model');
$state = !empty($this->viewVars['jsonData']['STATE']) ? $this->viewVars['jsonData']['STATE'] : array();
$state['displayPage'] = array_filter_keys($this->params['paging'][$paginateModel], array('page', 'count', 'pageCount', 'current'));
$state['displayPage']['perpage'] = $this->params['paging'][$paginateModel]['options']['limit'] ;
$total = $state['displayPage']['count'] + 0;	// as int
$state['displayPage']['total'] = $total;	// as int;	
if (isset($passedArgs['rating'])) {
	$state['showRatings']='show';
	$state['showDisplayOptions'] = 1;
}
$this->viewVars['jsonData']['STATE'] = $state;

$THUMBSIZE = isset($passedArgs['thumbSize']) ?  $passedArgs['thumbSize'] : 'lm';
$THUMBSIZE = $isPreview ? 'sq' : $THUMBSIZE;
?>

<?php  if ($isXhr && $isPreview) {
// isPreview: Xhr by NOT JSON. no lightbox, no gallery-header or paginator
		$this->Layout->blockStart('javascript');
	?>
		<script type="text/javascript">
			// xhr response
			SNAPPI.setPageLoading(true);
			SNAPPI.mergeSessionData();
			var cfg ={
				type:'Photo',
				isPreview: true, 
			};
			new SNAPPI.Gallery(cfg);
		</script>

	<?php  
		$this->Layout->blockEnd();
		
} else {
// HTTP GET response 
// init Gallery.Photo


		$this->Layout->blockStart('lightbox'); 
			echo $this->element('/lightbox'); 
		$this->Layout->blockEnd();
		
		echo $this->element('/photo/section-header');

		$this->Layout->blockStart('javascript'); 
	?>
	<script type="text/javascript">
		/**
		 * run after EACH XHR request
		 */
		var initOnce = function() {
			try {
				SNAPPI.setPageLoading(true);
				SNAPPI.mergeSessionData();
				SNAPPI.UIHelper.nav.setDisplayOptions();
			    var cfg = {type:'Photo'};     
				new SNAPPI.Gallery(cfg);
				
				// add create listener
				var create = SNAPPI.Y.one('header.head nav.user li.create');
				if (create) create.on('click', SNAPPI.UIHelper.create.launch_PageGallery);
			} catch (e) {}
		};
		try {
			SNAPPI.xhrFetch.fetchXhr; 
			initOnce(); 
		} catch (e) {
			PAGE.init.push(initOnce); 
		}	// run from Y.on('domready') for HTTP request
		var check;
	</script>	
<?php   $this->Layout->blockEnd();


}		// end if - else

	/*
	 * this is the actual photo "gallery" markup
	 */
?>
	<div class='gallery-container' >
		<?php 
			if ($isWide) {
				echo $this->element('/photo/header-wide', compact('total', 'isPreview', 'state'));
			} else echo $this->element('/photo/header', compact('total', 'isPreview', 'state'));
		?>
		<section class="<?php if ($isWide) echo "wide "; ?>gallery photo container_16">
			<div class='container grid_16'></div>
		</section>
	</div>	