<?php $uuid = Session::read('Auth.User.id'); ?>
<script <?php if ($this->layout == 'ajax')  echo "class='xhrInit' "; ?> type="text/javascript">
PAGE.section = "tab-identity";
SNAPPI.TabNav.selectByName(PAGE);
nextTab = {
		href:'/users/settings/<?php echo $uuid; ?>?xhrview=settings-emails',
		className: "tab-emails"
};
PAGE.init.push(SNAPPI.EditMode.init);
</script>
	<div id='profile' class="setting placeholder">
		<h3>Identity and Personal Profile</h3>
		<?php	
			$formOptions['id']='UserForm-profile';
			$formOptions['url']=Router::url(array('controller'=>'users', 'action'=>'edit', $this->Form->value('User.id')));
			$checkbox_attrs = array('legend'=> false, 'onclick'=>'return false;');
			$radio_attrs = array('legend'=> false,'onclick'=>'return false;' );			
			echo $this->Form->create('Profile', $formOptions);?>
			
		<h4>Your Display Names</h4>	
		<?php echo $this->Form->input('User.username', array('label'=>'Username', 'readOnly'=>true));?>
		<?php echo $this->Form->input('User.slug', array('label'=>'Vanity URL', 'readOnly'=>true));?>  
		<p>Member since: <?php echo substr($this->Time->nice($data['User']['created']), 0, -9); ?></p>
		   
		<h4>Your Real Name</h4>    
		<?php echo $this->Form->input('fname', array('label'=>'First Name', 'readOnly'=>true));?>
		<?php echo $this->Form->input('lname', array('label'=>'Last Name', 'readOnly'=>true));?> 
		<h4>Your Personal Information</h4> 
		<?php 
			$fields = array();
			//$fields['title'] = $this->Form->value('User.username');
			$fields['src_icon'] = Stagehand::getSrc(  $this->Form->value('User.src_thumbnail'),  'sq', 'person');
			$options = array('url'=>array_merge(array('plugin'=>'','controller'=>'users', 'action'=>'home', $this->Form->value('User.id')))); 
		    echo $this->Html->image($fields['src_icon'] , null) ?>
		   
		<?php echo $this->Form->input('gender', array('label'=>'Gender', 'readOnly'=>true));?>      
		<?php echo $this->Form->input('city', array('label'=>'City', 'readOnly'=>true));?> 
		<?php echo $this->Form->input('country', array('label'=>'Country', 'readOnly'=>true));?>
		<?php echo $this->Form->input('utcOffset', array('label'=>'Timezone', 'readOnly'=>true));?>
		<?php echo $this->Form->hidden('User.id');?>		
		<?php echo $this->Form->hidden('setting',array('value'=>$formOptions['id']));?>
		<?php echo $this->Form->end( array('label'=>'Edit', 'div'=>array('class'=>null)));?>	
	</div>