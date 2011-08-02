<?php 
	echo $this->element('nav/section', array('icon_src'=>$data['Group']['src_thumbnail']));
?>
<div class="groups contribute">
<h2><?php  echo sprintf(__('Join Group <b>%s</b>', true), __($data['Group']['title'], true)); ?></h2>
<?php echo $this->Form->create('Group', array('url'=>$this->here))?>
	
<h2><?php echo $data['Group']['title']; ?></h2>
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Owner'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Html->link($owner_name, array('controller' => 'users', 'action' => 'view', $data['Group']['owner_id'])); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Description'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['description']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Membership Policy'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['membership_policy']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Invitation Policy'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['invitation_policy']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Submission Policy'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['submission_policy']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('IsNC17'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['isNC17']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('LastVisit'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['lastVisit']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Created'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['created']; ?>
			&nbsp;
		</dd>
	</dl>

	<p>
<?php echo $this->Form->hidden('id', array('value'=>$id)); ?>	
<?php echo $this->Form->hidden('title', array('value'=>$data['Group']['title'])); ?>
<?php echo $this->Form->submit("I'd like to join this group!")?>	
<?php echo $this->Form->end()?>


</div>		