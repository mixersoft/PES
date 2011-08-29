<?php
/**
 * @param array $photos - usually $data['Asset'] from $Model->find()
 */
$context_class = Session::read('lookup.context.keyName');	

$this->Paginator->options['url']['plugin']='';
$paginateModel = Configure::read('paginate.Model');
$displayPage = array_filter_keys($this->params['paging']['Asset'], array('page', 'count', 'pageCount', 'current'));
$displayPage['perpage'] = $this->params['paging'][$paginateModel]['options']['limit'] ;  
$total = $displayPage['count'];
$state = array();
if (isset($this->passedArgs['rating'])) {
	$state['showRatings']='show';
	$state['showDisplayOptions'] = 1;
}
if (isset($this->passedArgs['thumbSize'])) {
	$state['thumbSize']=$this->passedArgs['thumbSize'];
}
if ($state) $this->viewVars['jsonData']['STATE'] = $state;
$isPreview = (!empty($this->params['url']['preview']));

$isWide = !empty($this->params['named']['wide']);		// fluid layout
$isXhr = Configure::read('controller.isXhr');
?>
	<div class='element-roll photo placeholder' >
		<?php 
			if ($isWide) {
				echo $this->element('/photo/header-wide', array('total'=>$total));
			} else echo $this->element('/photo/header', array('total'=>$total));
		?>
		<section class="<?php if ($isWide) echo "wide "; ?>gallery container_16">
			<ul class='photo-roll grid_16'></ul>
		</section>
	</div>
<?php $this->Layout->blockStart('javascript');?> 	
	<script type="text/javascript">
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
			} catch (e) {}
			
			
			SNAPPI.domJsBinder.bindAuditions2Photoroll();
			// TODO: SNAPPI.filter.initRating() should be moved into photoRoll.restoreState() (?)
			// 	make sure restoreState works for both HTTP GET and XHR page loads
			// or use 'snappi:ajaxLoad' custom event
			SNAPPI.filter.initRating();
		};
		try {
			SNAPPI.ajax; 
			initOnce(); 
		} catch (e) {
			PAGE.init.push(initOnce); 
		}	// run from Y.on('domready') for HTTP request
	</script>
<?php $this->Layout->blockEnd();?> 
<?php Configure::write('js.render_lightbox', true); ?>