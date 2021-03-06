<script type="text/javascript">
SNAPPI.tabSection.setFocus("#tab-identity");
SNAPPI.EditMode.init();
</script>
	<div id='profile' class="setting tab-panel">
		<h3>Identity and Personal Profile</h3>
		<?php	
			$orange = array('class'=>'orange'); 
			$formOptions['id']='UserForm-identity';
			$formOptions['url']=Router::url(array('controller'=>'my', 'action'=>'edit'));
			$checkbox_attrs = array('legend'=> false, 'onclick'=>'return false;');
			$radio_attrs = array('legend'=> false,'onclick'=>'return false;', 'separator'=>'<br />' );			
			echo $this->Form->create('Profile', $formOptions);?>
		<h4>Your Account</h4>	
		<?php echo $this->Form->button("Change Password", array('onclick'=>'window.location.href=\'/users/change_password\'; return false;', 'class'=>'orange')); ?>
			
		<h4>Your Display Names</h4>	
		<?php echo $this->Form->input('User.username', array('label'=>'Username', 'readOnly'=>true));?>
		<?php echo $this->Form->input('User.slug', array('label'=>'Vanity URL', 'readOnly'=>true));?>  
		<p>Member since: <?php echo substr($this->Time->nice($data['User']['created']), 0, -9); ?></p>
		   
		<h4>Your Real Name</h4>    
		<?php echo $this->Form->input('Profile.fname', array('label'=>'First Name', 'readOnly'=>true));?>
		<?php echo $this->Form->input('Profile.lname', array('label'=>'Last Name', 'readOnly'=>true));?> 
		<h4>Your Personal Information</h4> 
		<?php 
			$fields = array();
			//$fields['title'] = $this->Form->value('User.username');
			$fields['src_icon'] = Stagehand::getSrc(  $this->Form->value('User.src_thumbnail'),  'sq', 'Person');
			$options = array('url'=>array_merge(array('plugin'=>'','controller'=>'users', 'action'=>'home', $this->Form->value('User.id')))); 
		    echo $this->Html->image($fields['src_icon'] , null) ?>
		   
		<?php echo $this->Form->input('Profile.gender', array('label'=>'Gender', 'readOnly'=>true));?>      
		<?php echo $this->Form->input('Profile.city', array('label'=>'City', 'readOnly'=>true));?> 
		<?php echo $this->Form->input('Profile.country', array('label'=>'Country', 'readOnly'=>true));?>
		<?php echo $this->Form->input('Profile.utcOffset', array('label'=>'Timezone', 'readOnly'=>true));?>
		<?php echo $this->Form->hidden('User.id');?>		
		<?php echo $this->Form->hidden('setting',array('value'=>$formOptions['id']));?>
		<?php echo $this->Form->submit("Edit", array('value'=>"Edit", 'class'=>'green')); ?>
		<?php echo $this->Form->end(); ?>
	</div>