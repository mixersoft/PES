<?php 
	$commentWidget->options(array('allowAnonymousComment' => false));
	echo $commentWidget->display(array('subtheme'=>'discussion'));
?>
