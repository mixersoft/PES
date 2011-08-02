<div id='photos' class='preview-photos placeholder'>
	<h3>Photos</h3>
	<?php 
		Configure::write(array('state.showRatings'=>'show', 'state.showSubstitutes'=>'hide' ));
		echo $this->element('/photo/roll');
	?>
</div>
