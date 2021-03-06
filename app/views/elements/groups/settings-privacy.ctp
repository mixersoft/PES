<script type="text/javascript">
SNAPPI.tabSection.setFocus("#tab-privacy");
SNAPPI.EditMode.init();
</script>
	<div id='privacy' class="setting tab-panel">
		<h3>Privacy Settings</h3>
		<?php $formOptions['id']='GroupForm-privacy'; 
			$formOptions['url']=array('action'=>'edit');  
			echo $this->Form->create('Group', $formOptions);
			$radio_attrs = array('legend'=> false, 'onclick'=>'return false;', 'separator'=>'<br />');?>
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
		<?php echo $this->Form->submit("Edit", array('value'=>"Edit", 'class'=>'green')); ?>
		<?php echo $this->Form->end(); ?>
	</div>