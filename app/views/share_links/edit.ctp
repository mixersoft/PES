<div class="shareLinks form">
<?php echo $this->Form->create('ShareLink');?>
	<fieldset>
		<legend><?php __('Edit Share Link'); ?></legend>
	<?php
		echo $this->Form->input('id');
		echo $this->Form->input('secret_key');
		echo $this->Form->input('hashed_password');
		echo $this->Form->input('security_level');
		echo $this->Form->input('expiration_date');
		echo $this->Form->input('expiration_count');
		echo $this->Form->input('target_id');
		echo $this->Form->input('target_type');
		echo $this->Form->input('target_owner');
		echo $this->Form->input('active');
		echo $this->Form->input('owner_id');
		echo $this->Form->input('count');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit', true));?>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $this->Form->value('ShareLink.id')), null, sprintf(__('Are you sure you want to delete # %s?', true), $this->Form->value('ShareLink.id'))); ?></li>
		<li><?php echo $this->Html->link(__('List Share Links', true), array('action' => 'index'));?></li>
		<li><?php echo $this->Html->link(__('List Posts', true), array('controller' => 'posts', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Target', true), array('controller' => 'posts', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Users', true), array('controller' => 'users', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Target Owner', true), array('controller' => 'users', 'action' => 'add')); ?> </li>
	</ul>
</div>