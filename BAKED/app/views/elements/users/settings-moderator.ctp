<script type="text/javascript">
PAGE.section = "tab-moderator";
SNAPPI.TabNav.selectByName(PAGE);
// nextTab = {
		// href:'/my/settings?xhrview=settings-identity',
		// className: "tab-identity"
// };
Session::write('settings.tabName', 'moderator');
SNAPPI.EditMode.init();
</script>

	<div id='moderator' class="setting ">
		<h3>Moderator Settings</h3>
		<?php $formOptions['id']='UserForm-moderator';
			$formOptions['url']=Router::url(array('controller'=>'my', 'action'=>'edit'));
			$checkbox_attrs = array('legend'=> false, 'onclick'=>'return false;');
			$radio_attrs = array('legend'=> false,'onclick'=>'return false;' );		 
			echo $this->Form->create('Profile',$formOptions);
			?>
		<p>These settings control who can add social content to your stuff. Note that these people must ALSO be able to see your stuff (see Privacy Settings.) </p>
		
		<h4>Comments</h4>
		<p>The following people are allowed to add Comments to my content:</p>
		<?php echo $form->radio('Profile.socialComments', $moderator['Comments'], $radio_attrs );?>
		
		<h4>Tags</h4>
		<p>The following people are allowed to add Tags to my content:</p>
		<?php	echo $form->radio('Profile.socialTags', $moderator['Tags'], $radio_attrs );?>
		
		<h4>Notifications</h4>
		<?php echo $this->Form->input('Profile.notify_comments', array_merge( $checkbox_attrs , array('label'=>'Notify me when someone comments on my stuff.')));?>
		<?php echo $this->Form->input('Profile.notify_tags', array_merge( $checkbox_attrs , array('label'=>'Notify me when someone tags my stuff.')));?>
		<?php echo $this->Form->hidden('User.id');?>								
		<?php echo $this->Form->hidden('setting',array('value'=>$formOptions['id']));?>
		<?php echo $this->Form->submit("Edit", array('value'=>"Edit", 'class'=>'green')); ?>
		<?php echo $this->Form->end(); ?>
	</div>	