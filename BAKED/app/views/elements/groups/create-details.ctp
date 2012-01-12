<div id='panel-details' class="create tab-panel hide ">
	<h3>Description</h3>
	<?php	
		$checkbox_attrs = array('legend'=> false);
		$radio_attrs = array('legend'=> false, 'separator'=>'<br />' );
	?>
	<?php echo $this->Form->input('title', array('label'=>'Title'));?>
	<?php echo $this->Form->input('description', array('label'=>'Description'));?> 
	<br />
	<?php echo $this->Form->input('isNC17', array_merge( $checkbox_attrs , array('label'=>'NSFW')));?>
	<div class='submit'>
		<input id="details" type="button" class='orange' value="Next" onclick='return SNAPPI.tabSection.selectByCSS("#tab-privacy");'></input>
	</div>
</div>