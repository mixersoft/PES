<?php  $uuid = $this->passedArgs[0]; ?>
<script <?php if ($this->layout == 'ajax')  echo "class='xhrInit' "; ?> type="text/javascript">
PAGE.section = "tab-details";
nextTab = {
		href:'/groups/settings/<?php echo $uuid; ?>?xhrview=settings-privacy',
		className: "tab-privacy"
};
SNAPPI.TabNav.selectByName(PAGE);
PAGE.init.push(SNAPPI.EditMode.init);
</script>
	<div id='fields' class="setting placeholder">
		<h3>Description</h3>
		<?php	
			$formOptions['url']=Router::url(array(
				'controller'=>Configure::read('controller.alias'), 
				'action'=>'edit', 
				$this->Form->value('Group.id')));
			$formOptions['id']='GroupForm-fields';
			$checkbox_attrs = array('legend'=> false, 'onclick'=>'return false;');
			$radio_attrs = array('legend'=> false,'onclick'=>'return false;' );
			echo $this->Form->create('Group', $formOptions);?>
			
		<?php echo $this->Form->input('title', array('label'=>'Title', 'readOnly'=>true));?>
		<?php echo $this->Form->input('description', array('label'=>'Description', 'readOnly'=>true));?>
		<?php echo $this->Form->input('isNC17', array_merge( $checkbox_attrs , array('label'=>'Rated NC17')));?> 
		<?php echo $this->Form->hidden('setting',array('value'=>$formOptions['id']));?>
		<?php echo $this->Form->end( array('label'=>'Edit', 'div'=>array('class'=>null)));?>
	</div>	