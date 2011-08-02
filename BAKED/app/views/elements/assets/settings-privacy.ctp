<?php $uuid = $this->passedArgs[0]; ?>
<script <?php if ($this->layout == 'ajax')  echo "class='xhrInit' "; ?> type="text/javascript">
PAGE.section = "tab-privacy";
SNAPPI.TabNav.selectByName(PAGE);
nextTab = {
		href:'/groups/settings/<?php echo $uuid; ?>?xhrview=settings-details',
		className: "tab-policy"
};
try {
	PAGE.init.push(SNAPPI.EditMode.init);
} catch(e) {
	PAGE.init = [	SNAPPI.EditMode.init ];
}
/*
 * note: we must use Y.io ajax post if we don't want to navigate away from the tab on edit/save
 */
</script>
	
	<div id='privacy' class="setting placeholder">
		<h3>Privacy Settings</h3>
		<?php $formOptions['id']='AssetForm-privacy'; 
			$formOptions['url']=array('action'=>'edit');  
			echo $this->Form->create('Asset', $formOptions);
			$radio_attrs = array('legend'=> false, 'onclick'=>'return false;');?>
		<p>These settings control who can see this Photo.</p>
		
		<h4>Photos</h4>
		<p>This Photo is:</p>
		<?php echo $form->radio('privacy_assets', $privacy['Asset'], $radio_attrs );?>
			
		<h4>Group and Event Sharing</h4>
		<p>When this Photo is shared with a Group:</p>
		<?php echo $form->radio('privacy_groups', $privacy['Groups'], $radio_attrs );?>	
					
		<h4>Secret Key Sharing</h4>
		<p>Regardless of privacy settings, content can also be accessed by secret key. These keys are added to special links which can be selectively shared by email, IM or the web. Note that content accessed by Secret Key will not include links to related content.</p>
		<br></br>
		<p>Show Secret Keys to:</p>
		<?php echo $form->radio('privacy_secret_key', $privacy['SecretKey'], $radio_attrs );?>			
					
		<?php 	echo $this->Form->hidden('Asset.id');?>
		<?php 	// echo $this->Form->hidden('id');?>
		<?php echo $this->Form->end( array('label'=>'Edit', 'div'=>array('class'=>null)));?>					
	</div>	