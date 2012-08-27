<div class="shareLinks index">
	<h2><?php __('Share Links');?></h2>
	<table cellpadding="0" cellspacing="0">
	<tr>
			<th><?php echo $this->Paginator->sort('id');?></th>
			<th><?php echo $this->Paginator->sort('secret_key');?></th>
			<th><?php echo $this->Paginator->sort('hashed_password');?></th>
			<th><?php echo $this->Paginator->sort('security_level');?></th>
			<th><?php echo $this->Paginator->sort('expiration_date');?></th>
			<th><?php echo $this->Paginator->sort('expiration_count');?></th>
			<th><?php echo $this->Paginator->sort('target_id');?></th>
			<th><?php echo $this->Paginator->sort('target_type');?></th>
			<th><?php echo $this->Paginator->sort('target_owner');?></th>
			<th><?php echo $this->Paginator->sort('active');?></th>
			<th><?php echo $this->Paginator->sort('owner_id');?></th>
			<th><?php echo $this->Paginator->sort('count');?></th>
			<th><?php echo $this->Paginator->sort('created');?></th>
			<th><?php echo $this->Paginator->sort('modified');?></th>
			<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
	$i = 0;
	foreach ($shareLinks as $shareLink):
		$class = null;
		if ($i++ % 2 == 0) {
			$class = ' class="altrow"';
		}
	?>
	<tr<?php echo $class;?>>
		<td><?php echo $shareLink['ShareLink']['id']; ?>&nbsp;</td>
		<td><?php echo $shareLink['ShareLink']['secret_key']; ?>&nbsp;</td>
		<td><?php echo $shareLink['ShareLink']['hashed_password']; ?>&nbsp;</td>
		<td><?php echo $shareLink['ShareLink']['security_level']; ?>&nbsp;</td>
		<td><?php echo $shareLink['ShareLink']['expiration_date']; ?>&nbsp;</td>
		<td><?php echo $shareLink['ShareLink']['expiration_count']; ?>&nbsp;</td>
		<td>
			<?php echo $this->Html->link($shareLink['Target']['name'], array('controller' => 'posts', 'action' => 'view', $shareLink['Target']['id'])); ?>
		</td>
		<td><?php echo $shareLink['ShareLink']['target_type']; ?>&nbsp;</td>
		<td>
			<?php echo $this->Html->link($shareLink['TargetOwner']['username'], array('controller' => 'users', 'action' => 'view', $shareLink['TargetOwner']['id'])); ?>
		</td>
		<td><?php echo $shareLink['ShareLink']['active']; ?>&nbsp;</td>
		<td>
			<?php echo $this->Html->link($shareLink['Owner']['username'], array('controller' => 'users', 'action' => 'view', $shareLink['Owner']['id'])); ?>
		</td>
		<td><?php echo $shareLink['ShareLink']['count']; ?>&nbsp;</td>
		<td><?php echo $shareLink['ShareLink']['created']; ?>&nbsp;</td>
		<td><?php echo $shareLink['ShareLink']['modified']; ?>&nbsp;</td>
		<td class="actions">
			<?php echo $this->Html->link(__('View', true), array('action' => 'view', $shareLink['ShareLink']['secret_key'])); ?>
			<?php echo $this->Html->link(__('Edit', true), array('action' => 'edit', $shareLink['ShareLink']['id'])); ?>
			<?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $shareLink['ShareLink']['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $shareLink['ShareLink']['id'])); ?>
		</td>
	</tr>
<?php endforeach; ?>
	</table>
	<p>
	<?php
	echo $this->Paginator->counter(array(
	'format' => __('Page %page% of %pages%, showing %current% records out of %count% total, starting on record %start%, ending on %end%', true)
	));
	?>	</p>

	<div class="paging">
		<?php echo $this->Paginator->prev('<< ' . __('previous', true), array(), null, array('class'=>'disabled'));?>
	 | 	<?php echo $this->Paginator->numbers();?>
 |
		<?php echo $this->Paginator->next(__('next', true) . ' >>', array(), null, array('class' => 'disabled'));?>
	</div>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(__('New Share Link', true), array('action' => 'add')); ?></li>
		<li><?php echo $this->Html->link(__('List Posts', true), array('controller' => 'posts', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Target', true), array('controller' => 'posts', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(__('List Users', true), array('controller' => 'users', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(__('New Target Owner', true), array('controller' => 'users', 'action' => 'add')); ?> </li>
	</ul>
</div>