<div class="assetsGroups form">
<?php echo $this->Form->create('AssetsGroup');?>
	<fieldset>
 		<legend><?php printf(__('Add %s', true), __('Assets Group', true)); ?></legend>
	<?php
		echo $this->Form->input('asset_id');
		echo $this->Form->input('group_id');
		echo $this->Form->input('isApproved');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit', true));?>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Assets Groups', true)), array('action' => 'index'));?></li>
	</ul>
</div>