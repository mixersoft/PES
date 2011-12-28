<script type="text/javascript">
PAGE.section = "tab-privacy";
SNAPPI.TabNav.selectByName(PAGE);
// nextTab = {
		// href:'/my/settings?xhrview=settings-moderator',
		// className: "tab-moderator"
// };
SNAPPI.EditMode.init();
</script>

	<div id='privacy' class="setting ">
		<h3>Default Privacy Settings</h3>
		<?php
			$formOptions['id']='UserForm-privacy'; 
			$formOptions['url']=Router::url(array('controller'=>'my', 'action'=>'edit'));
			$checkbox_attrs = array('legend'=> false, 'onclick'=>'return false;');
			$radio_attrs = array('legend'=> false,'onclick'=>'return false;' );
			echo $this->Form->create('Profile',$formOptions);?>
		<p>These settings control who can see your stuff.</p>
		
		<h4>Photos</h4>
		<p>The Photos and Page Galleries I import, upload or create:</p>
		<?php echo $form->radio('Profile.privacy_assets', $privacy['Asset'], $radio_attrs );?>
		
		<h4>Groups and Events</h4>
		<p>Group privacy settings include both listings (i.e. title, description, policies, etc.) and shared content. It is possible to publish listings, but limit content access to members, only.</p>
		<br></br>
		<p>The Groups and Events that I create:</p>
		<?php	echo $form->radio('Profile.privacy_groups', $privacy['Groups'], $radio_attrs );?>
						
		<h4>Secret Key Sharing</h4>
		<p>Regardless of privacy settings, content can also be accessed by secret key. These keys are added to special links which can be selectively shared by email, IM or the web. Note that content accessed by Secret Key will not include links to related content.</p>
		<br></br>
		<p>Show Secret Keys to:</p>
		<?php echo $form->radio('Profile.privacy_secret_key', $privacy['SecretKey'], $radio_attrs );?>			
		<?php echo $this->Form->hidden('User.id');?>					
		<?php echo $this->Form->hidden('setting',array('value'=>$formOptions['id']));?>
		<?php echo $this->Form->end( array('label'=>'Edit', 'div'=>array('class'=>null)));?>					
	</div>