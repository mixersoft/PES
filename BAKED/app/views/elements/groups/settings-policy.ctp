<script type="text/javascript">
PAGE.section = "tab-policy";
SNAPPI.TabNav.selectByName(PAGE);
SNAPPI.EditMode.init();
</script>
	<div id='policies' class="setting">
		<h3>Policy Settings</h3>
		<?php $formOptions['id']='GroupForm-policy'; 
			$formOptions['url']=array('action'=>'edit');  
			$radio_attrs = array('legend'=> false,'onclick'=>'return false;' );
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
		<?php echo $this->Form->submit("Edit", array('value'=>"Edit", 'class'=>'green')); ?>
		<?php echo $this->Form->end(); ?>
	</div>	