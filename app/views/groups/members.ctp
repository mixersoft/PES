<?php 
if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		$badge_src = Stagehand::getSrc($data['Group']['src_thumbnail'], 'sq', $data['Group']['type']);
		echo $this->element('nav/section', compact('badge_src')); 
	$this->Layout->blockEnd();
}	
?>
	<?php echo $this->element('/member/roll');?>
