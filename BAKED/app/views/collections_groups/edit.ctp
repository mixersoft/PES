<div class="collectionsGroups form">
<?php echo $this->Form->create('CollectionsGroup');?>
	<fieldset>
 		<legend><?php printf(__('Edit %s', true), __('Collections Group', true)); ?></legend>
	<?php
		echo $this->Form->input('id');
		echo $this->Form->input('collection_id');
		echo $this->Form->input('group_id');
		echo $this->Form->input('isApproved');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit', true));?>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $this->Form->value('CollectionsGroup.id')), null, sprintf(__('Are you sure you want to delete # %s?', true), $this->Form->value('CollectionsGroup.id'))); ?></li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Collections Groups', true)), array('action' => 'index'));?></li>
	</ul>
</div>