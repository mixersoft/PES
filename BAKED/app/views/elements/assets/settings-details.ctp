<script type="text/javascript">
SNAPPI.tabSection.setFocus("#tab-details");
SNAPPI.EditMode.init();
</script>
	<div id='fields' class="setting  tab-panel">
		<h3>Description</h3>
		<?php	
			$formOptions['url']=Router::url(array(
				'controller'=>Configure::read('controller.alias'), 
				'action'=>'edit', 
				$this->Form->value('Asset.id')));
			$formOptions['id']='AssetForm-fields';
			echo $this->Form->create('Asset', $formOptions);?>
			
		<?php echo $this->Form->input('caption', array('label'=>'Caption', 'readOnly'=>true));?>
		<?php echo $this->Form->input('keyword', array('label'=>'Keywords', 'readOnly'=>true));?> 
		<?php echo $this->Form->hidden('setting',array('value'=>$formOptions['id']));?>
		<?php echo $this->Form->submit("Edit", array('value'=>"Edit", 'class'=>'green')); ?>
		<?php echo $this->Form->end(); ?>
	</div>	