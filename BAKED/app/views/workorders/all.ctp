<?php
if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		$badge_src=Stagehand::$default_badges['Circle'];
		echo $this->element('nav/section', compact('badge_src'));
	$this->Layout->blockEnd();	
}	
?>
<div class="groupsUsers index">
	<h2><?php __('Groups Users');?></h2>
	<table cellpadding="0" cellspacing="0">
	<tr>
			<th><?php echo $this->Paginator->sort('id');?></th>
			<th><?php echo $this->Paginator->sort('user_id');?></th>
			<th><?php echo $this->Paginator->sort('group_id');?></th>
			<th><?php echo $this->Paginator->sort('isApproved');?></th>
			<th><?php echo $this->Paginator->sort('role');?></th>
			<th><?php echo $this->Paginator->sort('isActive');?></th>
			<th><?php echo $this->Paginator->sort('suspendUntil');?></th>
			<th><?php echo $this->Paginator->sort('lastVisit');?></th>
			<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
	$i = 0;
	foreach ($groupsUsers as $groupsUser):
		$class = null;
		if ($i++ % 2 == 0) {
			$class = ' class="altrow"';
		}
	?>
	<tr<?php echo $class;?>>
		<td><?php echo $groupsUser['GroupsUser']['id']; ?>&nbsp;</td>
		<td><?php echo $groupsUser['GroupsUser']['user_id']; ?>&nbsp;</td>
		<td><?php echo $groupsUser['GroupsUser']['group_id']; ?>&nbsp;</td>
		<td><?php echo $groupsUser['GroupsUser']['isApproved']; ?>&nbsp;</td>
		<td><?php echo $groupsUser['GroupsUser']['role']; ?>&nbsp;</td>
		<td><?php echo $groupsUser['GroupsUser']['isActive']; ?>&nbsp;</td>
		<td><?php echo $groupsUser['GroupsUser']['suspendUntil']; ?>&nbsp;</td>
		<td><?php echo $groupsUser['GroupsUser']['lastVisit']; ?>&nbsp;</td>
		<td class="actions">
			<?php echo $this->Html->link(__('View', true), array('action' => 'view', $groupsUser['GroupsUser']['id'])); ?>
			<?php echo $this->Html->link(__('Edit', true), array('action' => 'edit', $groupsUser['GroupsUser']['id'])); ?>
			<?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $groupsUser['GroupsUser']['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $groupsUser['GroupsUser']['id'])); ?>
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
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Groups User', true)), array('action' => 'add')); ?></li>
	</ul>
</div>