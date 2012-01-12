<?php
echo $this->element('nav/section', array('icon_src'=>$data['Group']['src_thumbnail']));
?>
<div class="groups view main-div placeholder">
	<div id='fields' class="setting  tab-panel">
		<h3>Description</h3>
		<?php	
			$formOptions['url']=Router::url(array('controller'=>Configure::read('controller.alias'), 'action'=>'edit', $this->Form->value('Group.id')));
			$formOptions['id']='GroupForm-fields';
			$checkbox_attrs = array('legend'=> false);
			$radio_attrs = array('legend'=> false , 'separator'=>'<br />');
			echo $this->Form->create('Group', $formOptions);?>
			
		<?php echo $this->Form->input('title', array('label'=>'Title'));?>
		<?php echo $this->Form->input('description', array('label'=>'Description'));?> 
		<?php echo $this->Form->input('isNC17', array_merge( $checkbox_attrs , array('label'=>'Rated NC17')));?>
		 
		 
		<?php echo $this->Form->hidden('Group.id');?>
		<?php echo $this->Form->hidden('id');?>
		<?php echo $this->Form->hidden('setting',array('value'=>$formOptions['id']));?>
		<?php echo $this->Form->end(__('Submit', true));?>
	</div>	


	
	<div id='privacy' class="setting placeholder">
		<h3>Privacy Settings</h3>
		<?php $formOptions['id']='GroupForm-privacy'; 
			echo $this->Form->create('Group', $formOptions);
			$radio_attrs = array('legend'=> false, 'separator'=>'<br />');?>
		<p>These settings control who can see this Group.</p>

		<h4>Groups and Events</h4>
		<p>Group privacy settings include both listings (i.e. title, description, policies, etc.) and shared content. It is possible to publish listings, but limit content access to members, only.</p>
		<br></br>
		<p>The Groups and Events that I create:</p>
		<div class="radio-group">
		<?php	echo $form->radio('privacy_groups', $privacy['Groups'], $radio_attrs );?>
		</div>
						
		<h4>Secret Key Sharing</h4>
		<p>Regardless of privacy settings, content can also be accessed by secret key. These keys are added to special links which can be selectively shared by email, IM or the web. Note that content accessed by Secret Key will not include links to related content.</p>
		<br></br>
		<p>Show Secret Keys to:</p>
		<div class="radio-group">
		<?php echo $form->radio('privacy_secret_key', $privacy['SecretKey'], $radio_attrs );?>			
		</div>
		
		<?php 	echo $this->Form->hidden('Group.id');?>
		<?php 	echo $this->Form->hidden('id');?>
		<?php echo $this->Form->end(__('Submit', true));?>					
	</div>
	
	<div id='policies' class="setting placeholder">
		<h3>Policy Settings</h3>
		<?php $formOptions['id']='GroupForm-policy'; 
			echo $this->Form->create('Group', $formOptions);
			$radio_attrs = array('legend'=> false, 'separator'=>'<br />');?>
		<p>These settings control Group policies.</p>
		
		<h4>Membership</h4>
		<p>The Membership policy for this Group is:</p>
		<div class="radio-group">
		<?php echo $form->radio('membership_policy', $policy['membership'], $radio_attrs );?>
		</div>
		
		<h4>Invitations</h4>
		<p>Invitations to join this Group can be sent by:</p>
		<div class="radio-group">
		<?php	echo $form->radio('invitation_policy', $policy['invitation'], $radio_attrs );?>
		</div>
						
		<h4>Submissions</h4>
		<p>Contributed Photos are:</p>
		<div class="radio-group">
		<?php echo $form->radio('submission_policy', $policy['submission'], $radio_attrs );?>	
		</div>
					
		<?php 	echo $this->Form->hidden('Group.id');?>
		<?php 	echo $this->Form->hidden('id');?>
		<?php echo $this->Form->end(__('Submit', true));?>					
	</div>
</div>