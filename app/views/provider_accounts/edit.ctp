<div class="providerAccounts form">
<?php echo $this->Form->create('ProviderAccount');?>
	<fieldset>
 		<legend><?php printf(__('Edit %s', true), __('Provider Account', true)); ?></legend>
	<?php
		echo $this->Form->input('id');
		echo $this->Form->input('user_id');
		echo $this->Form->input('provider_name');
		echo $this->Form->input('provider_key');
		echo $this->Form->input('display_name');
		echo $this->Form->input('auth_token');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit', true));?>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $this->Form->value('ProviderAccount.id')), null, sprintf(__('Are you sure you want to delete # %s?', true), $this->Form->value('ProviderAccount.id'))); ?></li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Provider Accounts', true)), array('action' => 'index'));?></li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Users', true)), array('controller' => 'users', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Owner', true)), array('controller' => 'users', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Assets', true)), array('controller' => 'assets', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Asset', true)), array('controller' => 'assets', 'action' => 'add')); ?> </li>
	</ul>
</div>