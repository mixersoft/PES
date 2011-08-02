<div class="groupsUsers form">
<?php echo $this->Form->create('GroupsUser');?>
	<fieldset>
 		<legend><?php printf(__('Add %s', true), __('Groups User', true)); ?></legend>
	<?php
		echo $this->Form->input('user_id');
		echo $this->Form->input('group_id');
		echo $this->Form->input('isApproved');
		echo $this->Form->input('role');
		echo $this->Form->input('isActive');
		echo $this->Form->input('suspendUntil');
		echo $this->Form->input('lastVisit');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit', true));?>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Groups Users', true)), array('action' => 'index'));?></li>
	</ul>
</div>