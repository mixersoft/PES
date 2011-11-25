<?php 
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['Asset']['src_thumbnail'], 'sq');
		echo $this->element('nav/section', array('badge_src'=>$badge_src));
	$this->Layout->blockEnd();	
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
	$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom),'preview'=>1);
	$xhrSrc = Router::url($xhrSrc);
	echo "<div id='tags-preview-xhr' class='xhr-get' xhrSrc='{$xhrSrc}'></div>";
?>	
<script type="text/javascript">
var initOnce = function() {
	SNAPPI.ajax.initPaging();
};
try {SNAPPI.xhrFetch.fetchXhr; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>
