<div id='photos' class='preview-photos placeholder'>
	<h3>Photos</h3>
	<?php 
	$options = array('isPreview'=>true);
	echo $this->element('/photo/roll', $options);
	?>
</div>
