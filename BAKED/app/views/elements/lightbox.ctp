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
if (isset($this->viewVars['jsonData']['lightbox']))  {
	$count = $this->viewVars['jsonData']['lightbox']['castingCall']['CastingCall']['Auditions']['Total']; 
} else $count = 0;
if (Configure::read('controller.action')=='lightbox') {
	// full page lightbox
	$this->viewVars['jsonData']['lightbox']['full_page']=true;
	$this->viewVars['jsonData']['lightbox']['thumbsize']='tn';
}

	$focus = 'lbx-tiny';
	if (isset($this->passedArgs['lbxSize'])) {
		$focus = $this->passedArgs['lbxSize'];		
	}
	$sizes = array(
		'lbx-tiny'=>'/css/images/img_1.gif',
		'sq'=>'/css/images/img_2.gif',
		'lm'=>'/css/images/img_3.gif',
	);
?>
<div class="anchor-bottom">
<section class="lightbox drop container_16" id="lightbox">
	<section class="header">
		<ul class="inline">
			<li class="grid_2"><h3>My Lightbox <img src="/css/images/img_setting.gif" alt="" align="absmiddle"></h3></li>
			<li class="grid_8">
				<nav class="toolbar">
					<div>
						<ul class="inline menu-trigger">
							<li class="select-all"><input type="checkbox" value="" name=""><a class="menu-open"> </a></li>
							<li class="organize">Organize</li>
							<li class="share">Share</li>
							<li class="create">Create</li>
						</ul>
					</div>
					<h1 class="count"><?php echo $count ?> Snaps</h1>
				</nav>
			</li>			
			<li class="grid_6">
				<nav class="window-options right">
					<ul class="thumb-size inline">
						<li class="label">Size</li>
						<?php 
							foreach ($sizes as $size => $src ) {
								echo "<li class='btn ".($focus==$size ? 'focus' : '')."' action='set-display-size' size='{$size}'><img src='{$src}' alt=''></li>";
							}
						?>
					</ul>
					<ul class="inline">
						<li action='filmstrip'><img src="/css/images/img_zoomin.gif"></li><li action="maximize"><img src="/css/images/img_zoomout.gif"></li>
					</ul>
				</nav>
			</li>
		</ul>
	</section>
	<section class='gallery photo lightbox grid_16 '>
		<div class="filmstrip-wrap"><div class='filmstrip'><div class='container'></div>
			</div>
		</div>
	</section>
	<ul class="toolbar inline grid_16"></ul>
</section>
</div>
