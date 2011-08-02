<?php $uuid = $this->passedArgs[0]; ?>
<script <?php if ($this->layout == 'ajax')  echo "class='xhrInit' "; ?> type="text/javascript">
PAGE.section = "tab-details";
nextTab = {
		href:'/photos/settings/<?php echo $uuid; ?>?xhrview=settings-privacy',
		className: "tab-privacy"
};
SNAPPI.TabNav.selectByName(PAGE);
try {
	PAGE.init.push(SNAPPI.EditMode.init);
} catch(e) {
	PAGE.init = [	SNAPPI.EditMode.init ];
}
</script>
	<div id='fields' class="setting placeholder">
		<h3>Description</h3>
		<?php	
			$formOptions['url']=Router::url(array(
				'controller'=>Configure::read('controller.alias'), 
				'action'=>'edit', 
				$this->Form->value('Asset.id')));
			$formOptions['id']='AssetForm-fields';
			echo $this->Form->create('Asset', $formOptions);?>
			
		<?php echo $this->Form->input('caption', array('label'=>'Caption', 'readOnly'=>true));?>
		<?php echo $this->Form->input('keyword', array('label'=>'Keywords', 'readOnly'=>true));?> 
		<?php echo $this->Form->hidden('setting',array('value'=>$formOptions['id']));?>
		<?php echo $this->Form->end( array('label'=>'Edit', 'div'=>array('class'=>null)));?>
	</div>	