<?php 
// use $asset for PRIMARY asset in substitute view, NOT $data
if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['Asset']['src_thumbnail'], 'sq');
		echo $this->element('nav/section', compact('badge_src'));
	$this->Layout->blockEnd();
}
?>
<?php echo $this->element('/assets/substitutes');?>
<script type="text/javascript">
var initOnce = function() {
	// init xhr paging & fetch xhr-gets
	// NOTE: any xhr-gets will bind own PAGE.init() method
	SNAPPI.xhrFetch.init();
	SNAPPI.cfg.MenuCfg.renderPerpageMenu(); 
};
try {SNAPPI.xhrFetch.fetchXhr; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>