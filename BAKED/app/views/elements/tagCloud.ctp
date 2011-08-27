	<a name='trends'></a>
	<div id="tag-cloud" class='placeholder grid_5'>
	<?php 
	/*
	 * could be deprecated
	 */
		debug('WARNING: elements/tagCloud DEPRECATED?');
	if (!isset($cloudTags)) return;?>
		<aside>
			<section class="popular">
				<h3 class="popular"><?php __('Trends');?></h3>
				<ul>
				<?php 
					echo $this->TagCloud->display($cloudTags, array(
						'url' => array('plugin'=>'','controller' => 'tags', 'action'=>'home'),
						'before' => '<li><span style="font-size: %size%pt" class="tag">',
						'after' => '</span></li>',
						'minSize' => '12',
						'maxSize' => '20',
						'shuffle' => 0
					));
				?>
				</ul>				
			</section>
		</aside>
	</div>