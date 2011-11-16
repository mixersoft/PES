<?php 
	$this->Layout->blockStart('itemHeader');
		echo $this->element('nav/section', array('badge_src'=>null)); 
	$this->Layout->blockEnd();		
?>
<?php	// tagCloud
	$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show_more');
	if (isset($this->passedArgs['perpage'])) $xhrSrc['perpage'] = $this->passedArgs['perpage'];
	$xhrFrom = Configure::read('controller.xhrFrom');
	$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom), 'preview'=>0);
	$xhrSrc = Router::url($xhrSrc);	
	echo "<div id='paging-tags-xhr' class='xhr-get' xhrSrc='{$xhrSrc}'></div>";
?>	
<script type="text/javascript">
var initOnce = function() {
	// init xhr paging & fetch xhr-gets
	// NOTE: any xhr-gets will bind own PAGE.init() method
	SNAPPI.xhrFetch.init();
};
try {SNAPPI.xhrFetch.fetchXhr; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>