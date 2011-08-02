<?php 
	echo $this->element('nav/section', array('icon_src'=>$data['Asset']['src_thumbnail']));
?>
<div id='paging-comments' class="paging-content placeholder">
	<?php echo $this->element('comments/discussion')?>
</div>	