
<script type="text/javascript">
var initOnce = function() {
	SNAPPI.ajax.init(); 
};
PAGE.init.push(initOnce);
</script>
<?php 
	echo $this->element('nav/section'); 
?>
<div class="photos all ">
	<div id='paging-photos' class='paging-content' xhrTarget='paging-photos-inner'>
	<?php echo $this->element('/photo/roll');?>
	</div>
</div>		
<?php	// tagCloud
	$ajaxSrc = Router::url(array('plugin'=>'', 'controller'=>'tags','action'=>'all', 'filter'=>'Asset'));
	echo "<div id='tags-preview-xhr' class='fragment' ajaxSrc='{$ajaxSrc}'></div>";
?>	