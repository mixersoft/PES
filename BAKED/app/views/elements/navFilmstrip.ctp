<?php
	if (isset($this->viewVars['jsonData']['castingCall']['CastingCall']['Auditions']['Total']))  {
		$count = $this->viewVars['jsonData']['castingCall']['CastingCall']['Auditions']['Total']; 
	} else $count = 0;

	$PREFIX = 'nav-';
	$thumbSize = Session::read("thumbSize.{$PREFIX}");
	if (!$thumbSize) $thumbSize = 'sq';
	$sizes = array(
		'sq'=>'/css/images/img_1.gif',
		'tn'=>'/css/images/img_2.gif',
		'lm'=>'/css/images/img_3.gif',
	);
?>
<section id="nav-filmstrip" class="filmstrip filmstrip-bg minimize">
	<section class="gallery-header grid_16">
		<ul class="inline">
			<li><h3>Filmstrip <img src="/css/images/img_setting.gif" alt="" align="absmiddle"></h3></li>
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
						<li action='set-display-view:minimize'><img src="/css/images/img_zoomin.gif"></li><li action="set-display-view:one-row"><img src="/css/images/img_zoomout.gif"></li>
					</ul>
				</nav>
			</li>
		</ul>
	</section>
	<section class='gallery photo filmstrip grid_16 alpha-b1 omega-b1'>
		<div class="filmstrip-wrap hidden"><div class='filmstrip'><div class='container'></div></div></div>
	</section>
</section>
