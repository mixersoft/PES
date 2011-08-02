<a name='tags'></a>
<?php 	$model = Inflector::singularize($this->name); 
//			if (!isset($data[$model]['tags'])) return;
			?>
	<div id="<?php echo isset($domId) ? $domId : "{$this->params['controller']}-tags" ?>">
		<h3><?php __('Tags'); ?></h3>
			<div id='tag-list'>
				<?php if (!empty($data['Tag'])):?>
				<ul>
					<?php foreach ($data['Tag'] as $tag): ?>
					<li><?php echo $this->Html->link($data['name'], array('controller' => 'tags', 'action' => 'home', $data['keyname']))?></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			</div>
			<div id='tag=form'>
			<?php  echo $this->Form->create('Tag', array('url' => array('controller' => 'tags', 'action' => 'add')));
			echo $this->Form->input('strTags', array('label'=>'Add Tags'));
			echo $this->Form->hidden('foreignKey', array('value'=>$data[$model]['id']) );
			echo $this->Form->hidden('class', array('value'=>$model) );
			echo $this->Form->end('submit');
			?>
		</div>
	</div>
	