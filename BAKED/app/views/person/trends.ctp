<?php 
	echo $this->element('nav/section', array('icon_src'=>$data['User']['src_thumbnail']));
?>
<?php	// tagCloud
	$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show_more');
	if (isset($this->passedArgs['perpage'])) $xhrSrc['perpage'] = $this->passedArgs['perpage'];
	$xhrFrom = Configure::read('controller.xhrFrom');
	$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom), 'preview'=>0);
	$ajaxSrc = Router::url($xhrSrc);	
	echo "<div id='paging-tags-xhr' class='fragment' ajaxSrc='{$ajaxSrc}'></div>";
?>	
<script type="text/javascript">
var initOnce = function() {
	// init xhr paging & fetch fragments
	// NOTE: any fragments will bind own PAGE.init() method
	SNAPPI.ajax.init(); 
};
try {SNAPPI.ajax; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>