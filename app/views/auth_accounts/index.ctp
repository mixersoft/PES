<div class="authAccounts index">
	<h2><?php __('Auth Accounts');?></h2>
	<table cellpadding="0" cellspacing="0">
	<tr>
			<th><?php echo $this->Paginator->sort('id');?></th>
			<th><?php echo $this->Paginator->sort('unique_hash');?></th>
			<th><?php echo $this->Paginator->sort('user_id');?></th>
			<th><?php echo $this->Paginator->sort('provider_name');?></th>
			<th><?php echo $this->Paginator->sort('provider_key');?></th>
			<th><?php echo $this->Paginator->sort('password');?></th>
			<th><?php echo $this->Paginator->sort('display_name');?></th>
			<th><?php echo $this->Paginator->sort('email');?></th>
			<th><?php echo $this->Paginator->sort('url');?></th>
			<th><?php echo $this->Paginator->sort('photo');?></th>
			<th><?php echo $this->Paginator->sort('country');?></th>
			<th><?php echo $this->Paginator->sort('city');?></th>
			<th><?php echo $this->Paginator->sort('utcOffset');?></th>
			<th><?php echo $this->Paginator->sort('gender');?></th>
			<th><?php echo $this->Paginator->sort('active');?></th>
			<th><?php echo $this->Paginator->sort('lastVisit');?></th>
			<th><?php echo $this->Paginator->sort('created');?></th>
			<th><?php echo $this->Paginator->sort('modified');?></th>
			<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
	$i = 0;
	foreach ($authAccounts as $authAccount):
		$class = null;
		if ($i++ % 2 == 0) {
			$class = ' class="altrow"';
		}
	?>
	<tr<?php echo $class;?>>
		<td><?php echo $authAccount['AuthAccount']['id']; ?>&nbsp;</td>
		<td><?php echo $authAccount['AuthAccount']['unique_hash']; ?>&nbsp;</td>
		<td>
			<?php echo $this->Html->link($authAccount['User']['id'], array('controller' => 'users', 'action' => 'view', $authAccount['User']['id'])); ?>
		</td>
		<td><?php echo $authAccount['AuthAccount']['provider_name']; ?>&nbsp;</td>
		<td><?php echo $authAccount['AuthAccount']['provider_key']; ?>&nbsp;</td>
		<td><?php echo $authAccount['AuthAccount']['password']; ?>&nbsp;</td>
		<td><?php echo $authAccount['AuthAccount']['display_name']; ?>&nbsp;</td>
		<td><?php echo $authAccount['AuthAccount']['email']; ?>&nbsp;</td>
		<td><?php echo $authAccount['AuthAccount']['url']; ?>&nbsp;</td>
		<td><?php echo $authAccount['AuthAccount']['photo']; ?>&nbsp;</td>
		<td><?php echo $authAccount['AuthAccount']['country']; ?>&nbsp;</td>
		<td><?php echo $authAccount['AuthAccount']['city']; ?>&nbsp;</td>
		<td><?php echo $authAccount['AuthAccount']['utcOffset']; ?>&nbsp;</td>
		<td><?php echo $authAccount['AuthAccount']['gender']; ?>&nbsp;</td>
		<td><?php echo $authAccount['AuthAccount']['active']; ?>&nbsp;</td>
		<td><?php echo $authAccount['AuthAccount']['lastVisit']; ?>&nbsp;</td>
		<td><?php echo $authAccount['AuthAccount']['created']; ?>&nbsp;</td>
		<td><?php echo $authAccount['AuthAccount']['modified']; ?>&nbsp;</td>
		<td class="actions">
			<?php echo $this->Html->link(__('View', true), array('action' => 'view', $authAccount['AuthAccount']['id'])); ?>
			<?php echo $this->Html->link(__('Edit', true), array('action' => 'edit', $authAccount['AuthAccount']['id'])); ?>
			<?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $authAccount['AuthAccount']['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $authAccount['AuthAccount']['id'])); ?>
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
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Auth Account', true)), array('action' => 'add')); ?></li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Users', true)), array('controller' => 'users', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('User', true)), array('controller' => 'users', 'action' => 'add')); ?> </li>
	</ul>
</div>