<?php
echo $this->Html->css('workorder/workorder');
if (empty($this->passedArgs['wide'])) {
	$this->Layout->blockStart('itemHeader');
		$badge_src=Stagehand::$default_badges['Person'];
		echo $this->element('nav/section', compact('badge_src'));
	$this->Layout->blockEnd();	
}	
?>
<div class="workorders index">
	<table cellpadding="0" cellspacing="0">
	<tr>
			<th>&nbsp;</th>
			<th>Workorder Source</th>
			<th>Manager</th>
			<th>Status</th>
			<th>Created</th>
			<th class="actions"><?php __('Actions');?></th>
	</tr>
	<?php
	/**
	 * Workorder
	 */
	$i = 0;
	foreach ($data['Workorder'] as $workorder):
		$class = null;
		if ($i++ % 2 == 0) {
			$class = ' class="altrow"';
		}
		$label = array();
		switch($workorder['source_model']){
			case 'User': 
				$label['source'] = $data[$workorder['source_model']][$workorder['source_id']]['username']." ({$workorder['source_model']})"; 
			break; 
			case 'Group': 
				$label['source'] = $data[$workorder['source_model']][$workorder['source_id']]['title']." ({$workorder['source_model']})";
			break;
		}
		$label['manager_id'] = $data['User'][$workorder['manager_id']]['username'];
	?>
	<tr<?php echo $class;?>>
		<td><ul class="inline">
			<?php
				$isAssigned =  $workorder['manager_id'] == AppController::$userid;
				if ($isAssigned) {
					$next = array('action' => 'photos', $workorder['id'], 'raw'=>1) + Configure::read('passedArgs.min');
					$btn = $this->Html->link(__('Go', true), $next, array('target'=>'_blank'));
				} else $btn = 'Go';
				echo '<li class="btn rounded-5 '.(!$isAssigned ? 'white disabled' : 'orange').'">'.$btn.'</li>';
			?>&nbsp;
		<ul>&nbsp;</td>	
		<td><?php echo "{$label['source']} ({$workorder['assets_workorder_count']} Snaps)"; ?>&nbsp;</td>
		<td><?php echo $label['manager_id']; ?>&nbsp;</td>
		<td><?php echo $workorder['work_status']; ?>&nbsp;</td>
		<td><?php echo $this->Time->timeAgoInWords($workorder['created']);  ?>&nbsp;</td>
		<td class="actions">
			<ul class="inline">
				<?php
					$label = 'assign';
					if (!$isAssigned) {
						$next = array('action' => 'assign', $workorder['id'], 'me');
						$btn = $this->Html->link(__($label, true), $next);
					} else $btn = $label;
					echo '<li class="btn rounded-5 '.($isAssigned ? 'white disabled' : 'orange').'">'.$btn.'</li>';
				?>&nbsp;
				<?php
					$label = 'harvest';
					if ($isAssigned) {
						$next = array('action' => 'harvest', $workorder['id']);
						$btn = $this->Html->link(__($label, true), $next);
					} else $btn = $label;
					echo '<li class="btn rounded-5 '.(!$isAssigned ? 'white disabled' : 'orange').'">'.$btn.'</li>';
				?>&nbsp;
				<?php
					$label = 'new training set';
					if ($isAssigned) {
						$next = array('action' => 'train', $workorder['id'], 'null', AppController::$userid, 'ALL' );
						$btn = $this->Html->link(__($label, true), $next);
					} else $btn = $label;
					echo '<li class="btn rounded-5 '.(!$isAssigned ? 'white disabled' : 'orange').'">'.$btn.'</li>';
				?>&nbsp;
			<ul>&nbsp;
			<?php echo $this->Html->link(__('View', true), array('action' => 'view', $workorder['id'])); ?>
			<?php echo $this->Html->link(__('Edit', true), array('action' => 'edit', $workorder['id'])); ?>
			<?php echo $this->Html->link(__('Delete', true), array('action' => 'delete', $workorder['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $workorder['id'])); ?>
		</td>
	</tr>
	<?php
				/**
				 * TasksWorkorder BelongsTo Workorder
				 */
	foreach ($data['TasksWorkorder'][$workorder['id']] as $taskWorkorder):
		$label = array();
		$label['operator_id'] = $data['User'][$taskWorkorder['operator_id']]['username'];
	?>
	<tr<?php echo $class;?>>
		<td>&nbsp;</td>
		<td><ul class="inline">
			<?php
				$isAssigned =  $taskWorkorder['operator_id'] == AppController::$userid;
				if ($isAssigned) {
					$next = array('controller'=>'tasks_workorders', 'action' => 'photos', $taskWorkorder['id'], 'raw'=>1) + Configure::read('passedArgs.min');
					$btn = $this->Html->link(__('Go', true), $next, array('target'=>'_blank'));
				} else $btn = 'Go';
				echo '<li class="btn rounded-5 '.(!$isAssigned ? 'white disabled' : 'orange').'">'.$btn.'</li>';
			?>	&nbsp;
				<li><?php echo "Task: {$data['Task'][ $taskWorkorder['task_id'] ]['name']} ({$taskWorkorder['assets_task_count']} Snaps)"; ?>&nbsp;</li>
			</ul>&nbsp;
		</td>	
		<td><?php echo $label['operator_id']; ?>&nbsp;</td>		
		<td><?php echo $taskWorkorder['status']; ?>&nbsp;</td>
		<td class="actions">
			<ul class="inline">
				<?php
					$label = 'assign';
					if (!$isAssigned) {
						$next = array('controller'=>'tasks_workorders','action' => 'assign', $taskWorkorder['id'], 'me');
						$btn = $this->Html->link(__($label, true), $next);
					} else $btn = $label;
					echo '<li class="btn rounded-5 '.($isAssigned ? 'white disabled' : 'orange').'">'.$btn.'</li>';
				?>&nbsp;
				<?php
					$label = 'harvest';
					$enabled = $isAssigned && ($taskWorkorder['assets_task_count'] < $workorder['assets_workorder_count']);
					if ($enabled) {
						$next = array('controller'=>'tasks_workorders','action' => 'harvest', $taskWorkorder['id']);
						$btn = $this->Html->link(__($label, true), $next);
					} else $btn = $label;
					echo '<li class="btn rounded-5 '.(!$enabled ? 'white disabled' : 'orange').'">'.$btn.'</li>';
				?>&nbsp;
			<ul>&nbsp;
		</td>
	</tr>
	<?php endforeach; // TasksWorkorder ?>
	
	<tr><td>&nbsp;</td></tr>
<?php endforeach; // Workorder ?>
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
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Workorder', true)), array('action' => 'add')); ?></li>
	</ul>
</div>