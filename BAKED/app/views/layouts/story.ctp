<?php echo header('Pragma: no-cache');
	  echo header('Cache-control: no-cache');
	  echo header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
	  if (0 || strpos(env('SERVER_NAME'),'touch.')===0) $play_filename = 'play-touch';
	  else $play_filename = 'play';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
    <head>
        <!--[if IE]><style> img {behavior: url(/app/pagemaker/static/js/fixnaturalwh.htc)}</style><![endif]-->
        <!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=9">[endif]-->
        <meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
		<title>
			<?php echo $title_for_layout; ?>
		</title>		
        <link href="/static/img/favicon.ico" type="image/x-icon" rel="icon" />
       	<link media="screen" type="text/css" href="/app/pagemaker/static/css/<?php echo $play_filename; ?>.css" rel="stylesheet">
    </head>
     <?php flush(); ?>
    <body style="overflow:hidden">
        <?php echo $content_for_layout; ?>
    </body>
	<script type="text/javascript" src="http://yui.yahooapis.com/combo?3.3.0/build/yui/yui-min.js"></script>
	<script type="text/javascript" src="/app/pagemaker/static/js/<?php echo $play_filename; ?>.js"></script> 
</html>
<?php if (empty($this->params['url']['iframe'])) {
	 echo $this->element('analytics'); 
?>
<script type="text/javascript">var switchTo5x=false; _load_sharethis=true;</script>
<?php  }  ?>