<?php 
	$this->Layout->blockStart('itemHeader');
		$badge_src=Stagehand::$default_badges['Tag'];
		echo $this->element('nav/section', compact('badge_src')); 
	$this->Layout->blockEnd();	
?>
<div id='paging-comments' class="paging-content placeholder">
	<?php echo $this->element('comments/discussion')?>
</div>	