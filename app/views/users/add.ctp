<div class="users form">
<?php echo $this->Form->create('User');?>
	<fieldset>
 		<legend><?php printf(__('Add %s', true), __('User', true)); ?></legend>
	<?php
		echo $this->Form->input('username');
		echo $this->Form->input('password');
		echo $this->Form->input('email');
		echo $this->Form->input('active');
		echo $this->Form->input('group_id');
		echo $this->Form->input('privacy');
		echo $this->Form->input('lastVisit');
		echo $this->Form->input('Membership');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit', true));?>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Users', true)), array('action' => 'index'));?></li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Groups', true)), array('controller' => 'groups', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Primary Group', true)), array('controller' => 'groups', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Assets', true)), array('controller' => 'assets', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Asset', true)), array('controller' => 'assets', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Auth Accounts', true)), array('controller' => 'auth_accounts', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Auth Account', true)), array('controller' => 'auth_accounts', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Collections', true)), array('controller' => 'collections', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Collection', true)), array('controller' => 'collections', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Photostreams', true)), array('controller' => 'photostreams', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Photostream', true)), array('controller' => 'photostreams', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('User Edits', true)), array('controller' => 'user_edits', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('User Edit', true)), array('controller' => 'user_edits', 'action' => 'add')); ?> </li>
	</ul>
</div>