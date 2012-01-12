<script type="text/javascript">
SNAPPI.tabSection.setFocus("#tab-details");
SNAPPI.EditMode.init();
</script>
	<div id='fields' class="setting">
		<h3>Description</h3>
		<?php	
			$formOptions['url']=Router::url(array(
				'controller'=>Configure::read('controller.alias'), 
				'action'=>'edit', 
				$this->Form->value('Group.id')));
			$formOptions['id']='GroupForm-fields';
			$checkbox_attrs = array('legend'=> false, 'onclick'=>'return false;');
			$radio_attrs = array('legend'=> false,'onclick'=>'return false;', 'separator'=>'<br />' );
			echo $this->Form->create('Group', $formOptions);?>
			
		<?php echo $this->Form->input('title', array('label'=>'Title', 'readOnly'=>true));?>
		<?php echo $this->Form->input('description', array('label'=>'Description', 'readOnly'=>true));?>
		<?php echo $this->Form->input('isNC17', array_merge( $checkbox_attrs , array('label'=>'NSFW')));?> 
		<?php echo $this->Form->hidden('setting',array('value'=>$formOptions['id']));?>
		<?php echo $this->Form->submit("Edit", array('value'=>"Edit", 'class'=>'green')); ?>
		<?php echo $this->Form->end(); ?>
	</div>	