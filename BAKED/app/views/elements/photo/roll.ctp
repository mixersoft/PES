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
if ($state) $this->viewVars['jsonData']['STATE'] = $state;
$isPreview = (!empty($this->params['url']['preview']));
?>
<div id='paging-photos-inner'>
	<div class='element-roll photo placeholder' >
		<section id="display-option" class="container_16">
		    <div class="grid_9">
		      <div class="counts">
		          <h2><?php echo $total; ?>  Snaps</h2>
		      </div>
		    </div>
		    <div class="grid_7" style="border:0px solid #00CC66">
				<ul class="thumb-size">
					<li class="label">Thumbnail</li>
					<li class="small"><img src="/img/snappi/img_1.gif" alt=""></li>
					<li class="med  focus"><img src="/img/snappi/img_2.gif" alt=""></li>
					<li class="large"><img src="/img/snappi/img_3.gif" alt=""></li>
				</ul>
		    	<div class="display" onclick='PAGE.toggleDisplayOptions();'>
		    		<a>Display Options <img src="/img/snappi/arrow-down.png"></a>
		    		</div>
			</div>      
		</section> 
		<?php  echo $this->element('/photo/display-options');  ?>
		<section class="gallery container_16">
			<ul class='photo-roll grid_16'></ul>
		</section>
	</div>
</div>
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
			Y.one('#display-option div.display').addClass('open');
			Y.one('#display-option-sub').removeClass('hide');
		} else {
			Y.one('#display-option div.display').removeClass('open');
			Y.one('#display-option-sub').addClass('hide');
		}	
	} catch (e) {}
};
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
	SNAPPI.cfg.MenuCfg.renderPerpageMenu();
};
try {SNAPPI.ajax; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>
<?php Configure::write('js.render_lightbox', true); ?>