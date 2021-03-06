<div class="providers index">
	<h2><?php __('Providers');?></h2>
	<table cellpadding="0" cellspacing="0">
	<tr>
			<th><?php echo $this->Paginator->sort('id');?></th>
			<th><?php echo $this->Paginator->sort('name');?></th>
			<th><?php echo $this->Paginator->sort('description');?></th>
			<th><?php echo $this->Paginator->sort('created');?></th>
			<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
	$i = 0;
	foreach ($providers as $provider):
		$class = null;
		if ($i++ % 2 == 0) {
			$class = ' class="altrow"';
		}
	?>
	<tr<?php echo $class;?>>
		<td><?php echo $provider['Provider']['id']; ?>&nbsp;</td>
		<td><?php echo $provider['Provider']['name']; ?>&nbsp;</td>
		<td><?php echo $provider['Provider']['description']; ?>&nbsp;</td>
		<td><?php echo $provider['Provider']['created']; ?>&nbsp;</td>
		<td class="actions">
			<?php echo $this->Html->link(__('View', true), array('action' => 'view', $provider['Provider']['id'])); ?>
			<?php echo $this->Html->link(__('Edit', true), array('action' => 'edit', $provider['Provider']['id'])); ?>
			<?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $provider['Provider']['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $provider['Provider']['id'])); ?>
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
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Provider', true)), array('action' => 'add')); ?></li>
	</ul>
</div>