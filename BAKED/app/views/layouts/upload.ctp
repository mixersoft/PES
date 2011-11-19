<?php
/**
 *
 * PHP versions 4 and 5
 *
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2005-2010, Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @package       cake
 * @subpackage    cake.cake.libs.view.templates.layouts
 * @since         CakePHP(tm) v 0.10.0.1076
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<?php echo $this->Html->charset('UTF-8'); ?>
	<title>
		<?php echo $title_for_layout; ?>
	</title>
	<script id='css-start' type='text/javascript'> 
		PAGE = {jsonData:{STATE:{}, menu:{}}, init:[]}; 	
		ALLOY_VERSION='alloy-1.0.2';
	</script>
	<script src="/svc/lib/alloy-1.0.2/build/aui/aui.js" type="text/javascript"></script>
	<link rel="stylesheet" type="text/css" media="all" href="/css/manoj-css/reset.css" />
	<link rel="stylesheet" type="text/css" media="all" href="/css/manoj-css/960.css" />	
	<link rel="stylesheet" type="text/css" media="all" href="/css/manoj-css/style.css" />
	<link rel="stylesheet" type="text/css" media="all" href="/css/manoj-css/style.1.css" />
	<?php
		echo $this->Html->meta('favicon.ico', '/img/favicon.ico', array('type' => 'icon') );
		$basepath = Configure::read('path.fileUploader.basepath');
		echo $this->Html->css($basepath.'/client/fileuploader.css');
		echo $this->Html->script($basepath.'/client/fileuploader.js');		
	?>
</head>
<body class='simple'>
	<?php echo $this->element('/nav/primary'); ?>
	<?php $this->Layout->output($this->viewVars['itemHeader_for_layout']); ?>	
<section id="body-container" class='plain container_16'><!--body container start-->
	<div id="content">
		<div id="message" class="messages container_16">
			<?php echo $this->Session->flash(); ?>
			<?php echo $this->Session->flash('email'); ?>
		</div>
		<?php echo $content_for_layout; ?>
	</div>
</section><!--body container ends-->
<?php $this->Layout->output($relatedContent_for_layout); ?>

<?php $this->Layout->blockStart('javascript');?> 
	<script type="text/javascript">
		<?php 	
			$this->viewVars['jsonData']['controller'] = Configure::read('controller');
			foreach ($this->viewVars['jsonData'] as $key=>$value) {
				echo "PAGE.jsonData.{$key}=".json_encode($value).";\n"; 
			} 
		?>
		PAGE.goSearch = function() {
			var value = SNAPPI.Y.one('#search input').get('value');
			if (value) {
				if (value.length>2) {
					var here = window.location.href;
					var namedData = {q:value, page: null};
					window.location.href = SNAPPI.IO.setNamedParams(here, namedData);
					return true;
				} else {
					alert('please enter at least 3 chars in your search');
				}
			}  
			return false;
		}; 
	</script>	
<?php $this->Layout->blockEnd();?>	
<div class="anchor-bottom"></div>
<div id='markup'>
	<?php $this->Layout->output($this->viewVars['menuMarkup_for_layout']); ?>
</div>		

<div id="footer" class="container_16">
	<div class="grid_16">
		<span>&copy; 2008-2012 Snaphappi</span>
		<?php echo $this->Html->link(
				$this->Html->image('cake.power.gif', array('alt'=> __('CakePHP: the rapid development php framework', true), 'border' => '0')),
				'http://www.cakephp.org/',
				array('target' => '_blank', 'escape' => false)
			);
		?>		
	</div>
</div>
<?php // echo $this->element('sql_dump'); ?>
</body>
</html>
	<?php
		// TODO: use 'light' init, not main()
		echo $this->Html->script('/js/snappi/base_aui.js');		
		echo $scripts_for_layout;
		$this->Layout->output($this->viewVars['javascript_for_layout']);
	?>
