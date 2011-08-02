<div class="assets form">
<?php echo $this->Form->create('Asset');?>
	<fieldset>
 		<legend><?php printf(__('Add %s', true), __('Asset', true)); ?></legend>
	<?php
		echo $this->Form->input('provider_name');
		echo $this->Form->input('provider_key');
		echo $this->Form->input('provider_account_id');
		echo $this->Form->input('asset_hash');
		echo $this->Form->input('owner_id');
		echo $this->Form->input('dateTaken');
		echo $this->Form->input('src_thumbnail');
		echo $this->Form->input('json_src');
		echo $this->Form->input('json_exif');
		echo $this->Form->input('json_iptc');
		echo $this->Form->input('cameraId');
		echo $this->Form->input('isFlash');
		echo $this->Form->input('isRGB');
		echo $this->Form->input('uploadId');
		echo $this->Form->input('batchId');
		echo $this->Form->input('caption');
		echo $this->Form->input('keyword');
		echo $this->Form->input('Collection');
		echo $this->Form->input('Group');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit', true));?>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Assets', true)), array('action' => 'index'));?></li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Provider Accounts', true)), array('controller' => 'provider_accounts', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Provider Account', true)), array('controller' => 'provider_accounts', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Users', true)), array('controller' => 'users', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Owner', true)), array('controller' => 'users', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Shared Edits', true)), array('controller' => 'shared_edits', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Shared Edit', true)), array('controller' => 'shared_edits', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('User Edits', true)), array('controller' => 'user_edits', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('User Edit', true)), array('controller' => 'user_edits', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Collections', true)), array('controller' => 'collections', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Collection', true)), array('controller' => 'collections', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Groups', true)), array('controller' => 'groups', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Group', true)), array('controller' => 'groups', 'action' => 'add')); ?> </li>
	</ul>
</div>