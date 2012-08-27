<div class="permissions index">
	<h2><?php __('Permissions');?></h2>
	<table cellpadding="0" cellspacing="0">
	<tr>
			<th><?php echo $this->Paginator->sort('id');?></th>
			<th><?php echo $this->Paginator->sort('model');?></th>
			<th><?php echo $this->Paginator->sort('foreignId');?></th>
			<th><?php echo $this->Paginator->sort('oid');?></th>
			<th><?php echo $this->Paginator->sort('gid');?></th>
			<th><?php echo $this->Paginator->sort('perms');?></th>
			<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
	$i = 0;
	foreach ($permissions as $permission):
		$class = null;
		if ($i++ % 2 == 0) {
			$class = ' class="altrow"';
		}
	?>
	<tr<?php echo $class;?>>
		<td><?php echo $permission['Permission']['id']; ?>&nbsp;</td>
		<td><?php echo $permission['Permission']['model']; ?>&nbsp;</td>
		<td><?php echo $permission['Permission']['foreignId']; ?>&nbsp;</td>
		<td><?php echo $permission['Permission']['oid']; ?>&nbsp;</td>
		<td><?php echo $permission['Permission']['gid']; ?>&nbsp;</td>
		<td><?php echo $permission['Permission']['perms']; ?>&nbsp;</td>
		<td class="actions">
			<?php echo $this->Html->link(__('View', true), array('action' => 'view', $permission['Permission']['id'])); ?>
			<?php echo $this->Html->link(__('Edit', true), array('action' => 'edit', $permission['Permission']['id'])); ?>
			<?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $permission['Permission']['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $permission['Permission']['id'])); ?>
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
		<?php echo $this->Paginator->prev('<< '.__('previous', true), array(), null, array('class'=>'disabled'));?>
	 | 	<?php echo $this->Paginator->numbers(array('separator'=>null, 'modulus'=>'20'));?>
 |
		<?php echo $this->Paginator->next(__('next', true).' >>', array(), null, array('class' => 'disabled'));?>
	</div>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Permission', true)), array('action' => 'add')); ?></li>
	</ul>
</div>