<?php
/**
 * CakePHP Tags Plugin
 *
 * Copyright 2009 - 2010, Cake Development Corporation
 *                        1785 E. Sahara Avenue, Suite 490-423
 *                        Las Vegas, Nevada 89104
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 */
?>
<?php 
	$this->Layout->blockStart('itemHeader');
		echo $this->element('nav/section', array('badge_src'=>null)); 
	$this->Layout->blockEnd();	
?>
<div id='paging-tags' class='paging-content'  xhrTarget='paging-tags-inner'>
<?php 
	echo $this->element('tags/paging-tags');
?>
</div>
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

