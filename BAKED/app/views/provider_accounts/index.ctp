<div class="providerAccounts index">
	<h2><?php __('Provider Accounts');?></h2>
	<table cellpadding="0" cellspacing="0">
	<tr>
			<th class="actions"><?php __('Actions');?></th>
			<th><?php echo $this->Paginator->sort('id');?></th>
			<th><?php echo $this->Paginator->sort('user_id');?></th>
			<th><?php echo $this->Paginator->sort('provider_name');?></th>
			<th><?php echo $this->Paginator->sort('provider_key');?></th>
			<th><?php echo $this->Paginator->sort('display_name');?></th>
			<th><?php echo $this->Paginator->sort('auth_token');?></th>
			<th><?php echo $this->Paginator->sort('created');?></th>
			<th><?php echo $this->Paginator->sort('modified');?></th>
	</tr>
	<?php
	$i = 0;
	foreach ($providerAccounts as $providerAccount):
		$class = null;
		if ($i++ % 2 == 0) {
			$class = ' class="altrow"';
		}
	?>
	<tr<?php echo $class;?>>
		<td class="actions">
			<?php echo $this->Html->link(__('View', true), array('action' => 'view', $providerAccount['ProviderAccount']['id'])); ?>
			<?php echo $this->Html->link(__('Edit', true), array('action' => 'edit', $providerAccount['ProviderAccount']['id'])); ?>
			<?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $providerAccount['ProviderAccount']['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $providerAccount['ProviderAccount']['id'])); ?>
		</td>
		<td><?php echo $providerAccount['ProviderAccount']['id']; ?>&nbsp;</td>
		<td>
			<?php echo $this->Html->link($providerAccount['Owner']['id'], array('controller' => 'users', 'action' => 'view', $providerAccount['Owner']['id'])); ?>
		</td>
		<td><?php echo $providerAccount['ProviderAccount']['provider_name']; ?>&nbsp;</td>
		<td><?php echo $providerAccount['ProviderAccount']['provider_key']; ?>&nbsp;</td>
		<td><?php echo $providerAccount['ProviderAccount']['display_name']; ?>&nbsp;</td>
		<td><?php echo $providerAccount['ProviderAccount']['auth_token']; ?>&nbsp;</td>
		<td><?php echo $providerAccount['ProviderAccount']['created']; ?>&nbsp;</td>
		<td><?php echo $providerAccount['ProviderAccount']['modified']; ?>&nbsp;</td>
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
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Provider Account', true)), array('action' => 'add')); ?></li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Users', true)), array('controller' => 'users', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Owner', true)), array('controller' => 'users', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Assets', true)), array('controller' => 'assets', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Asset', true)), array('controller' => 'assets', 'action' => 'add')); ?> </li>
	</ul>
</div>