<div class="collections index">
	<h2><?php __('Collections');?></h2>
	<table cellpadding="0" cellspacing="0">
	<tr>
			<th><?php echo $this->Paginator->sort('id');?></th>
			<th><?php echo $this->Paginator->sort('title');?></th>
			<th><?php echo $this->Paginator->sort('owner_id');?></th>
			<th><?php echo $this->Paginator->sort('description');?></th>
			<th><?php echo $this->Paginator->sort('markup');?></th>
			<th><?php echo $this->Paginator->sort('src');?></th>
			<th><?php echo $this->Paginator->sort('lastVisit');?></th>
			<th><?php echo $this->Paginator->sort('created');?></th>
			<th><?php echo $this->Paginator->sort('modified');?></th>
			<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
	$i = 0;
	foreach ($collections as $collection):
		$class = null;
		if ($i++ % 2 == 0) {
			$class = ' class="altrow"';
		}
	?>
	<tr<?php echo $class;?>>
		<td><?php echo $collection['Collection']['id']; ?>&nbsp;</td>
		<td><?php echo $collection['Collection']['title']; ?>&nbsp;</td>
		<td>
			<?php echo $this->Html->link($collection['User']['id'], array('controller' => 'users', 'action' => 'view', $collection['User']['id'])); ?>
		</td>
		<td><?php echo $collection['Collection']['description']; ?>&nbsp;</td>
		<td><?php echo $collection['Collection']['markup']; ?>&nbsp;</td>
		<td><?php echo $collection['Collection']['src']; ?>&nbsp;</td>
		<td><?php echo $collection['Collection']['lastVisit']; ?>&nbsp;</td>
		<td><?php echo $collection['Collection']['created']; ?>&nbsp;</td>
		<td><?php echo $collection['Collection']['modified']; ?>&nbsp;</td>
		<td class="actions">
			<?php echo $this->Html->link(__('View', true), array('action' => 'view', $collection['Collection']['id'])); ?>
			<?php echo $this->Html->link(__('Edit', true), array('action' => 'edit', $collection['Collection']['id'])); ?>
			<?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $collection['Collection']['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $collection['Collection']['id'])); ?>
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
 
		<?php echo $this->Paginator->next(__('', true).' »', array(), null, array('class' => 'disabled'));?>
	</div>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Collection', true)), array('action' => 'add')); ?></li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Users', true)), array('controller' => 'users', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('User', true)), array('controller' => 'users', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Assets', true)), array('controller' => 'assets', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Asset', true)), array('controller' => 'assets', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Groups', true)), array('controller' => 'groups', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Group', true)), array('controller' => 'groups', 'action' => 'add')); ?> </li>
	</ul>
</div>