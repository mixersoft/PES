<?php
	$PREFIX = 'shot-';
	$thumbSize = Session::read("thumbSize.{$PREFIX}");
	if (!$thumbSize) $thumbSize = 'sq';
	$sizes = array(
		'sq'=>'/static/img/css-gui/img_1.gif',
		'tn'=>'/static/img/css-gui/img_2.gif',
		'lm'=>'/static/img/css-gui/img_3.gif',
	);
?>
<section id="shot-gallery" class="filmstrip filmstrip-bg drop minimize alpha grid_11 omega">
	<section class='gallery photo filmstrip hiddenshots grid_11 alpha-b1 omega-b1'>
		<div class="filmstrip-wrap hidden"><div class='filmstrip'><div class='container'></div></div></div>
	</section>	
	<section class="gallery-header alpha grid_11 omega">
		<ul class="inline hide">
			<li class='alpha-b1'><h3>Hidden Shot Gallery <img src="/static/img/css-gui/img_setting.gif" alt="" align="absmiddle"></h3></li>
			<li>
				<nav class="toolbar">
					<div>
						<ul class="inline menu-trigger">
							<li class="btn white select-all"><span class="menu-open"><input type="checkbox" value="" name=""></span></li>
						</ul>
					</div>
					<h1 class="count"></h1>
				</nav>
			</li>			
			<li class="right">
				<nav class="window-options omega-b1">
					<ul class="thumb-size inline">
						<li class="label">Size</li>
						<?php 
							foreach ($sizes as $size => $src ) {
								echo "<li class='btn white ".($thumbSize==$size ? 'focus' : '')."'  action='set-display-size:{$size}'><img src='{$src}' alt=''></li>\n";
							}
						?>
					</ul>
					<ul class="inline">
						<li action='set-display-view:minimize'><img src="/static/img/css-gui/img_zoomin.gif"></li><li action="set-display-view:one-row"><img src="/static/img/css-gui/img_zoomout.gif"></li>
					</ul>
				</nav>
			</li>
		</ul>
	</section>
</section>
