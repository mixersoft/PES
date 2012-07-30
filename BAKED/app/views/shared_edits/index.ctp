<div class="sharedEdits index">
	<h2><?php __('Shared Edits');?></h2>
	<table cellpadding="0" cellspacing="0">
	<tr>
			<th><?php echo $this->Paginator->sort('asset_id');?></th>
			<th><?php echo $this->Paginator->sort('rotate');?></th>
			<th><?php echo $this->Paginator->sort('votes');?></th>
			<th><?php echo $this->Paginator->sort('points');?></th>
			<th><?php echo $this->Paginator->sort('score');?></th>
			<th><?php echo $this->Paginator->sort('modified');?></th>
			<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
	$i = 0;
	foreach ($sharedEdits as $sharedEdit):
		$class = null;
		if ($i++ % 2 == 0) {
			$class = ' class="altrow"';
		}
	?>
	<tr<?php echo $class;?>>
		<td><?php echo $sharedEdit['SharedEdit']['asset_id']; ?>&nbsp;</td>
		<td><?php echo $sharedEdit['SharedEdit']['rotate']; ?>&nbsp;</td>
		<td><?php echo $sharedEdit['SharedEdit']['votes']; ?>&nbsp;</td>
		<td><?php echo $sharedEdit['SharedEdit']['points']; ?>&nbsp;</td>
		<td><?php echo $sharedEdit['SharedEdit']['score']; ?>&nbsp;</td>
		<td><?php echo $sharedEdit['SharedEdit']['modified']; ?>&nbsp;</td>
		<td class="actions">
			<?php echo $this->Html->link(__('View', true), array('action' => 'view', $sharedEdit['SharedEdit']['asset_id'])); ?>
			<?php echo $this->Html->link(__('Edit', true), array('action' => 'edit', $sharedEdit['SharedEdit']['asset_id'])); ?>
			<?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $sharedEdit['SharedEdit']['asset_id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $sharedEdit['SharedEdit']['asset_id'])); ?>
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
		<?php echo $this->Paginator->prev('« '.__('', true), array(), null, array('class'=>'disabled'));?>
	  	<?php echo $this->Paginator->numbers(array('separator'=>null, 'modulus'=>'20'));?>

		<?php echo $this->Paginator->next(__('', true).'»', array(), null, array('class' => 'disabled'));?>
	</div>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Shared Edit', true)), array('action' => 'add')); ?></li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Assets', true)), array('controller' => 'assets', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Asset', true)), array('controller' => 'assets', 'action' => 'add')); ?> </li>
	</ul>
</div>