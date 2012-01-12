<script type="text/javascript">
SNAPPI.tabSection.setFocus("#tab-privacy"");
SNAPPI.EditMode.init();
</script>
	<div id='privacy' class="setting ">
		<h3>Privacy Settings</h3>
		<?php $formOptions['id']='AssetForm-privacy'; 
			$formOptions['url']=array('action'=>'edit');  
			echo $this->Form->create('Asset', $formOptions);
			$radio_attrs = array('legend'=> false, 'onclick'=>'return false;', 'separator'=>'<br />');?>
		<p>These settings control who can see this Photo.</p>
		
		<h4>Photos</h4>
		<p>This Photo is:</p>
		<div class="radio-group">
		<?php echo $form->radio('privacy_assets', $privacy['Asset'], $radio_attrs );?>
		</div>
			
		<h4>Group and Event Sharing</h4>
		<p>When this Photo is shared with a Group:</p>
		<div class="radio-group">
		<?php echo $form->radio('privacy_groups', $privacy['Groups'], $radio_attrs );?>	
		</div>
		
		<h4>Secret Key Sharing</h4>
		<p>Regardless of privacy settings, content can also be accessed by secret key. These keys are added to special links which can be selectively shared by email, IM or the web. Note that content accessed by Secret Key will not include links to related content.</p>
		<br></br>
		<p>Show Secret Keys to:</p>
		<div class="radio-group">
		<?php echo $form->radio('privacy_secret_key', $privacy['SecretKey'], $radio_attrs );?>			
		</div>
					
		<?php 	echo $this->Form->hidden('Asset.id');?>
		<?php 	// echo $this->Form->hidden('id');?>
		<?php echo $this->Form->submit("Edit", array('value'=>"Edit", 'class'=>'green')); ?>
		<?php echo $this->Form->end(); ?>
	</div>	