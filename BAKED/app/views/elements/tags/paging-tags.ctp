<a name='trends'></a>
<div id="tag-cloud" class='placeholder'>
	<h3><?php __('Trends');?></h3>
	<div  class='paging-control paging-sort'>
	<p>Sort by:</p>
	<?php $this->Paginator->options['url']['plugin']=''; ?>
	<ul class="inline">
		<li><?php 
			//TODO the default direction is asc, but maybe later we need desc or switch between two directions
			$options = array('model' => 'Tagged', 'direction' => 'asc');
			echo $this->Paginator->sort('Name', '`Tag`.name', $options);
			?></li>
		<li><?php echo $this->Paginator->sort('Count', '0.occurrence');?></li>
	</ul>
	</div>	

	<?php 
		echo $this->element('/tags/tagCloud', array('isPreview'=>0)); 
	?>
</div>
<div style="text-align:center;"><span id="perpage_button" class="button">Perpage</span></div>