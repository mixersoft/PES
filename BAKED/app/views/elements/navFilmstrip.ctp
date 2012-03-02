<?php
	if (isset($this->viewVars['jsonData']['castingCall']['CastingCall']['Auditions']['Total']))  {
		$count = $this->viewVars['jsonData']['castingCall']['CastingCall']['Auditions']['Total']; 
	} else $count = 0;

	$PREFIX = 'nav-';
	$thumbSize = Session::read("thumbSize.{$PREFIX}");
	if (!$thumbSize) $thumbSize = 'sq';
	$sizes = array(
		'sq'=>AppController::$http_static[1].'/static/img/css-gui/img_1.gif',
		'tn'=>AppController::$http_static[0].'/static/img/css-gui/img_2.gif',
		'lm'=>AppController::$http_static[1].'/static/img/css-gui/img_3.gif',
	);
?>
<section id="nav-filmstrip" class="filmstrip filmstrip-bg minimize container_16 hide">
	<section class="gallery-header grid_16">
		<ul class="inline">
			<li><h3>Filmstrip <img src="/static/img/css-gui/info.gif" alt="" align="absmiddle"></h3></li>
			<li>
				<nav class="toolbar">
					<h1 class="count"></h1>
				</nav>
			</li>			
			<li class="right">
				<nav class="window-options ">
					<ul class="thumb-size inline">
						<li class="label">Size</li>
						<?php 
							foreach ($sizes as $size => $src ) {
								echo "<li class='btn white ".($thumbSize==$size ? 'focus' : '')."' action='set-display-size:{$size}'><img src='{$src}' alt=''></li>\n";
							}
						?>
					</ul>
					<ul class="inline">
						<li action='set-display-view:minimize'><img src="<?php echo AppController::$http_static[0]; ?>/static/img/css-gui/img_zoomin.gif"></li><li action="set-display-view:one-row"><img src="<?php echo AppController::$http_static[1]; ?>/static/img/css-gui/img_zoomout.gif"></li>
					</ul>
				</nav>
			</li>
		</ul>
	</section>
	<section class='gallery photo filmstrip grid_16 alpha-b1 omega-b1'>
		<div class="filmstrip-wrap hidden"><div class='filmstrip'><div class='container'></div></div></div>
	</section>
</section>
