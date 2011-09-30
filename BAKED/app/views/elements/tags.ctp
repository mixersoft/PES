<?php 	$model = Inflector::singularize($this->name); 
//			if (!isset($data[$model]['tags'])) return;
			?>
<div id='tag=form'>
	<?php  echo $this->Form->create('Tag', array('url' => array('controller' => 'tags', 'action' => 'add')));
	$options = array('type'=>'text',
		'value'=>'Enter tags', 
		'class'=>'help',
		'onclick'=>'this.value=null; this.className=null',
		'label'=>'');
	echo $this->Form->input('strTags', $options);
	echo $this->Form->hidden('foreignKey', array('value'=>$data[$model]['id']) );
	echo $this->Form->hidden('class', array('value'=>$model) );
	echo $this->Form->end('Go');
	?>
</div>
	