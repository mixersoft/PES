<?php 	$model = Inflector::singularize($this->name); 
//			if (!isset($data[$model]['tags'])) return;
			?>
<div id='tag=form'>
	<?php  
	// echo $this->Form->create('Tag', array('url' => array('controller' => 'tags', 'action' => 'add')));
	// $options = array('type'=>'text',
		// 'value'=>'Enter tags', 
		// 'class'=>'help',
		// 'onclick'=>'this.value=null; this.className=null',
		// 'label'=>'');
	// $orange = array('class'=>'orange');
	// echo $this->Form->input('strTags', $options);
	// echo $this->Form->hidden('foreignKey', array('value'=>$data[$model]['id']) );
	// echo $this->Form->hidden('class', array('value'=>$model) );
	// echo $this->Form->submit('Go', $orange );
	// echo $this->Form->end();
	?>

	<form id="TagHomeForm" method="post" action="/tags/add" accept-charset="utf-8">
		<input name="_method" value="POST" type="hidden">
		<label for="TagStrTags"></label>
		<input name="data[Tag][strTags]" value="Enter tags" class="help" onclick="this.value=null; this.className=null" id="TagStrTags" type="text">
		<input class="orange" value="Go" type="submit">
		<input name="data[Tag][foreignKey]" value="4bbb3907-1d88-4f31-82e4-11a0f67883f5" id="TagForeignKey" type="hidden">
		<input name="data[Tag][class]" value="Asset" id="TagClass" type="hidden">
	</form>	
</div>
	