<?php 
	$this->Layout->blockStart('itemHeader');
		echo $this->element('nav/section', array('badge_src'=>null)); 
	$this->Layout->blockEnd();	
?>
<div id='paging-comments' class="paging-content placeholder">
	<?php echo $this->element('comments/discussion')?>
</div>	