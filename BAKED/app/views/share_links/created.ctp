<h2>The link was created</h2>

<p>This is your link URL</p>

<h3>
	<?php
		echo $this->Html->link(array(
			'controller' => 'share_links', 
			'action' => 'view', 
			$shareLink['ShareLink']['secret_key'])
			, null, array('target'=>'_blank')
		); 
	?>
	</h3>