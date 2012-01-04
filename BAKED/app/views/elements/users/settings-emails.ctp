<script type="text/javascript">
PAGE.section = "tab-emails";
SNAPPI.TabNav.selectByName(PAGE);
// nextTab = {
		// href:'/my/settings?xhrview=settings-privacy',
		// className: "tab-privacy"
// };
SNAPPI.EditMode.init();
</script>

	<div id='email' class="setting ">
		<h3>Emails and Notifications</h3>
		<?php	
			$formOptions['id']='UserForm-emails'; 
			$formOptions['url']=Router::url(array('controller'=>'my', 'action'=>'edit'));
			$checkbox_attrs = array('legend'=> false, 'onclick'=>'return false;');
			$radio_attrs = array('legend'=> false,'onclick'=>'return false;' );
					
			echo $this->Form->create('Profile', $formOptions);?>
		<h4>Email Address</h4>
		<div class="input text required">
			<label for="UserEmail">Primary Email Address</label>
			<input type="text" id="UserEmail" value="<?php  echo $data['User']['email'] ?>" maxlength="166" readonly="1" name="data[User][email]">
		<?php 
			// echo $this->Form->input('User.email', array('label'=>'Primary Email Address', 'readOnly'=>true));
			if (0&& $data['Profile']['email_authenticated']) {
				echo "<span style='color:green;padding: 0 4px;' >Email Verified</span>";
			} else {
				echo "<span style='color:red;padding: 0 4px;' >Email Not Verified</span>&nbsp;";
				echo $this->Form->button("Resend Email Verification", array('onclick'=>'window.location.href=\'/users/resend_email_verify\'; return false;', 'class'=>'orange')); 
			}
		?>
		</div>
		<?php echo $this->Form->input('Profile.isHtmlEmailOk', array_merge( $checkbox_attrs , array('label'=>'Send me HTML emails.')));?>

		<h4>Emails</h4>
		<?php echo $this->Form->input('Profile.email_promotions', array_merge( $checkbox_attrs , array('label'=>'Send me emails about special offers and new features.')));?>
		<?php echo $this->Form->input('Profile.email_updates',array('type'=>'checkbox','label'=>'Send me emails on important site news.'));?>
		
		<h4>Notifications</h4>
		<?php echo $this->Form->input('Profile.notify_members', array_merge( $checkbox_attrs , array('label'=>'Notify me when new members join my Groups or Events.')));?>
		<?php echo $this->Form->input('Profile.notify_comments', array_merge( $checkbox_attrs , array( 'label'=>'Notify me when someone comments on my stuff.')));?>
		<?php echo $this->Form->input('Profile.notify_tags', array_merge( $checkbox_attrs , array('label'=>'Notify me when someone tags my stuff.')));?>
		<?php echo $this->Form->input('Profile.notify_favorites', array_merge( $checkbox_attrs , array('label'=>'Notify me when someone marks my stuff as a favorite.')));?>
		<?php echo $this->Form->input('Profile.notify_downloads', array_merge( $checkbox_attrs , array('label'=>'Notify me when someone downloads my stuff.')));?>
		<?php echo $this->Form->hidden('User.id');?>		
		<?php echo $this->Form->hidden('setting',array('value'=>$formOptions['id']));?>
		<?php echo $this->Form->submit("Edit", array('value'=>"Edit", 'class'=>'green')); ?>
		<?php echo $this->Form->end(); ?>	
	</div>	