<?php 
// use $asset for PRIMARY asset in substitute view, NOT $data
echo $this->element('nav/section', array('icon_src'=>$asset['Asset']['src_thumbnail']));
?>
<?php echo $this->element('/assets/substitutes');?>
<script type="text/javascript">
var initOnce = function() {
	// init xhr paging & fetch xhr-gets
	// NOTE: any xhr-gets will bind own PAGE.init() method
	SNAPPI.xhrFetch.init();
};
try {SNAPPI.xhrFetch.fetchXhr; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>