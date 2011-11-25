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
	<script type='text/javascript'> PAGE = {jsonData:{STATE:{}, menu:{}}, init:[]}; </script>
	<?php
		echo $this->Html->meta('favicon.ico', '/img/favicon.ico', array('type' => 'icon') );
		echo $this->Html->css('cake.generic', null,  array('id'=>'css-start'));
		
		echo $this->Html->script('http://yui.yahooapis.com/combo?3.3.0/build/yui/yui-min.js&3.3.0/build/loader/loader-min.js');

		echo $this->Html->css('snappi');
		echo $this->Html->css('menu-skin');
		if (Configure::read('js.bootstrap_snappi')) echo $this->Html->script('/js/snappi/base.js');		
		echo $scripts_for_layout;
	?>
</head>
<body class="yui3-skin-sam">
	<div id="container">
		<div id="header">
			<div id='logo' title='snaphappi' onclick='window.location.href="/photos/all";'></div>
			<?php echo $this->element('nav/search')?>
			<!-- 
			<div id="search"><input type='text' value=' search' title='not ready yet' > </input><a href='#'>Search</a> | <a href='#'>Discover</a></div>
			 -->
		</div>
		<div id="content">
			<?php // echo $this->element('/nav/tabs')?>
			<?php echo $this->element('/nav/header')?>
			<?php echo $this->Session->flash(); ?>
			<?php echo $this->Session->flash('email'); ?>
			<?php echo $content_for_layout; ?>
<script type="text/javascript">
<?php 	$this->viewVars['jsonData']['controller'] = Configure::read('controller');
		foreach ($this->viewVars['jsonData'] as $key=>$value) {
		echo "PAGE.jsonData.{$key}=".json_encode($value).";\n"; 
} ?>
</script>
			
		</div>
		<div id="footer">
			<span style="font-size:0.8em;vertical-align:text-top;">&copy; 2008-2010 Snaphappi</span>
			<?php echo $this->Html->link(
					$this->Html->image('cake.power.gif', array('alt'=> __('CakePHP: the rapid development php framework', true), 'border' => '0')),
					'http://www.cakephp.org/',
					array('target' => '_blank', 'escape' => false)
				);
			?>
		</div>
	</div>
	<?php echo $this->element('sqldump'); ?>
</body>
</html>