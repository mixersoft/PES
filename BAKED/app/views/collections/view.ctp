<div class="collections view">
<h2><?php  __('Collection');?></h2>
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Id'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $collection['Collection']['id']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Title'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $collection['Collection']['title']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('User'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Html->link($collection['User']['id'], array('controller' => 'users', 'action' => 'view', $collection['User']['id'])); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Description'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $collection['Collection']['description']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Markup'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $collection['Collection']['markup']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Src'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $collection['Collection']['src']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('LastVisit'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $collection['Collection']['lastVisit']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Created'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $collection['Collection']['created']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Modified'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $collection['Collection']['modified']; ?>
			&nbsp;
		</dd>
	</dl>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(sprintf(__('Edit %s', true), __('Collection', true)), array('action' => 'edit', $collection['Collection']['id'])); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('Delete %s', true), __('Collection', true)), array('action' => 'delete', $collection['Collection']['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $collection['Collection']['id'])); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Collections', true)), array('action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Collection', true)), array('action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Users', true)), array('controller' => 'users', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('User', true)), array('controller' => 'users', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Assets', true)), array('controller' => 'assets', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Asset', true)), array('controller' => 'assets', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Groups', true)), array('controller' => 'groups', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Group', true)), array('controller' => 'groups', 'action' => 'add')); ?> </li>
	</ul>
</div>
<div class="related">
	<h3><?php printf(__('Related %s', true), __('Assets', true));?></h3>
	<?php if (!empty($collection['Asset'])):?>
	<table cellpadding = "0" cellspacing = "0">
	<tr>
		<th><?php __('Id'); ?></th>
		<th><?php __('Provider Name'); ?></th>
		<th><?php __('Provider Key'); ?></th>
		<th><?php __('Photostream Id'); ?></th>
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
		foreach ($collection['Asset'] as $asset):
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}
		?>
		<tr<?php echo $class;?>>
			<td><?php echo $data['id'];?></td>
			<td><?php echo $data['provider_name'];?></td>
			<td><?php echo $data['provider_key'];?></td>
			<td><?php echo $data['photostream_id'];?></td>
			<td><?php echo $data['asset_hash'];?></td>
			<td><?php echo $data['owner_id'];?></td>
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
<div class="related">
	<h3><?php printf(__('Related %s', true), __('Groups', true));?></h3>
	<?php if (!empty($collection['Group'])):?>
	<table cellpadding = "0" cellspacing = "0">
	<tr>
		<th><?php __('Id'); ?></th>
		<th><?php __('User Id'); ?></th>
		<th><?php __('IsSystem'); ?></th>
		<th><?php __('Title'); ?></th>
		<th><?php __('Description'); ?></th>
		<th><?php __('Membership Policy'); ?></th>
		<th><?php __('Invitation Policy'); ?></th>
		<th><?php __('Submission Policy'); ?></th>
		<th><?php __('IsNC17'); ?></th>
		<th><?php __('LastVisit'); ?></th>
		<th><?php __('Created'); ?></th>
		<th><?php __('Modified'); ?></th>
		<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
		$i = 0;
		foreach ($collection['Group'] as $group):
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}
		?>
		<tr<?php echo $class;?>>
			<td><?php echo $data['id'];?></td>
			<td><?php echo $data['owner_id'];?></td>
			<td><?php echo $data['isSystem'];?></td>
			<td><?php echo $data['title'];?></td>
			<td><?php echo $data['description'];?></td>
			<td><?php echo $data['membership_policy'];?></td>
			<td><?php echo $data['invitation_policy'];?></td>
			<td><?php echo $data['submission_policy'];?></td>
			<td><?php echo $data['isNC17'];?></td>
			<td><?php echo $data['lastVisit'];?></td>
			<td><?php echo $data['created'];?></td>
			<td><?php echo $data['modified'];?></td>
			<td class="actions">
				<?php echo $this->Html->link(__('View', true), array('controller' => 'groups', 'action' => 'view', $data['id'])); ?>
				<?php echo $this->Html->link(__('Edit', true), array('controller' => 'groups', 'action' => 'edit', $data['id'])); ?>
				<?php echo $this->Html->link(__('Delete', true), array('controller' => 'groups', 'action' => 'delete', $data['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $data['id'])); ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</table>
<?php endif; ?>

	<div class="actions">
		<ul>
			<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Group', true)), array('controller' => 'groups', 'action' => 'add'));?> </li>
		</ul>
	</div>
</div>
