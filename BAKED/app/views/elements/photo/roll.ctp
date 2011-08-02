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
if (isset($this->passedArgs['rating'])) $state['showRatings']='show';
if ($state) $this->viewVars['jsonData']['STATE'] = $state;
$isPreview = (!empty($this->params['url']['preview']));
?>
<div id='paging-photos-inner'>
	<div class='element-roll photo placeholder' >
		<ul class='inline photo-roll-header'>
			<li>Total of <?php echo $total; ?> photos</li>
			<li><?php echo $this->element('context'); ?></li>
			<li>
				<span class='context'>
					<span id='filter-rating-parent' class='filter'>
						<span class='remove'>
							<a title='click here to REMOVE this filter' href='' onclick='window.location.reload();' >x</a>
						</span>
						at least 
					</span>
				</span>
			</li>
			<li class="button" onclick="var pr = SNAPPI.PhotoRoll.getFromDom(this); if (pr) pr.selectAll(); this.ynode().next().removeClass('hide');" >select all</li>
			<li id='select-all-pages' class="button hide" onclick="var pr = SNAPPI.PhotoRoll.getFromDom(this); if (pr) pr.selectAllPages();">select all pages</li>
			<li class="button" onclick="var pr = SNAPPI.PhotoRoll.getFromDom(this); if (pr) pr.clearAll(); this.ynode().previous().addClass('hide');">clear</li>
			<li id='show-ratings' class="button" onclick="var pr = SNAPPI.PhotoRoll.getFromDom(this); if (pr) pr.toggleRatings(this);">show Ratings</li>
			<li id='create-pagegallery' class="button" onclick="var pr = SNAPPI.PhotoRoll.getFromDom(this); if (pr) pr.launchPagemaker(this);">Create</li>
			<li id='element-roll_zoom_btn' class="button" onclick="var pr = SNAPPI.PhotoRoll.getFromDom(this); if (pr) pr.toggleZoomMode();">Zoom Mode</li>
		</ul>
		<ul class='photo-roll'></ul>
	</div>
</div>
<script type="text/javascript">
var initOnce = function() {
	SNAPPI.mergeSessionData();
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