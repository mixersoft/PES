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
	<?php echo $this->Html->charset('UTF-8'); ?>
	<title>
		<?php echo $title_for_layout; ?>
	</title>
	<link rel="stylesheet" type="text/css" media="all" href="/static/css/reset.css" />
	<link rel="stylesheet" type="text/css" media="all" href="/static/css/960.css" />	
	<link rel="stylesheet" type="text/css" media="all" href="/css/manoj-css/style.1.css" />
	<?php
		echo $this->Html->meta('favicon.ico', '/static/img/favicon.ico', array('type' => 'icon') );
		echo $this->element('analytics');
	?>
</head>
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
		<?php echo $this->Html->link(
				$this->Html->image('/static/img/css-gui/cake.power.gif', array('alt'=> __('CakePHP: the rapid development php framework', true), 'border' => '0')),
				'http://www.cakephp.org/',
				array('target' => '_blank', 'escape' => false)
			);
		?>		
	</div>
</div>
</body>
</html>
