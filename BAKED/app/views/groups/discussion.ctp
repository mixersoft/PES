<?php 
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['Group']['src_thumbnail'], 'sq', $data['Group']['type']);
		echo $this->element('nav/section', compact('badge_src'));
	$this->Layout->blockEnd();		
?>
<div id='paging-comments' class="paging-content grid_11">
	<?php echo $this->element('comments/discussion')?>
</div>	