<?php 
	echo $this->element('nav/section'); 
?>
<div class="groups all">
	<div id='paging-groups' class='paging-content'  xhrTarget='paging-groups-inner'>
		<?php echo $this->element('/group/paging-groups');?>
	</div>
</div>
<?php	// tagCloud
	$ajaxSrc = Router::url(array('plugin'=>'', 'controller'=>'tags','action'=>'all', 'filter'=>'Group'));
	echo "<div id='tags-preview-xhr' class='fragment' ajaxSrc='{$ajaxSrc}'></div>";
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
