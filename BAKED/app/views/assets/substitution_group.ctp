<?php 
// use $asset for PRIMARY asset in substitute view, NOT $data
echo $this->element('nav/section', array('icon_src'=>$asset['Asset']['src_thumbnail']));
?>
<?php echo $this->element('/assets/substitutes');?>
<script type="text/javascript">
var initOnce = function() {
	// init xhr paging & fetch fragments
	// NOTE: any fragments will bind own PAGE.init() method
	SNAPPI.ajax.init();
	SNAPPI.cfg.MenuCfg.renderPerpageMenu(); 
};
try {SNAPPI.ajax; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>