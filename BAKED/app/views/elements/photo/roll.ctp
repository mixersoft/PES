<?php
/**
 * @param array $photos - usually $data['Asset'] from $Model->find()
 */
$context_class = Session::read('lookup.context.keyName');	
$passedArgs = Configure::read('passedArgs.min');
$isPreview = !empty($this->params['url']['preview']);
$isGallery = !empty($this->params['url']['gallery']);	// init lightbox
$isWide = !empty($this->params['named']['wide']);		// fluid layout
$isXhr = Configure::read('controller.isXhr');
$paginateModel = Configure::read('paginate.Model');
$state = array();
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


<?php echo $this->element('/photo/section-header'); ?>
<div class='gallery-container' >
	<?php 
		if ($isWide) {
			echo $this->element('/photo/header-wide', array('total'=>$total));
		} else echo $this->element('/photo/header', array('total'=>$total));
	?>
	<section class="<?php if ($isWide) echo "wide "; ?>gallery photo container_16">
		<div class='container grid_16'></div>
	</section>
</div>
	
	
	
<?php 
	if ($isXhr && !$isGallery) {
		$this->Layout->blockStart('javascript'); ?>
		<script type="text/javascript">
			// xhr response
			SNAPPI.setPageLoading(true);
			SNAPPI.mergeSessionData();
			new SNAPPI.Gallery({type:'Photo'});
			// SNAPPI.filter.initRating();
		</script>
<?php		
		$this->Layout->blockEnd();
	} else {
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
				var ratingFilterNode = SNAPPI.Y.one('#filter-rating-parent');
			        if (ratingFilterNode) {
			        	SNAPPI.filter.initRating();
			        }				
			        
			        
				new SNAPPI.Gallery({type:'Photo'});
				// SNAPPI.filter.initRating();
				
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
<?php 
		$this->Layout->blockEnd();
		
		$this->Layout->blockStart('lightbox'); 
			echo $this->element('/lightbox'); 
		$this->Layout->blockEnd();
	}
?> 
