<div class="groups view main-div placeholder">
	<h2><?php  __('Group');?></h2>
	<h2><?php echo $this->Html->link( $data['Group']['title'], '/groups/view/'.$data['Owner']['id']); ?> </h2>
	<?php echo $this->Html->image( Stagehand::getSrc($data['Group']['src_thumbnail'], 'lm',$data['Group']['type'] ), array('url'=>"/groups/photos/{$data['Group']['id']}"));?>
	
	<div class="clear_div"></div>
	<div class="float-right"><?php echo $this->Html->link( 'Edit Group', '/groups/edit/'.$data['Group']['id']); ?> </div>
	<div class="clear_div"></div>
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Owner'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Html->link($data['Owner']['id'], array('controller' => 'users', 'action' => 'view', $data['Owner']['id'])); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Is System'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php if(1 == $data['Group']['isSystem']){ echo __('Yes'); } else { echo __('No');} ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Title'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['title']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Description'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['description']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Membership Policy'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['membership_policy']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Invitation Policy'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['invitation_policy']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Submission Policy'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['submission_policy']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Is NC17'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php if(1 == $data['Group']['isNC17']) { echo __('Yes');} else { echo __('No');} ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Last Visit'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['lastVisit']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Created On'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['created']; ?>
			&nbsp;
		</dd>
	</dl>
	<div class="comments"><?php echo $this->element('tags', array('domId'=>'groups-tags', 'data'=>&$group))?></div>
	<div class="comments"><?php echo $this->element('comments')?></div>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>
		<li><?php
				// only show join button if user is NOT a member of this group 
				echo (!$data['Group']['isMember'])
				? $this->Html->link(__('Join', true), array('action' => 'join', $data['Group']['id']))
				: null; ?></li>
		<li><?php 
				// only show "contribute" button if user is a member of this group
				echo ($data['Group']['isMember'])
				? $this->Html->link(__('Contribute Photos', true), array('action' => 'contribute', $data['Group']['id']))
				: null; ?></li>
		<li><hr></hr></li>
		<li><?php echo $this->Html->link(sprintf(__('Edit %s', true), __('Group', true)), array('action' => 'edit', $data['Group']['id'])); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('Delete %s', true), __('Group', true)), array('action' => 'delete', $data['Group']['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $data['Group']['id'])); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Groups', true)), array('action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Group', true)), array('action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Users', true)), array('controller' => 'users', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Owner', true)), array('controller' => 'users', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Assets', true)), array('controller' => 'assets', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Asset', true)), array('controller' => 'assets', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Collections', true)), array('controller' => 'collections', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Collection', true)), array('controller' => 'collections', 'action' => 'add')); ?> </li>
	</ul>
</div>
<div class="related">
	<h3><?php printf(__('Related %s', true), __('Users', true));?></h3>
	<?php if (!empty($data['PrimaryUser'])):?>
	<table cellpadding = "0" cellspacing = "0">
	<tr>
		<th><?php __('Id'); ?></th>
		<th><?php __('Username'); ?></th>
		<th><?php __('Password'); ?></th>
		<th><?php __('Email'); ?></th>
		<th><?php __('Active'); ?></th>
		<th><?php __('Primary Group Id'); ?></th>
		<th><?php __('Privacy'); ?></th>
		<th><?php __('LastVisit'); ?></th>
		<th><?php __('Modified'); ?></th>
		<th><?php __('Created'); ?></th>
		<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
		$i = 0;
		foreach ($data['PrimaryUser'] as $primaryUser):
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}
		?>
		<tr<?php echo $class;?>>
			<td><?php echo $primaryUser['id'];?></td>
			<td><?php echo $primaryUser['username'];?></td>
			<td><?php echo $primaryUser['password'];?></td>
			<td><?php echo $primaryUser['email'];?></td>
			<td><?php echo $primaryUser['active'];?></td>
			<td><?php echo $primaryUser['primary_group_id'];?></td>
			<td><?php echo $primaryUser['privacy'];?></td>
			<td><?php echo $primaryUser['lastVisit'];?></td>
			<td><?php echo $primaryUser['modified'];?></td>
			<td><?php echo $primaryUser['created'];?></td>
			<td class="actions">
				<?php echo $this->Html->link(__('View', true), array('controller' => 'users', 'action' => 'view', $primaryUser['id'])); ?>
				<?php echo $this->Html->link(__('Edit', true), array('controller' => 'users', 'action' => 'edit', $primaryUser['id'])); ?>
				<?php echo $this->Html->link(__('Delete', true), array('controller' => 'users', 'action' => 'delete', $primaryUser['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $primaryUser['id'])); ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</table>
<?php endif; ?>

	<div class="actions">
		<ul>
			<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Primary User', true)), array('controller' => 'users', 'action' => 'add'));?> </li>
		</ul>
	</div>
</div>
<div class="related">
	<h3><?php printf(__('Related %s', true), __('Assets', true));?></h3>
	<?php if (!empty($data['Asset'])):?>
	<table cellpadding = "0" cellspacing = "0">
	<tr>
		<th><?php __('Preview'); ?></th>
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
		foreach ($data['Asset'] as $asset):
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}
		?>
		<tr<?php echo $class;?>>
			<td><?php echo $this->Html->image( Stagehand::getSrc($data['src_thumbnail'], 'sq'), array('url'=>"/assets/view/{$data['id']}"));?></td>
			<td><?php echo $data['provider_name'];?></td>
			<td><?php echo $data['provider_key'];?></td>
			<td><?php echo $data['batchId'];?></td>
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
	<h3><?php printf(__('Related %s', true), __('Collections', true));?></h3>
	<?php if (!empty($data['Collection'])):?>
	<table cellpadding = "0" cellspacing = "0">
	<tr>
		<th><?php __('Id'); ?></th>
		<th><?php __('Title'); ?></th>
		<th><?php __('User Id'); ?></th>
		<th><?php __('Description'); ?></th>
		<th><?php __('Markup'); ?></th>
		<th><?php __('Src'); ?></th>
		<th><?php __('LastVisit'); ?></th>
		<th><?php __('Created'); ?></th>
		<th><?php __('Modified'); ?></th>
		<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
		$i = 0;
		foreach ($data['Collection'] as $collection):
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}
		?>
		<tr<?php echo $class;?>>
			<td><?php echo $collection['id'];?></td>
			<td><?php echo $collection['title'];?></td>
			<td><?php echo $collection['owner_id'];?></td>
			<td><?php echo $collection['description'];?></td>
			<td><?php echo $collection['markup'];?></td>
			<td><?php echo $collection['src'];?></td>
			<td><?php echo $collection['lastVisit'];?></td>
			<td><?php echo $collection['created'];?></td>
			<td><?php echo $collection['modified'];?></td>
			<td class="actions">
				<?php echo $this->Html->link(__('View', true), array('controller' => 'collections', 'action' => 'view', $collection['id'])); ?>
				<?php echo $this->Html->link(__('Edit', true), array('controller' => 'collections', 'action' => 'edit', $collection['id'])); ?>
				<?php echo $this->Html->link(__('Delete', true), array('controller' => 'collections', 'action' => 'delete', $collection['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $collection['id'])); ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</table>
<?php endif; ?>

	<div class="actions">
		<ul>
			<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Collection', true)), array('controller' => 'collections', 'action' => 'add'));?> </li>
		</ul>
	</div>
</div>
<div class="related">
	<h3><?php printf(__('Related %s', true), __('Users', true));?></h3>
	<?php if (!empty($data['Member'])):?>
	<table cellpadding = "0" cellspacing = "0">
	<tr>
		<th><?php __('Id'); ?></th>
		<th><?php __('Username'); ?></th>
		<th><?php __('Password'); ?></th>
		<th><?php __('Email'); ?></th>
		<th><?php __('Active'); ?></th>
		<th><?php __('Primary Group Id'); ?></th>
		<th><?php __('Privacy'); ?></th>
		<th><?php __('LastVisit'); ?></th>
		<th><?php __('Modified'); ?></th>
		<th><?php __('Created'); ?></th>
		<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
		$i = 0;
		foreach ($data['Member'] as $member):
			$class = null;
			if ($i++ % 2 == 0) {
				$class = ' class="altrow"';
			}
		?>
		<tr<?php echo $class;?>>
			<td><?php echo $member['id'];?></td>
			<td><?php echo $member['username'];?></td>
			<td><?php echo $member['password'];?></td>
			<td><?php echo $member['email'];?></td>
			<td><?php echo $member['active'];?></td>
			<td><?php echo $member['primary_group_id'];?></td>
			<td><?php echo $member['privacy'];?></td>
			<td><?php echo $member['lastVisit'];?></td>
			<td><?php echo $member['modified'];?></td>
			<td><?php echo $member['created'];?></td>
			<td class="actions">
				<?php echo $this->Html->link(__('View', true), array('controller' => 'users', 'action' => 'view', $member['id'])); ?>
				<?php echo $this->Html->link(__('Edit', true), array('controller' => 'users', 'action' => 'edit', $member['id'])); ?>
				<?php echo $this->Html->link(__('Delete', true), array('controller' => 'users', 'action' => 'delete', $member['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $member['id'])); ?>
			</td>
		</tr>
	<?php endforeach; ?>
	</table>
<?php endif; ?>

	<div class="actions">
		<ul>
			<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Member', true)), array('controller' => 'users', 'action' => 'add'));?> </li>
		</ul>
	</div>
</div>
