<div class="sharedEdits form">
<?php echo $this->Form->create('SharedEdit');?>
	<fieldset>
 		<legend><?php printf(__('Edit %s', true), __('Shared Edit', true)); ?></legend>
	<?php
		echo $this->Form->input('asset_id');
		echo $this->Form->input('rotate');
		echo $this->Form->input('votes');
		echo $this->Form->input('points');
		echo $this->Form->input('score');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit', true));?>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $this->Form->value('SharedEdit.asset_id')), null, sprintf(__('Are you sure you want to delete # %s?', true), $this->Form->value('SharedEdit.asset_id'))); ?></li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Shared Edits', true)), array('action' => 'index'));?></li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Assets', true)), array('controller' => 'assets', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Asset', true)), array('controller' => 'assets', 'action' => 'add')); ?> </li>
	</ul>
</div>