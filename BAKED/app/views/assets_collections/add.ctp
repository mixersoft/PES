<div class="assetsCollections form">
<?php echo $this->Form->create('AssetsCollection');?>
	<fieldset>
 		<legend><?php printf(__('Add %s', true), __('Assets Collection', true)); ?></legend>
	<?php
		echo $this->Form->input('collection_id');
		echo $this->Form->input('asset_id');
	?>
	</fieldset>
<?php echo $this->Form->end(__('Submit', true));?>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>

		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Assets Collections', true)), array('action' => 'index'));?></li>
	</ul>
</div>