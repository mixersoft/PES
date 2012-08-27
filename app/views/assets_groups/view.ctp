<div class="assetsGroups view">
<h2><?php  __('Assets Group');?></h2>
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Id'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $assetsGroup['AssetsGroup']['id']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Asset Id'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $assetsGroup['AssetsGroup']['asset_id']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Group Id'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $assetsGroup['AssetsGroup']['group_id']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('IsApproved'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $assetsGroup['AssetsGroup']['isApproved']; ?>
			&nbsp;
		</dd>
	</dl>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(sprintf(__('Edit %s', true), __('Assets Group', true)), array('action' => 'edit', $assetsGroup['AssetsGroup']['id'])); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('Delete %s', true), __('Assets Group', true)), array('action' => 'delete', $assetsGroup['AssetsGroup']['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $assetsGroup['AssetsGroup']['id'])); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Assets Groups', true)), array('action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Assets Group', true)), array('action' => 'add')); ?> </li>
	</ul>
</div>
