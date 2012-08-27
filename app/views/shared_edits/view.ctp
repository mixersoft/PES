<div class="sharedEdits view">
<h2><?php  __('Shared Edit');?></h2>
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Asset Hash'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $sharedEdit['SharedEdit']['asset_id']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Rotate'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $sharedEdit['SharedEdit']['rotate']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Votes'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $sharedEdit['SharedEdit']['votes']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Points'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $sharedEdit['SharedEdit']['points']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Score'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $sharedEdit['SharedEdit']['score']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Modified'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $sharedEdit['SharedEdit']['modified']; ?>
			&nbsp;
		</dd>
	</dl>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(sprintf(__('Edit %s', true), __('Shared Edit', true)), array('action' => 'edit', $sharedEdit['SharedEdit']['asset_id'])); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('Delete %s', true), __('Shared Edit', true)), array('action' => 'delete', $sharedEdit['SharedEdit']['asset_id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $sharedEdit['SharedEdit']['asset_id'])); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Shared Edits', true)), array('action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Shared Edit', true)), array('action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Assets', true)), array('controller' => 'assets', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Asset', true)), array('controller' => 'assets', 'action' => 'add')); ?> </li>
	</ul>
</div>
<div class="related">
	<h3><?php printf(__('Related %s', true), __('Assets', true));?></h3>
	<?php if (!empty($sharedEdit['Asset'])):?>
	<table cellpadding = "0" cellspacing = "0">
	<tr>
		<th><?php __('Id'); ?></th>
		<th><?php __('Provider Name'); ?></th>
		<th><?php __('Provider Key'); ?></th>
		<th><?php __('Provider Account Id'); ?></th>
		<th><?php __('Asset Hash'); ?></th>
		<th><?php __('User Id'); ?></th>
		<th><?php __('Caption'); ?></th>
		<th><?php __('DateTaken'); ?></th>
		<th><?php __('Src Thumbnail'); ?></th>
		<th><?php __('Json Src'); ?></th>
		<th><?php __('Json Exif'); ?></th>
		<th><?php __('CameraId'); ?></th>
		<th><?php __('IsFlash'); ?></th>
		<th><?php __('IsRGB'); ?></th>
		<th><?php __('UploadId'); ?></th>
		<th><?php __('BatchId'); ?></th>
		<th><?php __('Created'); ?></th>
		<th><?php __('Modified'); ?></th>
		<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
		$i = 0;
		foreach ($sharedEdit['Asset'] as $asset):
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}
		?>
		<tr<?php echo $class;?>>
			<td><?php echo $data['id'];?></td>
			<td><?php echo $data['provider_name'];?></td>
			<td><?php echo $data['provider_key'];?></td>
			<td><?php echo $data['provider_account_id'];?></td>
			<td><?php echo $data['asset_id'];?></td>
			<td><?php echo $data['user_id'];?></td>
			<td><?php echo $data['caption'];?></td>
			<td><?php echo $data['dateTaken'];?></td>
			<td><?php echo $data['src_thumbnail'];?></td>
			<td><?php echo $data['json_src'];?></td>
			<td><?php echo $data['json_exif'];?></td>
			<td><?php echo $data['cameraId'];?></td>
			<td><?php echo $data['isFlash'];?></td>
			<td><?php echo $data['isRGB'];?></td>
			<td><?php echo $data['uploadId'];?></td>
			<td><?php echo $data['batchId'];?></td>
			<td><?php echo $data['created'];?></td>
			<td><?php echo $data['modified'];?></td>
			<td class="actions">
				<?php echo $this->Html->link(__('View', true), array('controller' => 'assets', 'action' => 'view', $data['id'])); ?>
				<?php echo $this->Html->link(__('Edit', true), array('controller' => 'assets', 'action' => 'edit', $data['id'])); ?>
				<?php echo $this->Html->link(__('Delete', true), array('controller' => 'assets', 'action' => 'delete', $data['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $data['id'])); ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</table>
<?php endif; ?>

	<div class="actions">
		<ul>
			<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Asset', true)), array('controller' => 'assets', 'action' => 'add'));?> </li>
		</ul>
	</div>
</div>
