<a name='trends'></a>
<div id="tag-cloud" class='placeholder'>
	<h3><?php __('Trends');?></h3>
	<?php
		if (!isset($this->params['url']['preview'])) $this->params['url']['preview'] = 1;	// default isPreview==true
		if (isset($this->passedArgs['preview'])) $this->params['url']['preview'] = $this->passedArgs['preview'];	// override
		echo $this->element('/tags/tagCloud');
	?>
</div>