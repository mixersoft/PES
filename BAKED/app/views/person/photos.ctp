<div class="users photos">
	<div id='paging-photos'>
		<?php echo $this->element('/photo/roll');?>
	</div>
</div>

<div id="related-content" class="container_16">
<?php	// tagCloud
		$xhrSrc = array('plugin'=>'', 'controller'=>'tags','action'=>'show', 'filter'=>'Asset');
		$xhrFrom = Configure::read('controller.xhrFrom');
		$xhrSrc['?'] = array('xhrfrom'=>implode('~', $xhrFrom));
		$ajaxSrc = Router::url($xhrSrc);
	echo "<div id='tags-preview-xhr' class='fragment' ajaxSrc='{$ajaxSrc}'></div>";
?>	
</div>
