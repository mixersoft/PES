<?php
/**
 * @param array $photos - usually $data['Asset'] from $Model->find()
 */
$context_class = Session::read('lookup.context.keyName');	
$passedArgs = Configure::read('passedArgs.min');
$isPreview = !empty($this->params['url']['preview']);
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
		<div class='grid_16'></div>
	</section>
</div>
	
	
	
<?php 
	if ($isXhr) {
		$this->Layout->blockStart('javascript'); ?>
		<script type="text/javascript">
			// xhr response
			SNAPPI.mergeSessionData();
			SNAPPI.domJsBinder.bindAuditions2Photoroll();
			// SNAPPI.filter.initRating();
		</script>
<?php		
		$this->Layout->blockEnd();
	} else {
		$this->Layout->blockStart('javascript');
?> 	
	<script type="text/javascript">
		// HTTP GET response
		PAGE.orderBy = function (o) {
			window.location.href = o.options[o.selectedIndex].value;
		} 
		PAGE.toggleDisplayOptions  = function(o){
			var Y = SNAPPI.Y;
			try {
				SNAPPI.STATE.showDisplayOptions = SNAPPI.STATE.showDisplayOptions ? 0 : 1;
				PAGE.setDisplayOptions();
			} catch (e) {}
		};
		PAGE.setDisplayOptions = function(){
			var Y = SNAPPI.Y;
			try {
				if (SNAPPI.STATE.showDisplayOptions) {
					Y.one('section.gallery-header li.display-option').addClass('open');
					Y.one('section.gallery-display-options').removeClass('hide');
				} else {
					Y.one('section.gallery-header li.display-option').removeClass('open');
					Y.one('section.gallery-display-options').addClass('hide');
				}	
			} catch (e) {}
		};
		/**
		 * run after EACH XHR request
		 */
		var initOnce = function() {
			try {
				SNAPPI.mergeSessionData();
				PAGE.setDisplayOptions();
				SNAPPI.domJsBinder.bindAuditions2Photoroll();
				// SNAPPI.filter.initRating();
			} catch (e) {}
		};
		try {
			SNAPPI.ajax.fetchXhr; 
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
