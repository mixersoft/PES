<?php 
echo header('Pragma: no-cache');
echo header('Cache-control: no-cache');
echo header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
<?php	  
	$hostname = 'thats-me.snaphappi.com';
	echo '<link href="http://'.$hostname.'/min/b%3Dcss%26f%3Dbootstrap.css%2Cresponsive.css%2Cfonts.css%2Cbeachfront-2.css%2Cbeachfront.css%2Cresponsive-tablet.css%2Cresponsive-mobile.css%2Cfont-awesome.css" type="text/css" rel="stylesheet">';
	echo '<link href="http://'.$hostname.'/css/beachfront-less.css" type="text/css" rel="stylesheet">';	
	$this->Layout->output($this->viewVars['HEAD_for_layout']);
?>			
	</head>
	<body>
<?php	  
	echo $content_for_layout;	
?>		
	</body>
</html>

