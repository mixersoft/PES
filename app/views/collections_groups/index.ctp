<div class="collectionsGroups index">
	<h2><?php __('Collections Groups');?></h2>
	<table cellpadding="0" cellspacing="0">
	<tr>
			<th><?php echo $this->Paginator->sort('id');?></th>
			<th><?php echo $this->Paginator->sort('collection_id');?></th>
			<th><?php echo $this->Paginator->sort('group_id');?></th>
			<th><?php echo $this->Paginator->sort('isApproved');?></th>
			<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
	$i = 0;
	foreach ($collectionsGroups as $collectionsGroup):
		$class = null;
		if ($i++ % 2 == 0) {
			$class = ' class="altrow"';
		}
	?>
	<tr<?php echo $class;?>>
		<td><?php echo $collectionsGroup['CollectionsGroup']['id']; ?>&nbsp;</td>
		<td><?php echo $collectionsGroup['CollectionsGroup']['collection_id']; ?>&nbsp;</td>
		<td><?php echo $collectionsGroup['CollectionsGroup']['group_id']; ?>&nbsp;</td>
		<td><?php echo $collectionsGroup['CollectionsGroup']['isApproved']; ?>&nbsp;</td>
		<td class="actions">
			<?php echo $this->Html->link(__('View', true), array('action' => 'view', $collectionsGroup['CollectionsGroup']['id'])); ?>
			<?php echo $this->Html->link(__('Edit', true), array('action' => 'edit', $collectionsGroup['CollectionsGroup']['id'])); ?>
			<?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $collectionsGroup['CollectionsGroup']['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $collectionsGroup['CollectionsGroup']['id'])); ?>
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
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Collections Group', true)), array('action' => 'add')); ?></li>
	</ul>
</div>