
<script type="text/javascript">
var initOnce = function() {
	SNAPPI.xhrFetch.init(); 
};
PAGE.init.push(initOnce);
</script>
<?php 
if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		echo $this->element('nav/section', array('badge_src'=>null));
	$this->Layout->blockEnd();
}
?>
<div class="photos all ">
	<div id='paging-photos' class='paging-content' xhrTarget='paging-photos-inner'>
	<?php echo $this->element('/photo/roll');?>
	</div>
</div>		
<?php	// tagCloud
	$ajaxSrc = Router::url(array('plugin'=>'', 'controller'=>'tags','action'=>'all', 'filter'=>'Asset'));
	echo "<div id='tags-preview-xhr' class='xhr-get' xhrSrc='{$ajaxSrc}'></div>";
?>	