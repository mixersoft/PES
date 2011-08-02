	<?php if (!isset($cloudTags)) return;?>
	<a name='trends'></a>
	<div id="tag-cloud" class='placeholder'>
		<h3><?php __('Trends');?></h3>
		<?php 
			echo $this->TagCloud->display($cloudTags, array(
				'url' => array('plugin'=>'','controller' => 'tags', 'action'=>'home'),
				'before' => '<span style="font-size: %size%pt" class="tag">',
				'after' => '</span>',
				'minSize' => '12',
				'maxSize' => '20',
				'shuffle' => 0
			));
		?>
	</div>