<?php 
if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		echo $this->element('nav/section', array('icon_src'=>$data['Group']['src_thumbnail']));
	$this->Layout->blockEnd();	
}	
?>
	<?php echo $this->element('/member/roll');?>
