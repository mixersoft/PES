<?php 
/*
 * layout/CSS/JS for snappi-dev/gallery/story as shown in iframe from thats-me/story/[id]
 */

	  echo header('Pragma: no-cache');
	  echo header('Cache-control: no-cache');
	  echo header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
	  // debug($isTouch); // set in AppController::__redirectIfTouchDevice()
	  if ($isTouch) $play_filename = 'play-touch';
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
       	<style type="text/css">
       		body { overflow:hidden; background: none; }
       		#glass > div#bottom { background: none; }
       		#paging .prev, #paging .next {
       			  -webkit-border-radius: 4px;
				  -moz-border-radius: 4px;
				  -ms-border-radius: 4px;
				  -o-border-radius: 4px;
				  border-radius: 4px; 
       		}
       		#header, #footer, #share {
       			font-family: "Roboto", "Helvetica Neue", Helvetica, Arial, sans-serif;
       			color: #FDF9D0;
       		}
       	</style>
       	<script type="text/javascript">
       		window.onload = function() {
       			parent.postMessage('iframe#story-iframe loaded', '*');
			};	
       	</script>
    </head>
     <?php flush(); ?>
    <body>
        <?php echo $content_for_layout; ?>
    </body>
	<script type="text/javascript" src="/svc/lib/yui_3.4.0/yui/build/yui/yui-min.js"></script>
	<script type="text/javascript" src="/app/pagemaker/static/js/<?php echo $play_filename; ?>.js"></script> 
</html>
