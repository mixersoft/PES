<?php 
if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['User']['src_thumbnail'], 'sq', 'Person');
		echo $this->element('nav/section', compact('badge_src')); 
	$this->Layout->blockEnd();	
}		
?>
<p />
<?php Configure::write('js.render_lightbox', true); ?>
<script type="text/javascript">
var initOnce = function() {
	// init xhr paging & fetch xhr-gets
	// NOTE: any xhr-gets will bind own PAGE.init() method
};
try {SNAPPI.xhrFetch.fetchXhr; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>	
