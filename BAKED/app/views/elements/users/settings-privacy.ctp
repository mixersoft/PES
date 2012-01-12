<script type="text/javascript">
SNAPPI.tabSection.setFocus("#tab-privacy");
SNAPPI.EditMode.init();
</script>

	<div id='privacy' class="setting  tab-panel">
		<h3>Default Privacy Settings</h3>
		<?php
			$formOptions['id']='UserForm-privacy'; 
			$formOptions['url']=Router::url(array('controller'=>'my', 'action'=>'edit'));
			$checkbox_attrs = array('legend'=> false, 'onclick'=>'return false;');
			$radio_attrs = array('legend'=> false,'onclick'=>'return false;', 'separator'=>'<br />' );
			echo $this->Form->create('Profile',$formOptions);?>
		<p>These settings control who can see your stuff.</p>
		
		<h4>Photos</h4>
		<p>The Photos and Page Galleries I import, upload or create:</p>
		<div class="radio-group">
		<?php echo $form->radio('Profile.privacy_assets', $privacy['Asset'], $radio_attrs );?>
		</div>
		<h4>Groups and Events</h4>
		<p>Group privacy settings include both listings (i.e. title, description, policies, etc.) and shared content. It is possible to publish listings, but limit content access to members, only.</p>
		<br></br>
		<p>The Groups and Events that I create:</p>
		<div class="radio-group">
		<?php	echo $form->radio('Profile.privacy_groups', $privacy['Groups'], $radio_attrs );?>
		</div>
						
		<h4>Secret Key Sharing</h4>
		<p>Regardless of privacy settings, content can also be accessed by secret key. These keys are added to special links which can be selectively shared by email, IM or the web. Note that content accessed by Secret Key will not include links to related content.</p>
		<br></br>
		<p>Show Secret Keys to:</p>
		<div class="radio-group">
		<?php echo $form->radio('Profile.privacy_secret_key', $privacy['SecretKey'], $radio_attrs );?>			
		</div>
		<?php echo $this->Form->hidden('User.id');?>					
		<?php echo $this->Form->hidden('setting',array('value'=>$formOptions['id']));?>
		<?php echo $this->Form->submit("Edit", array('value'=>"Edit", 'class'=>'green')); ?>
		<?php echo $this->Form->end(); ?>
	</div>