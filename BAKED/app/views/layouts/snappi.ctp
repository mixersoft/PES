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
		// ALLOY_VERSION='alloy-1.0.2-20110809-export';
		ALLOY_VERSION='alloy-1.0.2';
	</script>
	<link rel="stylesheet" type="text/css" media="all" href="<?php echo AppController::$http_static[1]; ?>/combo/js?baseurl=svc/lib/alloy-1.0.2/build&/aui-skin-classic/css/aui-skin-classic-all.css&/aui-loading-mask/assets/skins/sam/aui-loading-mask.css&/aui-overlay/assets/skins/sam/aui-overlay.css&" />
	<link rel="stylesheet" type="text/css" href="<?php echo AppController::$http_static[0]; ?>/min/b=static/css&amp;f=reset.css,960.css" />	
	<link rel="stylesheet" type="text/css" media="all" href="<?php echo AppController::$http_static[1]; ?>/css/manoj-css/style.1.css" />
	<?php
		echo $this->Html->meta('favicon.ico', '/static/img/favicon.ico', array('type' => 'icon') );
	?>
</head>
 <?php flush(); ?>
<body>
	<?php echo $this->element('/nav/primary'); ?>
	<?php $this->Layout->output($this->viewVars['itemHeader_for_layout']); ?>	
<section id="body-container" class='container_16'><!--body container start-->
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
<div class="anchor-bottom"></div>
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
	<div id="menu-header-create-markup" class="menu yui3-aui-overlaycontext-hidden hide">
		<ul>
			<li action='authenticated' class="before-show disabled" title="Create a new Group or Event and invite your friends to join.">
				<a href="/groups/create" >New Circle</a>
			</li>
			<li action='authenticated' class="before-show disabled" title="Upload photos into Snaphappi">
				<a href="/my/upload">New Snaps</a>
			</li>	
			<li action='create_pagegallery' class="disabled before-show" title="Select Snaps or Add to Lightbox to create a new Story" >
				<a>New Story</a>
			</li>
		</ul>
	</div>		
	<div id="menu-select-all-markup" class="menu yui3-aui-overlaycontext-hidden hide">
		<ul>
			<li action='select_all' title='Select All Snaps visible on this page. Use Ctrl/Cmd- or Shift-Click to select individual thumbnails.'>Select All</li>
			<li action='select_all_pages' class='before-show'>Select All Pages</li>
			<li action='clear_all' title='Clear selection'>Clear All</li>
			<li action='remove_selected' title='Remove Snaps from the Lightbox.' class='before-show'>Remove Selected Snaps</li>
			<li action='delete' title='DELETE photo(s) from your Snaphappi account.' class='before-show'>Delete...</li>
		</ul>
	</div>	
	<div id="menu-lightbox-organize-markup" class="menu yui3-aui-overlaycontext-hidden hide">
		<ul>
			<li action='batch_rating' class='before-show'>Apply rating:</li>
			<li action='tag' class='before-show'>
				<input id='lbx-tag-field' class='help' type='text' size='16' maxlength='255' value='Enter tags' onclick='this.value=null; this.className=null;'>
				<input type='submit' value='Go' class='orange'/>
			</li>
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
	<div id="menu-pagemaker-selected-create-markup" class="menu yui3-aui-overlaycontext-hidden hide">
		<ul>
			<li action='create_pagegallery' >
				<a>New Story</a>
			</li>
		</ul>
	</div>	
	<div class="hide"><div id="hint-multiselect-markup" class="hint message blue rounded-5 cf">
		<h1>Getting Started</h1>
		<p>Use <span class='keypress multiselect-single'>Ctrl-Click</span> or <span class='keypress'>Shift-Click</span> to select the Snaps, and try <span class='keypress'>Right-Click</span> on an item for additional actions. 
		You can also drag selected Snaps to your <b>Lightbox</b> to hold them in place.</p>
	</div></div>
	<?php $this->Layout->output($this->viewVars['markup_for_layout']); ?>
</div>		
<div id="footer" class="container_16">
	<div class="grid_16">
		<span>&copy; 2008-2012 Snaphappi</span>
		<a href="http://www.cakephp.org/" target="_blank"><img src="<?php echo AppController::$http_static[0]; ?>/static/img/css-gui/cake.power.gif" alt="CakePHP: the rapid development php framework" border="0"></a>		
	</div>
</div>
<script src="<?php echo AppController::$http_static[0]; ?>/svc/lib/alloy-1.0.2/build/aui/aui.js" type="text/javascript"></script>
<script src="<?php echo AppController::$http_static[1]; ?>/js/snappi/base_aui.js" type="text/javascript"></script>
<script type="text/javascript">		
	// this has to be AFTER base_aui.js load
	SNAPPI.LazyLoad.gallery();
</script>
	<?php	
		// put JS at the bottom of the page
		// echo $this->Html->script( AppController::$http_static[0].'/svc/lib/alloy-1.0.2/build/aui/aui.js');
		// echo $this->Html->script( AppController::$http_static[1].'/js/snappi/base_aui.js');	
		echo $scripts_for_layout;
		$this->Layout->output($this->viewVars['javascript_for_layout']);
		echo $this->element('analytics');
		echo $this->element('dumpSQL');
	?>
</body>
</html>