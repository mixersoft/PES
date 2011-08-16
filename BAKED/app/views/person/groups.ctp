<script type="text/javascript">
var initOnce = function() {
	SNAPPI.ajax.init();
};
PAGE.init.push(initOnce);
</script>
<?php 
	echo $this->element('nav/section', array('icon_src'=>$data['User']['src_thumbnail']));
?>
<div class="users groups">
	<div id='paging-groups'>
	<?php echo $this->element('/group/roll');?>
	</div>
</div>
<?php	// tagCloud
		$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show', 'filter'=>'Group');
		$xhrFrom = Configure::read('controller.xhrFrom');
		$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom));
		$ajaxSrc = Router::url($xhrSrc);
	echo "<div id='tags-preview-xhr' class='fragment' ajaxSrc='{$ajaxSrc}'></div>";
?>	
