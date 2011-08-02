<div class="assetsCollections index">
	<h2><?php __('Assets Collections');?></h2>
	<table cellpadding="0" cellspacing="0">
	<tr>
			<th><?php echo $this->Paginator->sort('id');?></th>
			<th><?php echo $this->Paginator->sort('collection_id');?></th>
			<th><?php echo $this->Paginator->sort('asset_id');?></th>
			<th><?php echo $this->Paginator->sort('modified');?></th>
			<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
	$i = 0;
	foreach ($assetsCollections as $assetsCollection):
		$class = null;
		if ($i++ % 2 == 0) {
			$class = ' class="altrow"';
		}
	?>
	<tr<?php echo $class;?>>
		<td><?php echo $assetsCollection['AssetsCollection']['id']; ?>&nbsp;</td>
		<td><?php echo $assetsCollection['AssetsCollection']['collection_id']; ?>&nbsp;</td>
		<td><?php echo $assetsCollection['AssetsCollection']['asset_id']; ?>&nbsp;</td>
		<td><?php echo $assetsCollection['AssetsCollection']['modified']; ?>&nbsp;</td>
		<td class="actions">
			<?php echo $this->Html->link(__('View', true), array('action' => 'view', $assetsCollection['AssetsCollection']['id'])); ?>
			<?php echo $this->Html->link(__('Edit', true), array('action' => 'edit', $assetsCollection['AssetsCollection']['id'])); ?>
			<?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $assetsCollection['AssetsCollection']['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $assetsCollection['AssetsCollection']['id'])); ?>
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
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Assets Collection', true)), array('action' => 'add')); ?></li>
	</ul>
</div>