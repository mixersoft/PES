<div class="assetsGroups index">
	<h2><?php __('Assets Groups');?></h2>
	<table cellpadding="0" cellspacing="0">
	<tr>
			<th><?php echo $this->Paginator->sort('id');?></th>
			<th><?php echo $this->Paginator->sort('asset_id');?></th>
			<th><?php echo $this->Paginator->sort('group_id');?></th>
			<th><?php echo $this->Paginator->sort('isApproved');?></th>
			<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
	$i = 0;
	foreach ($assetsGroups as $assetsGroup):
		$class = null;
		if ($i++ % 2 == 0) {
			$class = ' class="altrow"';
		}
	?>
	<tr<?php echo $class;?>>
		<td><?php echo $assetsGroup['AssetsGroup']['id']; ?>&nbsp;</td>
		<td><?php echo $assetsGroup['AssetsGroup']['asset_id']; ?>&nbsp;</td>
		<td><?php echo $assetsGroup['AssetsGroup']['group_id']; ?>&nbsp;</td>
		<td><?php echo $assetsGroup['AssetsGroup']['isApproved']; ?>&nbsp;</td>
		<td class="actions">
			<?php echo $this->Html->link(__('View', true), array('action' => 'view', $assetsGroup['AssetsGroup']['id'])); ?>
			<?php echo $this->Html->link(__('Edit', true), array('action' => 'edit', $assetsGroup['AssetsGroup']['id'])); ?>
			<?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $assetsGroup['AssetsGroup']['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $assetsGroup['AssetsGroup']['id'])); ?>
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
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Assets Group', true)), array('action' => 'add')); ?></li>
	</ul>
</div>