<?php echo header('Pragma: no-cache');
	  echo header('Cache-control: no-cache');
	  echo header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
    <head>
        <!--[if IE]><style> img {behavior: url(/app/pagemaker/static/js/fixnaturalwh.htc)}</style><![endif]-->
        <!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=edge" /><![endif]-->
        <meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
		<title>
			<?php echo $title_for_layout; ?>
		</title>		
        <link href="/static/img/favicon.ico" type="image/x-icon" rel="icon" />
       	<link media="screen" type="text/css" href="/app/pagemaker/static/css/play.css" rel="stylesheet">
    </head>
     <?php flush(); ?>
    <body>
        <?php echo $content_for_layout; ?>
    </body>
	<script type="text/javascript" src="http://yui.yahooapis.com/combo?3.3.0/build/yui/yui-min.js"></script>
    <script type="text/javascript" src="/app/pagemaker/static/js/play.js"></script> 
</html>
<?php if (empty($this->params['url']['iframe'])) {
	 echo $this->element('analytics'); 
?>
<!--  share this  -->
<script type="text/javascript">var switchTo5x=false;</script>
<script type="text/javascript" src="http://w.sharethis.com/button/buttons.js"></script>
<script type="text/javascript">stLight.options({publisher: "ur-1fda4407-f1c8-d8ff-b0bd-1f1ff46eeb72"}); </script>
<?php } ?>