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
	<script src="/svc/lib/alloy-1.0.2/build/aui/aui.js" type="text/javascript"></script>
	<link rel="stylesheet" type="text/css" media="all" href="/css/manoj-css/reset.css" />
	<link rel="stylesheet" type="text/css" media="all" href="/css/manoj-css/960.css" />	
	<link rel="stylesheet" type="text/css" media="all" href="/css/manoj-css/style.css" />
	<link rel="stylesheet" type="text/css" media="all" href="/css/manoj-css/style.1.css" />
	<script id='css-start' type='text/javascript'> PAGE = {jsonData:{STATE:{}, menu:{}}, init:[]}; 	ALLOY_VERSION='alloy-1.0.2';</script>
	<?php
		echo $this->Html->meta('favicon.ico', '/img/favicon.ico', array('type' => 'icon') );
		echo $this->Html->css('cake.generic', null);
		
		$basepath = Configure::read('path.fileUploader.basepath');
		echo $this->Html->css($basepath.'/client/fileuploader.css');
		echo $this->Html->script($basepath.'/client/fileuploader.js');
				
	?>
</head>
<body class="yui3-skin-sam">
	<div id="container">
		<div id="header" class="container_16">
			<div id='logo' class='grid_4' title='snaphappi' onclick='window.location.href="/photos/all";'></div>
			<?php echo $this->element('/nav/header')?>
			<?php echo $this->element('nav/search')?>
			<!-- 
			<div id="search"><input type='text' value=' search' title='not ready yet' > </input><a href='#'>Search</a> | <a href='#'>Discover</a></div>
			 -->
		</div>
		<div id="content" class="container_16">
			<?php echo $this->Session->flash(); ?>
			<?php echo $content_for_layout; ?>
			<?php if (Configure::read('js.render_lightbox')) {echo $this->element('/lightbox'); }?>
<?php if (!empty($this->viewVars['jsonData'])) {  // debug($this->viewVars['jsonData']); ?>
<script type="text/javascript">
<?php foreach ($this->viewVars['jsonData'] as $key=>$value) {
		echo "PAGE.jsonData.{$key}=".json_encode($value).";\n"; 
} ?>
</script>
<?php } ?>
			
		</div>
		<div id='markup' >
			<div id="menu-header" class="menu yui3-aui-overlaycontext-hidden hide">
				<ul>
					<li id='home' >
						<a href="/my/home">My Home</a>
					</li>
					<li id='photos' >
						<a href="/my/photos">My Photos</a>
					</li>
					<li id='photostreams' >
						<a href="/my/photostreams">My Photostreams</a>
					</li>		
					<li id='groups' >
						<a href="/my/groups">My Groups</a>
					</li>			
					<li id='trends' >
						<a href="/my/home#trends">My Trends</a>
					</li>
					<li id='upload' >
						<a href="/my/upload">Upload Photos</a>
					</li>
					<li id='settings' >
						<a href="/my/settings">My Settings</a>
					</li>
					<li id='lightbox' class='before-show'>
						<a href="javascript:;">Hide Lightbox</a>
					</li>
				</ul>
			</div>		
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
	<?php echo $this->element('sql_dump'); ?>
	<?php
		if (Configure::read('js.bootstrap_snappi')) echo $this->Html->script('/js/snappi/base_aui.js');		
		echo $scripts_for_layout;
	?>	
</body>
</html>