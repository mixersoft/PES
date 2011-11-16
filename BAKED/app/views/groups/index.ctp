<?php 
	$this->Layout->blockStart('itemHeader');
		echo $this->element('nav/section', array('badge_src'=>null));
	$this->Layout->blockEnd();		
?>
<div class="groups all">
	<div id='paging-groups' class='paging-content'  xhrTarget='paging-groups-inner'>
		<?php echo $this->element('/group/paging-groups');?>
	</div>
</div>
<?php	// tagCloud
	$ajaxSrc = Router::url(array('plugin'=>'', 'controller'=>'tags','action'=>'all', 'filter'=>'Group'));
	echo "<div id='tags-preview-xhr' class='xhr-get' xhrSrc='{$ajaxSrc}'></div>";
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
