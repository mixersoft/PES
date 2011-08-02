<?php 
	echo $this->element('nav/section', array('icon_src'=>$data['Asset']['src_thumbnail']));
?>
<div class="assets groups">
	<div id='paging-groups' class='paging-content'  xhrTarget='paging-groups-inner'>
	<?php
		$passedArgs = $this->passedArgs;
		$options['url']['controller'] = 'photos';
		$options['url']['action'] = $this->action;
		$options['url'] = array_merge($options['url'], $passedArgs);
		$this->Paginator->options($options);
		 
		echo $this->element('/group/paging-groups');
	?>
	</div>
</div>

<?php	// tagCloud
	$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show', 'filter'=>'Group');
	$xhrFrom = Configure::read('controller.xhrFrom');
	$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom));
	$ajaxSrc = Router::url($xhrSrc);
	echo "<div id='tags-preview-xhr' class='fragment' ajaxSrc='{$ajaxSrc}'></div>";
?>	
<script type="text/javascript">
var initOnce = function() {
	SNAPPI.ajax.initPaging();
};
try {SNAPPI.ajax; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>
