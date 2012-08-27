<div class="authAccounts form">
<?php echo $this->Form->create('AuthAccount');?>
	<fieldset>
 		<legend><?php printf(__('Add %s', true), __('Auth Account', true)); ?></legend>
	<?php
		echo $this->Form->input('unique_hash');
		echo $this->Form->input('user_id');
		echo $this->Form->input('provider_name');
		echo $this->Form->input('provider_key');
		echo $this->Form->input('password');
		echo $this->Form->input('display_name');
		echo $this->Form->input('email');
		echo $this->Form->input('url');
		echo $this->Form->input('photo');
		echo $this->Form->input('country');
		echo $this->Form->input('city');
		echo $this->Form->input('utcOffset');
		echo $this->Form->input('gender');
		echo $this->Form->input('active');
		echo $this->Form->input('lastVisit');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit', true));?>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Auth Accounts', true)), array('action' => 'index'));?></li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Users', true)), array('controller' => 'users', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('User', true)), array('controller' => 'users', 'action' => 'add')); ?> </li>
	</ul>
</div>