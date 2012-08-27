<?php
/**
 * No javascript layout file. 
 * - repeat look and feel, but no javascript
 * - for browser_redirect 
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
	<title>
		<?php echo $title_for_layout; ?>
	</title>
	<link rel="stylesheet" type="text/css" href="<?php echo AppController::$http_static[0]; ?>/min/b=static/css&amp;f=reset.css,960.css" />	
	<link rel="stylesheet" type="text/css" media="all" href="<?php echo AppController::$http_static[1]; ?>/css/manoj-css/style.1.css" />
	<link rel="icon" type="image/x-icon" href="/static/img/favicon.ico">
</head>
 <?php flush(); ?>
<body class='simple'>
	<?php echo $this->element('/nav/primary'); ?>
	<?php $this->Layout->output($this->viewVars['itemHeader_for_layout']); ?>	
<section id="body-container" class='plain container_16'><!--body container start-->
	<div id="content">
		<div id="message" class="messages prefix_2 grid_12 suffix_2">
			<?php echo $this->Session->flash(); ?>
			<?php echo $this->Session->flash('email'); ?>
		</div>
		<div class='clear'></div>
		<?php echo $content_for_layout; ?>
	</div>
</section><!--body container ends-->
<?php $this->Layout->output($relatedContent_for_layout); ?>
<div id="footer" class="container_16">
	<div class="grid_16">
		<span>&copy; 2008-2012 Snaphappi</span>
		<a href="http://www.cakephp.org/" target="_blank"><img src="<?php echo AppController::$http_static[0]; ?>/static/img/css-gui/cake.power.gif" alt="CakePHP: the rapid development php framework" border="0"></a>		
	</div>
</div>
	<?php	
		// put JS at the bottom of the page
		echo $scripts_for_layout;
		$this->Layout->output($this->viewVars['javascript_for_layout']);
		echo $this->element('analytics');
		echo $this->element('dumpSQL');
	?>
</body>
</html>
