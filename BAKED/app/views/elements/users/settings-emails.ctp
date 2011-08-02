<?php $uuid = $this->passedArgs[0]; ?>
<script <?php if ($this->layout == 'ajax')  echo "class='xhrInit' "; ?> type="text/javascript">
PAGE.section = "tab-emails";
SNAPPI.TabNav.selectByName(PAGE);
nextTab = {
		href:'/users/settings/<?php echo $uuid; ?>?xhrview=settings-privacy',
		className: "tab-privacy"
};
PAGE.init.push(SNAPPI.EditMode.init);
</script>

	<div id='email' class="setting placeholder">
		<h3>Emails and Notifications</h3>
		<?php	
			$formOptions['id']='UserForm-email'; 
			$formOptions['url']=Router::url(array('controller'=>'users', 'action'=>'edit', $this->Form->value('User.id')));
			$checkbox_attrs = array('legend'=> false, 'onclick'=>'return false;');
			$radio_attrs = array('legend'=> false,'onclick'=>'return false;' );
					
			echo $this->Form->create('Profile', $formOptions);?>
		<h4>Email Address</h4>
		<?php echo $this->Form->input('email', array('label'=>'Primary Email Address', 'readOnly'=>true));?>
		<?php echo $this->Form->input('isHtmlEmailOk', array_merge( $checkbox_attrs , array('label'=>'Send me HTML emails.')));?>

		<h4>Emails</h4>
		<?php echo $this->Form->input('email_promotions', array_merge( $checkbox_attrs , array('label'=>'Send me emails about special offers and new features.')));?>
		<?php echo $this->Form->input('email_updates',array('type'=>'checkbox','label'=>'Send me emails on important site news.'));?>
		
		<h4>Notifications</h4>
		<?php echo $this->Form->input('notify_members', array_merge( $checkbox_attrs , array('label'=>'Notify me when new members join my Groups or Events.')));?>
		<?php echo $this->Form->input('notify_comments', array_merge( $checkbox_attrs , array( 'label'=>'Notify me when someone comments on my stuff.')));?>
		<?php echo $this->Form->input('notify_tags', array_merge( $checkbox_attrs , array('label'=>'Notify me when someone tags my stuff.')));?>
		<?php echo $this->Form->input('notify_favorites', array_merge( $checkbox_attrs , array('label'=>'Notify me when someone marks my stuff as a favorite.')));?>
		<?php echo $this->Form->input('notify_downloads', array_merge( $checkbox_attrs , array('label'=>'Notify me when someone downloads my stuff.')));?>
		<?php echo $this->Form->hidden('User.id');?>		
		<?php echo $this->Form->hidden('setting',array('value'=>$formOptions['id']));?>
		<?php echo $this->Form->end( array('label'=>'Edit', 'div'=>array('class'=>null)));?>	
			
	</div>	