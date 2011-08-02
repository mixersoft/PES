<?php 
	echo $this->element('nav/section');
?>
<div class="tags groups">
	<h2><?php 
		$link2Tag = $this->Html->link($data['Tag']['name'], array('action'=>'home', $data['Tag']['keyname']));  
		echo "Groups for Tag {$link2Tag}";?>
	</h2>
	<div id='paging-groups' class='paging-content' xhrTarget='paging-groups-inner'>
		<?php echo $this->element('/group/paging-groups');?>
	</div>
	
	<?php	// trends
		$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show', 'filter'=>'Group');
		$xhrFrom = Configure::read('controller.xhrFrom');
		$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom));
		$ajaxSrc = Router::url($xhrSrc);	
		echo "<div id='tags-preview-xhr' class='fragment' ajaxSrc='{$ajaxSrc}'></div>";
	?>
</div>
<script type="text/javascript">
var initOnce = function() {
	// init xhr paging & fetch fragments
	// NOTE: any fragments will bind own PAGE.init() method
	SNAPPI.ajax.init();
};
try {SNAPPI.ajax; initOnce(); }			// run now for XHR request, or
catch (e) {PAGE.init.push(initOnce); }	// run from Y.on('domready') for HTTP request
</script>