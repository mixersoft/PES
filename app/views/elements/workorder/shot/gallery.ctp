<?php
/**
 * 
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
// debug($data);
$this->viewVars['jsonData']['STATE'] = $state;
?>

<?php  if ($isXhr && $isPreview) {
	throw new Exception("Error: no preview for Workorder shot gallery");
} else {
// HTTP GET response 
// init Gallery.Photo

		// $this->Layout->blockStart('lightbox'); 
			// echo $this->element('/lightbox'); 
		// $this->Layout->blockEnd();
		
		// init section-header
		$this->viewVars['jsonData']['listeners']['SectionOptionClick'] = null;
		echo $this->element('/workorder/shot/section-header', compact('badge_src'));
		 

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
			SNAPPI.STATE.galleryType = 'Photo';
			SNAPPI.startListeners();	// catch any PAGE.jsonData.listeners by XHR
			/*
			 *  load photo gallery of bestshots, then load shot-gallery for each bestshot 
			 */
			SNAPPI.Y.on('snappi:gallery-render-complete', function(g){
				// parse shot_CastingCall
				var onDuplicate = SNAPPI.Auditions.onDuplicate_CHECK_SHOT;
				var shot_auditionSH = new SNAPPI.SortedHash();
				
				if (g._cfg.type == SNAPPI.STATE.galleryType ) {
					// Bestshots rendered, now load ShotGallery by XHR
					// reset shotsSH before parsing soht_CastingCall
					SNAPPI.Auditions._shotsSH.clear();
	        		SNAPPI.Auditions._shotsSH = SNAPPI.Auditions.parseCastingCall(
	        				PAGE.jsonData.shot_CastingCall, 
	        				null, 
	        				SNAPPI.Auditions._shotsSH, 
	        				onDuplicate
	        		);

					g.auditionSH.each(function(selected){
						// selected.id = [assetId]_[shotId], set using SNAPPI.Auditions.onDuplicate_CHECK_SHOT
						var thumb_node = this.container.one('#'+selected.id);
						thumb_node.ShotGallery = new SNAPPI.Gallery({
							type: 'ShotGalleryShot',
							node: thumb_node,
							size: 'tn',
							render: false,
							castingCall: PAGE.jsonData.shot_CastingCall,
							sh: SNAPPI.Auditions._shotsSH,
							shots: PAGE.jsonData.shot_CastingCall.shots,
						});
						selected.Audition.Substitutions.stale = false;
						
						// lookup correct shot is PAGE.jsonData.shot_castingCall.shot[shot.id]
						var shotId = selected.id.split('_')[1],
							shot = PAGE.jsonData.shot_CastingCall.shots[shotId];
						thumb_node.ShotGallery.showShotGallery(selected, {shot:shot});
					}, g);
				}
			});
			SNAPPI.Y.on('snappi:shot-gallery-render-complete', function(g, shot){
				if (g._cfg.type == "ShotGalleryShot") {
					switch(shot.priority) {
						case '10':
							g.header.one('nav.toolbar li.btn.un-group').addClass('hide disabled');
							break;
						case '20': 
						case '30':
							// add CSS class to color by priority
							if (shot.owner_id == '506a0861-0000-4bf3-8f16-6aab0afc6d44') {	
								// for testing image-group-circle
								g.header.ancestor('.filmstrip').addClass('shot-priority-31');
							} else g.header.ancestor('.filmstrip').addClass('shot-priority-'+shot.priority);
							// add CSS for toolbar unGroup/Group button
							if (parseInt(shot.active)) {
								g.header.one('nav.toolbar li.btn.un-group').removeClass('disabled');
							} else {
								g.header.one('nav.toolbar li.btn.un-group').addClass('disabled');
								g.header.one('nav.toolbar li.btn.group').removeClass('disabled').removeClass('hide');
								g.container.ancestor('.filmstrip.shot').addClass('disabled');
							}
							break;
						default: break;
					}
				}
			});
						
			try {
				var node = SNAPPI.Y.one('nav.section-header'); 
				node.one('li.gallery').addClass('focus'); 
			    var snapGallery = new SNAPPI.Gallery({
			    	type: SNAPPI.STATE.galleryType,		// TODO: change type=ShotGallery???
			    	node: 'div.gallery-container > section.gallery.shot',
			    	// SNAPPI.Auditions.onDuplicate_CHECK_SHOT calls Auditions.add_SHOT_to_key_by_hashcode() to append Shot.Id
			    	replace: SNAPPI.Auditions.onDuplicate_CHECK_SHOT,
			    });
			} catch(e){}
			
			SNAPPI.STATE.hints = SNAPPI.STATE.hints || {}; 
		} catch (e) {}
	};
	try {
		SNAPPI.xhrFetch.fetchXhr; 
		initOnce(); 
	} catch (e) {
		PAGE.init.push(initOnce); 
	}	// run from Y.on('domready') for HTTP request
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
				echo $this->element('/workorder/shot/header-wide', compact('total', 'ownerCount', 'isPreview', 'state'));
			} else echo $this->element('/workorder/shot/header', compact('total', 'ownerCount', 'isPreview', 'state'));
		?>
		<section class="<?php if ($isWide) echo "wide "; ?>gallery shot container_16">
			<div class='container grid_16'></div>
		</section>
	</div>	