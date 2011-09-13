<?php
if (Configure::read('controller.isXhr')) {
	echo $this->element('/group/paging-inner');
	return;
};
	
/**
 * @param array $groups - usually $data['Group'] from $Model->find()
 */
	$paginateModel = Configure::read('paginate.Model');
	$state['displayPage'] = array_filter_keys($this->params['paging'][$paginateModel], array('page', 'count', 'pageCount', 'current'));
	$total = $state['displayPage']['count'] + 0;	// as int
	$isPreview = (!empty($this->params['url']['preview']));
?>
<div class='gallery-container'>
	<section class="gallery-header">
		<ul class='inline group-roll-header'>
			<li>Total of <?php echo $total; ?> Groups</li>
			<li><?php echo $this->element('context'); ?></li>
		</ul>		
	</section>
	<?php echo $this->element('/group/paging-inner'); ?>
</div>
<script type="text/javascript">
var initOnce = function() {
	// SNAPPI.cfg.MenuCfg.listenToGroupRoll();
	// SNAPPI.cfg.MenuCfg.listenToSubstituteGroupRoll();
	
<?php if (!$isPreview) {
	// add aui-paginate
	echo 'SNAPPI.Paginator.paginate_Grouproll();';
	} 
?>	
};
try {SNAPPI.ajax; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>
