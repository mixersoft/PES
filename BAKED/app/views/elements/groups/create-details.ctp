	<div id='details' class="create tab-panel hide ">
		<h3>Description</h3>
		<?php	
			$checkbox_attrs = array('legend'=> false);
			$radio_attrs = array('legend'=> false );
		?>
		<?php echo $this->Form->input('title', array('label'=>'Title'));?>
		<?php echo $this->Form->input('description', array('label'=>'Description'));?> 
		<?php echo $this->Form->input('isNC17', array_merge( $checkbox_attrs , array('label'=>'Rated NC17')));?>
		 
			<div class='submit'>
				<input id="details" type="submit" class='orange' value="Next" onclick='return PAGE.gotoStep(this, "privacy");'></input>
			</div>
	</div>