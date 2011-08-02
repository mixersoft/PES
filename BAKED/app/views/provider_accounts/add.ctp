<div class="providerAccounts form">
<?php echo $this->Form->create('ProviderAccount');?>
	<fieldset>
 		<legend><?php printf(__('Add %s', true), __('Provider Account', true)); ?></legend>
	<?php
		echo $this->Form->input('owners', array('id'=>'ownerSelect'));
		echo $this->Form->input('user_id', array('id'=>'userId','type'=>'text'));
		echo $this->Form->input('provider_name');
		echo $this->Form->input('provider_key');
		echo $this->Form->input('display_name', array('id'=>'displayName'));
		echo $this->Form->input('auth_token');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit', true));?>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Provider Accounts', true)), array('action' => 'index'));?></li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Users', true)), array('controller' => 'users', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Owner', true)), array('controller' => 'users', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Assets', true)), array('controller' => 'assets', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Asset', true)), array('controller' => 'assets', 'action' => 'add')); ?> </li>
	</ul>
</div>
<script type="text/javascript" >
var owner = document.getElementById('ownerSelect');
owner.onchange = function() {
	var name = owner.options[owner.selectedIndex].text;
	var uid = owner.value;
	var userId = document.getElementById('userId');
	userId.value=uid;
	var displayName = document.getElementById('displayName');
	displayName.value=name;
};
</script>