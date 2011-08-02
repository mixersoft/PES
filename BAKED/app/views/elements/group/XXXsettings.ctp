<div class="groups view main-div placeholder">
	<h2><?php  __(ucwords(Inflector::singularize(Configure::read('controller.alias'))));?>
		<span><?php echo $this->Html->link( $data['Group']['title'], '/groups/home/'.$data['Group']['id']); ?> 
	</span>
	</h2>
	<?php echo $this->Html->image(Session::read('stagepath_baseurl').$data['Group']['src_thumbnail'], array('url'=>"/groups/photos/{$data['Group']['id']}"));?>

	
	<div id='fields' class="setting placeholder">
		<h3>Description</h3>
		<?php	
			$formOptions['url']=Router::url(array(
				'controller'=>Configure::read('controller.alias'), 
				'action'=>'edit', 
				$this->Form->value('Group.id')));
			$formOptions['id']='GroupForm-fields';
			$checkbox_attrs = array('legend'=> false, 'onclick'=>'return false;');
			$radio_attrs = array('legend'=> false,'onclick'=>'return false;' );
			echo $this->Form->create('Group', $formOptions);?>
			
		<?php echo $this->Form->input('title', array('label'=>'Title', 'readOnly'=>true));?>
		<?php echo $this->Form->input('description', array('label'=>'Description', 'readOnly'=>true));?>
		<?php echo $this->Form->input('isNC17', array_merge( $checkbox_attrs , array('label'=>'Rated NC17')));?> 
		<?php echo $this->Form->hidden('setting',array('value'=>$formOptions['id']));?>
		<?php echo $this->Form->end( array('label'=>'Edit', 'div'=>array('class'=>null)));?>
	</div>	

	
	<div id='privacy' class="setting placeholder">
		<h3>Privacy Settings</h3>
		<?php $formOptions['id']='GroupForm-privacy'; 
			echo $this->Form->create('Group', $formOptions);
			$radio_attrs = array('legend'=> false, 'onclick'=>'return false;');?>
		<p>These settings control who can see this Group.</p>

		<h4>Groups and Events</h4>
		<p>Group privacy settings include both listings (i.e. title, description, policies, etc.) and shared content. It is possible to publish listings, but limit content access to members, only.</p>
		<br></br>
		<p>The Groups and Events that I create:</p>
		<?php	echo $form->radio('privacy_groups', $privacy['Groups'], $radio_attrs );?>
						
		<h4>Secret Key Sharing</h4>
		<p>Regardless of privacy settings, content can also be accessed by secret key. These keys are added to special links which can be selectively shared by email, IM or the web. Note that content accessed by Secret Key will not include links to related content.</p>
		<br></br>
		<p>Show Secret Keys to:</p>
		<?php echo $form->radio('privacy_secret_key', $privacy['SecretKey'], $radio_attrs );?>			
		
		<?php 	echo $this->Form->hidden('Group.id');?>
		<?php 	echo $this->Form->hidden('id');?>
		<?php echo $this->Form->end( array('label'=>'Edit', 'div'=>array('class'=>null)));?>					
	</div>
	
	<div id='policies' class="setting placeholder">
		<h3>Policy Settings</h3>
		<?php $formOptions['id']='GroupForm-policy'; 
			echo $this->Form->create('Group', $formOptions);
			?>
		<p>These settings control Group policies.</p>
		
		<h4>Membership</h4>
		<p>The Membership policy for this Group is:</p>
		<?php echo $form->radio('membership_policy', $policy['membership'], $radio_attrs );?>
		
		<h4>Invitations</h4>
		<p>Invitations to join this Group can be sent by:</p>
		<?php	echo $form->radio('invitation_policy', $policy['invitation'], $radio_attrs );?>
						
		<h4>Submissions</h4>
		<p>Contributed Photos are:</p>
		<?php echo $form->radio('submission_policy', $policy['submission'], $radio_attrs );?>	
					
		<?php 	echo $this->Form->hidden('Group.id');?>
		<?php 	echo $this->Form->hidden('id');?>
		<?php echo $this->Form->end( array('label'=>'Edit', 'div'=>array('class'=>null)));?>					
	</div>	
		
	<dl><?php $i = 0; $class = ' class="altrow"';?>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Owner'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $this->Html->link($data['Owner']['id'], array('controller' => 'users', 'action' => 'view', $data['Owner']['id'])); ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Is System'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php if(1 == $data['Group']['isSystem']){ echo __('Yes'); } else { echo __('No');} ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Title'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['title']; ?>
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
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Is NC17'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php if(1 == $data['Group']['isNC17']) { echo __('Yes');} else { echo __('No');} ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Last Visit'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['lastVisit']; ?>
			&nbsp;
		</dd>
		<dt<?php if ($i % 2 == 0) echo $class;?>><?php __('Created On'); ?></dt>
		<dd<?php if ($i++ % 2 == 0) echo $class;?>>
			<?php echo $data['Group']['created']; ?>
			&nbsp;
		</dd>
	</dl>
</div>
<div class="actions">
	<h3><?php __('Actions'); ?></h3>
	<ul>
		<li><?php
				// only show join button if user is NOT a member of this group 
				$isMember = in_array($data['Group']['id'], Permissionable::getGroupIds());
				echo !$isMember
				? $this->Html->link(__('Join', true), array('action' => 'join', $data['Group']['id']))
				: null; ?></li>
		<li><?php 
				// only show "contribute" button if user is a member of this group
				echo $isMember
				? $this->Html->link(__('Contribute Photos', true), array('action' => 'contribute', $data['Group']['id']))
				: null; ?></li>
		<li><hr></hr></li>		
		<?php if (AppController::$writeOk) { ?>
			<li><?php echo $this->Html->link(__('Manage Membership', true), array('action' => 'membership', $data['Group']['id'])) ?></li>
			<li><?php echo $this->Html->link(__('Moderate Photos', true), array('action' => 'moderate', $data['Group']['id'])) ?></li>		
			<li><?php echo $this->Html->link(sprintf(__('Delete %s', true), __('Group', true)), array('action' => 'delete', $data['Group']['id']), null, sprintf(__('Are you sure you want to delete # %s?', true), $data['Group']['id'])); ?> </li>
			<li><hr></hr></li>
		<?php } ?>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Groups', true)), array('action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Group', true)), array('action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Users', true)), array('controller' => 'users', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Owner', true)), array('controller' => 'users', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Assets', true)), array('controller' => 'assets', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Asset', true)), array('controller' => 'assets', 'action' => 'add')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('List %s', true), __('Collections', true)), array('controller' => 'collections', 'action' => 'index')); ?> </li>
		<li><?php echo $this->Html->link(sprintf(__('New %s', true), __('Collection', true)), array('controller' => 'collections', 'action' => 'add')); ?> </li>
	</ul>
</div>

