<?php
$lightbox = $this->Session->read('lightbox');
if ($lightbox) $this->viewVars['jsonData']['lightbox'] = $lightbox;
if (!empty($lightbox['auditions'])) {
	// ignore innerHTML
	App::import('Controller', 'Assets');
	$AssetsController = new AssetsController();
	$AssetsController->constructClasses();
	$castingCall = $AssetsController->getCC($lightbox['auditions']);
	if ($castingCall) {
		$this->viewVars['jsonData']['lightbox']['castingCall'] = $castingCall;
		unset( $this->viewVars['jsonData']['lightbox']['auditions']);		// TODO: deprecate $lightbox['auditions']
		unset($this->viewVars['jsonData']['lightbox']['innerHTML']);		// TODO: deprecate
	}
}
if (isset($this->viewVars['jsonData']['lightbox']) && isset($this->viewVars['jsonData']['lightbox']['castingCall']))  {
	$count = $this->viewVars['jsonData']['lightbox']['castingCall']['CastingCall']['Auditions']['Total']; 
} else $count = 0;



	$PREFIX = 'lightbox-';	// Factory[type.].defaultCfg.ID_PREFIX 
	$thumbSize = Session::read("thumbSize.{$PREFIX}");
	
if (Configure::read('controller.action')=='lightbox') {
	// full page lightbox
	$this->viewVars['jsonData']['lightbox']['full_page']=true;
	$this->viewVars['jsonData']['lightbox']['thumbsize']='tn';
	if (!$thumbSize) $thumbSize = 'lm';
} else if (!$thumbSize) $thumbSize = 'lbx-tiny';	
		
	$sizes = array(
		'lbx-tiny'=>'/static/img/css-gui/img_1.gif',
		'sq'=>'/static/img/css-gui/img_2.gif',
		'lm'=>'/static/img/css-gui/img_3.gif',
	);	
	
	
?>
<section class="lightbox lightbox-bg cf filmstrip drop container_16 hide" id="lightbox">
	<div class='lightbox-tab lightbox-bg hide' title="move the mouse a little lower to see the Lightbox">Lightbox</div>
	<section class="gallery-header grid_16">
		<ul class="inline cf">
			<li><h3>Lightbox</h3></li>
			<li>
				<nav class="toolbar">
					<div>
						<ul class="inline menu-trigger">
							<li class="btn white select-all"><span class="menu-open"><input type="checkbox" value="" name=""></span></li>
							<li class="organize">Organize</li>
							<li class="share">Share</li>
							<li class="create">Create</li>
						</ul>
					</div>
					<h1 class="count"></h1>
				</nav>
			</li>			
			<li class="right">
				<nav class="window-options">
					<ul class="thumb-size inline">
						<li class="label" >Size</li>
						<?php 
							foreach ($sizes as $size => $src ) {
								echo "<li class='btn white ".($thumbSize==$size ? 'focus' : '')."'  action='set-display-size:{$size}'><img src='{$src}' alt='' title='set thumbnail size'></li>\n";
							}
						?>
					</ul>
					<ul class="inline">
						<li action='set-display-view:minimize' title="minimize Lightbox"><img src="/static/img/css-gui/img_zoomin.gif"><li action='set-display-view:one-row' title="show one row of thumbnails in Lightbox"><img src="/static/img/css-gui/img_zoomout.gif"></li><li action="set-display-view:maximize" title="maximize Lightbox"><img src="/static/img/css-gui/img_zoomout.gif"></li>
					</ul>
				</nav>
			</li>
		</ul>
	</section>
	<section class='gallery photo lightbox grid_16 '>
		<div class="filmstrip-wrap"><div class='filmstrip'><div class='container'></div>
			</div></div>
	</section>
</section>
