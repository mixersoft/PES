<div class="permissions form">
<?php echo $this->Form->create('Permission');?>
	<fieldset>
 		<legend><?php printf(__('Edit %s', true), __('Permission', true)); ?></legend>
	<?php
		echo $this->Form->input('id');
		echo $this->Form->input('model');
		echo $this->Form->input('foreignId');
		echo $this->Form->input('oid');
		echo $this->Form->input('gid');
		echo $this->Form->input('perms');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit', true));?>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $this->Form->value('Permission.id')), null, sprintf(__('Are you sure you want to delete # %s?', true), $this->Form->value('Permission.id'))); ?></li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Permissions', true)), array('action' => 'index'));?></li>
	</ul>
</div>