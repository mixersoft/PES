<?php 
	$this->Layout->blockStart('itemHeader');
		$icon_src = $badge_src = $data['User']['src_thumbnail'];
		echo $this->element('nav/section', compact('badge_src', 'icon_src'));
	$this->Layout->blockEnd();
?>
<div class="users form">
	<h2><?php printf(__('Edit %s', true), __('My Settings', true)); ?></h2>
	<div id='profile'  class="setting placeholder">
		<h3>Identity and Personal Profile</h3>
		<?php  
			$formOptions['url']=Router::url(array('controller'=>'users', 'action'=>'edit', $this->Form->value('User.id'))); 
			$formOptions['id']='UserForm-profile';
			$checkbox_attrs = array('legend'=> false);
			$radio_attrs = array('legend'=> false , 'separator'=>'<br />');
			echo $this->Form->create('Profile', $formOptions);?>	
			
		<h4>Your Display Names</h4>			
		<?php echo $this->Form->input('User.username', array('label'=>'Username'));?>
		<?php echo $this->Form->input('User.slug', array('label'=>'Vanity URL'));?>     
		<h4>Your Real Name</h4>    
		<?php echo $this->Form->input('fname', array('label'=>'First Name'));?>
		<?php echo $this->Form->input('lname', array('label'=>'Last Name'));?> 
		<h4>Your Personal Information</h4> 
		<?php 
			$fields = array();
			//$fields['title'] = $this->Form->value('User.username');
			$fields['src_icon'] = Stagehand::getSrc( $this->Form->value('User.src_thumbnail') , 'sq', 'person');
			$options = array('url'=>array_merge(array('plugin'=>'','controller'=>'users', 'action'=>'home', $this->Form->value('User.id')))); 
		    echo $this->Html->image($fields['src_icon'] , null) ?>
		   
		<?php echo $this->Form->input('gender', array('label'=>'Gender'));?>      
		<?php echo $this->Form->input('city', array('label'=>'City'));?> 
		<?php echo $this->Form->input('country', array('label'=>'Country'));?>
		<?php echo $this->Form->input('utcOffset', array('label'=>'Timezone'));?>

		<?php 	echo $this->Form->hidden('User.id');?>
		<?php 	echo $this->Form->hidden('id');?>
		<?php echo $this->Form->end(__('Submit', true));?>	
	</div>
	
	<div id='email'  class="setting placeholder">
		<h3>Emails and Notifications</h3>
		<?php $formOptions['id']='UserForm-email'; 
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
		
		<?php 	echo $this->Form->hidden('User.id');?>
		<?php 	echo $this->Form->hidden('id');?>
		<?php echo $this->Form->end(__('Submit', true));?>	
			
	</div>	
	
	<div id='privacy' class="setting placeholder">
		<h3>Default Privacy Settings</h3>
		<?php $formOptions['id']='UserForm-privacy'; 
			echo $this->Form->create('Profile',$formOptions);?>
		<p>These settings control who can see your stuff.</p>
		
		<h4>Photos</h4>
		<p>The Photos and Page Galleries I import, upload or create:</p>
		<div class="radio-group">
		<?php echo $form->radio('privacy_assets', $privacy['Asset'], $radio_attrs );?>
		</div>
		
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
						
		<?php 	echo $this->Form->hidden('User.id');?>
		<?php 	echo $this->Form->hidden('id');?>
		<?php echo $this->Form->end(__('Submit', true));?>					
	</div>
	
	
	<div id='moderator'  class="setting placeholder">
		<h3>Default Moderator Settings</h3>
		<?php $formOptions['id']='UserForm-moderator'; 
			echo $this->Form->create('Profile',$formOptions);
			?>
		<p>These settings control who can add social content to your stuff. Note that these people must ALSO be able to see your stuff (see Privacy Settings.) </p>
		
		<h4>Comments</h4>
		<p>The following people are allowed to add Comments to my content:</p>
		<div class="radio-group">
		<?php echo $form->radio('socialComments', $moderator['Comments'], $radio_attrs );?>
		</div>
		
		<h4>Tags</h4>
		<p>The following people are allowed to add Tags to my content:</p>
		<div class="radio-group">
		<?php	echo $form->radio('socialTags', $moderator['Tags'], $radio_attrs );?>
		</div>

		<h4>Notifications</h4>
		<?php echo $this->Form->input('notify_comments', array_merge( $checkbox_attrs , array('label'=>'Notify me when someone comments on my stuff.')));?>
		<?php echo $this->Form->input('notify_tags', array_merge( $checkbox_attrs , array('label'=>'Notify me when someone tags my stuff.')));?>
					
		<?php 	echo $this->Form->hidden('User.id');?>
		<?php 	echo $this->Form->hidden('id');?>
		<?php echo $this->Form->end(__('Submit', true));?>						
	</div>		
		
</div>