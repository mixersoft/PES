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
	<link rel="stylesheet" type="text/css" media="all" href="/combo/js?baseurl=svc/lib/alloy-1.0.2/build&/aui-skin-classic/css/aui-skin-classic-all.css&/aui-loading-mask/assets/skins/sam/aui-loading-mask.css&/aui-overlay/assets/skins/sam/aui-overlay.css&" />
	<link rel="stylesheet" type="text/css" media="all" href="/css/manoj-css/reset.css" />
	<link rel="stylesheet" type="text/css" media="all" href="/css/manoj-css/960-fluid.css" />	
<!-- 	<link rel="stylesheet" type="text/css" media="all" href="/css/manoj-css/style.css" /> -->
	<link rel="stylesheet" type="text/css" media="all" href="/css/manoj-css/style.1.css" />
	<?php
		echo $this->Html->meta('favicon.ico', '/img/favicon.ico', array('type' => 'icon') );
		// echo $this->Html->css('cake.generic', null);
		// echo $this->Html->css('snappi-aui-960');
		// echo $this->Html->css('menu-skin');
	?>
</head>
<body>
	<?php echo $this->element('/nav/primary-wide'); ?>
		
<section id="body-container" class='container_16'><!--body container start-->
	<?php // echo $this->element('/nav/secondary'); ?>
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
<?php $this->Layout->output($lightbox_for_layout); ?>	

<?php $this->Layout->blockStart('javascript');?> 
	<script type="text/javascript">
		<?php 	
			$this->viewVars['jsonData']['controller'] = Configure::read('controller');
			foreach ($this->viewVars['jsonData'] as $key=>$value) {
				echo "PAGE.jsonData.{$key}=".json_encode($value).";\n"; 
			} 
		?>
	</script>	
<?php $this->Layout->blockEnd();?>	

<div id='markup' style="display:none;">
	<div id="menu-header-markup" class="menu yui3-aui-overlaycontext-hidden hide">
		<ul>
			<li action='home' >
				<a href="/my/home">My Home</a>
			</li>
			<li action='photos' >
				<a href="/my/photos">My Photos</a>
			</li>
			<li action='photostreams'  class='disabled'>
				<a href="/my/photostreams">My Photostreams</a>
			</li>		
			<li action='groups' >
				<a href="/my/groups">My Groups</a>
			</li>			
			<li action='trends'  class='disabled'>
				<a href="/my/home#trends">My Trends</a>
			</li>
			<li action='upload' >
				<a href="/my/upload">Upload Photos</a>
			</li>
			<li action='settings' >
				<a href="/my/settings">My Settings</a>
			</li>
		</ul>
	</div>
	<div id="menu-select-all-markup" class="menu yui3-aui-overlaycontext-hidden hide">
		<ul>
			<li action='select_all'>Select All</li>
			<li action='select_all_pages' class='before-show'>Select All Pages</li>
			<li action='clear_all'>Clear All</li>
			<li action='remove_selected' class='before-show'>Remove Selected Snaps</li>
			<li action='delete' class='before-show'>Delete Selected Snaps</li>
		</ul>
	</div>	
	<div id="menu-lightbox-organize-markup" class="menu yui3-aui-overlaycontext-hidden hide">
		<ul>
			<li action='batch_rating' class='before-show'>Apply rating:</li>
			<li action='tag' class='before-show'></li>
			<li action='lightbox_group_as_shot'>Group as Shot</li>
		</ul>
	</div>
	<div id="menu-lightbox-share-markup" class="menu yui3-aui-overlaycontext-hidden hide">
		<ul>
			<li action='share_with_this_circle' class='before-show'>Share with this Circle</li>
			<li action='share_with_circle'>Share with Circle...</li>
			<li action='photo_privacy'>Privacy...</li>
		</ul>
	</div>	
	<?php $this->Layout->output($this->viewVars['markup_for_layout']); ?>
</div>		

<div id="footer" class="container_16">
	<span style="font-size:0.8em;vertical-align:text-top;">&copy; 2008-2011 Snaphappi</span>
	<?php echo $this->Html->link(
			$this->Html->image('cake.power.gif', array('alt'=> __('CakePHP: the rapid development php framework', true), 'border' => '0')),
			'http://www.cakephp.org/',
			array('target' => '_blank', 'escape' => false)
		);
	?>
</div>

	<?php echo $this->element('sqldump');
		$this->Layout->blockStart('javascript'); ?>
		<script type="text/javascript">		
			// this has to be AFTER base_aui.js load
			SNAPPI.LazyLoad.gallery();
		</script>	
	<?php	
		$this->Layout->blockEnd();
		
		echo $this->Html->script('/js/snappi/base_aui.js');		
		echo $scripts_for_layout;
		$this->Layout->output($this->viewVars['javascript_for_layout']);
	?>
</body>
</html>