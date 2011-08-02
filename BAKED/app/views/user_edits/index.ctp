<div class="userEdits index">
	<h2><?php __('User Edits');?></h2>
	<table cellpadding="0" cellspacing="0">
	<tr>
			<th><?php echo $this->Paginator->sort('id');?></th>
			<th><?php echo $this->Paginator->sort('asset_hash');?></th>
			<th><?php echo $this->Paginator->sort('owner_id');?></th>
			<th><?php echo $this->Paginator->sort('isEditor');?></th>
			<th><?php echo $this->Paginator->sort('isReviewed');?></th>
			<th><?php echo $this->Paginator->sort('isPublished');?></th>
			<th><?php echo $this->Paginator->sort('rotate');?></th>
			<th><?php echo $this->Paginator->sort('rating');?></th>
			<th><?php echo $this->Paginator->sort('syncOffset');?></th>
			<th><?php echo $this->Paginator->sort('isScrubbed');?></th>
			<th><?php echo $this->Paginator->sort('isCroppped');?></th>
			<th><?php echo $this->Paginator->sort('isLocked');?></th>
			<th><?php echo $this->Paginator->sort('isExported');?></th>
			<th><?php echo $this->Paginator->sort('isDone');?></th>
			<th><?php echo $this->Paginator->sort('src_json');?></th>
			<th><?php echo $this->Paginator->sort('edit_json');?></th>
			<th><?php echo $this->Paginator->sort('lastVisit');?></th>
			<th><?php echo $this->Paginator->sort('flaggedAt');?></th>
			<th><?php echo $this->Paginator->sort('flag_json');?></th>
			<th><?php echo $this->Paginator->sort('created');?></th>
			<th><?php echo $this->Paginator->sort('modified');?></th>
			<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
	$i = 0;
	foreach ($userEdits as $userEdit):
		$class = null;
		if ($i++ % 2 == 0) {
			$class = ' class="altrow"';
		}
	?>
	<tr<?php echo $class;?>>
		<td><?php echo $userEdit['UserEdit']['id']; ?>&nbsp;</td>
		<td>
			<?php echo $this->Html->link($userEdit['Asset']['src_thumbnail'], array('controller' => 'assets', 'action' => 'view', $userEdit['Asset']['id'])); ?>
		</td>
		<td>
			<?php echo $this->Html->link($userEdit['Owner']['id'], array('controller' => 'users', 'action' => 'view', $userEdit['Owner']['id'])); ?>
		</td>
		<td><?php echo $userEdit['UserEdit']['isEditor']; ?>&nbsp;</td>
		<td><?php echo $userEdit['UserEdit']['isReviewed']; ?>&nbsp;</td>
		<td><?php echo $userEdit['UserEdit']['isPublished']; ?>&nbsp;</td>
		<td><?php echo $userEdit['UserEdit']['rotate']; ?>&nbsp;</td>
		<td><?php echo $userEdit['UserEdit']['rating']; ?>&nbsp;</td>
		<td><?php echo $userEdit['UserEdit']['syncOffset']; ?>&nbsp;</td>
		<td><?php echo $userEdit['UserEdit']['isScrubbed']; ?>&nbsp;</td>
		<td><?php echo $userEdit['UserEdit']['isCroppped']; ?>&nbsp;</td>
		<td><?php echo $userEdit['UserEdit']['isLocked']; ?>&nbsp;</td>
		<td><?php echo $userEdit['UserEdit']['isExported']; ?>&nbsp;</td>
		<td><?php echo $userEdit['UserEdit']['isDone']; ?>&nbsp;</td>
		<td><?php echo $userEdit['UserEdit']['src_json']; ?>&nbsp;</td>
		<td><?php echo $userEdit['UserEdit']['edit_json']; ?>&nbsp;</td>
		<td><?php echo $userEdit['UserEdit']['lastVisit']; ?>&nbsp;</td>
		<td><?php echo $userEdit['UserEdit']['flaggedAt']; ?>&nbsp;</td>
		<td><?php echo $userEdit['UserEdit']['flag_json']; ?>&nbsp;</td>
		<td><?php echo $userEdit['UserEdit']['created']; ?>&nbsp;</td>
		<td><?php echo $userEdit['UserEdit']['modified']; ?>&nbsp;</td>
		<td class="actions">
			<?php echo $this->Html->link(__('View', true), array('action' => 'view', $userEdit['UserEdit']['id'])); ?>
			<?php echo $this->Html->link(__('Edit', true), array('action' => 'edit', $userEdit['UserEdit']['id'])); ?>
			<?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $userEdit['UserEdit']['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $userEdit['UserEdit']['id'])); ?>
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
		<?php echo $this->Paginator->prev('«'.__('', true), array(), null, array('class'=>'disabled'));?>
	  	<?php echo $this->Paginator->numbers(array('separator'=>null, 'modulus'=>'20'));?>

		<?php echo $this->Paginator->next(__('', true).'»', array(), null, array('class' => 'disabled'));?>
	</div>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('User Edit', true)), array('action' => 'add')); ?></li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Users', true)), array('controller' => 'users', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Owner', true)), array('controller' => 'users', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Assets', true)), array('controller' => 'assets', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Asset', true)), array('controller' => 'assets', 'action' => 'add')); ?> </li>
	</ul>
</div>