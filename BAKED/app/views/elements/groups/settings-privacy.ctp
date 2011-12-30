<script type="text/javascript">
PAGE.section = "tab-privacy";
SNAPPI.TabNav.selectByName(PAGE);
SNAPPI.EditMode.init();
</script>
	<div id='privacy' class="setting">
		<h3>Privacy Settings</h3>
		<?php $formOptions['id']='GroupForm-privacy'; 
			$formOptions['url']=array('action'=>'edit');  
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