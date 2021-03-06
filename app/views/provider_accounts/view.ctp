<div class="providerAccounts view">
<h2><?php  __('Provider Account');?></h2>
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Id'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $providerAccount['ProviderAccount']['id']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Owner'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Html->link($providerAccount['Owner']['id'], array('controller' => 'users', 'action' => 'view', $providerAccount['Owner']['id'])); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Provider Name'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $providerAccount['ProviderAccount']['provider_name']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Provider Key'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $providerAccount['ProviderAccount']['provider_key']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Display Name'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $providerAccount['ProviderAccount']['display_name']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Auth Token'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $providerAccount['ProviderAccount']['auth_token']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Created'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $providerAccount['ProviderAccount']['created']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Modified'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $providerAccount['ProviderAccount']['modified']; ?>
			&nbsp;
		</dd>
	</dl>
</div>
<div class="actions">
	<h3><?php __('Import Photos'); ?></h3>
	<ul><li>
		<?php echo $this->Html->link(__('Import Photos', true), array('action' => 'folders')); ?> 
	</li></ul>

	<h3><?php __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(sprintf(__('Edit %s', true), __('Provider Account', true)), array('action' => 'edit', $providerAccount['ProviderAccount']['id'])); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('Delete %s', true), __('Provider Account', true)), array('action' => 'delete', $providerAccount['ProviderAccount']['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $providerAccount['ProviderAccount']['id'])); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Provider Accounts', true)), array('action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Provider Account', true)), array('action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Users', true)), array('controller' => 'users', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Owner', true)), array('controller' => 'users', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Assets', true)), array('controller' => 'assets', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Asset', true)), array('controller' => 'assets', 'action' => 'add')); ?> </li>
	</ul>
</div>
<div class="related">
	<h3><?php printf(__('Related %s', true), __('Assets', true));?></h3>
	<?php if (!empty($providerAccount['Asset'])):?>
	<table cellpadding = "0" cellspacing = "0">
	<tr>
		<th><?php __('Id'); ?></th>
		<th><?php __('Provider Name'); ?></th>
		<th><?php __('Provider Key'); ?></th>
		<th><?php __('Provider Account Id'); ?></th>
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
		foreach ($providerAccount['Asset'] as $asset):
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}
		?>
		<tr<?php echo $class;?>>
			<td><?php echo $this->Html->image(Stagehand::getSrc($data['src_thumbnail']), array('url'=>"/assets/view/{$data['id']}"));?></td>
			<td><?php echo $data['provider_name'];?></td>
			<td><?php echo $data['provider_key'];?></td>
			<td><?php echo $data['provider_account_id'];?></td>
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
