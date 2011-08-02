<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<?php echo $this->Html->charset('UTF-8'); ?>
<title>Coming Soon</title>
<?php
echo $this->Html->meta('favicon.ico', '/img/favicon.ico', array('type' => 'icon') );
echo $this->Html->css('niftyCorners');
echo $this->Html->script('/js/niftycube.js');
?>
<script type="text/javascript">
window.onload=function(){
Nifty("div#container","big transparent");
}
</script>
<style>
<!--
body {
	font-family: arial, helvetica, sans-serif, 'Gill Sans', 'lucida grande';
}

a {
	text-decoration: none;
	color: white;
}

a:link a:visited {
	text-decoration: none;
}

a:hover {
	text-decoration: underline;
}

a img {
	border: none;
}

#container {
	background: white;
	width: 500px;
	padding: 50px;
	text-align: center;
	color: #538fb3;
	overflow: hidden;
}

#footer {
	margin: 4px;
	text-align: right;
	font-weight: bold;
}

#footer img {
	vertical-align: middle;
}
-->
</style>
</head>
<body style="background: #538fb3;">
<div style="margin: 20%; background: #538fb3;">
<div id="container">
<div style="font-size: 2em;">
<p style="text-align: center;">Get ready for the new</p>
<?php echo $this->Html->image('snaphappi.blue.gif', array('alt'=>'Snaphappi', 'url'=>'/welcome/about')); ?>
<p style="font-size: 0.8em">Remixed and ready to go. Summer 2010</p>
</div>
</div>
<div id='footer' style="background: #538fb3;"><a href="/welcome/about">about
us</a> <a href="http://www.facebook.com/pages/Snaphappi/16486082015"><img
	src="/img/comingsoon/facebook_32.png" alt="Find us on Facebook"
	title="Find us on Facebook" /></a> <a
	href="http://www.twitter.com/snaphappi"><img
	src="/img/comingsoon/twitter_32.png" alt="Follow @snaphappi on Twitter"
	title="Follow @snaphappi on Twitter" /></a></div>
</div>
</body>