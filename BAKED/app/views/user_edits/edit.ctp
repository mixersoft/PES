<div class="userEdits form">
<?php echo $this->Form->create('UserEdit');?>
	<fieldset>
 		<legend><?php printf(__('Edit %s', true), __('User Edit', true)); ?></legend>
	<?php
		echo $this->Form->input('id');
		echo $this->Form->input('asset_hash');
		echo $this->Form->input('owner_id');
		echo $this->Form->input('isEditor');
		echo $this->Form->input('isReviewed');
		echo $this->Form->input('isPublished');
		echo $this->Form->input('rotate');
		echo $this->Form->input('rating');
		echo $this->Form->input('syncOffset');
		echo $this->Form->input('isScrubbed');
		echo $this->Form->input('isCroppped');
		echo $this->Form->input('isLocked');
		echo $this->Form->input('isExported');
		echo $this->Form->input('isDone');
		echo $this->Form->input('src_json');
		echo $this->Form->input('edit_json');
		echo $this->Form->input('lastVisit');
		echo $this->Form->input('flaggedAt');
		echo $this->Form->input('flag_json');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit', true));?>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $this->Form->value('UserEdit.id')), null, sprintf(__('Are you sure you want to delete # %s?', true), $this->Form->value('UserEdit.id'))); ?></li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('User Edits', true)), array('action' => 'index'));?></li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Users', true)), array('controller' => 'users', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Owner', true)), array('controller' => 'users', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Assets', true)), array('controller' => 'assets', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Asset', true)), array('controller' => 'assets', 'action' => 'add')); ?> </li>
	</ul>
</div>