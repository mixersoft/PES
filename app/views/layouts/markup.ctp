<?php echo header('Pragma: no-cache');
	  echo header('Cache-control: no-cache');
	  echo header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); ?>
<?php 
	echo $content_for_layout;
	if (isset($cData)) 	echo $this->element('script/cookieData');
?>
