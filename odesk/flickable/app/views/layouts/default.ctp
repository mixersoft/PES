<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php echo $this->Html->charset(); ?>
	<title>
		<?php echo "Montage Browser File" ?>
		<?php echo $title_for_layout; ?>
	</title>
	<?php
		echo $this->Html->meta('icon');

		echo $this->Html->css('cake.generic');
		
		echo $this->Html->css('horizontal');

		echo $scripts_for_layout;
	?>
	
	<!--[if IE]><style> img {behavior: url(/app/pagemaker/js/fixnaturalwh.htc)}</style><![endif]-->
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="http://snaphappi.com/img/favicon.ico" type="image/x-icon"
	rel="icon" />
<title>Snaphappi Page Layout Maker</title>
<link media="screen" type="text/css"
	href="http://aws.snaphappi.com/app/pagemaker/css/pageGallery.css"
	rel="stylesheet">
<script type="text/javascript" src="http://yui.yahooapis.com/combo?3.3.0/build/yui/yui-min.js">        </script>
<script type="text/javascript" 	src="/flickable/js/pageGallery.js"></script>
<script src="http://yui.yahooapis.com/3.3.0/build/yui/yui-min.js" type="text/javascript" charset="utf-8"></script>	
	
</head>
<body>
	<div id="container">
		<div id="header">
			</div>
		<div id="content">

			<?php echo $this->Session->flash(); ?>

			<?php echo $content_for_layout; ?>

		</div>
		<div id="footer">
			<?php echo $this->Html->link(
					$this->Html->image('cake.power.gif', array('alt'=> __('CakePHP: the rapid development php framework', true), 'border' => '0')),
					'http://www.cakephp.org/',
					array('target' => '_blank', 'escape' => false)
				);
			?>
		</div>
	</div>
	<?php echo $this->element('sql_dump'); ?>
</body>
</html>