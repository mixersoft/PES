<?php
	$PREFIX = 'shot-';
	$thumbSize = Session::read("thumbSize.{$PREFIX}");
	if (!$thumbSize) $thumbSize = 'sq';
	$sizes = array(
		'sq'=>'/css/images/img_1.gif',
		'tn'=>'/css/images/img_2.gif',
		'lm'=>'/css/images/img_3.gif',
	);
?>
<section id="shot-gallery" class="filmstrip drop alpha omega">
	<section class='gallery photo filmstrip grid_11 alpha-b1 omega-b1'>
		<div class="filmstrip-wrap hidden"><div class='filmstrip'><div class='container'></div></div></div>
	</section>	
	<section class="header">
		<ul class="inline">
			<li class="grid_2 alpha-b1"><h3>Hidden Shot Gallery <img src="/css/images/img_setting.gif" alt="" align="absmiddle"></h3></li>
			<li class="grid_3">
				<nav class="toolbar">
					<div>
						<ul class="inline menu-trigger">
							<li class="select-all"><input type="checkbox" value="" name=""><a class="menu-open"> </a></li>
						</ul>
					</div>
					<h1 class="count">0 Snaps</h1>
				</nav>
			</li>			
			<li class="grid_6 omega-b1">
				<nav class="window-options right">
					<ul class="thumb-size inline">
						<li class="label">Size</li>
						<?php 
							foreach ($sizes as $size => $src ) {
								echo "<li class='btn ".($thumbSize==$size ? 'focus' : '')."' action='set-display-size' size='{$size}'><img src='{$src}' alt=''></li>";
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
</section>
